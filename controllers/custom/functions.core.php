<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Ensure PHPMailer is loaded only if not already done by inc.php
if (!class_exists('PHPMailer\PHPMailer\PHPMailer') && defined('PROJECT_ROOT')) {
    $autoloadPath = PROJECT_ROOT . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }
}


global $DB;
$DB = new DB();



function getSelected($request)
{
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

    // التعديل هنا: إضافة "AS select_value" لتوحيد مفتاح المصفوفة
    $sql = "SELECT $select_val AS select_value, CONCAT_WS(' ',$select_txt) AS select_txt FROM $table $join_query WHERE 1 $where $selected LIMIT 10";

    $response = $GLOBALS['DB']->select($sql);

    foreach ($response as $res) {
        // استخدام المفتاح الموحد 'select_value' بدلاً من المتغير $select_val
        echo '<option value="' . $res['select_value'] . '" selected="selected">' . $res['select_txt'] . '</option>';
    }
}

function dataById($data, $table, $join = [])
{

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
 * NEW: Generic and secure function to send emails using settings from .env
 */
function sendEmail($recipientEmail, $recipientName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for debugging
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION; // PHPMailer::ENCRYPTION_SMTPS or 'tls'
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
        return true;
    } catch (Exception $e) {
        // In a real app, you'd log this error instead of echoing it
        // error_log("Mailer Error: {$mail->ErrorInfo}");
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}


// Function to generate a random password
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
    // تنظيف الاسم ليكون URL Friendly
    $slug = strtolower(trim($lastName . '-' . $firstName)); // Lastname-Firstname
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // New Format: /dr/slug-id
    return SITE_URL . "/dr/$slug-$id";
}