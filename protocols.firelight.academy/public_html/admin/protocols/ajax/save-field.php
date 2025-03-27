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
$field_type = $_POST['field_type'] ?? '';
$field_name = $_POST['field_name'] ?? '';
$id_type = $_POST['id_type'] ?? '';
$id_value = $_POST['id_value'] ?? '';
$content = $_POST['content'] ?? '';

// Validate inputs
if (empty($field_name) || empty($id_type) || empty($id_value)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize content
$content = sanitize_input($content);

try {
    // Determine which table to update based on id_type
    $table = '';
    $id_column = '';
    $valid_fields = [];
    
    if ($id_type === 'id') {
        $table = 'protocols';
        $id_column = 'id';
        $valid_fields = ['title', 'protocol_number', 'description', 'category_id', 'is_published'];
    } else if ($id_type === 'section_id') {
        $table = 'protocol_sections';
        $id_column = 'id';
        $valid_fields = ['title', 'description'];
    } else if ($id_type === 'item_id') {
        $table = 'protocol_items';
        $id_column = 'id';
        $valid_fields = ['title', 'content'];
    } else if ($id_type === 'subitem_id') {
        $table = 'protocol_items';
        $id_column = 'id';
        $valid_fields = ['content'];
    } else {
        throw new Exception('Invalid ID type');
    }
    
    // Check if field name is valid
    if (!in_array($field_name, $valid_fields)) {
        throw new Exception('Invalid field name');
    }
    
    // Update the field
    $db->query("UPDATE {$table} SET {$field_name} = :content, updated_at = NOW() WHERE {$id_column} = :id");
    $db->bind(':content', $content);
    $db->bind(':id', $id_value);
    
    if ($db->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database update failed');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}