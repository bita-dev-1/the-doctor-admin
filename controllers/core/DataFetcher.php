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

            if ($table !== 'patient' && ($table === 'users' || $table === 'rdv')) {
                if (!$is_super_admin) {
                    if (!empty($user_cabinet_id)) {
                        $security_where = " AND {$table}.cabinet_id = " . intval($user_cabinet_id);
                    } else {
                        $security_where = " AND {$table}.cabinet_id IS NULL";
                    }
                }
            }
        } else {
            if ($table === 'patient' || $table === 'users' || $table === 'rdv') {
                throw new Exception("Authentication required.");
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
        $data = json_decode(customDecrypt($_POST['express']));
        $table = trim(customDecrypt($_POST['class']));
        $column = trim($data->column);
        $sql = "SELECT * FROM $table WHERE " . $column . " = " . $_POST['id'] . "";
        $response = $DB->select($sql);
        $DB = null;
        echo json_encode((array) $response[0]);
    } catch (\Throwable $th) {
        echo json_encode(array("state" => "false", "message" => $th));
    }
}
?>