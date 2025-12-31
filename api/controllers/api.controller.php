<?php

// Load Secret from Environment
$secret_Key = $_ENV['API_SECRET_KEY'] ?? 'default_fallback_key_change_me';

// Security: Whitelist tables allowed to be accessed via generic API
// IMPORTANT: Never include 'users', 'admins', 'payments' here.
const ALLOWED_API_TABLES = [
    'doctor',
    'specialty',
    'communes',
    'willaya',
    'doctor_motifs',
    'cabinets'
];

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
    return hash_is_valid($payload, $token);
}

function hash_is_valid($payload, $signature)
{
    global $secret_Key;
    $computed_hash = hash_hmac('sha256', $payload, base64_encode($secret_Key));
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

function sanitizeIdentifier($input)
{
    return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
}

function Read($db, $payload)
{
    if (is_array($payload))
        $payload = (object) $payload;

    $table = isset($payload->table) ? sanitizeIdentifier($payload->table) : null;

    // Security: Check Whitelist
    if (!in_array($table, ALLOWED_API_TABLES)) {
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
        echo json_encode(["state" => "false", "message" => "Access to this table is forbidden via API"]);
        return;
    }

    if (!isset($payload->data) && $table) {
        $params = [];
        $query_str = "SELECT * FROM $table";
        $count_query_str = "SELECT COUNT(*) as total FROM $table";

        // 1. Handle Inner Join
        $innerjoin_str = "";
        if (isset($payload->innerjoin) && isset($payload->innerjoincol)) {
            $joinTable = sanitizeIdentifier($payload->innerjoin);

            // Security: Check Join Table Whitelist too
            if (!in_array($joinTable, ALLOWED_API_TABLES)) {
                echo json_encode(["state" => "false", "message" => "Join table forbidden"]);
                return;
            }

            $joinCol = sanitizeIdentifier($payload->innerjoincol);
            $innerjoin_str = ", $joinTable";
            $query_str .= " $innerjoin_str WHERE $table.$joinCol = $joinTable.id ";
            $count_query_str .= " $innerjoin_str WHERE $table.$joinCol = $joinTable.id ";
        } else {
            $query_str .= " WHERE 1=1 ";
            $count_query_str .= " WHERE 1=1 ";
        }

        // 2. Handle Search
        if (isset($payload->search) && is_object($payload->search)) {
            $keys = [];
            foreach ($payload->search as $key => $val) {
                $safeKey = sanitizeIdentifier($key);
                if ($safeKey) {
                    $keys[] = $safeKey;
                    $params[] = "%" . $val . "%";
                }
            }
            if (!empty($keys)) {
                $cols = implode(", ", $keys);
                $query_str .= " AND CONCAT($cols) LIKE ? ";
                $count_query_str .= " AND CONCAT($cols) LIKE ? ";
            }
        }

        // 3. Handle Exact Match
        if (isset($payload->exact) && is_object($payload->exact)) {
            foreach ($payload->exact as $k => $v) {
                $safeK = sanitizeIdentifier($k);
                $query_str .= " AND $safeK = ? ";
                $count_query_str .= " AND $safeK = ? ";
                $params[] = $v;
            }
        }

        // 4. Handle Limit & Offset
        if (isset($payload->limit)) {
            $limit = intval($payload->limit);
            $offset = isset($payload->offset) ? intval($payload->offset) : 0;
            $query_str .= " LIMIT $limit OFFSET $offset";
        }

        try {
            // Execute Main Query
            $data = $db->select($query_str, $params);

            // Execute Count Query (Filtered)
            // Note: We reuse params because the WHERE clauses are identical
            // But we need to be careful if LIMIT was added to params (it wasn't, it's concatenated)
            $stmtCount = $db->prepare($count_query_str);
            $stmtCount->execute($params);
            $count_filtred = $stmtCount->fetchColumn();

            // Total Count (Unfiltered)
            $stmtTotal = $db->prepare("SELECT COUNT(*) FROM $table");
            $stmtTotal->execute();
            $count = $stmtTotal->fetchColumn();

            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');
            echo json_encode(["filtred" => $count_filtred, "count" => $count, "data" => $data]);

        } catch (Exception $e) {
            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 500 Internal Server Error');
            echo json_encode(["state" => "false", "message" => "Database Error"]);
        }

    } else {
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 401 Unauthorized');
        echo json_encode(["state" => "false", "message" => "access denied"]);
    }
}

function Create($db, $payload)
{
    if (is_array($payload))
        $payload = (object) $payload;

    $table = isset($payload->table) ? sanitizeIdentifier($payload->table) : null;

    // Security: Check Whitelist
    if (!in_array($table, ALLOWED_API_TABLES)) {
        echo json_encode(["state" => "false", "message" => "Forbidden table"]);
        return;
    }

    if (isset($payload->data) && !isset($payload->id) && !isset($payload->where)) {
        $db->data = (array) $payload->data;
        $db->table = $table;
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

    $table = isset($payload->table) ? sanitizeIdentifier($payload->table) : null;

    // Security: Check Whitelist
    if (!in_array($table, ALLOWED_API_TABLES)) {
        echo json_encode(["state" => "false", "message" => "Forbidden table"]);
        return;
    }

    if (isset($payload->data) && (isset($payload->id) || isset($payload->where))) {
        $db->data = (array) $payload->data;
        $db->table = $table;

        if (isset($payload->id)) {
            $db->where = "id = " . intval($payload->id);
        } else {
            // Warning: 'where' string from API is risky. 
            // For API updates, we strictly recommend using ID.
            // If 'where' is absolutely needed, it must be sanitized or parsed.
            // For now, we block generic 'where' updates via API for security.
            echo json_encode(["state" => "false", "message" => "Update via generic WHERE is disabled for security. Use ID."]);
            return;
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
        Read($db, $payload);
    } else {
        if (isset($payload->id) || isset($payload->where)) {
            Update($db, $payload);
        } else {
            Create($db, $payload);
        }
    }
}
?>