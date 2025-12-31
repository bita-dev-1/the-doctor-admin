<?php
class DB
{
    public $pdo;
    public $error;

    // إعدادات الاتصال الافتراضية (يجب أن تتطابق مع إعداداتك في config/DB.php الرئيسي)
    private $host = "localhost";
    private $db_name = "cloud-doctor1"; // تأكد من اسم القاعدة
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";

    public function __construct()
    {
        $dsn = "mysql:host=$this->host;dbname=$this->db_name;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            // في بيئة الإنتاج، لا تطبع الخطأ مباشرة للمستخدم
            // throw new \PDOException($e->getMessage(), (int)$e->getCode());
            die(json_encode(["state" => "false", "message" => "Database Connection Failed"]));
        }
    }

    // الدالة المصححة
    public function select($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);

            // التصحيح: تمرير المصفوفة هنا إلى execute
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }

            // إرجاع النتائج كمصفوفة ترابطية
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // تسجيل الخطأ بدلاً من إيقاف التنفيذ
            error_log("API DB Error: " . $e->getMessage() . " SQL: " . $sql);
            return [];
        }
    }

    // دوال مساعدة أخرى قد يحتاجها النظام (Insert, Update, etc.)
    // يتم استدعاؤها عادة عبر $db->table, $db->data ... 
    // سأضيف الدوال الأساسية لضمان عدم توقف الكود الذي يعتمد عليها

    public $table;
    public $data;
    public $where;

    public function insert()
    {
        if (empty($this->table) || empty($this->data))
            return false;

        $columns = implode(", ", array_keys($this->data));
        $placeholders = implode(", ", array_fill(0, count($this->data), "?"));
        $values = array_values($this->data);

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("API Insert Error: " . $e->getMessage());
            return false;
        }
    }

    public function update()
    {
        if (empty($this->table) || empty($this->data) || empty($this->where))
            return false;

        $set_parts = [];
        $values = [];
        foreach ($this->data as $key => $val) {
            $set_parts[] = "$key = ?";
            $values[] = $val;
        }
        $set_clause = implode(", ", $set_parts);

        // ملاحظة: $this->where هنا غالباً نصي في الكود القديم، وهذا خطر SQL Injection
        // يجب الحذر، لكن للتوافق سنتركه كما هو مع التوصية بتغييره لاحقاً
        $sql = "UPDATE {$this->table} SET $set_clause WHERE {$this->where}";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("API Update Error: " . $e->getMessage());
            return false;
        }
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    public function rowsCount($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>