<?php
// config/DB.php

class DB
{
    public $pdo;
    public $table;
    public $data;
    public $where;
    public $field;
    public $value;
    public $column;
    public $multi = false;
    public $error;

    public function __construct()
    {
        // Load config from .env if available, else fallback
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $db = $_ENV['DB_NAME'] ?? 'the_doctor_db'; // تأكد أن هذا الاسم يطابق قاعدتك cloud-doctor1 إذا لزم الأمر
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            // Show generic error to user, log specific one
            die("Database Connection Error.");
        }
    }

    /**
     * Secure Select Query
     * FIX: Added $params argument to handle Prepared Statements
     */
    public function select($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            // Log the error for debugging
            error_log("DB Select Error: " . $e->getMessage() . " | Query: " . $sql);
            return [];
        }
    }

    /**
     * Secure Insert
     */
    public function insert()
    {
        if (empty($this->data))
            return false;

        try {
            if ($this->multi) {
                // Multi-row insert
                $columns = array_keys($this->data[0]);
                $colsStr = implode(", ", $columns);

                $valuesStr = [];
                $params = [];

                foreach ($this->data as $row) {
                    $rowPlaceholders = [];
                    foreach ($columns as $col) {
                        $rowPlaceholders[] = "?";
                        $params[] = $row[$col];
                    }
                    $valuesStr[] = "(" . implode(", ", $rowPlaceholders) . ")";
                }

                $sql = "INSERT INTO {$this->table} ($colsStr) VALUES " . implode(", ", $valuesStr);
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);

            } else {
                // Single row insert
                $columns = implode(", ", array_keys($this->data));
                $placeholders = implode(", ", array_fill(0, count($this->data), "?"));
                $values = array_values($this->data);

                $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
                $stmt = $this->pdo->prepare($sql);

                if ($stmt->execute($values)) {
                    return $this->pdo->lastInsertId();
                }
                return false;
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log("DB Insert Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Secure Update
     */
    public function update()
    {
        if (empty($this->data) || empty($this->where))
            return false;

        try {
            $setParts = [];
            $params = [];

            foreach ($this->data as $key => $value) {
                $setParts[] = "$key = ?";
                $params[] = $value;
            }

            $setStr = implode(", ", $setParts);

            // Note: $this->where is currently a string coming from controllers.
            // In a full refactor, this should also be parameterized.
            $sql = "UPDATE {$this->table} SET $setStr WHERE {$this->where}";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log("DB Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Secure Delete
     */
    public function Delete()
    {
        try {
            if ($this->multi && is_array($this->data)) {
                $placeholders = implode(',', array_fill(0, count($this->data), '?'));
                $sql = "DELETE FROM {$this->table} WHERE {$this->column} IN ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute(array_values($this->data));
            } else {
                $sql = "DELETE FROM {$this->table} WHERE {$this->where}";
                return $this->pdo->exec($sql);
            }
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Helper: Row Count
     */
    public function rowsCount($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Helper: Validate Field Uniqueness
     */
    public function validateField()
    {
        $sql = "SELECT id FROM {$this->table} WHERE {$this->field} = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->value]);
        return $stmt->rowCount() > 0;
    }

    // Proxy to PDO prepare for manual usage
    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }
}
?>