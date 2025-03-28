<?php
/**
 * Admin Protocols List
 * 
 * This page displays a list of all protocols for management.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get category filter from URL if present
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get all categories for filter dropdown
$categories = get_categories();

// Get protocols based on filter
if ($category_filter > 0) {
    $protocols = db_get_results(
        "SELECT p.*, c.name as category_name, c.prefix 
         FROM protocols p 
         JOIN categories c ON p.category_id = c.id 
         WHERE p.category_id = ? 
         ORDER BY p.title ASC",
        [$category_filter],
        'i'
    );
} else {
    $protocols = db_get_results(
        "SELECT p.*, c.name as category_name, c.prefix 
         FROM protocols p 
         JOIN categories c ON p.category_id = c.id 
         ORDER BY c.prefix ASC, p.title ASC"
    );
}

// Check for success message
start_session();
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Set page title
$page_title = 'Protocols';

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manage Protocols</h1>
    <a href="<?php echo admin_url('protocols/create.php'); ?>" class="btn btn-success">
        <i class="fas fa-plus me-1"></i>Add New Protocol
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="card-title h5 mb-0">Protocol List</h2>
            
            <form method="get" action="<?php echo admin_url('protocols/index.php'); ?>" class="form-inline">
                <div class="input-group">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo db_escape_html($category['prefix'] . ' - ' . $category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($category_filter > 0): ?>
                        <a href="<?php echo admin_url('protocols/index.php'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($protocols)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Last Updated</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($protocols as $protocol): ?>
                            <tr>
                                <td><?php echo $protocol['id']; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('protocols/edit.php?id=' . $protocol['id']); ?>">
                                        <?php echo db_escape_html($protocol['title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo db_escape_html($protocol['prefix'] . ' - ' . $protocol['category_name']); ?>
                                </td>
                                <td>
                                    <?php echo format_date($protocol['updated_at']); ?>
                                </td>
                                <td class="text-center">
                                    <div class="table-actions">
                                        <a href="<?php echo admin_url('protocols/edit.php?id=' . $protocol['id']); ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo site_url('protocol.php?id=' . $protocol['id']); ?>" class="btn btn-sm btn-info" title="View" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo admin_url('protocols/delete.php?id=' . $protocol['id']); ?>" class="btn btn-sm btn-danger delete-protocol" title="Delete" data-protocol-id="<?php echo $protocol['id']; ?>" data-protocol-title="<?php echo db_escape_html($protocol['title']); ?>">
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
            <p class="text-center">
                <?php if ($category_filter > 0): ?>
                    No protocols found in this category. <a href="<?php echo admin_url('protocols/create.php?category=' . $category_filter); ?>">Create one</a>?
                <?php else: ?>
                    No protocols found. <a href="<?php echo admin_url('protocols/create.php'); ?>">Create your first protocol</a>?
                <?php endif; ?>
            </p>
        <?php endif; ?>
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
                <p>Are you sure you want to delete the protocol "<span id="protocolTitle"></span>"?</p>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete Protocol</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup delete confirmation modal
        const deleteButtons = document.querySelectorAll('.delete-protocol');
        const protocolTitle = document.getElementById('protocolTitle');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-protocol-id');
                const title = this.getAttribute('data-protocol-title');
                
                protocolTitle.textContent = title;
                confirmDeleteBtn.href = '<?php echo admin_url('protocols/delete.php?id='); ?>' + id;
                
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