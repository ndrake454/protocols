<?php
/**
 * Helper Functions
 * 
 * This file contains general helper functions used throughout the application.
 * 
 * CHAPTER 1: URL FUNCTIONS
 * CHAPTER 2: FORMATTING FUNCTIONS
 * CHAPTER 3: PROTOCOL FUNCTIONS
 * CHAPTER 4: SECURITY FUNCTIONS
 */

// Include required files
require_once 'config.php';
require_once 'db.php';

// ========================================
// CHAPTER 1: URL FUNCTIONS
// ========================================

/**
 * 1.1: Get the site URL
 * 
 * @param string $path The path to append to the base URL
 * @return string The complete URL
 */
function site_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * 1.2: Get the admin URL
 * 
 * @param string $path The path to append to the admin URL
 * @return string The complete admin URL
 */
function admin_url($path = '') {
    return site_url(ADMIN_PATH . ltrim($path, '/'));
}

/**
 * 1.3: Get the assets URL
 * 
 * @param string $path The path to append to the assets URL
 * @return string The complete assets URL
 */
function assets_url($path = '') {
    return site_url(ASSETS_PATH . ltrim($path, '/'));
}

/**
 * 1.4: Redirect to a URL
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// ========================================
// CHAPTER 2: FORMATTING FUNCTIONS
// ========================================

/**
 * 2.1: Format a date
 * 
 * @param string $date The date to format
 * @param string $format The format to use
 * @return string The formatted date
 */
function format_date($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * 2.2: Format a protocol number
 * 
 * @param string $prefix The category prefix
 * @param int $number The protocol number
 * @return string The formatted protocol number
 */
function format_protocol_number($prefix, $number) {
    return $prefix . '.' . str_pad($number, 2, '0', STR_PAD_LEFT);
}

/**
 * 2.3: Format text for display
 * 
 * @param string $text The text to format
 * @return string The formatted text
 */
function format_text($text) {
    return nl2br(db_escape_html($text));
}

/**
 * 2.4: Generate a slug from a string
 * 
 * @param string $string The string to convert
 * @return string The slug
 */
function generate_slug($string) {
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    $string = trim(strtolower($string));
    $string = preg_replace('/\s+/', '-', $string);
    return $string;
}

// ========================================
// CHAPTER 3: PROTOCOL FUNCTIONS
// ========================================

/**
 * 3.1: Get all protocol categories
 * 
 * @return array Array of categories
 */
function get_categories() {
    return db_get_results("SELECT * FROM categories ORDER BY `order` ASC");
}

/**
 * 3.2: Get a specific category
 * 
 * @param int $id The category ID
 * @return array|null The category or null if not found
 */
function get_category($id) {
    return db_get_row("SELECT * FROM categories WHERE id = ?", [$id], 'i');
}

/**
 * 3.3: Get protocols in a category
 * 
 * @param int $category_id The category ID
 * @return array Array of protocols
 */
function get_protocols_by_category($category_id) {
    return db_get_results(
        "SELECT p.*, c.prefix 
         FROM protocols p 
         JOIN categories c ON p.category_id = c.id 
         WHERE p.category_id = ? 
         ORDER BY p.title ASC",
        [$category_id],
        'i'
    );
}

/**
 * 3.4: Get a specific protocol
 * 
 * @param int $id The protocol ID
 * @return array|null The protocol or null if not found
 */
function get_protocol($id) {
    return db_get_row(
        "SELECT p.*, c.name as category_name, c.prefix 
         FROM protocols p 
         JOIN categories c ON p.category_id = c.id 
         WHERE p.id = ?",
        [$id],
        'i'
    );
}

/**
 * 3.5: Get sections for a protocol
 * 
 * @param int $protocol_id The protocol ID
 * @return array Array of sections
 */
function get_protocol_sections($protocol_id) {
    return db_get_results(
        "SELECT * FROM sections 
         WHERE protocol_id = ? 
         ORDER BY `order` ASC",
        [$protocol_id],
        'i'
    );
}

/**
 * 3.6: Get blocks for a section
 * 
 * @param int $section_id The section ID
 * @return array Array of blocks
 */
function get_section_blocks($section_id) {
    return db_get_results(
        "SELECT * FROM blocks 
         WHERE section_id = ? 
         ORDER BY `order` ASC",
        [$section_id],
        'i'
    );
}

/**
 * 3.7: Get provider levels for a block
 * 
 * @param int $block_id The block ID
 * @return array Array of provider levels
 */
function get_block_provider_levels($block_id) {
    return db_get_results(
        "SELECT pl.* 
         FROM provider_levels pl 
         JOIN provider_access pa ON pl.id = pa.provider_level_id 
         WHERE pa.block_id = ? 
         ORDER BY pl.`order` ASC",
        [$block_id],
        'i'
    );
}

/**
 * 3.8: Get all provider levels
 * 
 * @return array Array of provider levels
 */
function get_provider_levels() {
    return db_get_results("SELECT * FROM provider_levels ORDER BY `order` ASC");
}

// ========================================
// CHAPTER 4: SECURITY FUNCTIONS
// ========================================

/**
 * 4.1: Generate a random token
 * 
 * @param int $length The length of the token
 * @return string The generated token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 4.2: Verify CSRF token
 * 
 * @param string $token The token to verify
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 4.3: Get CSRF token
 * 
 * @return string The CSRF token
 */
function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_token();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * 4.4: CSRF token field
 * 
 * @return string HTML for a CSRF token field
 */
function csrf_token_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * 4.5: Sanitize input
 * 
 * @param string $input The input to sanitize
 * @return string The sanitized input
 */
function sanitize_input($input) {
    return trim(strip_tags($input));
}