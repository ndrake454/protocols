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
$id_type = $_POST['id_type'] ?? '';
$id_value = $_POST['id_value'] ?? '';
$content = $_POST['content'] ?? '';

// Validate inputs
if (empty($id_type) || empty($id_value)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Determine which table to update based on id_type
    $table = 'protocol_items';
    $id_column = 'id';
    
    if ($id_type === 'item_id' || $id_type === 'subitem_id') {
        // Update detailed info
        $db->query("UPDATE {$table} SET detailed_info = :content, updated_at = NOW() WHERE {$id_column} = :id");
        $db->bind(':content', $content);
        $db->bind(':id', $id_value);
        
        if ($db->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Database update failed');
        }
    } else {
        throw new Exception('Invalid ID type');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}