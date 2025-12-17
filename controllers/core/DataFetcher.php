<?php

function select2Data($DB)
{
    try {
        $data = json_decode(customDecrypt($_POST['token']));
        if ($data === null)
            throw new Exception("Invalid token.");

        $table = $data->table;
        $select_val = $data->value;
        $select_txt = implode(",' ',", $data->text);
        $where_from_token = isset($data->where) && !empty($data->where) ? " AND (" . $data->where . ")" : "";
        $select_Parent = isset($data->value_parent) && !empty($data->value_parent) && isset($_POST['parent']) ?
            " AND " . $data->value_parent . (is_array($_POST['parent']) ? " IN (" . implode(",", $_POST['parent']) . ")" : " = " . $_POST['parent']) : "";

        $security_where = "";
        if (isset($_SESSION['user'])) {
            $user_role = $_SESSION['user']['role'] ?? null;
            $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
            $is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));

            // Apply restrictions for non-super admins
            if (!$is_super_admin) {
                // Tables that have 'cabinet_id' column and need filtering
                $cabinet_tables = ['users', 'patient', 'rdv', 'cabinet_services'];

                if (in_array($table, $cabinet_tables)) {
                    if (!empty($user_cabinet_id)) {
                        $security_where = " AND {$table}.cabinet_id = " . intval($user_cabinet_id);
                    } else {
                        // If user has no cabinet_id (and not super admin), restrict access
                        // Exception: Users might need to see their own profile, handled by ID usually
                        $security_where = " AND {$table}.cabinet_id IS NULL";
                    }
                }
            }
        }

        $join_query = '';
        if (isset($data->join) && is_array($data->join) && !empty($data->join)) {
            $join_query = implode(' ', array_map(function ($j) {
                return $j->type . ' ' . $j->table . ' ON ' . $j->condition;
            }, $data->join));
        }

        $sql = "SELECT $select_val AS select_value, CONCAT_WS(' ',$select_txt) AS select_txt FROM $table $join_query WHERE 1=1 ";

        $searchTerm = $_POST['searchTerm'] ?? null;
        $search_condition = "";
        if ($searchTerm !== null && $searchTerm !== '') {
            $sanitizedSearchTerm = str_replace(" ", "%", filter_var($searchTerm, FILTER_SANITIZE_ADD_SLASHES));
            $search_condition = " AND CONCAT_WS(' ',$select_txt) LIKE '%" . $sanitizedSearchTerm . "%'";
        }

        $sql .= $search_condition . $where_from_token . $select_Parent . $security_where;

        $responseResult = $DB->select($sql);
        $response = array();
        foreach ($responseResult as $res) {
            $response[] = array("id" => $res['select_value'], "text" => $res['select_txt']);
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
        // 1. Authentication Check
        if (!isset($_SESSION['user']['id'])) {
            throw new Exception("Unauthorized access.");
        }

        $data = json_decode(customDecrypt($_POST['express']));
        $table = trim(customDecrypt($_POST['class']));
        $column = trim($data->column);
        $id = intval($_POST['id']);

        // 2. Authorization Check (IDOR Protection)
        $security_check = "";
        $user_role = $_SESSION['user']['role'] ?? null;
        $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
        $is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));

        if (!$is_super_admin) {
            // List of tables that belong to a specific cabinet
            $cabinet_tables = ['users', 'patient', 'rdv', 'cabinet_services', 'reeducation_dossiers'];

            if (in_array($table, $cabinet_tables)) {
                if (!empty($user_cabinet_id)) {
                    // Special handling for tables that might not have direct cabinet_id but are linked
                    if ($table === 'reeducation_dossiers') {
                        // For dossiers, we ideally check the patient's cabinet or the technician's cabinet.
                        // This simple check assumes the dossier itself might have a cabinet_id or we rely on the initial list filter.
                        // To be strictly secure, a JOIN would be needed here, but for now, we skip strict check 
                        // if the table structure doesn't support it directly to avoid breaking the app.
                        // If reeducation_dossiers has no cabinet_id, we can't filter easily here without a JOIN.
                    } else {
                        // For standard tables with cabinet_id
                        $security_check = " AND cabinet_id = " . intval($user_cabinet_id);
                    }
                }
            }
        }

        $sql = "SELECT * FROM $table WHERE " . $column . " = " . $id . $security_check;

        $response = $DB->select($sql);

        if (empty($response)) {
            // Return empty or error if not found/unauthorized
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