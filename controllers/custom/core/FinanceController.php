<?php

function get_dossier_payment_info($DB)
{
    if (!isset($_POST['dossier_id'])) {
        echo json_encode(["state" => "false"]);
        return;
    }
    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);

    // تصحيح الاستعلام: التحقق من cabinet_id عبر جدول المريض (p.cabinet_id) وليس الملف (rd.cabinet_id)
    $sql = "SELECT 
                rd.*, 
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                (SELECT SUM(amount_paid) FROM caisse_transactions WHERE dossier_id = rd.id) as total_paid
            FROM reeducation_dossiers rd
            JOIN patient p ON rd.patient_id = p.id
            WHERE rd.id = ?";

    $params = [$dossier_id];

    if (!empty($_SESSION['user']['cabinet_id'])) {
        $sql .= " AND p.cabinet_id = ?"; // تم التعديل هنا: p.cabinet_id
        $params[] = $_SESSION['user']['cabinet_id'];
    }

    $dossier = $DB->select($sql, $params)[0] ?? null;

    if ($dossier) {
        $stored_price = (float) $dossier['price'];
        $sessions_count = (int) ($dossier['sessions_prescribed'] > 0 ? $dossier['sessions_prescribed'] : 1);
        $total_paid = (float) ($dossier['total_paid'] ?? 0);
        $discount = (float) ($dossier['discount_amount'] ?? 0);

        $gross_total = $stored_price;
        $price_per_session = $gross_total / $sessions_count;

        $net_total = $gross_total - $discount;
        if ($net_total < 0)
            $net_total = 0;

        $remaining = $net_total - $total_paid;

        if ($remaining < 0.01)
            $remaining = 0;

        if ($dossier['payment_mode'] == 'package') {
            $amount_to_pay = $remaining;
        } else {
            $amount_to_pay = ($remaining < $price_per_session) ? $remaining : $price_per_session;
        }

        $dossier['total_paid'] = $total_paid;
        $dossier['gross_total'] = $gross_total;
        $dossier['net_total'] = $net_total;
        $dossier['remaining_balance'] = $remaining;
        $dossier['amount_to_pay'] = $amount_to_pay;
        $dossier['unit_price'] = $price_per_session;

        echo json_encode(["state" => "true", "data" => $dossier]);
    } else {
        echo json_encode(["state" => "false"]);
    }
}



