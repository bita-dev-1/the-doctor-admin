<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Ensure PHPMailer is loaded
if (!class_exists('PHPMailer\PHPMailer\PHPMailer') && defined('PROJECT_ROOT')) {
    $autoloadPath = PROJECT_ROOT . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }
}

global $DB;
$DB = new DB();

// --- NEW: Logging Helper Function ---
function writeToLog($message)
{
    $logFile = __DIR__ . '/../../email_debug.log'; // Save in controllers folder for easy access
    $timestamp = date("Y-m-d H:i:s");
    $formattedMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

function getSelected($request)
{
    // ... (باقي الدالة كما هي دون تغيير) ...
    $data = json_decode(customDecrypt($request));
    $table = $data->table;
    $select_val = $data->value;
    $select_txt = implode(",' ',", $data->text);
    $where = isset($data->where) && !empty($data->where) ? " AND " . $data->where : "";

    $join_query = '';
    if (isset($data->join) && is_array($data->join) && !empty($data->join)) {
        $join_query = implode(' ', array_map(function ($j) {
            $j = (array) $j;
            return $j['type'] . ' ' . $j['table'] . ' ON ' . $j['condition'];
        }, $data->join));
    }

    $selected = " AND " . $select_val;
    if (isset($data->selected) && !empty($data->selected))
        $selected .= (is_array($data->selected) ? " IN (" . implode(',', $data->selected) . ") " : " = " . $data->selected);

    $sql = "SELECT $select_val AS select_value, CONCAT_WS(' ',$select_txt) AS select_txt FROM $table $join_query WHERE 1 $where $selected LIMIT 10";

    $response = $GLOBALS['DB']->select($sql);

    foreach ($response as $res) {
        echo '<option value="' . $res['select_value'] . '" selected="selected">' . $res['select_txt'] . '</option>';
    }
}

function dataById($data, $table, $join = [])
{
    // ... (باقي الدالة كما هي دون تغيير) ...
    $join_query = '';
    if (!empty($join)) {
        $join_query = implode(' ', array_map(function ($j) {
            return $j['type'] . ' ' . $j['table'] . ' ON ' . $j['condition'];
        }, $join));
    }

    $sql = "SELECT * FROM $table $join_query WHERE $table.$data[column] = $data[val]";

    $response = $GLOBALS['DB']->select($sql);

    return $response;
}

/**
 * UPDATED: Send Email with Logging
 */
function sendEmail($recipientEmail, $recipientName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        writeToLog("Attempting to send email to: $recipientEmail");

        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable for verbose debug output in browser response
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = 'UTF-8';

        //Recipients
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($recipientEmail, $recipientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        writeToLog("Email sent successfully to: $recipientEmail");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        writeToLog("ERROR: $errorMsg");
        return $errorMsg;
    }
}

// ... (باقي الدوال generateRandomPassword و getDoctorUrl كما هي) ...
function generateRandomPassword($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    $char_length = strlen($characters);
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $char_length - 1)];
    }
    return $password;
}

function getDoctorUrl($id, $firstName, $lastName)
{
    $slug = strtolower(trim($lastName . '-' . $firstName));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return SITE_URL . "/dr/$slug-$id";
}

/**
 * Helper function to sanitize and validate inputs
 */
function secure_input($data, $type = 'string')
{
    if (is_array($data)) {
        return array_map(function ($item) use ($type) {
            return secure_input($item, $type);
        }, $data);
    }

    $data = trim($data);

    switch ($type) {
        case 'email':
            // Remove illegal characters from email
            $data = filter_var($data, FILTER_SANITIZE_EMAIL);
            // Validate email
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return false; // Invalid email
            }
            return $data;

        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);

        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);

        case 'phone':
            // Keep only numbers and +
            return preg_replace('/[^0-9+]/', '', $data);

        case 'date':
            // Basic YYYY-MM-DD format check
            if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data)) {
                return $data;
            }
            return null;

        case 'string':
        default:
            // Convert special characters to HTML entities (Prevents XSS)
            // Strip tags to remove HTML entirely (Stricter)
            return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }
}
?>