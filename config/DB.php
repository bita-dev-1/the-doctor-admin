<?php

class DB
{

    private $host = 'localhost';
    private $db_name = 'the_doctor_db1';
    private $username = 'root';
    private $password = '';

    public $pdo;

    public $table;
    public $data = [];
    public $where;
    public $column = 'id';
    public $multi = false;

    public function __construct()
    {
        if (isset($_ENV['DB_HOST']))
            $this->host = $_ENV['DB_HOST'];
        if (isset($_ENV['DB_NAME']))
            $this->db_name = $_ENV['DB_NAME'];
        if (isset($_ENV['DB_USER']))
            $this->username = $_ENV['DB_USER'];
        if (isset($_ENV['DB_PASS']))
            $this->password = $_ENV['DB_PASS'];

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";

            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // التعديل: تفعيل المحاكاة قد يساعد في تمرير الحزم الكبيرة في بعض البيئات
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 300
            );

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

            // محاولة فرض زيادة الحجم المسموح به عالمياً وللجلسة
            try {
                $this->pdo->exec("SET NAMES 'utf8mb4'");
                $this->pdo->exec("SET SESSION wait_timeout=28800");
                $this->pdo->exec("SET SESSION max_allowed_packet=67108864"); // 64MB Session
                // محاولة تغيير الإعداد العالمي (يتطلب صلاحيات root)
                $this->pdo->exec("SET GLOBAL max_allowed_packet=67108864");
            } catch (Exception $e) {
                // تجاهل الخطأ إذا لم تكن هناك صلاحيات
            }

        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function select($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Select Error: " . $e->getMessage());
        }
    }

    public function insert()
    {
        if (empty($this->data))
            return false;

        try {
            if ($this->multi) {
                $firstRow = reset($this->data);
                $keys = array_keys($firstRow);
                $fields = "`" . implode("`, `", $keys) . "`";

                $values = [];
                $allParams = [];

                foreach ($this->data as $row) {
                    $placeholders = [];
                    foreach ($row as $value) {
                        $placeholders[] = "?";
                        $allParams[] = $value;
                    }
                    $values[] = "(" . implode(", ", $placeholders) . ")";
                }

                $sql = "INSERT INTO `$this->table` ($fields) VALUES " . implode(", ", $values);
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($allParams);

                return $this->pdo->lastInsertId();

            } else {
                $keys = array_keys($this->data);
                $fields = "`" . implode("`, `", $keys) . "`";
                $placeholders = ":" . implode(", :", $keys);

                $sql = "INSERT INTO `$this->table` ($fields) VALUES ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($this->data);

                return $this->pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Insert Error: " . $e->getMessage());
            return false;
        }
    }

    public function update()
    {
        if (empty($this->data) || empty($this->where))
            return false;

        try {
            $setPart = [];
            foreach ($this->data as $key => $value) {
                $setPart[] = "`$key` = :$key";
            }

            $sql = "UPDATE `$this->table` SET " . implode(', ', $setPart) . " WHERE $this->where";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute($this->data);
        } catch (PDOException $e) {
            // إذا انقطع الاتصال، نحاول إعادة الاتصال مرة واحدة (Re-connect logic could be added here)
            if ($e->errorInfo[1] == 2006) {
                // Log critical error
                error_log("CRITICAL: MySQL server gone away. Packet size too large?");
            }
            throw $e;
        }
    }

    public function Delete()
    {
        try {
            if ($this->multi) {
                if (empty($this->data))
                    return false;
                $ids = implode(',', array_map('intval', $this->data));
                $sql = "DELETE FROM `$this->table` WHERE `$this->column` IN ($ids)";
                return $this->pdo->exec($sql);
            } else {
                if (empty($this->where))
                    return false;
                $sql = "DELETE FROM `$this->table` WHERE $this->where";
                return $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
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

    public function validateField()
    {
        try {
            $sql = "SELECT id FROM `$this->table` WHERE `$this->field` = :value LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':value' => $this->value]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function close()
    {
        $this->pdo = null;
    }
}
?>