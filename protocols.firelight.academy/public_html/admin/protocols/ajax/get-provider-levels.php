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

// Get item ID from URL
$item_id = filter_input(INPUT_GET, 'item_id', FILTER_SANITIZE_NUMBER_INT);

// Validate inputs
if (empty($item_id)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    // Get provider levels for this item
    $db->query("SELECT provider_id, percentage FROM item_provider_levels WHERE item_id = :item_id");
    $db->bind(':item_id', $item_id);
    $provider_levels = $db->resultSet();
    
    header('Content-Type: application/json');
    echo json_encode($provider_levels);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([]);
}