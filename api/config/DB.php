<?php
//date_default_timezone_set('UTC');
class DB extends PDO
{

    private $DB_TYPE = "mysql";
    private $DB_HOST = "localhost";
    private $DB_NAME = "cloud-doctor";
    private $DB_USER = "root";
    private $DB_PASS = "";


    public function __construct()
    {

        parent::__construct($this->DB_TYPE . ':host=' . $this->DB_HOST . ';dbname=' . $this->DB_NAME . ';charset=utf8', $this->DB_USER, $this->DB_PASS);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

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
            return $e;
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
                foreach ($this->where as $key => $value) {
                    $where .= "`$key`=:$key AND ";
                }
                $where = implode('AND', array_slice(explode('AND', $where), 0, -1));
                //$where = rtrim($where, 'AND');

                $sth = $this->prepare("DELETE FROM $this->table WHERE $where");

                foreach ($this->where as $key => $value) {
                    $sth->bindValue(":$key", $value);
                }
            }

            $deleted = $sth->execute();

            if ($deleted)
                return $deleted;
            else
                return 0;

        } catch (\Throwable $th) {
            return $th;
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
                echo json_encode($this->errorInfo());
            throw new Exception("Error: " . implode(',', $this->errorInfo()));
        }
    }

    public function dateformat($format = "Y-m-d H:i:s")
    {
        return date($format);
    }

    public function checkTable($table)
    {
        $query = "SELECT table_name FROM information_schema.tables WHERE table_type = 'base table' AND table_schema='$this->DB_NAME'";
        $sth = $this->prepare($query);

        if (!$sth->execute()) {
            $this->handleError();
        } else {
            $data = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (in_array(array("table_name" => trim($table)), $data))
                $check = 1;
            else
                $check = 0;
        }
        return $check;
    }

    public function checkColumn($table)
    {
        $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$this->DB_NAME' AND TABLE_NAME = 'users'";
        $sth = $this->prepare($query);

        if (!$sth->execute()) {
            $this->handleError();
        } else {
            $data = $sth->fetchAll(PDO::FETCH_ASSOC);
            $check = 1;
            foreach ($table as $col) {
                if (!in_array(array("COLUMN_NAME" => trim($col)), $data)) {
                    $check = 0;
                    break;
                }
            }
        }
        return $check;
    }


}
