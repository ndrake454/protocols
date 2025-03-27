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
    flash_message('You do not have permission to create protocols.', 'danger');
    redirect('../index.php');
}

// Get categories for dropdown
$db->query('SELECT * FROM categories ORDER BY sort_order, category_number');
$categories = $db->resultSet();

// Get provider levels
$db->query('SELECT * FROM provider_levels ORDER BY sort_order');
$provider_levels = $db->resultSet();

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
        
        // Insert protocol record
        $db->query('INSERT INTO protocols (category_id, protocol_number, title, description, created_by, updated_by, is_published) VALUES (:category_id, :protocol_number, :title, :description, :created_by, :updated_by, :is_published)');
        $db->bind(':category_id', $category_id);
        $db->bind(':protocol_number', $protocol_number);
        $db->bind(':title', $title);
        $db->bind(':description', $description);
        $db->bind(':created_by', $_SESSION['user_id']);
        $db->bind(':updated_by', $_SESSION['user_id']);
        $db->bind(':is_published', $is_published);
        $db->execute();
        
        $protocol_id = $db->lastInsertId();
        
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
        $db->bind(':revision_notes', 'Initial creation');
        $db->execute();
        
        // Commit transaction
        $db->endTransaction();
        
        flash_message('Protocol created successfully!', 'success');
        redirect('../protocols/edit.php?id=' . $protocol_id);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->cancelTransaction();
        flash_message('Error creating protocol: ' . $e->getMessage(), 'danger');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Protocol - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<style>
    .quill-editor {
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .quill-editor .ql-toolbar {
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #ddd;
    }
    .quill-editor .ql-container {
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
        height: 200px;
    }
</style>
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Create New Protocol</h1>
                <div class="header-actions">
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
                        <input type="text" id="protocol_number" name="protocol_number" required placeholder="e.g. 2001.1">
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Protocol Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g. Adult Respiratory Distress">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['category_number'] . '. ' . $category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" rows="3" placeholder="Brief description of this protocol"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_published" value="1"> 
                            Publish this protocol (immediately visible to users)
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <hr>
                    <h2>Protocol Content</h2>
                    <p class="help-text">Build your protocol by adding sections and items below.</p>
                </div>
                
                <div id="protocol-editor">
                    <div id="sections-container">
                        <!-- Sections will be added here dynamically -->
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="add-section" class="btn btn-secondary">
                            <span class="plus-icon">+</span> Add Section
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="../protocols/index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Protocol</button>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Quill editor for rich text fields
        const richTextFields = document.querySelectorAll('textarea[name*="detailed_info"]');
        const quillInstances = [];
        
        richTextFields.forEach(field => {
            // Create container element
            const container = document.createElement('div');
            container.className = 'quill-editor';
            field.parentNode.insertBefore(container, field);
            
            // Hide the original textarea
            field.style.display = 'none';
            
            // Initialize Quill
            const quill = new Quill(container, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'header': [1, 2, 3, false] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'clean']
                    ]
                }
            });
            
            // Set initial content from textarea if it exists
            if (field.value) {
                quill.root.innerHTML = field.value;
            }
            
            // Store quill instance for later use
            quillInstances.push({ quill, field });
        });
        
        // Update textareas on form submit
        const form = document.getElementById('protocol-form');
        if (form) {
            form.addEventListener('submit', function() {
                quillInstances.forEach(instance => {
                    instance.field.value = instance.quill.root.innerHTML;
                });
            });
        }
    });
</script>
    <script src="../../assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add an initial empty section when the page loads
            document.getElementById('add-section').click();
            
            // Handle adding subitems
            document.addEventListener('click', function(e) {
                if (e.target.matches('.add-subitem-btn')) {
                    addSubitem(e.target);
                } else if (e.target.matches('.remove-subitem-btn')) {
                    e.target.closest('.subitem').remove();
                }
            });
            
            // Helper function to add a subitem
            function addSubitem(button) {
                const container = button.closest('.subitem-list');
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
            
            // Fix the indices in form before submission
            document.getElementById('protocol-form').addEventListener('submit', function(e) {
                // Update section indices
                const sections = document.querySelectorAll('#sections-container .section');
                sections.forEach((section, sectionIndex) => {
                    section.querySelectorAll('[name^="sections["]').forEach(input => {
                        input.name = input.name.replace(/sections\[\d+\]/, `sections[${sectionIndex}]`);
                    });
                    
                    // Update item indices within this section
                    const items = section.querySelectorAll('.item');
                    items.forEach((item, itemIndex) => {
                        item.querySelectorAll('[name^="sections["][name*="items["]').forEach(input => {
                            input.name = input.name.replace(/items\[\d+\]/, `items[${itemIndex}]`);
                        });
                        
                        // Update subitem indices within this item
                        const subitems = item.querySelectorAll('.subitem');
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