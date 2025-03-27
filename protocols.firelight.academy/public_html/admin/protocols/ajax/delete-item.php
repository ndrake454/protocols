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

// Validate inputs
if (empty($type) || empty($id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();
    
    if ($type === 'section') {
        // Delete all items and subitems in this section
        $db->query("DELETE FROM protocol_items WHERE section_id = :section_id");
        $db->bind(':section_id', $id);
        $db->execute();
        
        // Delete the section
        $db->query("DELETE FROM protocol_sections WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
    } else if ($type === 'item') {
        // Delete all provider levels for this item
        $db->query("DELETE FROM item_provider_levels WHERE item_id = :item_id");
        $db->bind(':item_id', $id);
        $db->execute();
        
        // Delete all subitems
        $db->query("DELETE FROM protocol_items WHERE parent_id = :parent_id");
        $db->bind(':parent_id', $id);
        $db->execute();
        
        // Delete the item
        $db->query("DELETE FROM protocol_items WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
    } else if ($type === 'subitem') {
        // Delete the subitem
        $db->query("DELETE FROM protocol_items WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
    } else {
        throw new Exception('Invalid type');
    }
    
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