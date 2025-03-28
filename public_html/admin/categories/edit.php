<?php
/**
 * Admin Edit Category
 * 
 * This page allows admins to edit an existing category.
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
    "SELECT c.*, (SELECT COUNT(*) FROM protocols WHERE category_id = c.id) as protocol_count 
     FROM categories c 
     WHERE c.id = ?",
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

// Initialize variables
$name = $category['name'];
$prefix = $category['prefix'];
$order = $category['order'];
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = sanitize_input($_POST['name']);
        $prefix = sanitize_input($_POST['prefix']);
        $order = (int)$_POST['order'];
        
        // Validate required fields
        if (empty($name)) {
            $errors[] = 'Category name is required.';
        }
        
        if (empty($prefix)) {
            $errors[] = 'Category prefix is required.';
        } elseif (!preg_match('/^\d{4}$/', $prefix)) {
            $errors[] = 'Category prefix must be a 4-digit number.';
        }
        
        // Check if prefix already exists and is not this category's prefix
        $existing_prefix = db_get_row(
            "SELECT * FROM categories WHERE prefix = ? AND id != ?",
            [$prefix, $category_id],
            'si'
        );
        
        if ($existing_prefix) {
            $errors[] = 'Category prefix already exists. Please choose another.';
        }
        
        // If no errors, update the category
        if (empty($errors)) {
            $category_data = [
                'name' => $name,
                'prefix' => $prefix,
                'order' => $order
            ];
            
            $result = db_update('categories', $category_data, 'id = ?', [$category_id]);
            
            if ($result) {
                // Set success message
                start_session();
                $_SESSION['message'] = 'Category updated successfully!';
                $_SESSION['message_type'] = 'success';
                
                // Redirect to categories list
                redirect(admin_url('categories/index.php'));
            } else {
                $errors[] = 'Failed to update category. Please try again.';
            }
        }
    }
}

// Set page title
$page_title = 'Edit Category';

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Category</h1>
    <a href="<?php echo admin_url('categories/index.php'); ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to List
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title h5">Category Details</h2>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo admin_url('categories/edit.php?id=' . $category_id); ?>" class="needs-validation" novalidate>
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label required">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo db_escape_html($name); ?>" required>
                        <div class="invalid-feedback">Please enter a category name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="prefix" class="form-label required">Category Prefix (4 digits)</label>
                        <input type="text" class="form-control" id="prefix" name="prefix" value="<?php echo db_escape_html($prefix); ?>" pattern="\d{4}" required>
                        <div class="form-text">This will be used to prefix protocol numbers (e.g., 1000 for Procedures).</div>
                        <div class="invalid-feedback">Please enter a valid 4-digit prefix.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="order" name="order" value="<?php echo db_escape_html($order); ?>" min="1">
                        <div class="form-text">Categories are displayed in ascending order.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Category
                        </button>
                        <a href="<?php echo admin_url('categories/index.php'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title h5">Category Info</h2>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>ID</span>
                        <span class="badge bg-primary"><?php echo $category['id']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Protocols</span>
                        <?php if ($category['protocol_count'] > 0): ?>
                            <a href="<?php echo admin_url('protocols/index.php?category=' . $category_id); ?>" class="badge bg-info text-decoration-none">
                                <?php echo $category['protocol_count']; ?> protocol<?php echo $category['protocol_count'] != 1 ? 's' : ''; ?>
                            </a>
                        <?php else: ?>
                            <span class="badge bg-secondary">None</span>
                        <?php endif; ?>
                    </li>
                </ul>
                
                <?php if ($category['protocol_count'] > 0): ?>
                    <div class="mt-3">
                        <a href="<?php echo admin_url('protocols/index.php?category=' . $category_id); ?>" class="btn btn-info btn-sm w-100">
                            <i class="fas fa-list me-1"></i>View Protocols
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mt-3">
                        <a href="<?php echo admin_url('protocols/create.php?category=' . $category_id); ?>" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-plus me-1"></i>Add Protocol
                        </a>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="text-center">
                    <a href="<?php echo admin_url('categories/delete.php?id=' . $category_id); ?>" class="btn btn-danger btn-sm delete-category" data-category-id="<?php echo $category_id; ?>" data-category-name="<?php echo db_escape_html($name); ?>" data-protocol-count="<?php echo $category['protocol_count']; ?>">
                        <i class="fas fa-trash me-1"></i>Delete Category
                    </a>
                </div>
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
        // Form validation
        const form = document.querySelector('.needs-validation');
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
        
        // Setup delete confirmation modal
        const deleteButton = document.querySelector('.delete-category');
        if (deleteButton) {
            const categoryName = document.getElementById('categoryName');
            const warningText = document.getElementById('warningText');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            deleteButton.addEventListener('click', function(e) {
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
        }
    });
</script>

<?php
// Include footer
include '../../templates/admin/footer.php';
?>