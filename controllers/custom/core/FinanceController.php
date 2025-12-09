<?php

function get_dossier_payment_info($DB)
{
    if (!isset($_POST['dossier_id'])) {
        echo json_encode(["state" => "false"]);
        return;
    }
    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);

    $sql = "SELECT 
                rd.*, 
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                (SELECT SUM(amount_paid) FROM caisse_transactions WHERE dossier_id = rd.id) as total_paid
            FROM reeducation_dossiers rd
            JOIN patient p ON rd.patient_id = p.id
            WHERE rd.id = $dossier_id";

    $dossier = $DB->select($sql)[0] ?? null;

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
    if (!isset($_SESSION['user']['id']) || !in_array($_SESSION['user']['role'], ['admin', 'nurse', 'doctor'])) {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }
    if (!isset($_POST['dossier_id']) || !isset($_POST['amount_paid'])) {
        echo json_encode(["state" => "false", "message" => "Données manquantes."]);
        return;
    }

    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);
    $amount_paid = filter_var($_POST['amount_paid'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    try {
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

        $sql_dossier = "SELECT price, payment_mode, discount_amount, sessions_prescribed FROM reeducation_dossiers WHERE id = :id";
        $stmt_dossier = $DB->prepare($sql_dossier);
        $stmt_dossier->execute([':id' => $dossier_id]);
        $dossier = $stmt_dossier->fetch(PDO::FETCH_ASSOC);

        $sql_total = "SELECT SUM(amount_paid) as total FROM caisse_transactions WHERE dossier_id = :id";
        $stmt_total = $DB->prepare($sql_total);
        $stmt_total->execute([':id' => $dossier_id]);
        $total_paid = $stmt_total->fetchColumn() ?: 0;

        $total_price_net = (float) $dossier['price'] - (float) $dossier['discount_amount'];
        $sessions_count = (int) $dossier['sessions_prescribed'];
        if ($sessions_count <= 0)
            $sessions_count = 1;

        $price_per_session = $total_price_net / $sessions_count;

        $sessions_covered = 0;
        if ($price_per_session > 0) {
            $sessions_covered = floor(($total_paid + 0.1) / $price_per_session);
        } else {
            $sessions_covered = 999;
        }

        if ($sessions_covered > $sessions_count) {
            $sessions_covered = $sessions_count;
        }

        $sql_reset = "UPDATE reeducation_sessions SET payment_status = 'unpaid' WHERE dossier_id = $dossier_id";
        $stmt_reset = $DB->prepare($sql_reset);
        $stmt_reset->execute();

        if ($sessions_covered > 0) {
            $limit = intval($sessions_covered);
            $sql_update = "UPDATE reeducation_sessions 
                           SET payment_status = 'paid' 
                           WHERE dossier_id = $dossier_id 
                           ORDER BY id ASC 
                           LIMIT $limit";
            $stmt_update = $DB->prepare($sql_update);
            $stmt_update->execute();
        }

        echo json_encode(["state" => "true", "message" => "Synchronisation réussie. $sessions_covered séances marquées comme payées."]);

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
            WHERE cabinet_id = $cabinet_id 
            AND reeducation_type_id = $reeducation_type_id 
            AND deleted = 0";

    $config = $DB->select($sql)[0] ?? null;

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
            $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET balance = (balance - $array_data[balance])  WHERE id = " . $_SESSION['user']['data'][0]['id']);
            $updated_com = $stmt->execute();
        }

        $GLOBALS['db']->table = 'users';
        $GLOBALS['db']->data = array("balance" => $array_data['new_balance'], "credit" => $array_data['new_credit']);
        $GLOBALS['db']->where = 'id = ' . $array_data['user_id'];
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
        $GLOBALS['db']->where = 'id = ' . $id_payment;
        $updated = $GLOBALS['db']->update();

        if ($updated) {
            $GLOBALS['db']->table = 'users';
            $GLOBALS['db']->data = array("balance" => $array_data['new_balance'], "credit" => $array_data['new_credit'], "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['data'][0]['id']);
            $GLOBALS['db']->where = 'id = ' . $array_data['user_id'];
            $GLOBALS['db']->update();

            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
        } else {
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    } else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);

    $GLOBALS['db'] = null;
}

function get_product()
{

    $sql = "SELECT price FROM products WHERE id = " . (isset($_POST['id']) ? $_POST['id'] : 0) . "";

    $response = $GLOBALS['db']->select($sql);
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
    $sql = "SELECT name, image FROM cards WHERE deleted = 1 AND id = " . (isset($_POST['id']) ? $_POST['id'] : 0) . "";

    $response = $GLOBALS['db']->select($sql);

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
        $details = $GLOBALS['db']->select("SELECT cards.price, users.balance FROM `cards`, users WHERE users.id = " . $_SESSION['user']['data'][0]['id'] . " AND cards.id = $card_id");
        $balance = !empty($details) ? $details[0]['balance'] : 0;
        $price = !empty($details) ? $details[0]['price'] : 0;

        if (($price * $qty) > $balance)
            $error = 1;
    } else {
        $details = $GLOBALS['db']->select("SELECT cards.price FROM `cards` WHERE cards.id = $card_id");
        $price = !empty($details) ? $details[0]['price'] : 0;
    }

    if ($error == 0) {

        $ids = array_fill(0, $qty, $card_id);
        $data = array_map(function ($id) use ($price) {
            return array("product_id" => $id, "price" => $price, "user_id" => $_SESSION['user']['data'][0]['id'], "state" => 0); }, $ids);

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
        $res = $GLOBALS['db']->select("SELECT operations.* FROM `operations` WHERE operations.user_id = " . $_SESSION['user']['data'][0]['id'] . " AND operations.id = $id");

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

function sendCodes()
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

    $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);
    $card_id = filter_var($_POST['card_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $parent_id = (isset($_SESSION['user']) ? ($_SESSION['user']['data'][0]['type'] == 0 ? 0 : $_SESSION['user']['data'][0]['id']) : NULL);

    $sql = "SELECT * FROM codes WHERE deleted = 1 AND state = 1 AND card_id = $card_id AND user_id = $parent_id";
    $totalcodes = $GLOBALS['db']->rowsCount($sql);

    if ($qty <= $totalcodes) {

        $details = $GLOBALS['db']->select("SELECT cards.price, users.balance FROM `cards`, users WHERE users.id = $user_id AND cards.id = $card_id");
        $balance = !empty($details) ? $details[0]['balance'] : 0;
        $price = !empty($details) ? $details[0]['price'] : 0;

        if (($price * $qty) <= $balance) {
            $response = $GLOBALS['db']->select($sql . " LIMIT $qty");
            $ids = array_values(array_column($response, 'id'));
            $datetime = date('Y-m-d H:i:s');

            $GLOBALS['db']->table = 'codes';
            $GLOBALS['db']->data = array('user_id' => $user_id, "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['data'][0]['id']);
            $GLOBALS['db']->where = 'id IN ( ' . implode(', ', $ids) . ')';

            $updated = $GLOBALS['db']->update();

            if ($updated) {

                $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET `balance`= CASE WHEN id=" . $_SESSION['user']['data'][0]['id'] . " THEN (balance + ($price * $qty)) WHEN id=$user_id THEN (balance - ($price * $qty)) ELSE balance END WHERE id IN (" . $_SESSION['user']['data'][0]['id'] . ", $user_id)");
                $updated_balance = $stmt->execute();

                if ($_SESSION['user']['data'][0]['type'] != 0) {

                    $sql = "SELECT (SELECT commissions.percentage FROM commissions WHERE commissions.card_id IS NULL AND commissions.mobilis_com IS NULL AND commissions.ooredoo_com IS NULL AND commissions.djezzy_com IS NULL AND commissions.deleted = 1 AND commissions.child = " . $_SESSION['user']['data'][0]['id'] . " ) AS default_com,
                            (SELECT commissions.percentage FROM commissions INNER JOIN cards ON cards.id = commissions.card_id WHERE commissions.deleted = 1 AND commissions.child = " . $_SESSION['user']['data'][0]['id'] . " AND commissions.card_id = $card_id ) AS card_com";

                    if ($_SESSION['user']['data'][0]['parent'] != 0 && $_SESSION['user']['data'][0]['parent'] != NULL) {
                        $sql = "SELECT users.balance, (SELECT commissions.percentage FROM commissions WHERE commissions.card_id IS NULL AND commissions.mobilis_com IS NULL AND commissions.ooredoo_com IS NULL AND commissions.djezzy_com IS NULL AND commissions.deleted = 1 AND commissions.child = " . $_SESSION['user']['data'][0]['id'] . " ) AS default_com,
                                (SELECT commissions.percentage FROM commissions INNER JOIN cards ON cards.id = commissions.card_id WHERE commissions.deleted = 1 AND commissions.child = " . $_SESSION['user']['data'][0]['id'] . " AND commissions.card_id = $card_id ) AS card_com FROM users WHERE users.id = " . $_SESSION['user']['data'][0]['parent'] . " AND users.deleted = 1 ";
                    }

                    $commissions = $GLOBALS['db']->select($sql);
                    $commission = isset($commissions[0]['card_com']) && $commissions[0]['card_com'] != NULL ? $commissions[0]['card_com'] : (isset($commissions[0]['default_com']) && $commissions[0]['default_com'] != NULL ? $commissions[0]['default_com'] : 0);
                    $global_commision = (($price * $qty) * $commission) / 100;

                    if (isset($commissions[0]['balance'])) {

                        $query = ($commissions[0]['balance'] >= $global_commision ? " `balance`= (balance - $global_commision ) " : " `credit`= (credit + $global_commision ) ");

                        $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET $query WHERE id = " . $_SESSION['user']['data'][0]['parent']);
                        $updated_com = $stmt->execute();

                    }

                    $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET `bonus`= (bonus + $global_commision ) WHERE id = " . $_SESSION['user']['data'][0]['id']);
                    $updated_bonus = $stmt->execute();
                }

                $data = array_map(function ($id) {
                    return array("product_id" => $id, "user_id" => $_SESSION['user']['data'][0]['id'], "state" => 1); }, $ids);

                $GLOBALS['db']->table = 'operations';
                $GLOBALS['db']->data = $data;
                $GLOBALS['db']->multi = true;

                $GLOBALS['db']->insert();

                echo json_encode(["state" => "true", "message" => $GLOBALS['language']["sended successfully"]]);
            } else {
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']["something went wrong reload page and try again"]]);
            }

        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']["client balance is not enough"]]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']["There are not enough codes"]]);
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
            return customEncryption(trim($item)); }, $codes);

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

        // Remove rejected code from file
        $allowed_code = array_values(array_filter($codes, function ($item) use ($rejected_code) {
            return !in_array($item, $rejected_code);
        }));

        // prepare allowed code array
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
    $GLOBALS['db']->where = 'id = ' . $_POST['id'];

    $updated = $GLOBALS['db']->update();
    $GLOBALS['db'] = null;

    if ($updated) {
        echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
    } else {
        echo json_encode(["state" => "false", "message" => $updated]);
    }
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
?>