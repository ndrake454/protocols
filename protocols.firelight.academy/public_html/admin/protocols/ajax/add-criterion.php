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
$parent_item_id = $_POST['parent_item_id'] ?? '';
$content = $_POST['content'] ?? '';
$detailed_info = $_POST['detailed_info'] ?? '';

// Validate inputs
if (empty($parent_item_id) || empty($content)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize inputs
$content = sanitize_input($content);

try {
    // Get parent item details to get the section_id
    $db->query("SELECT section_id FROM protocol_items WHERE id = :id");
    $db->bind(':id', $parent_item_id);
    $parent_item = $db->single();
    
    if (!$parent_item) {
        throw new Exception('Parent item not found');
    }
    
    // Get the highest sort order for subitems under this parent
    $db->query("SELECT MAX(sort_order) as max_sort FROM protocol_items WHERE parent_id = :parent_id");
    $db->bind(':parent_id', $parent_item_id);
    $result = $db->single();
    $sort_order = ($result && isset($result['max_sort'])) ? $result['max_sort'] + 1 : 0;
    
    // Insert new criterion (subitem)
    $db->query("INSERT INTO protocol_items (section_id, parent_id, content, detailed_info, sort_order) 
               VALUES (:section_id, :parent_id, :content, :detailed_info, :sort_order)");
    $db->bind(':section_id', $parent_item['section_id']);
    $db->bind(':parent_id', $parent_item_id);
    $db->bind(':content', $content);
    $db->bind(':detailed_info', $detailed_info);
    $db->bind(':sort_order', $sort_order);
    
    if ($db->execute()) {
        $criterion_id = $db->lastInsertId();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'criterion_id' => $criterion_id]);
    } else {
        throw new Exception('Database insert failed');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}