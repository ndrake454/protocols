<?php
/**
 * Admin Create Category
 * 
 * This page allows admins to create a new category.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Initialize variables
$name = '';
$prefix = '';
$order = '';
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
        
        // Check if prefix already exists
        $existing_prefix = db_get_row(
            "SELECT * FROM categories WHERE prefix = ?",
            [$prefix],
            's'
        );
        
        if ($existing_prefix) {
            $errors[] = 'Category prefix already exists. Please choose another.';
        }
        
        // If no errors, insert the category
        if (empty($errors)) {
            // If order is not provided, get the max order and add 1
            if (empty($order)) {
                $max_order = db_get_row("SELECT MAX(`order`) as max_order FROM categories");
                $order = $max_order ? $max_order['max_order'] + 1 : 1;
            }
            
            $category_data = [
                'name' => $name,
                'prefix' => $prefix,
                'order' => $order
            ];
            
            $category_id = db_insert('categories', $category_data);
            
            if ($category_id) {
                // Set success message
                start_session();
                $_SESSION['message'] = 'Category created successfully!';
                $_SESSION['message_type'] = 'success';
                
                // Redirect to categories list
                redirect(admin_url('categories/index.php'));
            } else {
                $errors[] = 'Failed to create category. Please try again.';
            }
        }
    }
}

// Set page title
$page_title = 'Create Category';

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create New Category</h1>
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

<div class="card">
    <div class="card-header">
        <h2 class="card-title h5">Category Details</h2>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo admin_url('categories/create.php'); ?>" class="needs-validation" novalidate>
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
                <div class="form-text">Categories are displayed in ascending order. Leave blank for automatic ordering.</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Create Category
                </button>
                <a href="<?php echo admin_url('categories/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Cancel
                </a>
            </div>
        </form>
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
    });
</script>

<?php
// Include footer
include '../../templates/admin/footer.php';
?>