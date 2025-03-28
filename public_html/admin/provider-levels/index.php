<?php
/**
 * Admin Provider Levels List
 * 
 * This page displays a list of all provider levels for management.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get all provider levels
$provider_levels = db_get_results(
    "SELECT * FROM provider_levels ORDER BY `order` ASC"
);

// Check for success message
start_session();
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Set page title
$page_title = 'Provider Levels';

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manage Provider Levels</h1>
    <a href="<?php echo admin_url('provider-levels/create.php'); ?>" class="btn btn-success">
        <i class="fas fa-plus me-1"></i>Add New Provider Level
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title h5 mb-0">Provider Levels</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($provider_levels)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Abbreviation</th>
                            <th>Name</th>
                            <th>Color</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($provider_levels as $level): ?>
                            <tr>
                                <td><?php echo $level['order']; ?></td>
                                <td><?php echo db_escape_html($level['abbreviation']); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('provider-levels/edit.php?id=' . $level['id']); ?>">
                                        <?php echo db_escape_html($level['name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="color-preview me-2" style="background-color: <?php echo $level['color_code']; ?>;"></span>
                                        <?php echo db_escape_html($level['color_code']); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="table-actions">
                                        <a href="<?php echo admin_url('provider-levels/edit.php?id=' . $level['id']); ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo admin_url('provider-levels/delete.php?id=' . $level['id']); ?>" class="btn btn-sm btn-danger delete-provider" title="Delete" data-provider-id="<?php echo $level['id']; ?>" data-provider-name="<?php echo db_escape_html($level['name']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">No provider levels found. <a href="<?php echo admin_url('provider-levels/create.php'); ?>">Create your first provider level</a>?</p>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h2 class="card-title h5 mb-0">Provider Levels Preview</h2>
    </div>
    <div class="card-body">
        <div class="provider-levels">
            <?php foreach ($provider_levels as $level): ?>
                <div class="provider-level" style="background-color: <?php echo $level['color_code']; ?>;">
                    <?php echo db_escape_html($level['abbreviation']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <h5>Provider Bar Example</h5>
            <div class="provider-bar">
                <?php foreach ($provider_levels as $level): ?>
                    <div class="provider-segment" style="background-color: <?php echo $level['color_code']; ?>; width: <?php echo 100 / count($provider_levels); ?>%;"></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the provider level "<span id="providerName"></span>"?</p>
                <p class="text-danger">This will remove this provider level from all protocols!</p>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete Provider Level</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup delete confirmation modal
        const deleteButtons = document.querySelectorAll('.delete-provider');
        const providerName = document.getElementById('providerName');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-provider-id');
                const name = this.getAttribute('data-provider-name');
                
                providerName.textContent = name;
                confirmDeleteBtn.href = '<?php echo admin_url('provider-levels/delete.php?id='); ?>' + id;
                
                const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                modal.show();
            });
        });
    });
</script>

<?php
// Include footer
include '../../templates/admin/footer.php';
?>