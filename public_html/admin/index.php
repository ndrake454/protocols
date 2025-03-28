<?php
/**
 * Admin Dashboard
 * 
 * This is the main admin dashboard that shows an overview of the protocol system.
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get counts for dashboard
$protocol_count = db_get_row("SELECT COUNT(*) as count FROM protocols")['count'];
$category_count = db_get_row("SELECT COUNT(*) as count FROM categories")['count'];
$provider_level_count = db_get_row("SELECT COUNT(*) as count FROM provider_levels")['count'];

// Get recent protocols
$recent_protocols = db_get_results(
    "SELECT p.*, c.name as category_name 
     FROM protocols p 
     JOIN categories c ON p.category_id = c.id 
     ORDER BY p.updated_at DESC 
     LIMIT 5"
);

// Set page title
$page_title = 'Dashboard';

// Include header
include '../templates/admin/header.php';
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card stats-card">
            <i class="fas fa-file-medical"></i>
            <div class="stats-value"><?php echo $protocol_count; ?></div>
            <div class="stats-label">Protocols</div>
            <a href="<?php echo admin_url('protocols/index.php'); ?>" class="btn btn-sm btn-primary mt-3">Manage Protocols</a>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stats-card">
            <i class="fas fa-folder"></i>
            <div class="stats-value"><?php echo $category_count; ?></div>
            <div class="stats-label">Categories</div>
            <a href="<?php echo admin_url('categories/index.php'); ?>" class="btn btn-sm btn-primary mt-3">Manage Categories</a>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stats-card">
            <i class="fas fa-user-md"></i>
            <div class="stats-value"><?php echo $provider_level_count; ?></div>
            <div class="stats-label">Provider Levels</div>
            <a href="<?php echo admin_url('provider-levels/index.php'); ?>" class="btn btn-sm btn-primary mt-3">Manage Providers</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="m-0">Recently Updated Protocols</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_protocols)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_protocols as $protocol): ?>
                                    <tr>
                                        <td><?php echo db_escape_html($protocol['title']); ?></td>
                                        <td><?php echo db_escape_html($protocol['category_name']); ?></td>
                                        <td><?php echo format_date($protocol['updated_at'], 'M j, Y'); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="<?php echo admin_url('protocols/edit.php?id=' . $protocol['id']); ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo site_url('protocol.php?id=' . $protocol['id']); ?>" class="btn btn-sm btn-info" title="View" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No protocols have been created yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <a href="<?php echo admin_url('protocols/create.php'); ?>" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>Add New Protocol
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-5 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="m-0">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?php echo admin_url('protocols/create.php'); ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-plus-circle text-success me-2"></i>
                                Create New Protocol
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    <a href="<?php echo admin_url('categories/create.php'); ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-folder-plus text-primary me-2"></i>
                                Add New Category
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    <a href="<?php echo admin_url('provider-levels/index.php'); ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-users-cog text-info me-2"></i>
                                Manage Provider Levels
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    <a href="#" id="change-password-btn" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-key text-warning me-2"></i>
                                Change Password
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="m-0">System Information</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        PHP Version
                        <span class="badge bg-primary"><?php echo PHP_VERSION; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        MySQL Version
                        <span class="badge bg-primary">
                            <?php 
                            $mysql_version = db_get_row("SELECT VERSION() as version")['version'];
                            echo $mysql_version;
                            ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Last Login
                        <span class="badge bg-info">
                            <?php 
                            $user_id = get_current_user_id();
                            $last_login = db_get_row(
                                "SELECT last_login FROM admin WHERE id = ?", 
                                [$user_id], 
                                'i'
                            )['last_login'];
                            echo format_date($last_login, 'M j, Y H:i');
                            ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="change-password-form" method="post" action="<?php echo admin_url('change-password.php'); ?>">
                <?php echo csrf_token_field(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize change password modal
        const changePasswordBtn = document.getElementById('change-password-btn');
        if (changePasswordBtn) {
            changePasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
                modal.show();
            });
        }
        
        // Validate password form
        const passwordForm = document.getElementById('change-password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('The new password and confirmation do not match.');
                }
            });
        }
    });
</script>

<?php
// Include footer
include '../templates/admin/footer.php';
?>