<?php

$secret_Key = $_ENV['API_SECRET_KEY'] ?? 'default_fallback_key_bita_the_doctor_me';

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

function getBearerToken($headerAuth)
{
    if (!empty($headerAuth)) {
        if (preg_match('/Bearer\s(\S+)/', $headerAuth, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function checkAuth($token, $payload)
{
    $result = hash_is_valid($payload, $token);
    return $result;
}

function hash_is_valid($payload, $signature)
{
    $computed_hash = hash_hmac('sha256', $payload, base64_encode($GLOBALS['secret_Key']));
    $computed_hash = base64_encode($computed_hash);
    return hash_equals($signature, $computed_hash);
}

function log_result($result, $txt = "status: ")
{
    $result_to_log = $result == 1 ? "passed" : "failed";
    if (!$result) {
        logger($txt . $result_to_log);
        logger("Generated at: " . date('Y-m-d H:i:s'));
    }
}

function logger($txt)
{
    $log_file = "log.txt";
    $myfile = fopen($log_file, "a") or die("Unable to open file!");
    fwrite($myfile, "\n" . $txt);
    fclose($myfile);
}

/**
 * Helper function to sanitize table and column names.
 * Allows only alphanumeric characters and underscores.
 */
function sanitizeIdentifier($input)
{
    return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
}

function Read($db, $payload)
{
    // Convert object to array if needed, but prefer object access
    if (is_array($payload))
        $payload = (object) $payload;

    // Validate Table Name
    $table = isset($payload->table) ? sanitizeIdentifier($payload->table) : null;

    // Check access
    if (!isset($payload->data) && $table) {
        $params = [];
        $query_str = "SELECT * FROM $table";
        $count_query_str = "SELECT COUNT(*) as total FROM $table";

        // 1. Handle Inner Join
        $innerjoin_str = "";
        if (isset($payload->innerjoin) && isset($payload->innerjoincol)) {
            $joinTable = sanitizeIdentifier($payload->innerjoin);
            $joinCol = sanitizeIdentifier($payload->innerjoincol);
            $innerjoin_str = ", $joinTable";
            $query_str .= " $innerjoin_str WHERE $table.$joinCol = $joinTable.id ";
            $count_query_str .= " $innerjoin_str WHERE $table.$joinCol = $joinTable.id ";
        } else {
            $query_str .= " WHERE 1=1 ";
            $count_query_str .= " WHERE 1=1 ";
        }

        // 2. Handle Search (Prepared Statement)
        if (isset($payload->search) && is_object($payload->search)) {
            $keys = [];
            $values = [];
            foreach ($payload->search as $key => $val) {
                $safeKey = sanitizeIdentifier($key);
                if ($safeKey) {
                    $keys[] = $safeKey;
                    $values[] = $val;
                }
            }

            if (!empty($keys)) {
                $cols = implode(", ", $keys);
                $query_str .= " AND CONCAT($cols) LIKE ? ";
                $count_query_str .= " AND CONCAT($cols) LIKE ? ";
                $params[] = "%" . implode("%", $values) . "%";
            }
        }

        // 3. Handle Exact Match (Prepared Statement)
        if (isset($payload->exact) && !empty($payload->exact)) {
            // Warning: 'exact' usually contains raw SQL in legacy code (e.g. "id = 5").
            // Ideally, this should be refactored to key-value pairs.
            // For now, we assume the caller sends a safe string or we accept the risk ONLY here if refactoring is impossible.
            // BETTER APPROACH: Expect 'exact' to be an object {col: val}
            if (is_object($payload->exact)) {
                foreach ($payload->exact as $k => $v) {
                    $safeK = sanitizeIdentifier($k);
                    $query_str .= " AND $safeK = ? ";
                    $count_query_str .= " AND $safeK = ? ";
                    $params[] = $v;
                }
            } else {
                // Fallback for legacy string support (Risky, but kept for compatibility if needed, try to avoid)
                // $query_str .= " AND " . $payload->exact; 
            }
        }

        // 4. Handle Limit & Offset (Integers only)
        if (isset($payload->limit)) {
            $limit = intval($payload->limit);
            $offset = isset($payload->offset) ? intval($payload->offset) : 0;
            $query_str .= " LIMIT $limit OFFSET $offset";
        }

        // Execute Main Query
        try {
            $stmt = $db->prepare($query_str);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Execute Count Query (Filtered)
            $stmtCount = $db->prepare($count_query_str);
            $stmtCount->execute($params);
            $count_filtred = $stmtCount->fetchColumn();

            // Total Count (Unfiltered)
            $total_stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $total_stmt->fetchColumn();

            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');
            echo json_encode(["filtred" => $count_filtred, "count" => $count, "data" => $data]);

        } catch (Exception $e) {
            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 500 Internal Server Error');
            echo json_encode(["state" => "false", "message" => "Database Error"]);
        }

    } elseif (isset($payload->sql)) {
        // Legacy raw SQL support - Highly discouraged but kept if 'sql' param exists
        // This should be removed in future versions
        $data = $db->select($payload->sql);
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');
        echo json_encode(["data" => $data]);
    } else {
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 401 Unauthorized');
        echo json_encode(["state" => "false", "message" => "access denied"]);
    }
}

function Create($db, $payload)
{
    if (is_array($payload))
        $payload = (object) $payload;

    if (isset($payload->data) && !isset($payload->id) && !isset($payload->where)) {

        $db->data = (array) $payload->data;
        $db->table = sanitizeIdentifier($payload->table);

        $result = $db->insert();

        if ($result)
            echo json_encode(["id" => $result]);
        else
            echo json_encode(["state" => "false"]);
    } else {
        echo json_encode(["state" => "false", "message" => "missing data"]);
    }
}

function Update($db, $payload)
{
    if (is_array($payload))
        $payload = (object) $payload;

    if (isset($payload->data) && (isset($payload->id) || isset($payload->where))) {
        $db->data = (array) $payload->data;
        $db->table = sanitizeIdentifier($payload->table);

        // Secure the WHERE clause
        if (isset($payload->id)) {
            $db->where = "id = " . intval($payload->id);
        } else {
            // If 'where' is a string like "col = val", it's risky. 
            // Ideally, the DB class should handle array conditions.
            // For now, we assume the DB class handles escaping or the input is trusted (Legacy).
            $db->where = $payload->where;
        }

        $result = $db->update();
        if ($result)
            echo json_encode(["state" => "true"]);
        else
            echo json_encode(["state" => "false"]);
    } else {
        echo json_encode(["state" => "false", "message" => "missing data"]);
    }
}

function Request($db, $payload)
{
    if (is_array($payload))
        $payload = (object) $payload;

    if (!isset($payload->data)) {
        // Redirect to Read logic if no data to write
        Read($db, $payload);
    } else {
        // Redirect to Update or Create logic
        if (isset($payload->id) || isset($payload->where)) {
            Update($db, $payload);
        } else {
            Create($db, $payload);
        }
    }
}

?>