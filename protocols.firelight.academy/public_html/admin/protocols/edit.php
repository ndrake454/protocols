<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Initialize database connection
$db = new Database();

// Start session
session_start([
    'name' => SESSION_NAME,
    'cookie_lifetime' => SESSION_LIFETIME
]);

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    redirect('../login.php');
}

if (!has_permission('editor')) {
    flash_message('You do not have permission to edit protocols.', 'danger');
    redirect('../index.php');
}

// Get protocol ID from URL
$protocol_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$protocol_id) {
    flash_message('No protocol specified.', 'danger');
    redirect('../protocols/index.php');
}

// Get protocol details
$db->query('SELECT * FROM protocols WHERE id = :id');
$db->bind(':id', $protocol_id);
$protocol = $db->single();

if (!$protocol) {
    flash_message('Protocol not found.', 'danger');
    redirect('../protocols/index.php');
}

// Get categories for dropdown
$db->query('SELECT * FROM categories ORDER BY sort_order, category_number');
$categories = $db->resultSet();

// Get provider levels
$db->query('SELECT * FROM provider_levels ORDER BY sort_order');
$provider_levels = $db->resultSet();

// Get protocol sections
$db->query('SELECT * FROM protocol_sections WHERE protocol_id = :protocol_id ORDER BY sort_order');
$db->bind(':protocol_id', $protocol_id);
$sections = $db->resultSet();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Basic protocol data
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
        $protocol_number = sanitize_input($_POST['protocol_number']);
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        // Update protocol record
        $db->query('UPDATE protocols SET category_id = :category_id, protocol_number = :protocol_number, title = :title, description = :description, updated_by = :updated_by, is_published = :is_published, last_updated = NOW() WHERE id = :id');
        $db->bind(':category_id', $category_id);
        $db->bind(':protocol_number', $protocol_number);
        $db->bind(':title', $title);
        $db->bind(':description', $description);
        $db->bind(':updated_by', $_SESSION['user_id']);
        $db->bind(':is_published', $is_published);
        $db->bind(':id', $protocol_id);
        $db->execute();
        
        // Delete all existing section and item data to rebuild
        $db->query('DELETE FROM protocol_sections WHERE protocol_id = :protocol_id');
        $db->bind(':protocol_id', $protocol_id);
        $db->execute();
        
        // Process sections
        if (isset($_POST['sections']) && is_array($_POST['sections'])) {
            foreach ($_POST['sections'] as $section_index => $section) {
                // Insert section
                $db->query('INSERT INTO protocol_sections (protocol_id, title, description, sort_order, section_type) VALUES (:protocol_id, :title, :description, :sort_order, :section_type)');
                $db->bind(':protocol_id', $protocol_id);
                $db->bind(':title', sanitize_input($section['title']));
                $db->bind(':description', sanitize_input($section['description'] ?? ''));
                $db->bind(':sort_order', $section_index);
                $db->bind(':section_type', sanitize_input($section['type']));
                $db->execute();
                
                $section_id = $db->lastInsertId();
                
                // Process items in this section
                if (isset($section['items']) && is_array($section['items'])) {
                    foreach ($section['items'] as $item_index => $item) {
                        // Insert item
                        $db->query('INSERT INTO protocol_items (section_id, title, content, detailed_info, sort_order, parent_id, is_decision) VALUES (:section_id, :title, :content, :detailed_info, :sort_order, :parent_id, :is_decision)');
                        $db->bind(':section_id', $section_id);
                        $db->bind(':title', sanitize_input($item['title'] ?? ''));
                        $db->bind(':content', sanitize_input($item['content']));
                        $db->bind(':detailed_info', sanitize_input($item['detailed_info'] ?? ''));
                        $db->bind(':sort_order', $item_index);
                        $db->bind(':parent_id', null); // Parent items will be null
                        $db->bind(':is_decision', isset($item['is_decision']) ? 1 : 0);
                        $db->execute();
                        
                        $item_id = $db->lastInsertId();
                        
                        // Process provider levels for this item
                        if (isset($item['providers']) && is_array($item['providers'])) {
                            foreach ($item['providers'] as $provider_id => $percentage) {
                                if ($percentage > 0) {
                                    $db->query('INSERT INTO item_provider_levels (item_id, provider_id, percentage) VALUES (:item_id, :provider_id, :percentage)');
                                    $db->bind(':item_id', $item_id);
                                    $db->bind(':provider_id', $provider_id);
                                    $db->bind(':percentage', $percentage);
                                    $db->execute();
                                }
                            }
                        }
                        
                        // Process sub-items (criteria under assessment item, etc.)
                        if (isset($item['subitems']) && is_array($item['subitems'])) {
                            foreach ($item['subitems'] as $subitem_index => $subitem) {
                                $db->query('INSERT INTO protocol_items (section_id, content, detailed_info, sort_order, parent_id, is_decision) VALUES (:section_id, :content, :detailed_info, :sort_order, :parent_id, :is_decision)');
                                $db->bind(':section_id', $section_id);
                                $db->bind(':content', sanitize_input($subitem['content']));
                                $db->bind(':detailed_info', sanitize_input($subitem['detailed_info'] ?? ''));
                                $db->bind(':sort_order', $subitem_index);
                                $db->bind(':parent_id', $item_id);
                                $db->bind(':is_decision', 0);
                                $db->execute();
                            }
                        }
                    }
                }
            }
        }
        
        // Save revision history
        $revision_notes = sanitize_input($_POST['revision_notes'] ?? 'Updated protocol');
        $revision_data = json_encode([
            'protocol_number' => $protocol_number,
            'title' => $title,
            'description' => $description,
            'category_id' => $category_id,
            'is_published' => $is_published
        ]);
        
        $db->query('INSERT INTO protocol_revisions (protocol_id, user_id, revision_data, revision_notes) VALUES (:protocol_id, :user_id, :revision_data, :revision_notes)');
        $db->bind(':protocol_id', $protocol_id);
        $db->bind(':user_id', $_SESSION['user_id']);
        $db->bind(':revision_data', $revision_data);
        $db->bind(':revision_notes', $revision_notes);
        $db->execute();
        
        // Commit transaction
        $db->endTransaction();
        
        flash_message('Protocol updated successfully!', 'success');
        redirect($_SERVER['PHP_SELF'] . '?id=' . $protocol_id);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->cancelTransaction();
        flash_message('Error updating protocol: ' . $e->getMessage(), 'danger');
    }
}

