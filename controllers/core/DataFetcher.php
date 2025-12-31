<?php

function select2Data($DB)
{
    try {
        $data = json_decode(customDecrypt($_POST['token']));
        if ($data === null)
            throw new Exception("Invalid token.");

        // Sanitize Table Name (Allow only alphanumeric and underscores)
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $data->table);

        $select_val = preg_replace('/[^a-zA-Z0-9_]/', '', $data->value);

        // Sanitize Columns
        $select_txt_arr = array_map(function ($col) {
            return preg_replace('/[^a-zA-Z0-9_.]/', '', $col);
        }, $data->text);
        $select_txt = implode(",' ',", $select_txt_arr);

        // Warning: $data->where comes from token. We assume token integrity via encryption key.
        // Ideally, we should not pass raw SQL in token.
        $where_from_token = isset($data->where) && !empty($data->where) ? " AND (" . $data->where . ")" : "";

        $select_Parent = "";
        if (isset($data->value_parent) && !empty($data->value_parent) && isset($_POST['parent'])) {
            $parentCol = preg_replace('/[^a-zA-Z0-9_]/', '', $data->value_parent);
            $parentVal = $_POST['parent'];

            if (is_array($parentVal)) {
                $placeholders = implode(',', array_fill(0, count($parentVal), '?'));
                $select_Parent = " AND $parentCol IN ($placeholders) ";
                $parentParams = $parentVal;
            } else {
                $select_Parent = " AND $parentCol = ? ";
                $parentParams = [$parentVal];
            }
        } else {
            $parentParams = [];
        }

        $security_where = "";
        if (isset($_SESSION['user'])) {
            $user_role = $_SESSION['user']['role'] ?? null;
            $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
            $is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));

            if (!$is_super_admin) {
                $cabinet_tables = ['users', 'patient', 'rdv', 'cabinet_services'];
                if (in_array($table, $cabinet_tables)) {
                    if (!empty($user_cabinet_id)) {
                        $security_where = " AND {$table}.cabinet_id = " . intval($user_cabinet_id);
                    } else {
                        $security_where = " AND {$table}.cabinet_id IS NULL";
                    }
                }
            }
        }

        $join_query = '';
        if (isset($data->join) && is_array($data->join) && !empty($data->join)) {
            // Basic sanitization for joins (Legacy support)
            $join_query = implode(' ', array_map(function ($j) {
                return $j->type . ' ' . $j->table . ' ON ' . $j->condition;
            }, $data->join));
        }

        $sql = "SELECT $select_val AS select_value, CONCAT_WS(' ',$select_txt) AS select_txt FROM $table $join_query WHERE 1=1 ";

        $searchTerm = $_POST['searchTerm'] ?? null;
        $search_condition = "";
        $searchParams = [];

        if ($searchTerm !== null && $searchTerm !== '') {
            $search_condition = " AND CONCAT_WS(' ',$select_txt) LIKE ? ";
            $searchParams[] = "%" . $searchTerm . "%";
        }

        $sql .= $search_condition . $where_from_token . $select_Parent . $security_where;

        // Merge parameters
        $finalParams = array_merge($searchParams, $parentParams);

        $responseResult = $DB->select($sql, $finalParams);

        $response = array();
        foreach ($responseResult as $res) {
            // XSS Protection on Output
            $response[] = array(
                "id" => htmlspecialchars($res['select_value']),
                "text" => htmlspecialchars($res['select_txt'])
            );
        }
        echo json_encode($response);

    } catch (Throwable $th) {
        http_response_code(500);
        echo json_encode(["error" => $th->getMessage()]);
    } finally {
        $DB = null;
    }
}

function dataById_handler($DB)
{
    try {
        if (!isset($_SESSION['user']['id'])) {
            throw new Exception("Unauthorized access.");
        }

        $data = json_decode(customDecrypt($_POST['express']));
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', trim(customDecrypt($_POST['class'])));
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', trim($data->column));
        $id = intval($_POST['id']);

        $security_check = "";
        $user_role = $_SESSION['user']['role'] ?? null;
        $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
        $is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));

        if (!$is_super_admin) {
            $cabinet_tables = ['users', 'patient', 'rdv', 'cabinet_services', 'reeducation_dossiers'];
            if (in_array($table, $cabinet_tables)) {
                if (!empty($user_cabinet_id)) {
                    if ($table !== 'reeducation_dossiers') {
                        $security_check = " AND cabinet_id = " . intval($user_cabinet_id);
                    }
                }
            }
        }

        // Secure Query
        $sql = "SELECT * FROM $table WHERE $column = ? $security_check";
        $response = $DB->select($sql, [$id]);

        if (empty($response)) {
            echo json_encode(["state" => "false", "message" => "Data not found or access denied"]);
        } else {
            $DB = null;
            echo json_encode((array) $response[0]);
        }

    } catch (\Throwable $th) {
        echo json_encode(array("state" => "false", "message" => "Error: " . $th->getMessage()));
    }
}
?>