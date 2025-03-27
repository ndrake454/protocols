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
$item_id = $_POST['item_id'] ?? '';
$providers = json_decode($_POST['providers'] ?? '[]', true);

// Validate inputs
if (empty($item_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing item ID']);
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Delete existing provider levels for this item
    $db->query("DELETE FROM item_provider_levels WHERE item_id = :item_id");
    $db->bind(':item_id', $item_id);
    $db->execute();
    
    // Insert new provider levels
    if (!empty($providers)) {
        foreach ($providers as $provider) {
            $db->query("INSERT INTO item_provider_levels (item_id, provider_id, percentage) VALUES (:item_id, :provider_id, :percentage)");
            $db->bind(':item_id', $item_id);
            $db->bind(':provider_id', $provider['provider_id']);
            $db->bind(':percentage', $provider['percentage']);
            $db->execute();
        }
    }
    
    // Commit transaction
    $db->endTransaction();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->cancelTransaction();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}