// Load section items and their data
$section_data = [];
foreach ($sections as $section) {
    // Get items for this section (parent items only)
    $db->query('SELECT * FROM protocol_items WHERE section_id = :section_id AND parent_id IS NULL ORDER BY sort_order');
    $db->bind(':section_id', $section['id']);
    $items = $db->resultSet();
    
    $item_data = [];
    foreach ($items as $item) {
        // Get provider levels for this item
        $db->query('SELECT p.id, ipl.percentage FROM provider_levels p 
                    JOIN item_provider_levels ipl ON p.id = ipl.provider_id 
                    WHERE ipl.item_id = :item_id');
        $db->bind(':item_id', $item['id']);
        $providers = $db->resultSet();
        
        $provider_data = [];
        foreach ($providers as $provider) {
            $provider_data[$provider['id']] = $provider['percentage'];
        }
        
        // Get sub-items for this item
        $db->query('SELECT * FROM protocol_items WHERE parent_id = :parent_id ORDER BY sort_order');
        $db->bind(':parent_id', $item['id']);
        $subitems = $db->resultSet();
        
        $item['providers'] = $provider_data;
        $item['subitems'] = $subitems;
        $item_data[] = $item;
    }
    
    $section['items'] = $item_data;
    $section_data[] = $section;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Protocol - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Edit Protocol: <?= htmlspecialchars($protocol['title']); ?></h1>
                <div class="header-actions">
                    <a href="../../protocol.php?id=<?= $protocol_id; ?>" class="btn btn-outline" target="_blank">View Protocol</a>
                    <a href="../protocols/index.php" class="btn btn-outline">Back to Protocols</a>
                </div>
            </div>
            
            <?php echo flash_message(); ?>
            
            <form method="POST" id="protocol-form" data-validate="true" class="form-container">
                <div class="form-row">
                    <h2>Protocol Information</h2>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="protocol_number">Protocol Number</label>
                        <input type="text" id="protocol_number" name="protocol_number" required placeholder="e.g. 2001.1" value="<?= htmlspecialchars($protocol['protocol_number']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Protocol Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g. Adult Respiratory Distress" value="<?= htmlspecialchars($protocol['title']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id']; ?>" <?= ($category['id'] == $protocol['category_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($category['category_number'] . '. ' . $category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" rows="3" placeholder="Brief description of this protocol"><?= htmlspecialchars($protocol['description']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_published" value="1" <?= $protocol['is_published'] ? 'checked' : ''; ?>> 
                            Publish this protocol (immediately visible to users)
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="revision_notes">Revision Notes (Optional)</label>
                        <textarea id="revision_notes" name="revision_notes" rows="2" placeholder="Describe what changed in this update (visible in revision history)"><?= isset($_POST['revision_notes']) ? htmlspecialchars($_POST['revision_notes']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <hr>
                    <h2>Protocol Content</h2>
                    <p class="help-text">Edit your protocol by modifying sections and items below.</p>
                </div>
                
                <div id="protocol-editor">
                    <div id="sections-container">
                        <?php foreach ($section_data as $section_index => $section): ?>
                        <div class="section" id="section-<?= $section_index; ?>">
                            <div class="section-header">
                                <h3><?= htmlspecialchars($section['title']); ?></h3>
                                <button type="button" class="remove-btn" data-target="section-<?= $section_index; ?>">Remove</button>
                            </div>
                            <div class="section-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Section Type</label>
                                        <select class="section-type-select" name="sections[<?= $section_index; ?>][type]" required>
                                            <option value="">Select Type</option>
                                            <option value="assessment" <?= ($section['section_type'] == 'assessment') ? 'selected' : ''; ?>>Assessment Boxes</option>
                                            <option value="flowchart" <?= ($section['section_type'] == 'flowchart') ? 'selected' : ''; ?>>Flowchart</option>
                                            <option value="checklist" <?= ($section['section_type'] == 'checklist') ? 'selected' : ''; ?>>Checklist</option>
                                            <option value="information" <?= ($section['section_type'] == 'information') ? 'selected' : ''; ?>>Information</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Section Title</label>
                                        <input type="text" name="sections[<?= $section_index; ?>][title]" required placeholder="e.g. Assessment Criteria" value="<?= htmlspecialchars($section['title']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row type-specific type-information" style="display: <?= ($section['section_type'] == 'information') ? 'block' : 'none'; ?>;">
                                    <div class="form-group">
                                        <label>Section Description</label>
                                        <textarea name="sections[<?= $section_index; ?>][description]" rows="3" placeholder="General information about this section"><?= htmlspecialchars($section['description']); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="items-container">
                                    <?php foreach ($section['items'] as $item_index => $item): ?>
                                    <div class="item" id="item-<?= $section_index; ?>-<?= $item_index; ?>">
                                        <div class="item-header">
                                            <h4><?= !empty($item['title']) ? htmlspecialchars($item['title']) : 'Item ' . ($item_index + 1); ?></h4>
                                            <button type="button" class="remove-btn" data-target="item-<?= $section_index; ?>-<?= $item_index; ?>">Remove</button>
                                        </div>
                                        <div class="item-body">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Item Title (for assessment boxes)</label>
                                                    <input type="text" name="sections[<?= $section_index; ?>][items][<?= $item_index; ?>][title]" placeholder="e.g. Inadequate Oxygenation" value="<?= htmlspecialchars($item['title']); ?>">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Content</label>
                                                    <textarea name="sections[<?= $section_index; ?>][items][<?= $item_index; ?>][content]" rows="2" required placeholder="Item content"><?= htmlspecialchars($item['content']); ?></textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Detailed Information (for info modals)</label>
                                                    <textarea name="sections[<?= $section_index; ?>][items][<?= $item_index; ?>][detailed_info]" rows="4" placeholder="Additional information that will appear in a popup modal when the item is clicked"><?= htmlspecialchars($item['detailed_info']); ?></textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group checkbox-group">
                                                    <label>
                                                        <input type="checkbox" name="sections[<?= $section_index; ?>][items][<?= $item_index; ?>][is_decision]" value="1" <?= $item['is_decision'] ? 'checked' : ''; ?>> 
                                                        This is a decision box (for flowcharts)
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <h5>Provider Levels</h5>
                                                <div class="provider-options">
                                                    <?php foreach ($provider_levels as $level): 
                                                        $percentage = $item['providers'][$level['id']] ?? 0;
                                                    ?>
                                                    <div class="provider-option">
                                                        <label class="provider-label" style="background-color: <?= $level['color_code']; ?>;">
                                                            <input type="checkbox" class="provider-checkbox" data-provider-id="<?= $level['id']; ?>" <?= ($percentage > 0) ? 'checked' : ''; ?>>
                                                            <?= htmlspecialchars($level['shortname']); ?>
                                                        </label>
                                                        <input type="number" name="sections[<?= $section_index; ?>][items][<?= $item_index; ?>][providers][<?= $level['id']; ?>]" class="provider-percentage" value="<?= $percentage; ?>" min="0" max="100" style="display: <?= ($percentage > 0) ? 'inline-block' : 'none'; ?>;">%
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="subitems-container">
                                                <!-- For assessment criteria or similar nested items -->
                                                <div class="form-row">
                                                    <div class="form-group">
                                                        <label>Sub-items (e.g. assessment criteria)</label>
                                                        <div class="subitem-list">
                                                            <?php if (empty($item['subitems'])): ?>
                                                                <div class="no-subitems">No sub-items added yet.</div>
                                                            <?php else: ?>
                                                                <?php foreach ($item['subitems'] as $subitem_index => $subitem): ?>
                                                                <div class="subitem">
                                                                    <div class="form-row">
                                                                        <div class="form-group">
                                                                            <label>Content</label>
                                                                            <textarea name="sections[<?= $section_index; ?>][items][<?= $item_index; ?>][subitems][<?= $subitem_index; ?>][content]" rows="2" required placeholder="Sub-item content"><?= htmlspecialchars($subitem['content']); ?></textarea>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Detailed Information</label>
                                                                            <textarea name="sections[<?= $section_index; ?>][items][<?= $item_index; ?>][subitems][<?= $subitem_index; ?>][detailed_info]" rows="3" placeholder="Additional information for this sub-item"><?= htmlspecialchars($subitem['detailed_info']); ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" class="remove-subitem-btn btn btn-sm btn-danger">Remove</button>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button type="button" class="add-subitem-btn btn btn-sm btn-outline">Add Sub-item</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="form-row">
                                    <button type="button" class="add-item-btn btn btn-outline" data-section-id="section-<?= $section_index; ?>">
                                        <span class="plus-icon">+</span> Add Item
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="add-section" class="btn btn-secondary">
                            <span class="plus-icon">+</span> Add Section
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="../protocols/index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
            
            <!-- Templates for dynamic content -->
            <template id="section-template">
                <div class="section" id="section-template-id">
                    <div class="section-header">
                        <h3>New Section</h3>
                        <button type="button" class="remove-btn" data-target="section-template-id">Remove</button>
                    </div>
                    <div class="section-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Section Type</label>
                                <select class="section-type-select" name="sections[0][type]" required>
                                    <option value="">Select Type</option>
                                    <option value="assessment">Assessment Boxes</option>
                                    <option value="flowchart">Flowchart</option>
                                    <option value="checklist">Checklist</option>
                                    <option value="information">Information</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Section Title</label>
                                <input type="text" name="sections[0][title]" required placeholder="e.g. Assessment Criteria">
                            </div>
                        </div>
                        
                        <div class="form-row type-specific type-information" style="display: none;">
                            <div class="form-group">
                                <label>Section Description</label>
                                <textarea name="sections[0][description]" rows="3" placeholder="General information about this section"></textarea>
                            </div>
                        </div>
                        
                        <div class="items-container">
                            <!-- Items will be added here dynamically -->
                        </div>
                        
                        <div class="form-row">
                            <button type="button" class="add-item-btn btn btn-outline" data-section-id="section-template-id">
                                <span class="plus-icon">+</span> Add Item
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            
            <template id="item-template">
                <div class="item" id="item-template-id">
                    <div class="item-header">
                        <h4>New Item</h4>
                        <button type="button" class="remove-btn" data-target="item-template-id">Remove</button>
                    </div>
                    <div class="item-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Item Title (for assessment boxes)</label>
                                <input type="text" name="sections[0][items][0][title]" placeholder="e.g. Inadequate Oxygenation">
                            </div>
                            
                            <div class="form-group">
                                <label>Content</label>
                                <textarea name="sections[0][items][0][content]" rows="2" required placeholder="Item content"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Detailed Information (for info modals)</label>
                                <textarea name="sections[0][items][0][detailed_info]" rows="4" placeholder="Additional information that will appear in a popup modal when the item is clicked"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="sections[0][items][0][is_decision]" value="1"> 
                                    This is a decision box (for flowcharts)
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <h5>Provider Levels</h5>
                            <div class="provider-options">
                                <?php foreach ($provider_levels as $level): ?>
                                    <div class="provider-option">
                                        <label class="provider-label" style="background-color: <?= $level['color_code']; ?>;">
                                            <input type="checkbox" class="provider-checkbox" data-provider-id="<?= $level['id']; ?>">
                                            <?= htmlspecialchars($level['shortname']); ?>
                                        </label>
                                        <input type="number" name="sections[0][items][0][providers][<?= $level['id']; ?>]" class="provider-percentage" value="0" min="0" max="100" style="display: none;">%
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="subitems-container">
                            <!-- For assessment criteria or similar nested items -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Sub-items (e.g. assessment criteria)</label>
                                    <div class="subitem-list">
                                        <div class="no-subitems">No sub-items added yet.</div>
                                    </div>
                                    <button type="button" class="add-subitem-btn btn btn-sm btn-outline">Add Sub-item</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <template id="subitem-template">
                <div class="subitem">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="sections[0][items][0][subitems][0][content]" rows="2" required placeholder="Sub-item content"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Detailed Information</label>
                            <textarea name="sections[0][items][0][subitems][0][detailed_info]" rows="3" placeholder="Additional information for this sub-item"></textarea>
                        </div>
                    </div>
                    <button type="button" class="remove-subitem-btn btn btn-sm btn-danger">Remove</button>
                </div>
            </template>
        </main>
    </div>
    
    <?php include '../includes/admin-footer.php'; ?>
    
    <script>
        // Initialize TinyMCE for rich text editors
        tinymce.init({
            selector: 'textarea[name*="detailed_info"]',
            height: 200,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | \
                     alignleft aligncenter alignright alignjustify | \
                     bullist numlist outdent indent | removeformat | help'
        });
    </script>
    <script src="../../assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle adding subitems
            document.addEventListener('click', function(e) {
                if (e.target.matches('.add-subitem-btn')) {
                    addSubitem(e.target);
                } else if (e.target.matches('.remove-subitem-btn')) {
                    e.target.closest('.subitem').remove();
                }
            });
            
            // Activate section type display toggling
            document.querySelectorAll('.section-type-select').forEach(select => {
                updateSectionFields(select);
            });
            
            // Helper function to add a subitem
            function addSubitem(button) {
                const container = button.closest('.form-group').querySelector('.subitem-list');
                const noSubitems = container.querySelector('.no-subitems');
                if (noSubitems) {
                    noSubitems.remove();
                }
                
                const template = document.getElementById('subitem-template');
                const newSubitem = template.content.cloneNode(true);
                
                // Update the name attributes to use the correct indices
                const item = button.closest('.item');
                const section = item.closest('.section');
                const sectionIndex = Array.from(section.parentNode.children).indexOf(section);
                const itemIndex = Array.from(item.parentNode.children).indexOf(item);
                const subitemIndex = container.querySelectorAll('.subitem').length;
                
                newSubitem.querySelectorAll('[name]').forEach(input => {
                    input.name = input.name
                        .replace('sections[0]', `sections[${sectionIndex}]`)
                        .replace('items[0]', `items[${itemIndex}]`)
                        .replace('subitems[0]', `subitems[${subitemIndex}]`);
                });
                
                container.appendChild(newSubitem);
            }
            
            // Helper function to update section fields based on type
            function updateSectionFields(select) {
                const section = select.closest('.section');
                const sectionType = select.value;
                
                // Hide all type-specific fields
                section.querySelectorAll('.type-specific').forEach(field => {
                    field.style.display = 'none';
                });
                
                // Show fields specific to the selected type
                if (sectionType) {
                    section.querySelectorAll('.type-' + sectionType).forEach(field => {
                        field.style.display = 'block';
                    });
                }
            }
            
            // Fix the indices in form before submission
            document.getElementById('protocol-form').addEventListener('submit', function(e) {
                // Update section indices
                const sections = document.querySelectorAll('#sections-container .section');
                sections.forEach((section, sectionIndex) => {
                    section.querySelectorAll('[name^="sections["]').forEach(input => {
                        input.name = input.name.replace(/sections\[\d+\]/, `sections[${sectionIndex}]`);
                    });
                    
                    // Update item indices within this section
                    const items = section.querySelectorAll('.items-container .item');
                    items.forEach((item, itemIndex) => {
                        item.querySelectorAll('[name^="sections["][name*="items["]').forEach(input => {
                            input.name = input.name.replace(/items\[\d+\]/, `items[${itemIndex}]`);
                        });
                        
                        // Update subitem indices within this item
                        const subitems = item.querySelectorAll('.subitem-list .subitem');
                        subitems.forEach((subitem, subitemIndex) => {
                            subitem.querySelectorAll('[name^="sections["][name*="items["][name*="subitems["]').forEach(input => {
                                input.name = input.name.replace(/subitems\[\d+\]/, `subitems[${subitemIndex}]`);
                            });
                        });
                    });
                });
            });
        });
    </script>
</body>
</html>