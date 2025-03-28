<?php
/**
 * Admin Logout
 * 
 * This page handles admin logout.
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Log out the user
logout_user();

// Redirect to login page
redirect(admin_url('login.php'));