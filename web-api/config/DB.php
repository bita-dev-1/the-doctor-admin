<?php
//date_default_timezone_set('UTC');

class DB extends PDO
{

    private $DB_TYPE;
    private $DB_HOST;
    private $DB_NAME;
    private $DB_USER;
    private $DB_PASS;

    // Helper properties for query building
    public $table;
    public $data;
    public $where;
    public $column;
    public $value;
    public $field;
    public $multi = false;
    public $_errorLog = false;

    public function __construct()
    {

        // Load credentials from Environment Variables (.env)
        // Fallback values are kept just in case .env is missing during migration
        $this->DB_TYPE = $_ENV['DB_CONNECTION'] ?? 'mysql';
        $this->DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
        $this->DB_NAME = $_ENV['DB_NAME'] ?? 'cloud-doctor1';
        $this->DB_USER = $_ENV['DB_USERNAME'] ?? 'root';
        $this->DB_PASS = $_ENV['DB_PASSWORD'] ?? '';

        try {
            parent::__construct($this->DB_TYPE . ':host=' . $this->DB_HOST . ';dbname=' . $this->DB_NAME . ';charset=utf8', $this->DB_USER, $this->DB_PASS);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            // Prevent leaking password in stack trace
            die("Database Connection Error: " . $e->getMessage());
        }

    }

    public function select($sql, $fetchMode = PDO::FETCH_ASSOC)
    {

        $sth = $this->prepare($sql);

        if (!$sth->execute())
            $this->handleError();
        else
            return $sth->fetchAll($fetchMode);
    }

    public function rowsCount($sql)
    {

        $sth = $this->prepare($sql);
        $sth->execute();
        return $sth->rowCount();
    }

    public function insert()
    {
        try {

            ksort($this->data);

            if (!isset($this->multi) || $this->multi === false) {
                $fieldNames = implode('`, `', array_keys($this->data));
                $fieldValues = ':' . implode(', :', array_keys($this->data));

                $sth = $this->prepare("INSERT INTO $this->table (`$fieldNames`) VALUES ($fieldValues)");

                foreach ($this->data as $key => $value) {
                    $sth->bindValue(":$key", $value);
                }

                $resul = $sth->execute();
            } else {
                //$this->beginTransaction(); // also helps speed up your inserts.
                $data = $this->data;
                if (count($data)) {
                    $fieldNames = implode('`, `', array_keys((array) $data[0]));

                    $fieldValues = array();
                    $fieldBinding = array(); // Initialize array

                    foreach ($data as $object) {
                        $object = is_array($object) ? $object : (array) $object;
                        $fieldBinding[] = '(' . $this->bindingValues(sizeof($object)) . ' )';
                        $fieldValues = array_merge($fieldValues, array_values($object));
                    }
                    //$sql = rtrim($sql, ',');
                }

                $sql = "INSERT INTO $this->table (`$fieldNames`) VALUES " . implode(',', $fieldBinding);

                $sth = $this->prepare($sql);

                $resul = $sth->execute($fieldValues);

                //$this->commit();
            }

            if ($resul) {
                $resul = $this->lastInsertId() ? $this->lastInsertId() : $resul;
                return $resul;
            } else
                return 0;

        } catch (Exception $e) {
            //$this->rollback();
            // Return 0 or log error instead of returning Exception object to frontend
            error_log($e->getMessage());
            return 0;
        }

    }

    public function bindingValues($count = 0, $text = '?', $separator = ",")
    {
        $result = array();
        if ($count > 0) {
            for ($x = 0; $x < $count; $x++) {
                $result[] = $text;
            }
        }

        return implode($separator, $result);
    }

    public function validateField()
    {
        $count = 0;

        $sth = $this->prepare("SELECT * FROM  $this->table WHERE $this->field = :$this->field");
        $sth->bindValue(":$this->field", $this->value);
        $sth->execute();
        $count = $sth->rowCount();

        return $count;
    }

    public function update()
    {

        $fieldDetails = NULL;
        foreach ($this->data as $key => $value) {
            $fieldDetails .= "`$key`=:$key,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');
        $sth = $this->prepare("UPDATE $this->table SET $fieldDetails WHERE $this->where");


        foreach ($this->data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }

        return $sth->execute();
    }

    public function Delete()
    {

        try {
            if (!isset($this->where)) {

                $sth = $this->prepare("DELETE FROM $this->table WHERE $this->column =:$this->column");
                $sth->bindValue(":$this->column", $this->value, PDO::PARAM_INT);

            } else {
                $where = NULL;
                if (is_array($this->where)) {
                    foreach ($this->where as $key => $value) {
                        $where .= "`$key`=:$key AND ";
                    }
                    $where = implode('AND', array_slice(explode('AND', $where), 0, -1));

                    $sth = $this->prepare("DELETE FROM $this->table WHERE $where");

                    foreach ($this->where as $key => $value) {
                        $sth->bindValue(":$key", $value);
                    }
                } else {
                    // Fallback for string where clause (Legacy support)
                    $sth = $this->prepare("DELETE FROM $this->table WHERE $this->where");
                }
            }

            $deleted = $sth->execute();

            if ($deleted)
                return $deleted;
            else
                return 0;

        } catch (\Throwable $th) {
            error_log($th->getMessage());
            return 0;
        }

    }

    public function execSql($sql)
    {
        $sth = $this->prepare($sql);
        return $sth->execute();
    }

    private function handleError()
    {
        if ($this->errorCode() != '00000') {
            if ($this->_errorLog == true)
                error_log(json_encode($this->errorInfo())); // Log instead of echo
            throw new Exception("Database Error"); // Generic message for security
        }
    }

    public function dateformat($format = "Y-m-d H:i:s")
    {
        return date($format);
    }

    public function checkTable($table)
    {
        // Use prepared statement for table name check to be safer
        $query = "SELECT table_name FROM information_schema.tables WHERE table_type = 'base table' AND table_schema = :dbname AND table_name = :tablename";
        $sth = $this->prepare($query);
        $sth->bindValue(':dbname', $this->DB_NAME);
        $sth->bindValue(':tablename', trim($table));

        if (!$sth->execute()) {
            $this->handleError();
            return 0;
        } else {
            return $sth->rowCount() > 0 ? 1 : 0;
        }
    }

    public function checkColumn($table)
    {
        // This function logic seems specific to 'users' table in original code, kept logic but secured it.
        $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = 'users'";
        $sth = $this->prepare($query);
        $sth->bindValue(':dbname', $this->DB_NAME);

        if (!$sth->execute()) {
            $this->handleError();
            return 0;
        } else {
            $data = $sth->fetchAll(PDO::FETCH_COLUMN); // Fetch simple array
            $check = 1;
            foreach ($table as $col) {
                if (!in_array(trim($col), $data)) {
                    $check = 0;
                    break;
                }
            }
        }
        return $check;
    }
}
?>