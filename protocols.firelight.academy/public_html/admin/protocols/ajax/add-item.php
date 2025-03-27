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
$section_id = $_POST['section_id'] ?? '';
$item_type = $_POST['item_type'] ?? '';
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$detailed_info = $_POST['detailed_info'] ?? '';
$is_decision = $_POST['is_decision'] ?? 0;
$providers = json_decode($_POST['providers'] ?? '[]', true);

// Validate inputs
if (empty($section_id) || empty($content)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize inputs
$title = sanitize_input($title);
$content = sanitize_input($content);

try {
    // Start transaction
    $db->beginTransaction();
    
    // Get the highest sort order for this section
    $db->query("SELECT MAX(sort_order) as max_sort FROM protocol_items WHERE section_id = :section_id AND parent_id IS NULL");
    $db->bind(':section_id', $section_id);
    $result = $db->single();
    $sort_order = ($result && isset($result['max_sort'])) ? $result['max_sort'] + 1 : 0;
    
    // Insert new item
    $db->query("INSERT INTO protocol_items (section_id, title, content, detailed_info, is_decision, sort_order) 
               VALUES (:section_id, :title, :content, :detailed_info, :is_decision, :sort_order)");
    $db->bind(':section_id', $section_id);
    $db->bind(':title', $title);
    $db->bind(':content', $content);
    $db->bind(':detailed_info', $detailed_info);
    $db->bind(':is_decision', $is_decision);
    $db->bind(':sort_order', $sort_order);
    
    if ($db->execute()) {
        $item_id = $db->lastInsertId();
        
        // Insert provider levels if any
        if (!empty($providers)) {
            foreach ($providers as $provider) {
                $db->query("INSERT INTO item_provider_levels (item_id, provider_id, percentage) 
                           VALUES (:item_id, :provider_id, :percentage)");
                $db->bind(':item_id', $item_id);
                $db->bind(':provider_id', $provider['provider_id']);
                $db->bind(':percentage', $provider['percentage']);
                $db->execute();
            }
        }
        
        // If this is a decision box and it's a flowchart, add yes/no paths
        if ($is_decision == 1 && $item_type == 'decision') {
            // Add Yes path
            $db->query("INSERT INTO protocol_items (section_id, content, sort_order, is_decision) 
                       VALUES (:section_id, 'Yes path action', :sort_order, 0)");
            $db->bind(':section_id', $section_id);
            $db->bind(':sort_order', $sort_order + 1);
            $db->execute();
            
            // Add No path
            $db->query("INSERT INTO protocol_items (section_id, content, sort_order, is_decision) 
                       VALUES (:section_id, 'No path action', :sort_order, 0)");
            $db->bind(':section_id', $section_id);
            $db->bind(':sort_order', $sort_order + 2);
            $db->execute();
        }
        
        $db->endTransaction();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'item_id' => $item_id]);
    } else {
        throw new Exception('Database insert failed');
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->cancelTransaction();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}