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
$protocol_id = $_POST['protocol_id'] ?? '';
$protocol_number = $_POST['protocol_number'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$is_published = $_POST['is_published'] ?? 0;
$description = $_POST['description'] ?? '';

// Validate inputs
if (empty($protocol_id) || empty($protocol_number) || empty($category_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize inputs
$protocol_number = sanitize_input($protocol_number);
$description = sanitize_input($description);

try {
    // Update protocol
    $db->query("UPDATE protocols SET protocol_number = :protocol_number, category_id = :category_id, 
               description = :description, is_published = :is_published, updated_by = :updated_by, 
               last_updated = NOW() WHERE id = :id");
    $db->bind(':protocol_number', $protocol_number);
    $db->bind(':category_id', $category_id);
    $db->bind(':description', $description);
    $db->bind(':is_published', $is_published);
    $db->bind(':updated_by', $_SESSION['user_id']);
    $db->bind(':id', $protocol_id);
    
    if ($db->execute()) {
        // Save revision record
        $revision_data = json_encode([
            'protocol_number' => $protocol_number,
            'category_id' => $category_id,
            'description' => $description,
            'is_published' => $is_published
        ]);
        
        $db->query("INSERT INTO protocol_revisions (protocol_id, user_id, revision_data, revision_notes) 
                   VALUES (:protocol_id, :user_id, :revision_data, :revision_notes)");
        $db->bind(':protocol_id', $protocol_id);
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':revision_data', $revision_data);
        $db->bind(':revision_notes', 'Updated via WYSIWYG editor');
        $db->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database update failed');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}