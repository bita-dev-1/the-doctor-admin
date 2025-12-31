<?php
// worker_mail.php - النسخة المستقلة (Standalone)

// 1. ضبط المسار والوقت
chdir(__DIR__);
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0); // منع توقف السكربت بسبب الوقت

// دالة تسجيل
function worker_log($msg)
{
    // $logFile = __DIR__ . '/worker_debug.log';
    // $date = date('Y-m-d H:i:s');
    // $pid = getmypid();
    // file_put_contents($logFile, "[$date] [PID:$pid] $msg" . PHP_EOL, FILE_APPEND);
}

worker_log("Worker started (Standalone Mode)...");

try {
    // 2. تحميل مكتبات Composer (للـ PHPMailer و Dotenv)
    if (!file_exists('vendor/autoload.php')) {
        throw new Exception("vendor/autoload.php not found. Run 'composer install'");
    }
    require_once 'vendor/autoload.php';

    // 3. تحميل متغيرات البيئة (.env) يدوياً
    if (file_exists('.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } else {
        worker_log("Warning: .env file not found.");
    }

    // 4. تحميل كلاس قاعدة البيانات
    if (!file_exists('config/DB.php')) {
        throw new Exception("config/DB.php not found");
    }
    require_once 'config/DB.php';

    // 5. الاتصال بقاعدة البيانات
    $db = new DB();
    worker_log("Database connected.");

    // 6. تعريف دالة إرسال الإيميل محلياً (لتجنب مشاكل functions.core.php)
    function sendEmailLocal($to, $name, $subject, $body)
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
            $mail->Port = $_ENV['MAIL_PORT'] ?? 587;
            $mail->CharSet = 'UTF-8';

            // تجاوز مشاكل SSL في السيرفر المحلي
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME'] ?? 'The Doctor');
            $mail->addAddress($to, $name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return "Mailer Error: " . $mail->ErrorInfo;
        }
    }

    // 7. جلب الإيميلات المعلقة
    $sql = "SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY id ASC LIMIT 5";
    $emails = $db->select($sql);

    if (!empty($emails)) {
        worker_log("Found " . count($emails) . " emails.");

        foreach ($emails as $mail) {
            $id = $mail['id'];

            // تحديث المحاولات
            $db->pdo->query("UPDATE email_queue SET attempts = attempts + 1 WHERE id = $id");

            worker_log("Sending to: " . $mail['recipient_email']);

            $result = sendEmailLocal($mail['recipient_email'], $mail['recipient_name'], $mail['subject'], $mail['body']);

            if ($result === true) {
                $stmt = $db->pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
                $stmt->execute([$id]);
                worker_log("ID $id: SENT.");
            } else {
                $errorInfo = substr($result, 0, 250);
                $stmt = $db->pdo->prepare("UPDATE email_queue SET status = 'failed' WHERE id = ?");
                $stmt->execute([$id]);
                worker_log("ID $id: FAILED. $errorInfo");
            }
        }
    } else {
        worker_log("No pending emails.");
    }

} catch (Exception $e) {
    worker_log("FATAL ERROR: " . $e->getMessage());
}

worker_log("Worker finished.");
?>