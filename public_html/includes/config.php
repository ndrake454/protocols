<?php
/**
 * Configuration File
 * 
 * This file contains all the configuration settings for the application.
 * Edit these values to match your environment.
 * 
 * CHAPTER 1: DATABASE CONFIGURATION
 * CHAPTER 2: APPLICATION SETTINGS
 * CHAPTER 3: SECURITY SETTINGS
 */

// ========================================
// CHAPTER 1: DATABASE CONFIGURATION
// ========================================

// 1.1: Database connection details
define('DB_HOST', 'localhost');      // Database host
define('DB_NAME', '-');   // Database name
define('DB_USER', '-');    // Database username
define('DB_PASS', '-');    // Database password

// ========================================
// CHAPTER 2: APPLICATION SETTINGS
// ========================================

// 2.1: Application paths
define('BASE_URL', 'https://protocols.firelight.academy'); // No trailing slash
define('SITE_NAME', 'EMS Protocols');
define('ADMIN_EMAIL', 'admin@example.com');

// 2.2: File paths (relative to document root)
define('INCLUDES_PATH', 'includes/');
define('TEMPLATES_PATH', 'templates/');
define('ASSETS_PATH', 'assets/');
define('ADMIN_PATH', 'admin/');

// ========================================
// CHAPTER 3: SECURITY SETTINGS
// ========================================

// 3.1: Session configuration
define('SESSION_NAME', 'protocols_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// 3.2: Security keys
define('AUTH_KEY', 'replace_with_random_string'); // Use a random string generator to create this
define('SECURE_AUTH_KEY', 'replace_with_random_string');

// 3.3: Error reporting (set to 0 in production)
define('DEBUG_MODE', 1); // 1 for development, 0 for production

// Set error reporting based on DEBUG_MODE
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ========================================
// CHAPTER 4: TIME ZONE SETTINGS
// ========================================

// 4.1: Set the default timezone
date_default_timezone_set('America/Denver'); // Change to your timezone