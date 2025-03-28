<?php
/**
 * Admin Edit Protocol
 * 
 * This page allows admins to edit an existing protocol.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
require_admin();

// Get protocol ID from URL
$protocol_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no protocol ID is provided, redirect to protocols list
if ($protocol_id <= 0) {
    redirect(admin_url('protocols/index.php'));
}

// Get protocol information
$protocol = get_protocol($protocol_id);

// If protocol doesn't exist, redirect to protocols list
if (!$protocol) {
    start_session();
    $_SESSION['message'] = 'Protocol not found.';
    $_SESSION['message_type'] = 'danger';
    redirect(admin_url('protocols/index.php'));
}

// Get all categories for dropdown
$categories = get_categories();

// Get all provider levels
$provider_levels = get_provider_levels();

// Get protocol sections
$sections = get_protocol_sections($protocol_id);

// Initialize variables
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
        
        // If no errors, update the protocol
        if (empty($errors)) {
            $protocol_data = [
                'title' => $title,
                'description' => $description,
                'category_id' => $category_id,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Begin transaction
            db_begin_transaction();
            
            try {
                // Update protocol
                $result = db_update('protocols', $protocol_data, 'id = ?', [$protocol_id]);
                
                if (!$result) {
                    throw new Exception('Failed to update protocol.');
                }
                
                // Process sections
                if (isset($_POST['sections']) && is_array($_POST['sections'])) {
                    // Get existing sections to determine which ones to delete
                    $existing_sections = [];
                    foreach ($sections as $section) {
                        $existing_sections[$section['id']] = $section;
                    }
                    
                    // Track processed sections to determine which ones to delete
                    $processed_sections = [];
                    
                    foreach ($_POST['sections'] as $section_data) {
                        $section_id = isset($section_data['id']) && !empty($section_data['id']) ? $section_data['id'] : null;
                        
                        if ($section_id && isset($existing_sections[$section_id])) {
                            // Update existing section
                            $section = [
                                'title' => sanitize_input($section_data['title']),
                                'order' => (int)$section_data['order'],
                                'section_type' => sanitize_input($section_data['type'])
                            ];
                            
                            $result = db_update('sections', $section, 'id = ?', [$section_id]);
                            
                            if (!$result) {
                                throw new Exception('Failed to update section.');
                            }
                            
                            $processed_sections[] = $section_id;
                        } else {
                            // Insert new section
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
                            
                            $processed_sections[] = $section_id;
                        }
                        
                        // Process blocks
                        if (isset($section_data['blocks']) && is_array($section_data['blocks'])) {
                            // Get existing blocks for this section
                            $existing_blocks = db_get_results(
                                "SELECT * FROM blocks WHERE section_id = ?",
                                [$section_id],
                                'i'
                            );
                            
                            $existing_blocks_map = [];
                            foreach ($existing_blocks as $block) {
                                $existing_blocks_map[$block['id']] = $block;
                            }
                            
                            // Track processed blocks
                            $processed_blocks = [];
                            
                            foreach ($section_data['blocks'] as $block_data) {
                                $block_id = isset($block_data['id']) && !empty($block_data['id']) ? $block_data['id'] : null;
                                
                                if ($block_id && isset($existing_blocks_map[$block_id])) {
                                    // Update existing block
                                    $block = [
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
                                    
                                    $result = db_update('blocks', $block, 'id = ?', [$block_id]);
                                    
                                    if (!$result) {
                                        throw new Exception('Failed to update block.');
                                    }
                                    
                                    $processed_blocks[] = $block_id;
                                    
                                    // Update provider levels
                                    // First remove all existing provider access
                                    db_delete('provider_access', 'block_id = ?', [$block_id]);
                                    
                                    // Then add new provider access
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
                                } else {
                                    // Insert new block
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
                                    
                                    $processed_blocks[] = $block_id;
                                    
                                    // Add provider levels
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
                            
                            // Delete blocks that weren't processed
                            foreach ($existing_blocks_map as $id => $block) {
                                if (!in_array($id, $processed_blocks)) {
                                    $result = db_delete('blocks', 'id = ?', [$id]);
                                    
                                    if (!$result) {
                                        throw new Exception('Failed to delete block.');
                                    }
                                }
                            }
                        }
                    }
                    
                    // Delete sections that weren't processed
                    foreach ($existing_sections as $id => $section) {
                        if (!in_array($id, $processed_sections)) {
                            $result = db_delete('sections', 'id = ?', [$id]);
                            
                            if (!$result) {
                                throw new Exception('Failed to delete section.');
                            }
                        }
                    }
                }
                
                // Commit the transaction
                db_commit();
                
                // Set success message
                start_session();
                $_SESSION['message'] = 'Protocol updated successfully!';
                $_SESSION['message_type'] = 'success';
                
                // Redirect to refresh the page
                redirect(admin_url('protocols/edit.php?id=' . $protocol_id));
                
            } catch (Exception $e) {
                // Rollback the transaction
                db_rollback();
                $errors[] = 'Error updating protocol: ' . $e->getMessage();
            }
        }
    }
}

// Set page title
$page_title = 'Edit Protocol';

// Set extra JS and CSS
$extra_js = ['editor.js'];
$extra_css = ['protocol.css'];

// Include header
include '../../templates/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Protocol</h1>
    <div>
        <a href="<?php echo site_url('protocol.php?id=' . $protocol_id); ?>" class="btn btn-info me-2" target="_blank">
            <i class="fas fa-eye me-1"></i>View Protocol
        </a>
        <a href="<?php echo admin_url('protocols/index.php'); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to List
        </a>
    </div>
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

<form method="post" action="<?php echo admin_url('protocols/edit.php?id=' . $protocol_id); ?>" id="protocol-form" class="needs-validation" novalidate>
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
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo db_escape_html($protocol['title']); ?>" required>
                        <div class="invalid-feedback">Please enter a title.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label required">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $protocol['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo db_escape_html($category['prefix'] . ' - ' . $category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a category.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo db_escape_html($protocol['description']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Protocol Builder -->
            <div class="card mb-4" id="protocol-builder">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title h5">Protocol Content</h2>
                        <button type="button" class="btn btn-success btn-sm" id="add-section-btn">
                            <i class="fas fa-plus me-1"></i>Add Section
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="sections-container">
                        <?php if (!empty($sections)): ?>
                            <?php foreach ($sections as $index => $section): ?>
                                <div class="section-container" data-section-id="<?php echo $section['id']; ?>" data-section-index="<?php echo $index; ?>">
                                    <div class="section-header">
                                        <h3 class="section-title"><?php echo db_escape_html($section['title']); ?></h3>
                                        <div class="section-actions">
                                            <button type="button" class="btn btn-sm btn-primary edit-section-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger remove-section-btn">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="section-content">
                                        <input type="hidden" name="sections[<?php echo $index; ?>][id]" value="<?php echo $section['id']; ?>">
                                        <input type="hidden" name="sections[<?php echo $index; ?>][title]" value="<?php echo db_escape_html($section['title']); ?>">
                                        <input type="hidden" name="sections[<?php echo $index; ?>][order]" value="<?php echo $index; ?>">
                                        <input type="hidden" name="sections[<?php echo $index; ?>][type]" value="<?php echo db_escape_html($section['section_type']); ?>">
                                        
                                        <?php
                                        // Get blocks for this section
                                        $blocks = get_section_blocks($section['id']);
                                        ?>
                                        
                                        <div class="blocks-container" data-section-id="<?php echo $section['id']; ?>">
                                            <?php if (!empty($blocks)): ?>
                                                <?php foreach ($blocks as $block_index => $block): ?>
                                                    <?php
                                                    // Get provider levels for this block
                                                    $block_provider_levels = get_block_provider_levels($block['id']);
                                                    $block_provider_ids = array_map(function($level) {
                                                        return $level['id'];
                                                    }, $block_provider_levels);
                                                    ?>
                                                    
                                                    <div class="block-container" data-block-id="<?php echo $block['id']; ?>" data-block-type="<?php echo $block['block_type']; ?>">
                                                        <div class="block-header">
                                                            <span class="sortable-handle"><i class="fas fa-grip-vertical"></i></span>
                                                            <h4 class="block-title"><?php echo db_escape_html(ucfirst($block['block_type'])); ?> Block</h4>
                                                            <div class="block-actions">
                                                                <button type="button" class="btn btn-sm btn-primary edit-block-btn">
                                                                    <i class="fas fa-edit"></i> Edit
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-danger remove-block-btn">
                                                                    <i class="fas fa-trash"></i> Remove
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="block-content">
                                                            <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][id]" value="<?php echo $block['id']; ?>">
                                                            <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][type]" value="<?php echo db_escape_html($block['block_type']); ?>">
                                                            <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][order]" value="<?php echo $block_index; ?>">
                                                            <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][content]" value="<?php echo db_escape_html($block['content']); ?>">
                                                            <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][detailed_info]" value="<?php echo db_escape_html($block['detailed_info']); ?>">
                                                            <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][is_decision]" value="<?php echo $block['is_decision']; ?>">
                                                            
                                                            <?php if ($block['is_decision']): ?>
                                                                <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][yes_target]" value="<?php echo $block['yes_target_id']; ?>">
                                                                <input type="hidden" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][no_target]" value="<?php echo $block['no_target_id']; ?>">
                                                            <?php endif; ?>
                                                            
                                                            <div class="provider-levels-container">
                                                                <p><strong>Available to Provider Levels:</strong></p>
                                                                <div class="provider-checkbox-group">
                                                                    <?php foreach ($provider_levels as $level): ?>
                                                                        <div class="provider-checkbox">
                                                                            <input type="checkbox" name="sections[<?php echo $index; ?>][blocks][<?php echo $block_index; ?>][providers][]" value="<?php echo $level['id']; ?>" id="provider-<?php echo $index; ?>-<?php echo $block_index; ?>-<?php echo $level['id']; ?>" <?php echo in_array($level['id'], $block_provider_ids) ? 'checked' : ''; ?>>
                                                                            <label for="provider-<?php echo $index; ?>-<?php echo $block_index; ?>-<?php echo $level['id']; ?>">
                                                                                <?php echo db_escape_html($level['abbreviation']); ?>
                                                                                <span class="color-preview" style="background-color: <?php echo $level['color_code']; ?>;"></span>
                                                                            </label>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted">No blocks added yet. Click "Add Block" to add content.</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="text-center mt-3">
                                            <button type="button" class="btn btn-success add-block-btn" data-section-id="<?php echo $section['id']; ?>">
                                                <i class="fas fa-plus"></i> Add Block
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">No sections added yet. Click "Add Section" to begin building your protocol.</p>
                        <?php endif; ?>
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
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                        <a href="<?php echo admin_url('protocols/index.php'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <a href="<?php echo site_url('protocol.php?id=' . $protocol_id); ?>" class="btn btn-info" target="_blank">
                            <i class="fas fa-eye me-1"></i>View Protocol
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title h5">Protocol Info</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>ID</span>
                            <span class="badge bg-primary"><?php echo $protocol['id']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Category</span>
                            <span class="badge bg-info"><?php echo db_escape_html($protocol['category_name']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Created</span>
                            <span><?php echo format_date($protocol['created_at'], 'M j, Y'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Last Updated</span>
                            <span><?php echo format_date($protocol['updated_at'], 'M j, Y H:i'); ?></span>
                        </li>
                    </ul>
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

<?php
// Include footer
include '../../templates/admin/footer.php';
?>