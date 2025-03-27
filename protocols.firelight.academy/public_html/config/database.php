<?php
require_once __DIR__ . '/config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $statement;
    private $error;
    
    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            if (DEBUG_MODE) {
                echo 'Connection Error: ' . $this->error;
            } else {
                error_log('Database connection error: ' . $this->error);
                echo 'A database error occurred. Please contact the administrator.';
            }
            exit;
        }
    }
    
    // Prepare statement with query
    public function query($sql) {
        $this->statement = $this->conn->prepare($sql);
        return $this;
    }
    
    // Bind values
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }
    
    // Execute the prepared statement
    public function execute() {
        try {
            return $this->statement->execute();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            if (DEBUG_MODE) {
                echo 'Query Error: ' . $this->error;
            } else {
                error_log('Database query error: ' . $this->error);
            }
            return false;
        }
    }
    
    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->statement->fetchAll();
    }
    
    // Get single record as object
    public function single() {
        $this->execute();
        return $this->statement->fetch();
    }
    
    // Get row count
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    // End transaction
    public function endTransaction() {
        return $this->conn->commit();
    }
    
    // Cancel transaction
    public function cancelTransaction() {
        return $this->conn->rollBack();
    }
}