<?php
/**
 * Category Page
 * 
 * This page displays all protocols in a specific category.
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no category ID is provided, redirect to homepage
if ($category_id <= 0) {
    redirect(site_url());
}

// Get category information
$category = get_category($category_id);

// If category doesn't exist, redirect to homepage
if (!$category) {
    redirect(site_url());
}

// Get protocols in this category
$protocols = get_protocols_by_category($category_id);

// Set page title
$page_title = $category['prefix'] . ' - ' . $category['name'];

// Include header
include 'templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo site_url(); ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo db_escape_html($category['name']); ?></li>
            </ol>
        </nav>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2><?php echo db_escape_html($category['prefix']); ?> - <?php echo db_escape_html($category['name']); ?></h2>
            </div>
            <div class="card-body">
                <?php if (!empty($protocols)): ?>
                    <div class="row">
                        <?php foreach ($protocols as $protocol): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h3 class="card-title h5">
                                            <?php echo db_escape_html($protocol['title']); ?>
                                        </h3>
                                        <?php if (!empty($protocol['description'])): ?>
                                            <p class="card-text">
                                                <?php echo format_text($protocol['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                        <a href="<?php echo site_url('protocol.php?id=' . $protocol['id']); ?>" class="btn btn-primary btn-sm">
                                            View Protocol
                                        </a>
                                        <small class="text-muted">
                                            Updated: <?php echo format_date($protocol['updated_at'], 'M j, Y'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No protocols found in this category.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'templates/footer.php';
?>