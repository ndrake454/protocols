<?php
/**
 * Homepage
 * 
 * This is the main entry point for the public-facing site.
 * It displays a list of protocol categories and recently updated protocols.
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get all categories
$categories = get_categories();

// Get recent protocols
$recent_protocols = db_get_results(
    "SELECT p.*, c.name as category_name, c.prefix 
     FROM protocols p 
     JOIN categories c ON p.category_id = c.id 
     ORDER BY p.updated_at DESC 
     LIMIT 10"
);

// Set page title
$page_title = 'Home';

// Include header
include 'templates/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h2>Protocol Categories</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h3 class="card-title h5">
                                            <?php echo db_escape_html($category['prefix']); ?> - <?php echo db_escape_html($category['name']); ?>
                                        </h3>
                                        <?php
                                        // Get count of protocols in this category
                                        $count = db_get_row(
                                            "SELECT COUNT(*) as count FROM protocols WHERE category_id = ?", 
                                            [$category['id']], 
                                            'i'
                                        );
                                        ?>
                                        <p class="card-text text-muted">
                                            <?php echo $count['count']; ?> protocol<?php echo $count['count'] != 1 ? 's' : ''; ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <a href="<?php echo site_url('category.php?id=' . $category['id']); ?>" class="btn btn-primary btn-sm">
                                            View Protocols
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p>No protocol categories found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h2>Recently Updated</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_protocols)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recent_protocols as $protocol): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="<?php echo site_url('protocol.php?id=' . $protocol['id']); ?>">
                                        <?php echo db_escape_html($protocol['title']); ?>
                                    </a>
                                    <small class="d-block text-muted">
                                        <?php echo db_escape_html($protocol['category_name']); ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo format_date($protocol['updated_at'], 'M j, Y'); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent protocols found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Provider Levels</h2>
            </div>
            <div class="card-body">
                <div class="provider-levels">
                    <div class="provider-level emr">EMR</div>
                    <div class="provider-level emt">EMT</div>
                    <div class="provider-level emt-iv">EMT-IV</div>
                    <div class="provider-level aemt">AEMT</div>
                    <div class="provider-level intermediate">INTERMEDIATE</div>
                    <div class="provider-level paramedic">PARAMEDIC</div>
                </div>
                <p class="mt-3">
                    Provider levels are used throughout the protocols to indicate which providers are authorized to perform specific interventions.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'templates/footer.php';
?>