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
$section_type = $_POST['section_type'] ?? '';
$title = $_POST['title'] ?? '';

// Validate inputs
if (empty($protocol_id) || empty($section_type) || empty($title)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize inputs
$title = sanitize_input($title);

try {
    // Get the highest sort order for this protocol
    $db->query("SELECT MAX(sort_order) as max_sort FROM protocol_sections WHERE protocol_id = :protocol_id");
    $db->bind(':protocol_id', $protocol_id);
    $result = $db->single();
    $sort_order = ($result && isset($result['max_sort'])) ? $result['max_sort'] + 1 : 0;
    
    // Insert new section
    $db->query("INSERT INTO protocol_sections (protocol_id, title, section_type, sort_order) VALUES (:protocol_id, :title, :section_type, :sort_order)");
    $db->bind(':protocol_id', $protocol_id);
    $db->bind(':title', $title);
    $db->bind(':section_type', $section_type);
    $db->bind(':sort_order', $sort_order);
    
    if ($db->execute()) {
        $section_id = $db->lastInsertId();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'section_id' => $section_id]);
    } else {
        throw new Exception('Database insert failed');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}