<?php
require_once __DIR__ . '/../config/config.php';

// Clean user input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Format date for display
function format_date($date, $format = 'M j, Y') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

// Generate URL-friendly slug
function create_slug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check user role permissions
function has_permission($required_role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $roles = ['viewer' => 1, 'editor' => 2, 'admin' => 3];
    $user_role_level = $roles[$_SESSION['user_role']] ?? 0;
    $required_role_level = $roles[$required_role] ?? 999;
    
    return $user_role_level >= $required_role_level;
}

// Handle page redirects
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Display error or success message
function flash_message($message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    } else if (isset($_SESSION['flash_message'])) {
        $class = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return "<div class='alert alert-{$class}'>{$message}</div>";
    }
    
    return '';
}

// Get protocol by ID or number
function get_protocol($id_or_number) {
    global $db;
    
    if (is_numeric($id_or_number)) {
        $db->query("SELECT * FROM protocols WHERE id = :id AND is_published = 1");
        $db->bind(':id', $id_or_number);
    } else {
        $db->query("SELECT * FROM protocols WHERE protocol_number = :number AND is_published = 1");
        $db->bind(':number', $id_or_number);
    }
    
    return $db->single();
}

// Get protocol sections
function get_protocol_sections($protocol_id) {
    global $db;
    
    $db->query("SELECT * FROM protocol_sections WHERE protocol_id = :protocol_id ORDER BY sort_order");
    $db->bind(':protocol_id', $protocol_id);
    
    return $db->resultSet();
}

// Get protocol section items
function get_section_items($section_id, $parent_id = null) {
    global $db;
    
    if ($parent_id === null) {
        $db->query("SELECT * FROM protocol_items WHERE section_id = :section_id AND parent_id IS NULL ORDER BY sort_order");
        $db->bind(':section_id', $section_id);
    } else {
        $db->query("SELECT * FROM protocol_items WHERE section_id = :section_id AND parent_id = :parent_id ORDER BY sort_order");
        $db->bind(':section_id', $section_id);
        $db->bind(':parent_id', $parent_id);
    }
    
    return $db->resultSet();
}

// Get item provider levels
function get_item_providers($item_id) {
    global $db;
    
    $db->query("SELECT p.*, ipl.percentage FROM provider_levels p 
                JOIN item_provider_levels ipl ON p.id = ipl.provider_id 
                WHERE ipl.item_id = :item_id
                ORDER BY p.sort_order");
    $db->bind(':item_id', $item_id);
    
    return $db->resultSet();
}

// Continue with the provider bar code you referenced:
function render_provider_bar($providers) {
    echo '<div class="provider-bar">';
    foreach ($providers as $provider) {
        $width = isset($provider['percentage']) ? $provider['percentage'] : 100 / count($providers);
        $provider_class = strtolower($provider['shortname']);
        echo '<div class="provider-segment ' . $provider_class . '" style="width: ' . $width . '%"></div>';
    }
    echo '</div>';
}