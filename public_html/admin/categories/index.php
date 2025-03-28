<?php
/**
 * Admin Categories List
 * 
 * This page displays a list of all categories for management.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get all categories
$categories = db_get_results(
    "SELECT c.*, (SELECT COUNT(*) FROM protocols WHERE category_id = c.id) as protocol_count 
     FROM categories c 
     ORDER BY c.order ASC"
);

// Check for success message
start_session();
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Set page title
$page_title = 'Categories';

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manage Categories</h1>
    <a href="<?php echo admin_url('categories/create.php'); ?>" class="btn btn-success">
        <i class="fas fa-plus me-1"></i>Add New Category
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
        <h2 class="card-title h5 mb-0">Category List</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($categories)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Prefix</th>
                            <th>Name</th>
                            <th>Protocols</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['order']; ?></td>
                                <td><?php echo db_escape_html($category['prefix']); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('categories/edit.php?id=' . $category['id']); ?>">
                                        <?php echo db_escape_html($category['name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    if ($category['protocol_count'] > 0) {
                                        echo '<a href="' . admin_url('protocols/index.php?category=' . $category['id']) . '">';
                                        echo $category['protocol_count'] . ' protocol' . ($category['protocol_count'] != 1 ? 's' : '');
                                        echo '</a>';
                                    } else {
                                        echo '0 protocols';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <div class="table-actions">
                                        <a href="<?php echo admin_url('categories/edit.php?id=' . $category['id']); ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo admin_url('protocols/create.php?category=' . $category['id']); ?>" class="btn btn-sm btn-success" title="Add Protocol">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                        <a href="<?php echo admin_url('categories/delete.php?id=' . $category['id']); ?>" class="btn btn-sm btn-danger delete-category" title="Delete" data-category-id="<?php echo $category['id']; ?>" data-category-name="<?php echo db_escape_html($category['name']); ?>" data-protocol-count="<?php echo $category['protocol_count']; ?>">
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
            <p class="text-center">No categories found. <a href="<?php echo admin_url('categories/create.php'); ?>">Create your first category</a>?</p>
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
                <p>Are you sure you want to delete the category "<span id="categoryName"></span>"?</p>
                <div id="warningText"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete Category</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup delete confirmation modal
        const deleteButtons = document.querySelectorAll('.delete-category');
        const categoryName = document.getElementById('categoryName');
        const warningText = document.getElementById('warningText');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-category-id');
                const name = this.getAttribute('data-category-name');
                const protocolCount = parseInt(this.getAttribute('data-protocol-count'), 10);
                
                categoryName.textContent = name;
                
                if (protocolCount > 0) {
                    warningText.innerHTML = `
                        <p class="text-danger">This category contains ${protocolCount} protocol${protocolCount !== 1 ? 's' : ''}!</p>
                        <p class="text-danger"><strong>If you delete this category, all protocols within it will also be deleted.</strong></p>
                    `;
                } else {
                    warningText.innerHTML = '<p class="text-danger">This action cannot be undone!</p>';
                }
                
                confirmDeleteBtn.href = '<?php echo admin_url('categories/delete.php?id='); ?>' + id;
                
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