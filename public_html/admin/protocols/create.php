<?php
/**
 * Admin Create Protocol
 * 
 * This page allows admins to create a new protocol.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Add to top of file, after the initial PHP tag
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure user is logged in
require_admin();

// Get all categories for dropdown
$categories = get_categories();

// Get all provider levels
$provider_levels = get_provider_levels();

// Initialize variables
$title = '';
$description = '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $category_id = (int)$_POST['category_id'];
        
        // Validate required fields
        if (empty($title)) {
            $errors[] = 'Protocol title is required.';
        }
        
        if ($category_id <= 0) {
            $errors[] = 'Please select a category.';
        }
        
        // If no errors, insert the protocol
        if (empty($errors)) {
            $protocol_data = [
                'title' => $title,
                'description' => $description,
                'category_id' => $category_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Begin transaction
            db_begin_transaction();
            
            try {
                // Insert protocol
                $protocol_id = db_insert('protocols', $protocol_data);
                
                if (!$protocol_id) {
                    throw new Exception('Failed to create protocol.');
                }
                
                // Process sections if provided
                if (isset($_POST['sections']) && is_array($_POST['sections'])) {
                    foreach ($_POST['sections'] as $section_data) {
                        // Insert section
                        $section = [
                            'protocol_id' => $protocol_id,
                            'title' => sanitize_input($section_data['title']),
                            'order' => (int)$section_data['order'],
                            'section_type' => sanitize_input($section_data['type'])
                        ];
                        
                        $section_id = db_insert('sections', $section);
                        
                        if (!$section_id) {
                            throw new Exception('Failed to create section.');
                        }
                        
                        // Process blocks if provided
                        if (isset($section_data['blocks']) && is_array($section_data['blocks'])) {
                            foreach ($section_data['blocks'] as $block_data) {
                                // Insert block
                                $block = [
                                    'section_id' => $section_id,
                                    'content' => $block_data['content'],
                                    'detailed_info' => isset($block_data['detailed_info']) ? $block_data['detailed_info'] : '',
                                    'order' => (int)$block_data['order'],
                                    'block_type' => sanitize_input($block_data['type']),
                                    'is_decision' => isset($block_data['is_decision']) ? (int)$block_data['is_decision'] : 0
                                ];
                                
                                // Add decision-specific fields if applicable
                                if (isset($block_data['yes_target'])) {
                                    $block['yes_target_id'] = $block_data['yes_target'];
                                }
                                
                                if (isset($block_data['no_target'])) {
                                    $block['no_target_id'] = $block_data['no_target'];
                                }
                                
                                $block_id = db_insert('blocks', $block);
                                
                                if (!$block_id) {
                                    throw new Exception('Failed to create block.');
                                }
                                
                                // Process provider levels for this block
                                if (isset($block_data['providers']) && is_array($block_data['providers'])) {
                                    foreach ($block_data['providers'] as $provider_id) {
                                        $provider_access = [
                                            'block_id' => $block_id,
                                            'provider_level_id' => (int)$provider_id
                                        ];
                                        
                                        $provider_access_id = db_insert('provider_access', $provider_access);
                                        
                                        if (!$provider_access_id) {
                                            throw new Exception('Failed to set provider access.');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Commit the transaction
                db_commit();
                
                // Set success message
                start_session();
                $_SESSION['message'] = 'Protocol created successfully!';
                $_SESSION['message_type'] = 'success';
                
                // Redirect to edit page
                redirect(admin_url('protocols/edit.php?id=' . $protocol_id));
                
            } catch (Exception $e) {
                // Rollback the transaction
                db_rollback();
                $errors[] = 'Error creating protocol: ' . $e->getMessage();
            }
        }
    }
}

// Set page title
$page_title = 'Create Protocol';

// Set extra JS and CSS
$extra_js = ['editor.js'];
$extra_css = ['protocol.css'];

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create New Protocol</h1>
    <a href="<?php echo admin_url('protocols/index.php'); ?>" class="btn btn-secondary">
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

<form method="post" action="<?php echo admin_url('protocols/create.php'); ?>" id="protocol-form" class="needs-validation" novalidate>
    <?php echo csrf_token_field(); ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title h5">Protocol Details</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label required">Protocol Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo db_escape_html($title); ?>" required>
                        <div class="invalid-feedback">Please enter a title.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label required">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo db_escape_html($category['prefix'] . ' - ' . $category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a category.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo db_escape_html($description); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title h5">Protocol Actions</h2>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Protocol
                        </button>
                        <a href="<?php echo admin_url('protocols/index.php'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-info mb-0">
                        <p class="mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            After creating the protocol, you'll be able to add sections and content.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title h5">Provider Levels</h2>
                </div>
                <div class="card-body">
                    <div class="provider-levels">
                        <?php foreach ($provider_levels as $level): ?>
                            <div class="provider-level <?php echo strtolower($level['abbreviation']); ?>">
                                <?php echo db_escape_html($level['abbreviation']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.getElementById('protocol-form');
        
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