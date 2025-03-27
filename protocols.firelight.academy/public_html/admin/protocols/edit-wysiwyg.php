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
$db->query('SELECT p.*, c.name as category_name, c.id as category_id 
            FROM protocols p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id');
$db->bind(':id', $protocol_id);
$protocol = $db->single();

if (!$protocol) {
    flash_message('Protocol not found.', 'danger');
    redirect('../protocols/index.php');
}

// Get protocol sections
$sections = get_protocol_sections($protocol_id);

// Get provider levels for reference
$db->query("SELECT * FROM provider_levels ORDER BY sort_order");
$provider_levels = $db->resultSet();

// Get all categories for dropdown
$db->query('SELECT * FROM categories ORDER BY sort_order, category_number');
$categories = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit: <?= htmlspecialchars($protocol['title']); ?> - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/editor.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        .editor-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #2c3e50;
            color: white;
            padding: 10px 20px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .editor-actions {
            display: flex;
            gap: 10px;
        }
        
        body {
            padding-top: 60px;
        }
        
        .editable {
            position: relative;
            border: 2px dashed transparent;
            transition: all 0.2s;
            min-height: 30px;
        }
        
        .editable:hover {
            border-color: #006699;
        }
        
        .edit-controls {
            position: absolute;
            right: 5px;
            top: 5px;
            display: none;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .editable:hover .edit-controls {
            display: flex;
        }
        
        .edit-btn, .delete-btn, .move-up-btn, .move-down-btn {
            width: 24px;
            height: 24px;
            background-color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: all 0.2s;
        }
        
        .edit-btn:hover, .delete-btn:hover, .move-up-btn:hover, .move-down-btn:hover {
            opacity: 1;
            background-color: #f0f0f0;
        }
        
        .delete-btn {
            color: #dc3545;
        }
        
        .add-btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            background-color: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .add-btn:hover {
            background-color: #e9ecef;
            border-color: #006699;
        }
        
        .add-btn-icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            margin-right: 5px;
            background-image: url('../../assets/images/icons/add.svg');
            background-size: contain;
            background-repeat: no-repeat;
            vertical-align: middle;
        }
        
        /* Modal styles for editing */
        .edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .edit-modal-content {
            position: relative;
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.3);
        }
        
        .edit-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .edit-modal-close {
            font-size: 1.5rem;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .quill-editor {
            height: 200px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Placeholder styles */
        .placeholder {
            padding: 20px;
            background-color: #f8f9fa;
            border: 2px dashed #ddd;
            text-align: center;
            color: #6c757d;
            margin: 20px 0;
            border-radius: 8px;
        }
        
        /* Provider level selector styles */
        .provider-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .provider-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .provider-label {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .provider-percentage {
            width: 60px;
            text-align: center;
        }
        
        /* Saving indicator */
        .saving-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Editor Toolbar -->
    <div class="editor-toolbar">
        <div class="editor-info">
            <strong>Editing:</strong> <?= htmlspecialchars($protocol['title']); ?>
        </div>
        <div class="editor-actions">
            <button id="save-protocol" class="btn btn-primary">Save Protocol</button>
            <a href="../protocols/index.php" class="btn btn-outline">Exit Editor</a>
        </div>
    </div>
    
    <!-- Main Protocol Content -->
    <header>
        <div class="container">
            <div class="logo-container">
                <img src="../../assets/images/logo.png" alt="Northern Colorado Prehospital Protocols" class="logo">
                <h1 class="editable" data-field="title" data-type="text" data-id="<?= $protocol['id']; ?>">
                    <?= htmlspecialchars($protocol['title']); ?>
                    <div class="edit-controls">
                        <button class="edit-btn" title="Edit Title"><i class="icon-edit"></i></button>
                    </div>
                </h1>
            </div>
        </div>
        
        <div class="provider-levels">
            <?php foreach ($provider_levels as $level): ?>
                <div class="provider-level <?= strtolower($level['shortname']); ?>">
                    <?= htmlspecialchars($level['shortname']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </header>
    
    <div class="container">
        <div class="protocol-controls">
            <div class="form-row">
                <div class="form-group">
                    <label for="protocol_number">Protocol Number:</label>
                    <input type="text" id="protocol_number" value="<?= htmlspecialchars($protocol['protocol_number']); ?>" 
                           data-field="protocol_number" data-type="text" data-id="<?= $protocol['id']; ?>" class="inline-edit">
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select id="category_id" data-field="category_id" data-type="select" data-id="<?= $protocol['id']; ?>" class="inline-edit">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id']; ?>" <?= ($category['id'] == $protocol['category_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category['category_number'] . '. ' . $category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="is_published" value="1" <?= $protocol['is_published'] ? 'checked' : ''; ?> 
                               data-field="is_published" data-type="checkbox" data-id="<?= $protocol['id']; ?>" class="inline-edit">
                        Publish this protocol
                    </label>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" rows="2" data-field="description" data-type="textarea" 
                              data-id="<?= $protocol['id']; ?>" class="inline-edit"><?= htmlspecialchars($protocol['description']); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="protocol-container">
            <?php 
            if (empty($sections)): 
            ?>
                <div class="placeholder">No sections added yet. Use the buttons below to add sections.</div>
            <?php 
            else:
                // Render each section based on its type
                foreach ($sections as $section):
                    $section_id = $section['id'];
                    
                    if ($section['section_type'] === 'assessment'): 
                        // Render assessment section (left column)
            ?>
                <div class="assessment-column editable-section" data-section-id="<?= $section_id; ?>" data-section-type="assessment">
                    <div class="section-header editable" data-field="title" data-type="text" data-section-id="<?= $section_id; ?>">
                        <h2><?= htmlspecialchars($section['title']); ?></h2>
                        <div class="edit-controls">
                            <button class="edit-btn" title="Edit Title"><i class="icon-edit"></i></button>
                            <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                            <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                            <button class="delete-btn" title="Delete Section"><i class="icon-trash"></i></button>
                        </div>
                    </div>
                    
                    <?php 
                    // Get assessment boxes
                    $db->query('SELECT * FROM protocol_items WHERE section_id = :section_id AND parent_id IS NULL ORDER BY sort_order');
                    $db->bind(':section_id', $section_id);
                    $items = $db->resultSet();
                    
                    foreach ($items as $item):
                        $item_id = $item['id'];
                        
                        // Get provider levels for this item
                        $db->query('SELECT p.id, ipl.percentage FROM provider_levels p 
                                  JOIN item_provider_levels ipl ON p.id = ipl.provider_id 
                                  WHERE ipl.item_id = :item_id');
                        $db->bind(':item_id', $item_id);
                        $item_providers = $db->resultSet();
                    ?>
                        <div class="assessment-box editable-item" data-item-id="<?= $item_id; ?>" data-info="<?= htmlspecialchars($item['detailed_info']); ?>">
                            <div class="assessment-title editable" data-field="title" data-type="text" data-item-id="<?= $item_id; ?>">
                                <?= htmlspecialchars($item['title']); ?>
                                <div class="edit-controls">
                                    <button class="edit-btn" title="Edit Title"><i class="icon-edit"></i></button>
                                    <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                    <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                    <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                    <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                </div>
                            </div>
                            
                            <?php 
                            // Get sub-items (criteria)
                            $db->query('SELECT * FROM protocol_items WHERE parent_id = :parent_id ORDER BY sort_order');
                            $db->bind(':parent_id', $item_id);
                            $criteria = $db->resultSet();
                            
                            if (!empty($criteria)):
                            ?>
                                <ul class="assessment-criteria">
                                    <?php foreach ($criteria as $criterion): 
                                        $criterion_id = $criterion['id'];
                                    ?>
                                        <li class="editable-subitem" data-subitem-id="<?= $criterion_id; ?>" data-info="<?= htmlspecialchars($criterion['detailed_info']); ?>">
                                            <span class="editable" data-field="content" data-type="text" data-subitem-id="<?= $criterion_id; ?>">
                                                <?= htmlspecialchars($criterion['content']); ?>
                                            </span>
                                            <div class="edit-controls">
                                                <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                                <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                                <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                                <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                                <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <button class="add-criteria-btn btn btn-sm btn-outline" data-item-id="<?= $item_id; ?>">Add Criterion</button>
                        </div>
                    <?php endforeach; ?>
                    
                    <button class="add-item-btn btn btn-outline" data-section-id="<?= $section_id; ?>" data-item-type="assessment">Add Assessment Box</button>
                </div>
            <?php 
                    elseif ($section['section_type'] === 'flowchart'):
                        // Render flowchart section (right column)
            ?>
                <div class="flowchart-column editable-section" data-section-id="<?= $section_id; ?>" data-section-type="flowchart">
                    <div class="section-header editable" data-field="title" data-type="text" data-section-id="<?= $section_id; ?>">
                        <h2><?= htmlspecialchars($section['title']); ?></h2>
                        <div class="edit-controls">
                            <button class="edit-btn" title="Edit Title"><i class="icon-edit"></i></button>
                            <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                            <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                            <button class="delete-btn" title="Delete Section"><i class="icon-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="flowchart">
                        <?php 
                        // Get flowchart steps
                        $db->query('SELECT * FROM protocol_items WHERE section_id = :section_id AND parent_id IS NULL ORDER BY sort_order');
                        $db->bind(':section_id', $section_id);
                        $steps = $db->resultSet();
                        
                        foreach ($steps as $index => $step):
                            $step_id = $step['id'];
                            
                            // Get provider levels for this step
                            $db->query('SELECT p.id, ipl.percentage FROM provider_levels p 
                                      JOIN item_provider_levels ipl ON p.id = ipl.provider_id 
                                      WHERE ipl.item_id = :item_id');
                            $db->bind(':item_id', $step_id);
                            $step_providers = $db->resultSet();
                            
                            if ($step['is_decision']):
                        ?>
                                <div class="decision-box editable-item" data-item-id="<?= $step_id; ?>" data-info="<?= htmlspecialchars($step['detailed_info']); ?>">
                                    <?php if (!empty($step_providers)): ?>
                                        <div class="provider-bar">
                                            <?php foreach ($step_providers as $provider): 
                                                $provider_level = get_provider_level($provider['id']);
                                                $width = $provider['percentage'] ?: (100 / count($step_providers));
                                                $provider_class = strtolower($provider_level['shortname']);
                                            ?>
                                                <div class="provider-segment <?= $provider_class; ?>" style="width: <?= $width; ?>%"></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <span class="editable" data-field="content" data-type="text" data-item-id="<?= $step_id; ?>">
                                        <?= htmlspecialchars($step['content']); ?>
                                    </span>
                                    
                                    <div class="edit-controls">
                                        <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                        <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                        <button class="edit-providers-btn" title="Edit Provider Levels"><i class="icon-users"></i></button>
                                        <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                        <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                        <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                    </div>
                                </div>
                                
                                <?php
                                // Check if next items are branches (yes/no paths)
                                if (isset($steps[$index+1]) && isset($steps[$index+2])):
                                    $yes_path = $steps[$index+1];
                                    $no_path = $steps[$index+2];
                                    $yes_id = $yes_path['id'];
                                    $no_id = $no_path['id'];
                                    
                                    // Get providers for yes path
                                    $db->query('SELECT p.id, ipl.percentage FROM provider_levels p 
                                              JOIN item_provider_levels ipl ON p.id = ipl.provider_id 
                                              WHERE ipl.item_id = :item_id');
                                    $db->bind(':item_id', $yes_id);
                                    $yes_providers = $db->resultSet();
                                    
                                    // Get providers for no path
                                    $db->query('SELECT p.id, ipl.percentage FROM provider_levels p 
                                              JOIN item_provider_levels ipl ON p.id = ipl.provider_id 
                                              WHERE ipl.item_id = :item_id');
                                    $db->bind(':item_id', $no_id);
                                    $no_providers = $db->resultSet();
                                ?>
                                <div class="yes-no-container">
                                    <div class="yes-path">
                                        <div class="path-label">Yes</div>
                                        <div class="flow-arrow"></div>
                                        
                                        <?php if (strpos($yes_path['content'], 'protocol') !== false): ?>
                                            <div class="protocol-link editable-item" data-item-id="<?= $yes_id; ?>" data-info="<?= htmlspecialchars($yes_path['detailed_info']); ?>">
                                                <span class="editable" data-field="content" data-type="text" data-item-id="<?= $yes_id; ?>">
                                                    <?= htmlspecialchars($yes_path['content']); ?>
                                                </span>
                                                <div class="edit-controls">
                                                    <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                                    <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                                    <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                                    <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                                    <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="flow-step editable-item" data-item-id="<?= $yes_id; ?>" data-info="<?= htmlspecialchars($yes_path['detailed_info']); ?>">
                                                <?php if (!empty($yes_providers)): ?>
                                                    <div class="provider-bar">
                                                        <?php foreach ($yes_providers as $provider): 
                                                            $provider_level = get_provider_level($provider['id']);
                                                            $width = $provider['percentage'] ?: (100 / count($yes_providers));
                                                            $provider_class = strtolower($provider_level['shortname']);
                                                        ?>
                                                            <div class="provider-segment <?= $provider_class; ?>" style="width: <?= $width; ?>%"></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <span class="editable" data-field="content" data-type="text" data-item-id="<?= $yes_id; ?>">
                                                    <?= htmlspecialchars($yes_path['content']); ?>
                                                </span>
                                                
                                                <div class="edit-controls">
                                                    <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                                    <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                                    <button class="edit-providers-btn" title="Edit Provider Levels"><i class="icon-users"></i></button>
                                                    <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                                    <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                                    <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="no-path">
                                        <div class="path-label">No</div>
                                        <div class="flow-arrow"></div>
                                        
                                        <?php if (strpos($no_path['content'], 'protocol') !== false): ?>
                                            <div class="protocol-link editable-item" data-item-id="<?= $no_id; ?>" data-info="<?= htmlspecialchars($no_path['detailed_info']); ?>">
                                                <span class="editable" data-field="content" data-type="text" data-item-id="<?= $no_id; ?>">
                                                    <?= htmlspecialchars($no_path['content']); ?>
                                                </span>
                                                <div class="edit-controls">
                                                    <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                                    <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                                    <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                                    <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                                    <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="flow-step editable-item" data-item-id="<?= $no_id; ?>" data-info="<?= htmlspecialchars($no_path['detailed_info']); ?>">
                                                <?php if (!empty($no_providers)): ?>
                                                    <div class="provider-bar">
                                                        <?php foreach ($no_providers as $provider): 
                                                            $provider_level = get_provider_level($provider['id']);
                                                            $width = $provider['percentage'] ?: (100 / count($no_providers));
                                                            $provider_class = strtolower($provider_level['shortname']);
                                                        ?>
                                                            <div class="provider-segment <?= $provider_class; ?>" style="width: <?= $width; ?>%"></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <span class="editable" data-field="content" data-type="text" data-item-id="<?= $no_id; ?>">
                                                    <?= htmlspecialchars($no_path['content']); ?>
                                                </span>
                                                
                                                <div class="edit-controls">
                                                    <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                                    <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                                    <button class="edit-providers-btn" title="Edit Provider Levels"><i class="icon-users"></i></button>
                                                    <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                                    <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                                    <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php 
                                    // Skip the next two items since we've rendered them as branches
                                    $index += 2;
                                endif;
                            else:
                                // Regular step
                                ?>
                                <div class="flow-step editable-item" data-item-id="<?= $step_id; ?>" data-info="<?= htmlspecialchars($step['detailed_info']); ?>">
                                    <?php if (!empty($step_providers)): ?>
                                        <div class="provider-bar">
                                            <?php foreach ($step_providers as $provider): 
                                                $provider_level = get_provider_level($provider['id']);
                                                $width = $provider['percentage'] ?: (100 / count($step_providers));
                                                $provider_class = strtolower($provider_level['shortname']);
                                            ?>
                                                <div class="provider-segment <?= $provider_class; ?>" style="width: <?= $width; ?>%"></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <span class="editable" data-field="content" data-type="text" data-item-id="<?= $step_id; ?>">
                                        <?= htmlspecialchars($step['content']); ?>
                                    </span>
                                    
                                    <div class="edit-controls">
                                        <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                        <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                        <button class="edit-providers-btn" title="Edit Provider Levels"><i class="icon-users"></i></button>
                                        <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                        <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                        <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                    </div>
                                </div>
                                
                                <?php if (isset($steps[$index+1]) && !$steps[$index+1]['is_decision']): ?>
                                    <div class="flow-arrow"></div>
                                <?php endif; ?>
                                
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="flowchart-controls">
                        <button class="add-item-btn btn btn-outline" data-section-id="<?= $section_id; ?>" data-item-type="flow-step">Add Step</button>
                        <button class="add-item-btn btn btn-outline" data-section-id="<?= $section_id; ?>" data-item-type="decision">Add Decision</button>
                    </div>
                </div>
            <?php 
                elseif ($section['section_type'] === 'checklist'):
                    // Render checklist section
            ?>
                <div class="section editable-section" data-section-id="<?= $section_id; ?>" data-section-type="checklist">
                    <div class="section-header editable" data-field="title" data-type="text" data-section-id="<?= $section_id; ?>">
                        <h2><?= htmlspecialchars($section['title']); ?></h2>
                        <div class="edit-controls">
                            <button class="edit-btn" title="Edit Title"><i class="icon-edit"></i></button>
                            <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                            <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                            <button class="delete-btn" title="Delete Section"><i class="icon-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="checklist-items">
                        <?php 
                        // Get checklist items
                        $db->query('SELECT * FROM protocol_items WHERE section_id = :section_id AND parent_id IS NULL ORDER BY sort_order');
                        $db->bind(':section_id', $section_id);
                        $items = $db->resultSet();
                        
                        foreach ($items as $item):
                            $item_id = $item['id'];
                            
                            // Get provider levels for this item
                            $db->query('SELECT p.id, ipl.percentage FROM provider_levels p 
                                      JOIN item_provider_levels ipl ON p.id = ipl.provider_id 
                                      WHERE ipl.item_id = :item_id');
                            $db->bind(':item_id', $item_id);
                            $item_providers = $db->resultSet();
                        ?>
                            <div class="checklist-item editable-item" data-item-id="<?= $item_id; ?>" data-info="<?= htmlspecialchars($item['detailed_info']); ?>">
                                <label class="checkbox-container">
                                    <input type="checkbox">
                                    <span class="checkmark"></span>
                                    <span class="editable" data-field="content" data-type="text" data-item-id="<?= $item_id; ?>">
                                        <?= htmlspecialchars($item['content']); ?>
                                    </span>
                                </label>
                                
                                <div class="edit-controls">
                                    <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                    <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                    <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                    <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                    <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button class="add-item-btn btn btn-outline" data-section-id="<?= $section_id; ?>" data-item-type="checklist">Add Checklist Item</button>
                </div>
            <?php 
                elseif ($section['section_type'] === 'information'):
                    // Render information section
            ?>
                <div class="info-section editable-section" data-section-id="<?= $section_id; ?>" data-section-type="information">
                    <div class="section-header editable" data-field="title" data-type="text" data-section-id="<?= $section_id; ?>">
                        <h2><?= htmlspecialchars($section['title']); ?></h2>
                        <div class="edit-controls">
                            <button class="edit-btn" title="Edit Title"><i class="icon-edit"></i></button>
                            <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                            <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                            <button class="delete-btn" title="Delete Section"><i class="icon-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="info-content">
                        <div class="editable" data-field="description" data-type="textarea" data-section-id="<?= $section_id; ?>">
                            <?= htmlspecialchars($section['description']); ?>
                            <div class="edit-controls">
                                <button class="edit-btn" title="Edit Description"><i class="icon-edit"></i></button>
                            </div>
                        </div>
                        
                        <?php 
                        // Get information items
                        $db->query('SELECT * FROM protocol_items WHERE section_id = :section_id AND parent_id IS NULL ORDER BY sort_order');
                        $db->bind(':section_id', $section_id);
                        $items = $db->resultSet();
                        
                        if (!empty($items)):
                        ?>
                            <ul class="info-list">
                                <?php foreach ($items as $item): 
                                    $item_id = $item['id'];
                                ?>
                                    <li class="editable-item" data-item-id="<?= $item_id; ?>" data-info="<?= htmlspecialchars($item['detailed_info']); ?>">
                                        <span class="editable" data-field="content" data-type="text" data-item-id="<?= $item_id; ?>">
                                            <?= htmlspecialchars($item['content']); ?>
                                        </span>
                                        <div class="edit-controls">
                                            <button class="edit-btn" title="Edit Content"><i class="icon-edit"></i></button>
                                            <button class="edit-info-btn" title="Edit Detailed Info"><i class="icon-info"></i></button>
                                            <button class="move-up-btn" title="Move Up"><i class="icon-arrow-up"></i></button>
                                            <button class="move-down-btn" title="Move Down"><i class="icon-arrow-down"></i></button>
                                            <button class="delete-btn" title="Delete Item"><i class="icon-trash"></i></button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <button class="add-item-btn btn btn-outline" data-section-id="<?= $section_id; ?>" data-item-type="info">Add Information Item</button>
                </div>
            <?php 
                endif;
            endforeach; 
            endif;
            ?>
        </div>
        
        <!-- Add Section Controls -->
        <div class="add-section-controls">
            <button class="add-btn" id="add-section-btn">
                <span class="add-btn-icon"></span> Add New Section
            </button>
        </div>
        
        <p class="note-disclaimer">This is not intended to be a comprehensive guide. Always follow local protocols and medical direction.</p>
    </div>
    
    <!-- Edit Modals -->
    <!-- Text Edit Modal -->
    <div class="edit-modal" id="text-edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Edit Content</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <input type="hidden" id="edit-field-type">
                <input type="hidden" id="edit-field-name">
                <input type="hidden" id="edit-id-type">
                <input type="hidden" id="edit-id-value">
                
                <div class="form-group">
                    <input type="text" id="text-edit-input" class="form-control">
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-primary save-edit" id="save-text-edit">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Textarea Edit Modal -->
    <div class="edit-modal" id="textarea-edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Edit Content</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <input type="hidden" id="textarea-field-type">
                <input type="hidden" id="textarea-field-name">
                <input type="hidden" id="textarea-id-type">
                <input type="hidden" id="textarea-id-value">
                
                <div class="form-group">
                    <textarea id="textarea-edit-input" class="form-control" rows="5"></textarea>
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-primary save-edit" id="save-textarea-edit">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rich Text Edit Modal (for detailed info) -->
    <div class="edit-modal" id="rich-edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Edit Detailed Information</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <input type="hidden" id="rich-id-type">
                <input type="hidden" id="rich-id-value">
                
                <div class="form-group">
                    <div id="quill-editor" class="quill-editor"></div>
                    <input type="hidden" id="rich-edit-input">
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-primary save-edit" id="save-rich-edit">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Provider Levels Edit Modal -->
    <div class="edit-modal" id="providers-edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Edit Provider Levels</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <input type="hidden" id="providers-id-value">
                
                <div class="provider-selector">
                    <?php foreach ($provider_levels as $level): ?>
                        <div class="provider-option">
                            <label class="provider-label" style="background-color: <?= $level['color_code']; ?>;">
                                <input type="checkbox" class="provider-checkbox" data-provider-id="<?= $level['id']; ?>">
                                <?= htmlspecialchars($level['shortname']); ?>
                            </label>
                            <input type="number" name="provider_percentage_<?= $level['id']; ?>" class="provider-percentage" value="0" min="0" max="100">%
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-primary save-edit" id="save-providers-edit">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Section Modal -->
    <div class="edit-modal" id="add-section-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Add New Section</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <div class="form-group">
                    <label for="section-type">Section Type</label>
                    <select id="section-type" class="form-control">
                        <option value="">Select Section Type</option>
                        <option value="assessment">Assessment Boxes</option>
                        <option value="flowchart">Flowchart</option>
                        <option value="checklist">Checklist</option>
                        <option value="information">Information</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="section-title">Section Title</label>
                    <input type="text" id="section-title" class="form-control" placeholder="e.g. Assessment Criteria">
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-primary" id="save-new-section">Add Section</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Item Modal -->
    <div class="edit-modal" id="add-item-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Add New Item</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <input type="hidden" id="parent-section-id">
                <input type="hidden" id="item-type">
                
                <div class="form-group" id="item-title-group">
                    <label for="item-title">Item Title</label>
                    <input type="text" id="item-title" class="form-control" placeholder="e.g. Inadequate Oxygenation">
                </div>
                
                <div class="form-group">
                    <label for="item-content">Content</label>
                    <textarea id="item-content" class="form-control" rows="3" placeholder="Item content"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="item-detailed-info">Detailed Information</label>
                    <div id="item-detailed-info" class="quill-editor"></div>
                    <input type="hidden" id="item-detailed-info-value">
                </div>
                
                <div class="form-group checkbox-group" id="decision-checkbox-group" style="display: none;">
                    <label>
                        <input type="checkbox" id="is-decision" value="1">
                        This is a decision box (for flowcharts)
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="item-providers">Provider Levels</label>
                    <div class="provider-selector" id="item-providers">
                        <?php foreach ($provider_levels as $level): ?>
                            <div class="provider-option">
                                <label class="provider-label" style="background-color: <?= $level['color_code']; ?>;">
                                    <input type="checkbox" class="provider-checkbox" data-provider-id="<?= $level['id']; ?>">
                                    <?= htmlspecialchars($level['shortname']); ?>
                                </label>
                                <input type="number" name="new_provider_percentage_<?= $level['id']; ?>" class="provider-percentage" value="0" min="0" max="100" style="display: none;">%
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-primary" id="save-new-item">Add Item</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Criterion Modal -->
    <div class="edit-modal" id="add-criterion-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Add New Criterion</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <input type="hidden" id="parent-item-id">
                
                <div class="form-group">
                    <label for="criterion-content">Content</label>
                    <textarea id="criterion-content" class="form-control" rows="3" placeholder="Criterion content"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="criterion-detailed-info">Detailed Information</label>
                    <div id="criterion-detailed-info" class="quill-editor"></div>
                    <input type="hidden" id="criterion-detailed-info-value">
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-primary" id="save-new-criterion">Add Criterion</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="edit-modal" id="delete-confirm-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Confirm Delete</h3>
                <button class="edit-modal-close">&times;</button>
            </div>
            <div class="edit-modal-body">
                <input type="hidden" id="delete-type">
                <input type="hidden" id="delete-id">
                
                <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                
                <div class="form-actions">
                    <button class="btn btn-outline cancel-edit">Cancel</button>
                    <button class="btn btn-danger" id="confirm-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Saving Indicator -->
    <div class="saving-indicator" id="saving-indicator">
        <span>Saving...</span>
    </div>
    
    <script>
        // Helper function to get a provider level by ID
        function get_provider_level($id) {
            <?php 
            echo "const providerLevels = ". json_encode($provider_levels) . ";";
            ?>
            return providerLevels.find(level => level.id == $id) || {};
        }
    </script>
    
    <script src="../../assets/js/editor.js"></script>
</body>
</html>