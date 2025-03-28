<?php
/**
 * Authentication Functions
 * 
 * This file handles user authentication and session management.
 * 
 * CHAPTER 1: SESSION MANAGEMENT
 * CHAPTER 2: LOGIN FUNCTIONS
 * CHAPTER 3: ACCESS CONTROL
 */

// Include required files
require_once 'config.php';
require_once 'db.php';

// ========================================
// CHAPTER 1: SESSION MANAGEMENT
// ========================================

/**
 * 1.1: Start the session
 * 
 * @return void
 */
function start_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * 1.2: Regenerate session ID
 * 
 * @param bool $delete_old_session Whether to delete the old session
 * @return bool True on success, false on failure
 */
function regenerate_session($delete_old_session = true) {
    return session_regenerate_id($delete_old_session);
}

/**
 * 1.3: End the session
 * 
 * @return void
 */
function end_session() {
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

// ========================================
// CHAPTER 2: LOGIN FUNCTIONS
// ========================================

/**
 * 2.1: Authenticate a user
 * 
 * @param string $username The username
 * @param string $password The password
 * @return bool True if authenticated, false otherwise
 */
function authenticate_user($username, $password) {
    $user = db_get_row(
        "SELECT * FROM admin WHERE username = ?",
        [$username],
        's'
    );
    
    if (!$user) {
        return false;
    }
    
    if (password_verify($password, $user['password_hash'])) {
        // Update last login time
        db_update(
            'admin',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        // Set session variables
        start_session();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_time'] = time();
        
        regenerate_session();
        
        return true;
    }
    
    return false;
}

/**
 * 2.2: Log out a user
 * 
 * @return void
 */
function logout_user() {
    end_session();
}

/**
 * 2.3: Change a user's password
 * 
 * @param int $user_id The user ID
 * @param string $current_password The current password
 * @param string $new_password The new password
 * @return bool True on success, false on failure
 */
function change_password($user_id, $current_password, $new_password) {
    $user = db_get_row(
        "SELECT * FROM admin WHERE id = ?",
        [$user_id],
        'i'
    );
    
    if (!$user) {
        return false;
    }
    
    if (password_verify($current_password, $user['password_hash'])) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        return db_update(
            'admin',
            ['password_hash' => $password_hash],
            'id = ?',
            [$user_id]
        );
    }
    
    return false;
}

// ========================================
// CHAPTER 3: ACCESS CONTROL
// ========================================

/**
 * 3.1: Check if a user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    start_session();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    // Check if session has expired
    if ($_SESSION['login_time'] + SESSION_LIFETIME < time()) {
        logout_user();
        return false;
    }
    
    return true;
}

/**
 * 3.2: Require admin login
 * 
 * Redirects to login page if not logged in
 * 
 * @return void
 */
function require_admin() {
    if (!is_logged_in()) {
        $current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $redirect_url = admin_url('login.php');
        
        if ($current_url && !strpos($current_url, 'login.php')) {
            $redirect_url .= '?redirect=' . urlencode($current_url);
        }
        
        redirect($redirect_url);
    }
}

/**
 * 3.3: Get the current user ID
 * 
 * @return int|null The user ID or null if not logged in
 */
function get_current_user_id() {
    start_session();
    
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * 3.4: Get the current username
 * 
 * @return string|null The username or null if not logged in
 */
function get_current_username() {
    start_session();
    
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}