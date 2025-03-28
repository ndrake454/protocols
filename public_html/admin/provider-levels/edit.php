<?php
/**
 * Admin Edit Provider Level
 * 
 * This page allows admins to edit an existing provider level.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get provider level ID from URL
$provider_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no provider ID is provided, redirect to provider levels list
if ($provider_id <= 0) {
    redirect(admin_url('provider-levels/index.php'));
}

// Get provider level information
$provider_level = db_get_row(
    "SELECT * FROM provider_levels WHERE id = ?",
    [$provider_id],
    'i'
);

// If provider level doesn't exist, redirect to provider levels list
if (!$provider_level) {
    start_session();
    $_SESSION['message'] = 'Provider level not found.';
    $_SESSION['message_type'] = 'danger';
    redirect(admin_url('provider-levels/index.php'));
}

// Initialize variables
$name = $provider_level['name'];
$abbreviation = $provider_level['abbreviation'];
$color_code = $provider_level['color_code'];
$order = $provider_level['order'];
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = sanitize_input($_POST['name']);
        $abbreviation = sanitize_input($_POST['abbreviation']);
        $color_code = sanitize_input($_POST['color_code']);
        $order = (int)$_POST['order'];
        
        // Validate required fields
        if (empty($name)) {
            $errors[] = 'Provider level name is required.';
        }
        
        if (empty($abbreviation)) {
            $errors[] = 'Abbreviation is required.';
        }
        
        if (empty($color_code)) {
            $errors[] = 'Color code is required.';
        } elseif (!preg_match('/^#[a-f0-9]{6}$/i', $color_code)) {
            $errors[] = 'Color code must be a valid hex color (e.g., #FF5733).';
        }
        
        // Check if abbreviation already exists and is not this provider's abbreviation
        $existing_abbreviation = db_get_row(
            "SELECT * FROM provider_levels WHERE abbreviation = ? AND id != ?",
            [$abbreviation, $provider_id],
            'si'
        );
        
        if ($existing_abbreviation) {
            $errors[] = 'Abbreviation already exists. Please choose another.';
        }
        
        // If no errors, update the provider level
        if (empty($errors)) {
            $provider_data = [
                'name' => $name,
                'abbreviation' => $abbreviation,
                'color_code' => $color_code,
                'order' => $order
            ];
            
            $result = db_update('provider_levels', $provider_data, 'id = ?', [$provider_id]);
            
            if ($result) {
                // Set success message
                start_session();
                $_SESSION['message'] = 'Provider level updated successfully!';
                $_SESSION['message_type'] = 'success';
                
                // Redirect to provider levels list
                redirect(admin_url('provider-levels/index.php'));
            } else {
                $errors[] = 'Failed to update provider level. Please try again.';
            }
        }
    }
}

// Set page title
$page_title = 'Edit Provider Level';

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Provider Level</h1>
    <a href="<?php echo admin_url('provider-levels/index.php'); ?>" class="btn btn-secondary">
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
                <h2 class="card-title h5">Provider Level Details</h2>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo admin_url('provider-levels/edit.php?id=' . $provider_id); ?>" class="needs-validation" novalidate>
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label required">Provider Level Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo db_escape_html($name); ?>" required>
                            <div class="invalid-feedback">Please enter a provider level name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="abbreviation" class="form-label required">Abbreviation</label>
                            <input type="text" class="form-control" id="abbreviation" name="abbreviation" value="<?php echo db_escape_html($abbreviation); ?>" required>
                            <div class="form-text">Short form to be displayed in the protocols (e.g., EMT, PARA).</div>
                            <div class="invalid-feedback">Please enter an abbreviation.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="color_code" class="form-label required">Color Code</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="color_picker" value="<?php echo db_escape_html($color_code); ?>" title="Choose a color">
                                <input type="text" class="form-control" id="color_code" name="color_code" value="<?php echo db_escape_html($color_code); ?>" pattern="#[a-fA-F0-9]{6}" required>
                            </div>
                            <div class="form-text">Color used to visually represent this provider level in protocols.</div>
                            <div class="invalid-feedback">Please enter a valid hex color code.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="order" name="order" value="<?php echo db_escape_html($order); ?>" min="1">
                            <div class="form-text">Provider levels are displayed in ascending order.</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Preview</label>
                        <div class="d-flex align-items-center">
                            <div class="provider-level me-3" id="preview_level" style="background-color: <?php echo db_escape_html($color_code); ?>;">
                                <?php echo db_escape_html($abbreviation); ?>
                            </div>
                            <div class="provider-bar" style="width: 200px;">
                                <div class="provider-segment" id="preview_segment" style="background-color: <?php echo db_escape_html($color_code); ?>; width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Provider Level
                        </button>
                        <a href="<?php echo admin_url('provider-levels/index.php'); ?>" class="btn btn-outline-secondary">
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
                <h2 class="card-title h5">Provider Level Info</h2>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>ID</span>
                        <span class="badge bg-primary"><?php echo $provider_level['id']; ?></span>
                    </li>
                </ul>
                
                <hr>
                
                <div class="text-center">
                    <a href="<?php echo admin_url('provider-levels/delete.php?id=' . $provider_id); ?>" class="btn btn-danger btn-sm delete-provider" data-provider-id="<?php echo $provider_id; ?>" data-provider-name="<?php echo db_escape_html($name); ?>">
                        <i class="fas fa-trash me-1"></i>Delete Provider Level
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
        // Form validation
        const form = document.querySelector('.needs-validation');
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
        
        // Color picker and preview functionality
        const colorPicker = document.getElementById('color_picker');
        const colorCode = document.getElementById('color_code');
        const previewLevel = document.getElementById('preview_level');
        const previewSegment = document.getElementById('preview_segment');
        const abbreviationInput = document.getElementById('abbreviation');
        
        colorPicker.addEventListener('input', function() {
            colorCode.value = this.value;
            updatePreview();
        });
        
        colorCode.addEventListener('input', function() {
            colorPicker.value = this.value;
            updatePreview();
        });
        
        abbreviationInput.addEventListener('input', function() {
            updatePreview();
        });
        
        function updatePreview() {
            const color = colorCode.value;
            const abbr = abbreviationInput.value;
            
            previewLevel.style.backgroundColor = color;
            previewLevel.textContent = abbr;
            previewSegment.style.backgroundColor = color;
        }
        
        // Setup delete confirmation modal
        const deleteButton = document.querySelector('.delete-provider');
        if (deleteButton) {
            const providerName = document.getElementById('providerName');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            deleteButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-provider-id');
                const name = this.getAttribute('data-provider-name');
                
                providerName.textContent = name;
                confirmDeleteBtn.href = '<?php echo admin_url('provider-levels/delete.php?id='); ?>' + id;
                
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