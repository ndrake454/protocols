<?php
/**
 * Admin Delete Protocol
 * 
 * This page deletes a protocol.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get protocol ID from URL
$protocol_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no protocol ID is provided, redirect to protocols list
if ($protocol_id <= 0) {
    redirect(admin_url('protocols/index.php'));
}

// Get protocol information
$protocol = get_protocol($protocol_id);

// If protocol doesn't exist, redirect to protocols list
if (!$protocol) {
    start_session();
    $_SESSION['message'] = 'Protocol not found.';
    $_SESSION['message_type'] = 'danger';
    redirect(admin_url('protocols/index.php'));
}

// Begin transaction
db_begin_transaction();

try {
    // Get the sections to delete their blocks and provider access
    $sections = get_protocol_sections($protocol_id);
    
    foreach ($sections as $section) {
        $section_id = $section['id'];
        
        // Get blocks for this section
        $blocks = get_section_blocks($section_id);
        
        foreach ($blocks as $block) {
            $block_id = $block['id'];
            
            // Delete provider access for this block
            db_delete('provider_access', 'block_id = ?', [$block_id]);
        }
        
        // Delete blocks for this section
        db_delete('blocks', 'section_id = ?', [$section_id]);
    }
    
    // Delete sections for this protocol
    db_delete('sections', 'protocol_id = ?', [$protocol_id]);
    
    // Delete the protocol
    db_delete('protocols', 'id = ?', [$protocol_id]);
    
    // Commit the transaction
    db_commit();
    
    // Set success message
    start_session();
    $_SESSION['message'] = 'Protocol deleted successfully!';
    $_SESSION['message_type'] = 'success';
    
} catch (Exception $e) {
    // Rollback the transaction
    db_rollback();
    
    // Set error message
    start_session();
    $_SESSION['message'] = 'Error deleting protocol: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// Redirect to protocols list
redirect(admin_url('protocols/index.php'));