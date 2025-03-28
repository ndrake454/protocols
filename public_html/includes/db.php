<?php
/**
 * Database Connection
 * 
 * This file handles the database connection and provides helper functions
 * for database operations.
 * 
 * CHAPTER 1: DATABASE CONNECTION
 * CHAPTER 2: QUERY FUNCTIONS
 * CHAPTER 3: HELPER FUNCTIONS
 */

// Include configuration if not already included
if (!defined('DB_HOST')) {
    require_once 'config.php';
}

// ========================================
// CHAPTER 1: DATABASE CONNECTION
// ========================================

/**
 * 1.1: Connect to the database
 * 
 * @return mysqli The database connection object
 */
function db_connect() {
    static $conn;
    
    if ($conn === NULL) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log('Database connection failed: ' . $conn->connect_error);
            die('Database connection failed. Please contact the administrator.');
        }
        
        $conn->set_charset('utf8mb4');
    }
    
    return $conn;
}

// ========================================
// CHAPTER 2: QUERY FUNCTIONS
// ========================================

/**
 * 2.1: Execute a query and return the result
 * 
 * @param string $sql The SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters (i=integer, s=string, d=double, b=blob)
 * @return mysqli_result|bool The result of the query
 */
function db_query($sql, $params = [], $types = '') {
    $conn = db_connect();
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log('Query preparation failed: ' . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        // Generate types string if not provided
        if (empty($types)) {
            $types = str_repeat('s', count($params));
        }
        
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        error_log('Query execution failed: ' . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

/**
 * 2.2: Get a single row from the database
 * 
 * @param string $sql The SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return array|null The row as an associative array or null if not found
 */
function db_get_row($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);
    
    if (!$result) {
        return null;
    }
    
    $row = $result->fetch_assoc();
    $result->free();
    
    return $row;
}

/**
 * 2.3: Get multiple rows from the database
 * 
 * @param string $sql The SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return array An array of rows as associative arrays
 */
function db_get_results($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);
    
    if (!$result) {
        return [];
    }
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $result->free();
    
    return $rows;
}

/**
 * 2.4: Insert a row into the database
 * 
 * @param string $table The table to insert into
 * @param array $data Associative array of column => value pairs
 * @return int|bool The inserted ID or false on failure
 */
function db_insert($table, $data) {
    $conn = db_connect();
    
    // Add backticks around column names to handle reserved words
    $columns = implode(', ', array_map(function($col) {
        return "`$col`";
    }, array_keys($data)));
    
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
    
    $params = array_values($data);
    
    // Determine types
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } elseif (is_string($param)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    $result = db_query($sql, $params, $types);
    
    if (!$result) {
        return false;
    }
    
    return $conn->insert_id;
}

/**
 * 2.5: Update a row in the database
 * 
 * @param string $table The table to update
 * @param array $data Associative array of column => value pairs
 * @param string $where The WHERE clause
 * @param array $where_params Parameters for the WHERE clause
 * @return bool True on success, false on failure
 */
function db_update($table, $data, $where, $where_params = []) {
    $conn = db_connect();
    
    $set = [];
    foreach (array_keys($data) as $column) {
        // Escape the column name with backticks to handle reserved words
        $set[] = "`$column` = ?";
    }
    
    $set_clause = implode(', ', $set);
    
    $sql = "UPDATE `$table` SET $set_clause WHERE $where";
    
    $params = array_merge(array_values($data), $where_params);
    
    // Determine types
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } elseif (is_string($param)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    $result = db_query($sql, $params, $types);
    
    if (!$result) {
        return false;
    }
    
    return true;
}

/**
 * 2.6: Delete a row from the database
 * 
 * @param string $table The table to delete from
 * @param string $where The WHERE clause
 * @param array $params Parameters for the WHERE clause
 * @return bool True on success, false on failure
 */
function db_delete($table, $where, $params = []) {
    $sql = "DELETE FROM `$table` WHERE $where";
    
    $result = db_query($sql, $params);
    
    if (!$result) {
        return false;
    }
    
    return true;
}

// ========================================
// CHAPTER 3: HELPER FUNCTIONS
// ========================================

/**
 * 3.1: Escape a string for safe output in HTML
 * 
 * @param string $string The string to escape
 * @return string The escaped string
 */
function db_escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 3.2: Get the last error message
 * 
 * @return string The last error message
 */
function db_error() {
    $conn = db_connect();
    return $conn->error;
}

/**
 * 3.3: Begin a transaction
 * 
 * @return bool True on success, false on failure
 */
function db_begin_transaction() {
    $conn = db_connect();
    return $conn->begin_transaction();
}

/**
 * 3.4: Commit a transaction
 * 
 * @return bool True on success, false on failure
 */
function db_commit() {
    $conn = db_connect();
    return $conn->commit();
}

/**
 * 3.5: Rollback a transaction
 * 
 * @return bool True on success, false on failure
 */
function db_rollback() {
    $conn = db_connect();
    return $conn->rollback();
}