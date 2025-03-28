<?php
/**
 * Admin Delete Category
 * 
 * This page deletes a category and all its protocols.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no category ID is provided, redirect to categories list
if ($category_id <= 0) {
    redirect(admin_url('categories/index.php'));
}

// Get category information
$category = db_get_row(
    "SELECT * FROM categories WHERE id = ?",
    [$category_id],
    'i'
);

// If category doesn't exist, redirect to categories list
if (!$category) {
    start_session();
    $_SESSION['message'] = 'Category not found.';
    $_SESSION['message_type'] = 'danger';
    redirect(admin_url('categories/index.php'));
}

// Begin transaction
db_begin_transaction();

try {
    // Get all protocols in this category
    $protocols = db_get_results(
        "SELECT id FROM protocols WHERE category_id = ?",
        [$category_id],
        'i'
    );
    
    // Delete all protocols in this category
    foreach ($protocols as $protocol) {
        $protocol_id = $protocol['id'];
        
        // Get the sections for this protocol
        $sections = db_get_results(
            "SELECT id FROM sections WHERE protocol_id = ?",
            [$protocol_id],
            'i'
        );
        
        foreach ($sections as $section) {
            $section_id = $section['id'];
            
            // Get blocks for this section
            $blocks = db_get_results(
                "SELECT id FROM blocks WHERE section_id = ?",
                [$section_id],
                'i'
            );
            
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
    }
    
    // Delete the category
    db_delete('categories', 'id = ?', [$category_id]);
    
    // Commit the transaction
    db_commit();
    
    // Set success message
    start_session();
    $_SESSION['message'] = 'Category deleted successfully!';
    $_SESSION['message_type'] = 'success';
    
} catch (Exception $e) {
    // Rollback the transaction
    db_rollback();
    
    // Set error message
    start_session();
    $_SESSION['message'] = 'Error deleting category: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// Redirect to categories list
redirect(admin_url('categories/index.php'));