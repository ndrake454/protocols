<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', '-'); // Change this to your actual database user
define('DB_PASS', '-'); // Change this to your secure password
define('DB_NAME', '-');

// Application paths
define('BASE_PATH', dirname(__DIR__));
define('PROTOCOL_PATH', BASE_PATH . '/protocols');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('ASSETS_PATH', BASE_PATH . '/assets');

// URL configuration
define('BASE_URL', 'https://protocols.firelight.academy'); // Change to your actual domain
define('PROTOCOL_URL', BASE_URL . '/protocols');
define('ADMIN_URL', BASE_URL . '/admin');
define('ASSETS_URL', BASE_URL . '/assets');

// Session configuration
define('SESSION_NAME', 'ems_protocols_session');
define('SESSION_LIFETIME', 7200); // 2 hours in seconds

// Security
define('ENCRYPTION_KEY', 'your_random_encryption_key'); // Generate a random key for production

// Debug mode (set to false in production)
define('DEBUG_MODE', false);

// Protocol settings
define('ITEMS_PER_PAGE', 20);
define('DEFAULT_PROTOCOL_IMAGE', ASSETS_URL . '/images/default-protocol.jpg');

// Time zone
date_default_timezone_set('America/Denver'); // Adjust for your timezone