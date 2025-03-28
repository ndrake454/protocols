<?php
/**
 * Admin Delete Provider Level
 * 
 * This page deletes a provider level and removes its access from all blocks.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get provider level ID from URL
$provider_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no provider ID is provided, redirect to provider levels list
if ($provider_id <= 0) {
    redirect(admin_url('provider-levels/index.php'));
}

// Get provider level information
$provider_level = db_get_row(
    "SELECT * FROM provider_levels WHERE id = ?",
    [$provider_id],
    'i'
);

// If provider level doesn't exist, redirect to provider levels list
if (!$provider_level) {
    start_session();
    $_SESSION['message'] = 'Provider level not found.';
    $_SESSION['message_type'] = 'danger';
    redirect(admin_url('provider-levels/index.php'));
}

// Begin transaction
db_begin_transaction();

try {
    // Delete provider access for this provider level
    db_delete('provider_access', 'provider_level_id = ?', [$provider_id]);
    
    // Delete the provider level
    db_delete('provider_levels', 'id = ?', [$provider_id]);
    
    // Commit the transaction
    db_commit();
    
    // Set success message
    start_session();
    $_SESSION['message'] = 'Provider level deleted successfully!';
    $_SESSION['message_type'] = 'success';
    
} catch (Exception $e) {
    // Rollback the transaction
    db_rollback();
    
    // Set error message
    start_session();
    $_SESSION['message'] = 'Error deleting provider level: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// Redirect to provider levels list
redirect(admin_url('provider-levels/index.php'));