<?php




function data_table($DB)
{
    // 1. تعريف الأيقونات
    $icons = array(
        "view-icon" => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "delete-icon" => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fd5757" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',
        "edit-icon" => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00D894" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>',
        "message-icon" => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-chat-text" viewBox="0 0 16 16"> <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/> <path d="M4 5.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zM4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8zm0 2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5z"/> </svg>',
        "popup-icon" => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0071BC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>'
    );

    // 2. تعريف الشارات (Badges)
    $badgeStates = array(
        -1 => '<span class="badge rounded-pill badge-light-danger">' . ($GLOBALS["language"]["canceled"] ?? "Annulé") . '</span>',
        0 => '<span class="badge rounded-pill badge-light-info">' . $GLOBALS["language"]["created"] . '</span>',
        1 => '<span class="badge rounded-pill badge-light-success">' . $GLOBALS["language"]["completed"] . '</span>'
    );

    // 3. تحضير الاستعلام الأساسي
    $query = $GLOBALS['queries'][$_POST['query']];

    // استخراج الأعمدة للترتيب والبحث
    $sub = substr($query, stripos($query, 'SELECT'), stripos($query, 'FROM'));
    $sub = substr($sub, stripos($sub, 'SELECT') + 6, strlen($sub));
    $str_arr = preg_split("/,(?![^(]+\))/", $sub);

    $order_cols = [];
    $search_cols = [];
    foreach ($str_arr as $col_str) {
        $col_str = trim($col_str);
        $has_alias = stripos($col_str, ' AS ');
        $col_for_order = '';
        $col_for_search = '';

        if ($has_alias !== false) {
            $col_for_order = substr($col_str, $has_alias + 4);
            $col_for_search = substr($col_str, 0, $has_alias);
        } else {
            $col_for_order = $col_str;
            $col_for_search = $col_str;
        }

        $order_cols[] = trim(str_replace(array("_photo", "__action", "_state", "_BadgeState", "_stateId", "__enableRdv"), "", $col_for_order));

        if (
            stripos($col_for_search, '_photo') === false &&
            stripos($col_for_search, '__action') === false &&
            stripos($col_for_search, '_state') === false &&
            stripos($col_for_search, '_BadgeState') === false &&
            stripos($col_for_search, '_stateId') === false &&
            stripos($col_for_search, '__enableRdv') === false
        ) {
            $search_cols[] = $col_for_search;
        }
    }

    // 4. [SECURITY FIX] التحقق من الشرط الإضافي (Condition)
    // نسمح فقط بالشروط البسيطة (عمود = رقم) لمنع حقن SQL
    if (isset($_POST['condition']) && !empty($_POST['condition'])) {
        $cond = $_POST['condition'];
        // Regex: يسمح فقط بأحرف، أرقام، نقاط، ومساواة مع رقم (مثال: rdv.doctor_id = 509)
        if (preg_match('/^[a-zA-Z0-9_.]+\s*=\s*[0-9]+$/', $cond)) {
            $query .= " AND " . $cond;
        }
    }

    $base_query = $query;
    $params = []; // مصفوفة لتخزين القيم الآمنة

    // 5. [SECURITY FIX] البحث باستخدام Prepared Statements
    if (!empty($_REQUEST['search']['value'])) {
        $search_value = $_REQUEST['search']['value'];

        if (!empty($search_cols)) {
            // استخدام ? بدلاً من وضع القيمة مباشرة
            $base_query .= " AND CONCAT_WS(' ', " . implode(",", $search_cols) . ") LIKE ? ";
            $params[] = "%" . $search_value . "%";
        }
    }

    // 6. [SECURITY FIX] فلترة التواريخ (Whitelist Columns)
    if ((isset($_REQUEST['dateStart']) && !empty($_REQUEST['dateStart'])) || (isset($_REQUEST['dateFin']) && !empty($_REQUEST['dateFin']))) {
        // قائمة بيضاء للأعمدة المسموح بها كتواريخ
        $allowed_date_cols = ['date', 'rdv.date', 'created_at', 'payment_date'];
        $date_col = $_REQUEST['dateFilter'];

        if (in_array($date_col, $allowed_date_cols)) {
            if (isset($_REQUEST['dateStart']) && !empty($_REQUEST['dateStart'])) {
                $base_query .= " AND " . $date_col . " >= ? ";
                $params[] = $_REQUEST['dateStart'];
            }
            if (isset($_REQUEST['dateFin']) && !empty($_REQUEST['dateFin'])) {
                $base_query .= " AND " . $date_col . " <= ? ";
                $params[] = $_REQUEST['dateFin'];
            }
        }
    }

    try {
        // 7. تنفيذ استعلام العد (Total Count)
        // نستخدم PDO مباشرة للوصول إلى prepare/execute
        $stmtCount = $DB->pdo->prepare($base_query);
        $stmtCount->execute($params);
        $totalData = $stmtCount->rowCount();

        // 8. الترتيب والحدود (Order & Limit)
        $order_column_index = $_REQUEST['order'][0]['column'];
        $order_column = $order_cols[$order_column_index] ?? $order_cols[0];

        // التحقق من أن عمود الترتيب موجود في القائمة المسموحة
        if (!in_array($order_column, $order_cols)) {
            $order_column = 'id';
        }

        $dir = strtoupper($_REQUEST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
        $base_query .= " ORDER BY " . $order_column . " " . $dir . " LIMIT " . intval($_REQUEST['start']) . " ," . intval($_REQUEST['length']);

        // 9. تنفيذ الاستعلام الرئيسي
        $stmt = $DB->pdo->prepare($base_query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        die(json_encode([
            "draw" => 1,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => "Database Error"
        ]));
    }

    $DB = null;
    $data = array();

    // 10. معالجة البيانات للعرض (Output Loop)
    foreach ($results as $result) {
        $single_data = array();
        $item_id = $result['id'] ?? null;
        foreach ($result as $key => $value) {
            if (stripos($key, '_stateId') !== false) {
                $item_id = $value;
            }

            // معالجة الأعمدة الخاصة (HTML آمن مولد من النظام)
            if (stripos($key, '__action') !== false || stripos($key, '_state') !== false || stripos($key, '_BadgeState') !== false || stripos($key, '_photo') !== false) {
                if (stripos($key, '__action') !== false) {
                    if (isset($_POST['actions']) && is_array($_POST['actions'])) {
                        $actions_btn = '';
                        foreach ($_POST['actions'] as $action) {
                            if (!isset($action['attr'])) {
                                $action_id = ($action['action'] == "delete" || !isset($action['url'])) ? 'href="javascript:void(0);"' : 'href="' . $action['url'] . '' . $value . '"';
                            } else {
                                $action_id = 'href="javascript:void(0);"';
                                foreach ($action['attr'] as $key_attr => $attr) {
                                    $action_id .= ' ' . $key_attr . ' = ' . "$attr";
                                }
                            }
                            $action_cls = isset($action['class']) ? $action['class'] : "";
                            if ($action['action'] == 'message') {
                                $action_id = isset($result['username']) ? 'href="' . $action['url'] . '' . $result['username'] . '"' : '';
                            }

                            $default_icons = [
                                "edit-icon" => $icons['edit-icon'],
                                "delete-icon" => $icons['delete-icon'],
                                "view-icon" => $icons['view-icon'],
                                "reset_password-icon" => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0071BC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>'
                            ];
                            $icon_to_use = isset($action['icon']) ? $action['icon'] : ($default_icons[$action['action'] . '-icon'] ?? '');

                            $actions_btn .= '<a ' . $action_id . ' data-id="' . $value . '" class="' . $action['action'] . '-record ' . $action_cls . '">' . $icon_to_use . '</a>';
                        }
                        $single_data[] = $actions_btn;
                    } else {
                        $single_data[] = $value;
                    }
                }
                if ((stripos($key, '_state') !== false || stripos($key, '__enableRdv') !== false) && stripos($key, '_stateId') === false) {
                    $checked = $value == 1 || $value === 'active' ? "checked" : "";
                    $single_data[] = '<div class="form-check form-check-primary form-switch"><input type="checkbox" class="form-check-input switch-table-record" data-id="' . $item_id . '" value="1" ' . $checked . ' ' . (stripos($key, '__enableRdv') !== false ? 'data-express="rdv"' : "") . ' ></div>';
                }
                if (stripos($key, '_photo') !== false) {
                    $default_img = $value != "" ? $value : "assets/images/default_product.png";
                    $single_data[] = '<td><img src="' . $default_img . '" class="rounded" height="60px" /></td>';
                }
                if (stripos($key, '_BadgeState') !== false) {
                    $single_data[] = $badgeStates[$value];
                }
            } else if (stripos($key, '__rdvstate') !== false) {
                switch ($value) {
                    case 0:
                        $single_data[] = '<span class="badge badge-light-info stateOrder px-1 py-75">' . $GLOBALS["language"]["created"] . ': </span>
							<button type="button" class="btn btn-outline-success buttonstate px-1 py-75" data-value="1" data-id="' . $item_id . '">' . $GLOBALS["language"]["Accept"] . '</button>
							<button type="button" class="btn btn-outline-danger buttonstate px-1 py-75" data-value="3" data-id="' . $item_id . '">' . $GLOBALS["language"]["Cancel"] . '</button>';
                        break;
                    case 1:
                        $single_data[] = '<span class="badge badge-light-success stateOrder px-1 py-75">' . $GLOBALS["language"]["accepted"] . ': </span>
							<button type="button" class="btn btn-outline-info buttonstate px-1 py-75" data-value="2" data-id="' . $item_id . '">' . $GLOBALS["language"]["Complete"] . '</button>
							<button type="button" class="btn btn-outline-danger buttonstate px-1 py-75" data-value="3" data-id="' . $item_id . '">' . $GLOBALS["language"]["Cancel"] . '</button>';
                        break;
                    case 2:
                        $single_data[] = '<button type="button" class="btn btn-outline-success px-1 py-75" data-value="2" data-id="' . $item_id . '" disabled style="background-color: #e2f7ff; opacity: 1;border: none !important;">' . $GLOBALS["language"]["completed"] . '</button>';
                        break;
                    case 3:
                        $single_data[] = '<button type="button" class="btn btn-outline-danger px-1 py-75" data-value="3" data-id="' . $item_id . '" disabled style="background-color: #ffe2e2; opacity: 1;border: none !important;">' . $GLOBALS["language"]["Canceled"] . '</button>';
                        break;
                }
            } else if (stripos($key, '_receipt') !== false) {
                $single_data[] = '<td><img src="' . ($value != "" ? $value : "assets/images/default_product.png") . '" class="rounded" height="60px" /></td>';
            } else if (stripos($key, '__enableRdv') !== false) {
                $single_data[] = '<div class="form-check form-check-primary form-switch"><input type="checkbox" class="form-check-input switch-table-record" data-id="' . $item_id . '" value="1" ' . ($value == 1 ? "checked" : "") . ' data-express="rdv" ></div>';
            } else if (stripos($key, 'statut_paiement') !== false) {
                if ($value === 'paid') {
                    $single_data[] = '<span class="badge badge-light-success">Payé</span>';
                } else {
                    $single_data[] = '<span class="badge badge-light-danger">Impayé</span>';
                }
            } else {
                // [SECURITY FIX] XSS Protection: تنظيف البيانات النصية العادية
                $single_data[] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }
        }
        $data[] = $single_data;
    }

    $json_data = array(
        "draw" => intval($_REQUEST['draw']),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($totalData),
        "data" => $data
    );
    echo json_encode($json_data);
}

function data_table_Beta($DB)
{
    $query = $GLOBALS['queries'][$_POST['query']];
    $str_arr = preg_split("/,(?![^(]+\))/", $query);

    $str_arr = array_values(array_filter($str_arr, function ($v) {
        if (stripos($v, ' _stateId') === false) {
            return $v;
        }
    }));

    // FIX: Replaced $request['table'] with $_POST['table']
    echo '<table id="codexTable" class="datatables-ajax table table-responsive" data-express="' . customEncryption(json_encode($_POST['table'])) . '"><thead><tr>';
    $draw_col = array();
    foreach ($str_arr as $item) {
        if (stripos($item, ' AS') !== false)
            $item = substr($item, stripos($item, ' AS') + 3, strlen($item));
        if (stripos($item, ' __action') !== false) {
            $item = substr($item, stripos($item, ' __action') + 3, strlen($item));
            $item = str_replace("'", "", $item);
        }
        if (stripos($item, ' _state') !== false) {
            $item = substr($item, stripos($item, ' _state') + 2, strlen($item));
            $item = str_replace("'", "", $item);
        }
        if (stripos($item, ' _BadgeState') !== false) {
            $item = substr($item, stripos($item, ' _BadgeState') + 2, strlen($item));
            $item = str_replace("'", "", $item);
        }
        if (stripos($item, ' __BadgeStatee') !== false) {
            $item = substr($item, stripos($item, ' _BadgeStatee') + 3, strlen($item));
            $item = str_replace("'", "", $item);
        }
        $draw_col[] = trim($item);
    }

    $colsSecondSplit = implode(",", $draw_col);
    if (stripos($colsSecondSplit, 'FROM') !== false) {
        $colsSecondSplit = substr($colsSecondSplit, 0, stripos($colsSecondSplit, 'FROM'));
    }
    if (stripos($colsSecondSplit, 'SELECT') !== false) {
        $colsSecondSplit = substr($colsSecondSplit, stripos($colsSecondSplit, 'SELECT') + 6, strlen($colsSecondSplit));
    }
    $draw_col = preg_split("/,(?![^(]+\))/", $colsSecondSplit);

    foreach ($draw_col as $item) {
        if (stripos($item, '.') !== false) {
            $item = substr($item, stripos($item, '.') + 1, strlen($item));
        }
        echo '<th style="padding-right : 20px ;" ><a href="#">' . $GLOBALS['language'][trim($item)] . '</a></th>';
    }

    echo '</tr></thead></table>';
}

function deleteItem_table($DB)
{
    $datetime = date('Y-m-d H:i:s');
    $table = json_decode(customDecrypt($_POST['table']));

    if ($table === 'users') {
        $DB->table = 'users';
        $DB->data = array("status" => "inactive", "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['id']);
        $DB->where = 'id=' . $_POST['id'];
        $action_result = $DB->update();
        $message = $GLOBALS['language']['Deactivated successfully'] ?? 'Deactivated successfully';
    } else {
        $DB->table = $table;
        $DB->data = array("deleted" => "1", "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['id']);
        $DB->where = 'id=' . $_POST['id'];
        $action_result = $DB->update();
        $message = $GLOBALS['language']['Successfully Deleted'];
    }

    $DB = null;
    if ($action_result) {
        echo json_encode(["state" => $action_result, "message" => $message]);
    } else {
        echo json_encode(["state" => "false", "message" => $action_result]);
    }
}

function changeState($DB)
{
    $datetime = date('Y-m-d H:i:s');
    $state_value = $_POST['state'] == 1 ? 'active' : 'inactive';

    $DB->table = json_decode(customDecrypt($_POST['table']));
    $column_name = ($DB->table === 'users') ? 'status' : (isset($_POST['col']) ? $_POST['col'] : "state");
    $value_to_set = ($DB->table === 'users') ? $state_value : $_POST['state'];

    $DB->data = array($column_name => $value_to_set, "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['id']);
    $DB->where = 'id=' . $_POST['id'];

    $Changed = $DB->update();
    $DB = null;
    if ($Changed) {
        echo json_encode(["state" => $Changed, "message" => $GLOBALS['language']['Successfully Changed']]);
    } else {
        echo json_encode(["state" => "false", "message" => $Changed]);
    }
}
?>