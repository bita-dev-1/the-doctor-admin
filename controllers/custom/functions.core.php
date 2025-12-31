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

// Logging Helper
function writeToLog($message)
{
    $logFile = __DIR__ . '/../../email_debug.log';
    $timestamp = date("Y-m-d H:i:s");
    $formattedMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

function getSelected($request)
{
    $data = json_decode(customDecrypt($request));

    // Sanitize table name
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $data->table);

    $select_val = preg_replace('/[^a-zA-Z0-9_]/', '', $data->value);

    $select_txt_arr = array_map(function ($col) {
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $col);
    }, $data->text);
    $select_txt = implode(",' ',", $select_txt_arr);

    $where = "";
    // Note: $data->where is still raw from token, relying on encryption integrity for now.
    if (isset($data->where) && !empty($data->where)) {
        $where = " AND " . $data->where;
    }

    $join_query = '';
    if (isset($data->join) && is_array($data->join) && !empty($data->join)) {
        $join_query = implode(' ', array_map(function ($j) {
            $j = (array) $j;
            return $j['type'] . ' ' . $j['table'] . ' ON ' . $j['condition'];
        }, $data->join));
    }

    $params = [];
    $selected_clause = "";

    if (isset($data->selected) && !empty($data->selected)) {
        if (is_array($data->selected)) {
            $placeholders = implode(',', array_fill(0, count($data->selected), '?'));
            $selected_clause = " AND $select_val IN ($placeholders) ";
            $params = $data->selected;
        } else {
            $selected_clause = " AND $select_val = ? ";
            $params[] = $data->selected;
        }
    }

    $sql = "SELECT $select_val AS select_value, CONCAT_WS(' ',$select_txt) AS select_txt FROM $table $join_query WHERE 1 $where $selected_clause LIMIT 10";

    $response = $GLOBALS['DB']->select($sql, $params);

    foreach ($response as $res) {
        echo '<option value="' . htmlspecialchars($res['select_value']) . '" selected="selected">' . htmlspecialchars($res['select_txt']) . '</option>';
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

    // Secure Query
    $col = preg_replace('/[^a-zA-Z0-9_]/', '', $data['column']);
    $sql = "SELECT * FROM $table $join_query WHERE $table.$col = ?";

    $response = $GLOBALS['DB']->select($sql, [$data['val']]);

    return $response;
}


/**
 * Send Email using Environment Variables
 */
function sendEmail($recipientEmail, $recipientName, $subject, $body)
{
    // تأكد من تحميل الكلاس
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return "PHPMailer class not found.";
    }

    $mail = new PHPMailer(true);

    try {
        // إعدادات السيرفر
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // قم بإلغاء التعليق لرؤية تفاصيل الاتصال في المتصفح عند التجربة
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION; // tls
        $mail->Port = MAIL_PORT;       // 587

        // إعدادات الترميز (مهمة للغة العربية)
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // حل مشكلة شهادات SSL في السيرفر المحلي (Localhost)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // المرسل والمستقبل
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($recipientEmail, $recipientName);

        // المحتوى
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // نسخة نصية للأجهزة التي لا تدعم HTML

        $mail->send();
        return true;

    } catch (Exception $e) {
        // تسجيل الخطأ في ملف log بدلاً من عرضه للمستخدم
        $errorMsg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        error_log($errorMsg); // يسجل الخطأ في php_error.log
        return $errorMsg;
    }
}

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
            $data = filter_var($data, FILTER_SANITIZE_EMAIL);
            if (!filter_var($data, FILTER_VALIDATE_EMAIL))
                return false;
            return $data;
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        case 'phone':
            return preg_replace('/[^0-9+]/', '', $data);
        case 'date':
            if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data))
                return $data;
            return null;
        case 'string':
        default:
            return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }
}

function queue_email($to, $name, $subject, $body)
{
    global $db;
    if (!isset($db) || !($db instanceof DB)) {
        $db = new DB();
    }

    // 1. إدخال الإيميل في قاعدة البيانات
    $db->table = 'email_queue';
    $db->data = [
        'recipient_email' => $to,
        'recipient_name' => $name,
        'subject' => $subject,
        'body' => $body,
        'status' => 'pending'
    ];
    $insertId = $db->insert();

    if (!$insertId) {
        error_log("Failed to insert email into queue.");
        return false;
    }

    // 2. تشغيل العامل (Worker)
    $scriptPath = PROJECT_ROOT . '/worker_mail.php';

    // الطريقة 1: محاولة التشغيل عبر سطر الأوامر (الأسرع)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // في ويندوز، نحاول استخدام php مباشرة. 
        // إذا لم يعمل، تأكد من إضافة مسار php.exe إلى Environment Variables
        pclose(popen("start /B php \"$scriptPath\"", "r"));
    } else {
        exec("php \"$scriptPath\" > /dev/null 2>&1 &");
    }

    // الطريقة 2 (احتياطية): إذا فشل سطر الأوامر، يمكن استدعاء الملف عبر رابط HTTP
    // (قم بإلغاء التعليق أدناه إذا لم تعمل الطريقة الأولى)
    /*
    $url = SITE_URI . 'worker_mail.php?secret_trigger=1';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // لا ننتظر الرد
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
    */

    return true;
}
?>