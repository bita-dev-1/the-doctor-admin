<?php
class DB
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $pdo;
    public $table;
    public $data;
    public $where;
    public $column;
    public $multi = false;
    public $field;
    public $value;

    public function __construct()
    {
        // 1. محاولة جلب البيانات من متغيرات البيئة (التي حملها inc.php)
        // نستخدم $_ENV كخيار أول، ثم getenv كخيار ثانٍ
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'the_doctor_db';
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

        // التحقق من وجود البيانات (للتصحيح فقط)
        if (empty($this->db_name) || empty($this->username)) {
            // محاولة تحميل inc.php يدوياً إذا لم يتم تحميله (حالة نادرة)
            if (file_exists(__DIR__ . '/../../inc.php')) {
                require_once(__DIR__ . '/../../inc.php');
                $this->host = $_ENV['DB_HOST'] ?? 'localhost';
                $this->db_name = $_ENV['DB_NAME'] ?? '';
                $this->username = $_ENV['DB_USER'] ?? 'root';
                $this->password = $_ENV['DB_PASS'] ?? '';
            }
        }

        $this->connect();
    }

    public function connect()
    {
        $this->pdo = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $exception) {
            // تسجيل الخطأ في ملف اللوج بدلاً من عرضه للمستخدم
            error_log("DB Connection Error: " . $exception->getMessage());

            // إرجاع رسالة JSON نظيفة
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                "state" => "false",
                "message" => "Database Connection Failed. Check server logs."
            ]);
            exit();
        }

        return $this->pdo;
    }

    // ... (باقي دوال الكلاس: select, insert, update, delete تبقى كما هي) ...

    public function select($sql)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage() . " in Query: " . $sql);
            return [];
        }
    }

    public function rowsCount($sql)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function insert()
    {
        if (!empty($this->data) && !empty($this->table)) {
            try {
                if ($this->multi) {
                    // Multi insert logic
                    $columns = implode(", ", array_keys($this->data[0]));
                    $values = array();
                    $params = array();

                    foreach ($this->data as $row) {
                        $row_placeholders = [];
                        foreach ($row as $key => $val) {
                            $row_placeholders[] = "?";
                            $params[] = $val;
                        }
                        $values[] = "(" . implode(", ", $row_placeholders) . ")";
                    }

                    $sql = "INSERT INTO $this->table ($columns) VALUES " . implode(", ", $values);
                    $stmt = $this->pdo->prepare($sql);
                    return $stmt->execute($params);

                } else {
                    // Single insert logic
                    $columns = implode(", ", array_keys($this->data));
                    $placeholders = ":" . implode(", :", array_keys($this->data));

                    $sql = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
                    $stmt = $this->pdo->prepare($sql);

                    if ($stmt->execute($this->data)) {
                        return $this->pdo->lastInsertId();
                    }
                }
            } catch (PDOException $e) {
                error_log("Insert Error: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    public function update()
    {
        if (!empty($this->data) && !empty($this->table) && !empty($this->where)) {
            try {
                $fields = "";
                foreach ($this->data as $key => $value) {
                    $fields .= "$key = :$key, ";
                }
                $fields = rtrim($fields, ", ");

                $sql = "UPDATE $this->table SET $fields WHERE $this->where";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($this->data);
            } catch (PDOException $e) {
                error_log("Update Error: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    public function Delete()
    {
        if (!empty($this->table)) {
            try {
                if ($this->multi && !empty($this->data) && !empty($this->column)) {
                    $ids = implode(",", array_map('intval', $this->data));
                    $sql = "DELETE FROM $this->table WHERE $this->column IN ($ids)";
                } else if (!empty($this->where)) {
                    $sql = "DELETE FROM $this->table WHERE $this->where";
                } else {
                    return false;
                }

                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute();
            } catch (PDOException $e) {
                error_log("Delete Error: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    public function validateField()
    {
        // Used for checking uniqueness
        $sql = "SELECT * FROM $this->table WHERE $this->field = :value";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':value' => $this->value]);
        return $stmt->rowCount() > 0;
    }

    // Helper for direct PDO access if needed
    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }
}
?>