<?php
require_once '../../../config/config.php';
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';

// Initialize database connection
$db = new Database();

// Start session
session_start([
    'name' => SESSION_NAME,
    'cookie_lifetime' => SESSION_LIFETIME
]);

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !has_permission('editor')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';
$direction = $_POST['direction'] ?? '';

// Validate inputs
if (empty($type) || empty($id) || empty($direction)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Determine which table to update based on type
    $table = '';
    $sort_column = 'sort_order';
    $parent_column = null;
    
    if ($type === 'section') {
        $table = 'protocol_sections';
    } else if ($type === 'item') {
        $table = 'protocol_items';
        $parent_column = 'section_id';
    } else if ($type === 'subitem') {
        $table = 'protocol_items';
        $parent_column = 'parent_id';
    } else {
        throw new Exception('Invalid type');
    }
    
    // Get current item's info
    $db->query("SELECT * FROM {$table} WHERE id = :id");
    $db->bind(':id', $id);
    $item = $db->single();
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    // Get neighboring item based on direction
    if ($direction === 'up') {
        $operator = '<';
        $order = 'DESC';
    } else {
        $operator = '>';
        $order = 'ASC';
    }
    
    if ($parent_column) {
        $db->query("SELECT id, {$sort_column} FROM {$table} 
                   WHERE {$parent_column} = :parent_id 
                   AND {$sort_column} {$operator} :sort_order 
                   ORDER BY {$sort_column} {$order} 
                   LIMIT 1");
        $db->bind(':parent_id', $item[$parent_column]);
    } else {
        $db->query("SELECT id, {$sort_column} FROM {$table} 
                   WHERE {$sort_column} {$operator} :sort_order 
                   ORDER BY {$sort_column} {$order} 
                   LIMIT 1");
    }
    
    $db->bind(':sort_order', $item[$sort_column]);
    $neighbor = $db->single();
    
    if (!$neighbor) {
        // No neighbor in that direction
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Swap sort orders
    $db->beginTransaction();
    
    // Update current item
    $db->query("UPDATE {$table} SET {$sort_column} = :sort_order WHERE id = :id");
    $db->bind(':sort_order', $neighbor[$sort_column]);
    $db->bind(':id', $item['id']);
    $db->execute();
    
    // Update neighbor
    $db->query("UPDATE {$table} SET {$sort_column} = :sort_order WHERE id = :id");
    $db->bind(':sort_order', $item[$sort_column]);
    $db->bind(':id', $neighbor['id']);
    $db->execute();
    
    $db->endTransaction();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->cancelTransaction();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}