function record_payment($DB)
{
    // 1. التحقق من الصلاحيات العامة
    if (!isset($_SESSION['user']['id']) || !in_array($_SESSION['user']['role'], ['admin', 'nurse', 'doctor'])) {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }

    // 2. التحقق من المدخلات
    if (!isset($_POST['dossier_id']) || !isset($_POST['amount_paid'])) {
        echo json_encode(["state" => "false", "message" => "Données manquantes."]);
        return;
    }

    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);
    $amount_paid = filter_var($_POST['amount_paid'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // 3. التحقق الأمني (IDOR Check) - المنطق المصحح
    // نربط مع جدول المستخدمين (u) للتحقق من عيادة الطبيب أيضاً
    $check_sql = "SELECT rd.id 
                  FROM reeducation_dossiers rd 
                  JOIN patient p ON rd.patient_id = p.id 
                  LEFT JOIN users u ON rd.technician_id = u.id 
                  WHERE rd.id = ?";
    $check_params = [$dossier_id];

    if ($_SESSION['user']['role'] === 'doctor') {
        // الطبيب: يجب أن يكون هو التقني المسؤول عن الملف
        $check_sql .= " AND rd.technician_id = ?";
        $check_params[] = $_SESSION['user']['id'];
    } elseif (!empty($_SESSION['user']['cabinet_id'])) {
        // الأدمن/الممرض: يجب أن يتبع المريض للعيادة أو الطبيب يتبع للعيادة
        $check_sql .= " AND (p.cabinet_id = ? OR u.cabinet_id = ?)";
        $check_params[] = $_SESSION['user']['cabinet_id'];
        $check_params[] = $_SESSION['user']['cabinet_id'];
    }

    $check = $DB->select($check_sql, $check_params);

    if (empty($check)) {
        echo json_encode(["state" => "false", "message" => "Dossier introuvable ou accès refusé."]);
        return;
    }

    // 4. تنفيذ عملية الدفع
    try {
        // تسجيل المعاملة
        if ($amount_paid > 0) {
            $data = [
                'dossier_id' => $dossier_id,
                'amount_paid' => $amount_paid,
                'recorded_by' => $_SESSION['user']['id']
            ];
            $DB->table = 'caisse_transactions';
            $DB->data = $data;
            $DB->insert();
        }

        // جلب تفاصيل الملف لإعادة الحساب
        $stmt_dossier = $DB->prepare("SELECT price, payment_mode, discount_amount, sessions_prescribed FROM reeducation_dossiers WHERE id = ?");
        $stmt_dossier->execute([$dossier_id]);
        $dossier = $stmt_dossier->fetch(PDO::FETCH_ASSOC);

        // حساب إجمالي المدفوعات
        $stmt_total = $DB->prepare("SELECT SUM(amount_paid) as total FROM caisse_transactions WHERE dossier_id = ?");
        $stmt_total->execute([$dossier_id]);
        $total_paid = $stmt_total->fetchColumn() ?: 0;

        // حساب عدد الجلسات المدفوعة
        $total_price_net = (float) $dossier['price'] - (float) $dossier['discount_amount'];
        $sessions_count = (int) $dossier['sessions_prescribed'];
        if ($sessions_count <= 0)
            $sessions_count = 1;

        $price_per_session = $total_price_net / $sessions_count;

        $sessions_covered = 0;
        if ($price_per_session > 0) {
            $sessions_covered = floor(($total_paid + 0.1) / $price_per_session);
        } else {
            $sessions_covered = 999; // حالة خاصة إذا كان السعر 0
        }

        if ($sessions_covered > $sessions_count) {
            $sessions_covered = $sessions_count;
        }

        // تحديث حالة الجلسات
        // أولاً: تصفير الكل
        $stmt_reset = $DB->prepare("UPDATE reeducation_sessions SET payment_status = 'unpaid' WHERE dossier_id = ?");
        $stmt_reset->execute([$dossier_id]);

        // ثانياً: تحديث الجلسات المغطاة
        if ($sessions_covered > 0) {
            $limit = intval($sessions_covered);
            $sql_update = "UPDATE reeducation_sessions 
                           SET payment_status = 'paid' 
                           WHERE dossier_id = ? 
                           ORDER BY id ASC 
                           LIMIT $limit";
            $stmt_update = $DB->prepare($sql_update);
            $stmt_update->execute([$dossier_id]);
        }

        echo json_encode(["state" => "true", "message" => "Paiement enregistré avec succès."]);

    } catch (Exception $e) {
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
}
function get_service_pricing_details($DB)
{
    if (!isset($_SESSION['user']['cabinet_id'])) {
        echo json_encode(["state" => "false", "message" => "Cabinet non identifié"]);
        return;
    }

    $reeducation_type_id = filter_var($_POST['reeducation_type_id'], FILTER_SANITIZE_NUMBER_INT);
    $sessions_count = filter_var($_POST['sessions_count'], FILTER_SANITIZE_NUMBER_INT);
    $cabinet_id = $_SESSION['user']['cabinet_id'];

    $sql = "SELECT * FROM cabinet_services 
            WHERE cabinet_id = ? 
            AND reeducation_type_id = ? 
            AND deleted = 0";

    $config = $DB->select($sql, [$cabinet_id, $reeducation_type_id])[0] ?? null;

    if (!$config) {
        echo json_encode(["state" => "false", "message" => "Service non configuré pour ce cabinet"]);
        return;
    }

    $effective_sessions = $sessions_count;

    $total_price = 0;
    $rules = json_decode($config['pricing_rules'], true);
    usort($rules, function ($a, $b) {
        return $a['limit'] - $b['limit'];
    });

    $remaining_sessions = $effective_sessions;
    $previous_limit = 0;

    foreach ($rules as $rule) {
        if ($remaining_sessions <= 0)
            break;

        $tier_capacity = $rule['limit'] - $previous_limit;
        $sessions_in_tier = min($remaining_sessions, $tier_capacity);

        $total_price += $sessions_in_tier * floatval($rule['price']);

        $remaining_sessions -= $sessions_in_tier;
        $previous_limit = $rule['limit'];
    }

    $commission_total = 0;
    if ($config['commission_type'] === 'fixed') {
        $commission_total = $effective_sessions * floatval($config['commission_value']);
    } else {
        $commission_total = ($total_price * floatval($config['commission_value'])) / 100;
    }

    echo json_encode([
        "state" => "true",
        "data" => [
            "total_price" => $total_price,
            "commission_total" => $commission_total,
            "payment_model" => $config['pricing_model'],
            "duration" => $config['session_duration'],
            "package_capacity" => $config['package_capacity']
        ]
    ]);
}

// Legacy Functions (Secured)
function postPayment()
{
    $array_data = array();
    $table = 'payments';

    foreach ($_POST['data'] as $data) {
        $array_data[$data['name']] = $data['value'];
    }

    if (isset($array_data['csrf'])) {
        $csrf_token = customDecrypt($array_data['csrf']);
        unset($array_data['csrf']);

        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $array_data = array_merge($array_data, array("ref" => createCode($GLOBALS['db']), "created_by" => $_SESSION['user']['data'][0]['id']));

    $GLOBALS['db']->table = $table;
    $GLOBALS['db']->data = $array_data;

    $inserted = $GLOBALS['db']->insert();

    if ($inserted) {
        if ($_SESSION['user']['data'][0]['type'] != 0) {
            $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET balance = (balance - ?) WHERE id = ?");
            $stmt->execute([$array_data['balance'], $_SESSION['user']['data'][0]['id']]);
        }

        $GLOBALS['db']->table = 'users';
        $GLOBALS['db']->data = array("balance" => $array_data['new_balance'], "credit" => $array_data['new_credit']);
        $GLOBALS['db']->where = 'id = ' . intval($array_data['user_id']);
        $GLOBALS['db']->update();

        echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Added successfully']]);
    } else {
        echo json_encode(["state" => "false", "message" => $inserted]);
    }
    $GLOBALS['db'] = null;
}

function updatePayment()
{
    $id_payment = isset($_POST['id']) && !empty($_POST['id']) ? explode("-", customDecrypt($_POST['id'])) : 0;
    $id_payment = is_array($id_payment) && is_numeric($id_payment[1]) ? $id_payment[1] : 0;

    if ($id_payment) {
        $array_data = array();
        $table = 'payments';

        foreach ($_POST['data'] as $data) {
            $array_data[$data['name']] = $data['value'];
        }

        if (isset($array_data['csrf'])) {
            $csrf_token = customDecrypt($array_data['csrf']);
            unset($array_data['csrf']);

            if (!is_csrf_valid($csrf_token)) {
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }

        $datetime = date('Y-m-d H:i:s');
        $array_data = array_merge($array_data, array("modified_at" => "$datetime", "modified_by" => $_SESSION['user']['data'][0]['id']));

        $GLOBALS['db']->table = $table;
        $GLOBALS['db']->data = $array_data;
        $GLOBALS['db']->where = 'id = ' . intval($id_payment);
        $updated = $GLOBALS['db']->update();

        if ($updated) {
            $GLOBALS['db']->table = 'users';
            $GLOBALS['db']->data = array("balance" => $array_data['new_balance'], "credit" => $array_data['new_credit'], "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['data'][0]['id']);
            $GLOBALS['db']->where = 'id = ' . intval($array_data['user_id']);
            $GLOBALS['db']->update();

            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
        } else {
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    } else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);

    $GLOBALS['db'] = null;
}

function createCode($DB, $table = "payments", $field = "ref")
{
    $DB->table = $table;
    $DB->field = $field;

    do {
        $Code = generateCode();
        $DB->value = $Code;
    } while ($DB->validateField());

    return $Code;
}

function generateCode()
{
    $bytes = random_bytes(5);
    $encoded = base64_encode($bytes);
    $stripped = str_replace(['=', '+', '/'], '', $encoded);

    return $stripped;
}

function get_product()
{
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $sql = "SELECT price FROM products WHERE id = ?";
    $response = $GLOBALS['db']->select($sql, [$id]);
    $GLOBALS['db'] = null;
    echo json_encode($response[0]);
}

function get_card()
{
    if (isset($_POST['csrf'])) {
        $csrf_token = customDecrypt($_POST['csrf']);
        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $id = abs(filter_var(customDecrypt($_POST['id']), FILTER_SANITIZE_NUMBER_INT));
    $sql = "SELECT name, image FROM cards WHERE deleted = 1 AND id = ?";
    $response = $GLOBALS['db']->select($sql, [$id]);

    $GLOBALS['db'] = null;
    if (!empty($response)) {
        echo json_encode(["state" => "true", "data" => $response[0]]);
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);
    }
}

function get_codes()
{
    if (isset($_POST['csrf'])) {
        $csrf_token = customDecrypt($_POST['csrf']);
        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $qty = isset($_POST['qty']) && !empty($_POST['qty']) ? filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT) : 1;
    $card_id = filter_var($_POST['card_id'], FILTER_SANITIZE_NUMBER_INT);
    $error = 0;

    if ($_SESSION['user']['data'][0]['type'] != 0) {
        $details = $GLOBALS['db']->select("SELECT cards.price, users.balance FROM `cards`, users WHERE users.id = ? AND cards.id = ?", [$_SESSION['user']['data'][0]['id'], $card_id]);
        $balance = !empty($details) ? $details[0]['balance'] : 0;
        $price = !empty($details) ? $details[0]['price'] : 0;

        if (($price * $qty) > $balance)
            $error = 1;
    } else {
        $details = $GLOBALS['db']->select("SELECT cards.price FROM `cards` WHERE cards.id = ?", [$card_id]);
        $price = !empty($details) ? $details[0]['price'] : 0;
    }

    if ($error == 0) {
        $ids = array_fill(0, $qty, $card_id);
        $data = array_map(function ($id) use ($price) {
            return array("product_id" => $id, "price" => $price, "user_id" => $_SESSION['user']['data'][0]['id'], "state" => 0);
        }, $ids);

        $GLOBALS['db']->table = 'operations';
        $GLOBALS['db']->data = $data;
        $GLOBALS['db']->multi = true;

        $inserted = $GLOBALS['db']->insert();

        echo json_encode(["state" => "true", "id" => $inserted]);

    } else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']["balance is not enough"]]);

    $GLOBALS['db'] = null;
}

function state_operation()
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $res = $GLOBALS['db']->select("SELECT operations.* FROM `operations` WHERE operations.user_id = ? AND operations.id = ?", [$_SESSION['user']['data'][0]['id'], $id]);

        if (!empty($res)) {
            if ($res[0]['state'] == 0) {
                echo json_encode(["state" => "true", "message" => "false"]);
            } else {
                if ($res[0]['state'] == 1) {
                    echo json_encode(["state" => "true", "message" => "true", "code" => $res[0]['code']]);
                } else {
                    echo json_encode(["state" => "true", "message" => ($res[0]['product_id'] != NULL && $res[0]['product_id'] != 0 ? $GLOBALS['language']["failed to get code"] : $GLOBALS['language']["amount transfer failed"])]);
                }
            }
        } else
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']["something went wrong reload page and try again"]]);

    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
    }
    $GLOBALS['db'] = null;
}

function postCodes()
{
    $table = 'codes';

    if (isset($_POST['csrf'])) {
        $csrf_token = customDecrypt($_POST['csrf']);
        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    if (isset($_POST['file']) && !empty($_POST['file']) && isset($_POST['card_id']) && !empty($_POST['card_id'])) {
        $card_id = $_POST['card_id'];
        $codes = file(SITE_URL . "$_POST[file]");

        $codes = array_map(function ($item) {
            return customEncryption(trim($item));
        }, $codes);

        $codes_string = array_reduce($codes, function ($carry, $item) {
            return $carry . "'" . $item . "',";
        }, '');
        $codes_string = rtrim($codes_string, ',');

        if ($codes_string != '') {
            $sql = "SELECT code FROM codes WHERE card_id = $card_id AND code IN ($codes_string)";
            $stmt = $GLOBALS['db']->prepare($sql);
            $rejected_code = (($stmt->execute()) ? $stmt->fetchAll(PDO::FETCH_COLUMN) : []);
        } else
            $rejected_code = [];

        $allowed_code = array_values(array_filter($codes, function ($item) use ($rejected_code) {
            return !in_array($item, $rejected_code);
        }));

        $allowed_code = array_map(function ($item) use ($card_id) {
            return array("card_id" => $card_id, "code" => $item, "created_by" => $_SESSION['user']['data'][0]['id']);
        }, $allowed_code);

    } else
        $codes = [];

    if (count($codes)) {
        if (count($allowed_code)) {
            $GLOBALS['db']->table = $table;
            $GLOBALS['db']->data = $allowed_code;
            $GLOBALS['db']->multi = true;
            $inserted = $GLOBALS['db']->insert();
            $GLOBALS['db'] = null;

            if ($inserted) {
                echo json_encode(["state" => "true", "message" => $GLOBALS['language']['inserted_code'] . ': ' . count($allowed_code) . '<br/>' . $GLOBALS['language']['codes_exist_in_queue'] . ': ' . count($rejected_code)]);
            } else
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
        } else
            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['inserted_code'] . ': 0 <br/>' . $GLOBALS['language']['codes_exist_in_queue'] . ': ' . count($rejected_code)]);
    } else
        echo json_encode(["state" => "true", "message" => $GLOBALS['language']['The file does not contain any code']]);
}

function updateCodes()
{
    $table = 'codes';

    if (isset($_POST['csrf'])) {
        $csrf_token = customDecrypt($_POST['csrf']);
        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $datetime = date('Y-m-d H:i:s');
    $array_data = array_merge($_POST['data'], array("modified_at" => "$datetime", "modified_by" => $_SESSION['user']['data'][0]['id']));

    array_walk_recursive($array_data, function (&$item, $key) {
        if ($key == 'code')
            $item = customEncryption($item);
    });

    $GLOBALS['db']->table = $table;
    $GLOBALS['db']->data = $array_data;
    $GLOBALS['db']->where = 'id = ' . intval($_POST['id']);

    $updated = $GLOBALS['db']->update();
    $GLOBALS['db'] = null;

    if ($updated) {
        echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
    } else {
        echo json_encode(["state" => "false", "message" => $updated]);
    }
}
?>