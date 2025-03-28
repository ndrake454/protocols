<?php
/**
 * Admin Change Password
 * 
 * This page handles changing the admin password.
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_admin();

// Initialize variables
$error = '';
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Check if fields are empty
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } 
        // Check if new passwords match
        elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        }
        // Check password length
        elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        }
        else {
            // Attempt to change password
            $user_id = get_current_user_id();
            if (change_password($user_id, $current_password, $new_password)) {
                // Set success message in session
                start_session();
                $_SESSION['message'] = 'Password changed successfully!';
                $_SESSION['message_type'] = 'success';
                
                // Redirect to dashboard
                redirect(admin_url());
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

// If there's an error, set it in session and redirect back
if (!empty($error)) {
    start_session();
    $_SESSION['message'] = $error;
    $_SESSION['message_type'] = 'danger';
    redirect(admin_url());
}