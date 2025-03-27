<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();

// Start session
session_start([
    'name' => SESSION_NAME,
    'cookie_lifetime' => SESSION_LIFETIME
]);

// Get protocol ID from URL
$protocol_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$protocol_id) {
    redirect('index.php');
}

// Get protocol details
$db->query("SELECT p.*, c.name as category_name, c.id as category_id 
            FROM protocols p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id AND p.is_published = 1");
$db->bind(':id', $protocol_id);
$protocol = $db->single();

if (!$protocol) {
    redirect('index.php');
}

// Get protocol sections
$sections = get_protocol_sections($protocol_id);

// Get provider levels for reference
$db->query("SELECT * FROM provider_levels ORDER BY sort_order");
$provider_levels = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($protocol['title']); ?> - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="assets/images/favicon.ico">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo-container">
                <img src="assets/images/logo.png" alt="Northern Colorado Prehospital Protocols" class="logo">
                <h1>Northern Colorado <span>Prehospital Protocols</span></h1>
            </div>
            <div class="search-container">
                <form action="search.php" method="GET">
                    <input type="text" name="q" placeholder="Search protocols...">
                    <button type="submit"><i class="search-icon"></i></button>
                </form>
            </div>
        </div>
    </header>
    
    <nav class="breadcrumb">
        <div class="container">
            <a href="index.php">Home</a> &gt;
            <a href="category.php?id=<?= $protocol['category_id']; ?>"><?= htmlspecialchars($protocol['category_name']); ?></a> &gt;
            <span><?= htmlspecialchars($protocol['title']); ?></span>
        </div>
    </nav>
    
    <main class="container">
        <section class="protocol-header">
            <h1><?= htmlspecialchars($protocol['title']); ?></h1>
            <div class="provider-levels">
                <?php foreach ($provider_levels as $level): ?>
                    <div class="provider-level <?= strtolower($level['shortname']); ?>">
                        <?= htmlspecialchars($level['shortname']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="updated-info">Last updated: <?= format_date($protocol['last_updated']); ?></p>
        </section>
        
        <div class="protocol-container">
            <?php 
            // Render each section based on its type
            foreach ($sections as $section):
                if ($section['section_type'] === 'assessment'): 
                    // Render assessment section (left column)
            ?>
                <div class="assessment-column">
                    <?php 
                    // Get assessment boxes
                    $items = get_section_items($section['id']);
                    foreach ($items as $item):
                        $item_providers = get_item_providers($item['id']);
                    ?>
                        <div class="assessment-box" data-info="<?= htmlspecialchars($item['detailed_info']); ?>">
                            <div class="assessment-title"><?= htmlspecialchars($item['title']); ?></div>
                            <?php 
                            // Get assessment criteria (sub-items)
                            $criteria = get_section_items($section['id'], $item['id']);
                            if (!empty($criteria)):
                            ?>
                                <ul class="assessment-criteria">
                                    <?php foreach ($criteria as $criterion): ?>
                                        <li data-info="<?= htmlspecialchars($criterion['detailed_info']); ?>">
                                            <?= htmlspecialchars($criterion['content']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php 
                elseif ($section['section_type'] === 'flowchart'):
                    // Render flowchart section (right column)
            ?>
                <div class="flowchart-column">
                    <div class="flowchart">
                        <?php 
                        // Get flowchart steps
                        $steps = get_section_items($section['id']);
                        foreach ($steps as $index => $step):
                            $step_providers = get_item_providers($step['id']);
                            
                            if ($step['is_decision']):
                                // Decision box
                        ?>
                                <div class="decision-box" data-info="<?= htmlspecialchars($step['detailed_info']); ?>">
                                    <?php if (!empty($step_providers)): ?>
                                        <?php render_provider_bar($step_providers); ?>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($step['content']); ?>
                                </div>
                                
                                <?php
                                // Check if next items are branches (yes/no paths)
                                if (isset($steps[$index+1]) && isset($steps[$index+2])):
                                    $yes_path = $steps[$index+1];
                                    $no_path = $steps[$index+2];
                                ?>
                                <div class="yes-no-container">
                                    <div class="yes-path">
                                        <div class="path-label">Yes</div>
                                        <div class="flow-arrow"></div>
                                        <?php if (strpos($yes_path['content'], 'protocol') !== false): ?>
                                            <div class="protocol-link" data-info="<?= htmlspecialchars($yes_path['detailed_info']); ?>">
                                                <?= htmlspecialchars($yes_path['content']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="flow-step" data-info="<?= htmlspecialchars($yes_path['detailed_info']); ?>">
                                                <?php 
                                                $yes_providers = get_item_providers($yes_path['id']);
                                                if (!empty($yes_providers)):
                                                    render_provider_bar($yes_providers);
                                                endif;
                                                ?>
                                                <?= htmlspecialchars($yes_path['content']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="no-path">
                                        <div class="path-label">No</div>
                                        <div class="flow-arrow"></div>
                                        <?php if (strpos($no_path['content'], 'protocol') !== false): ?>
                                            <div class="protocol-link" data-info="<?= htmlspecialchars($no_path['detailed_info']); ?>">
                                                <?= htmlspecialchars($no_path['content']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="flow-step" data-info="<?= htmlspecialchars($no_path['detailed_info']); ?>">
                                                <?php 
                                                $no_providers = get_item_providers($no_path['id']);
                                                if (!empty($no_providers)):
                                                    render_provider_bar($no_providers);
                                                endif;
                                                ?>
                                                <?= htmlspecialchars($no_path['content']); ?>
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
                                <div class="flow-step" data-info="<?= htmlspecialchars($step['detailed_info']); ?>">
                                    <?php if (!empty($step_providers)): ?>
                                        <?php render_provider_bar($step_providers); ?>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($step['content']); ?>
                                </div>
                                
                                <?php if (isset($steps[$index+1]) && !$steps[$index+1]['is_decision']): ?>
                                    <div class="flow-arrow"></div>
                                <?php endif; ?>
                                
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php 
                elseif ($section['section_type'] === 'checklist'):
                    // Render checklist section
            ?>
                <div class="section">
                    <div class="section-header">
                        <h2><?= htmlspecialchars($section['title']); ?></h2>
                    </div>
                    <div class="checklist-items">
                        <?php 
                        // Get checklist items
                        $items = get_section_items($section['id']);
                        foreach ($items as $item):
                            $item_providers = get_item_providers($item['id']);
                            $item_id = 'check_' . $item['id'];
                        ?>
                            <div class="checklist-item" data-info="<?= htmlspecialchars($item['detailed_info']); ?>">
                                <input type="checkbox" id="<?= $item_id; ?>">
                                <label for="<?= $item_id; ?>"><?= htmlspecialchars($item['content']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php 
                elseif ($section['section_type'] === 'information'):
                    // Render information section
            ?>
                <div class="info-section">
                    <h2><?= htmlspecialchars($section['title']); ?></h2>
                    <div class="info-content">
                        <?= htmlspecialchars($section['description']); ?>
                        
                        <?php 
                        // Get information items
                        $items = get_section_items($section['id']);
                        if (!empty($items)):
                        ?>
                            <ul class="info-list">
                                <?php foreach ($items as $item): ?>
                                    <li data-info="<?= htmlspecialchars($item['detailed_info']); ?>">
                                        <?= htmlspecialchars($item['content']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
        
        <p class="note-disclaimer">This is not intended to be a comprehensive guide. Always follow local protocols and medical direction.</p>
    </main>
    
    <!-- Modal Window for Detailed Information -->
    <div id="infoModal" class="modal">
        <div class="modal-content">
            <button class="close-btn">&times;</button>
            <div class="modal-header">
                <h3 id="modalTitle">Item Details</h3>
            </div>
            <div class="modal-body">
                <p id="modalInfo">Detailed information will appear here.</p>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y'); ?> Northern Colorado EMS. All rights reserved.</p>
            <p>Last system update: <?= format_date(date('Y-m-d')); ?></p>
            <?php if (!is_logged_in()): ?>
                <a href="admin/login.php" class="admin-link">Admin Login</a>
            <?php else: ?>
                <a href="admin/index.php" class="admin-link">Admin Dashboard</a>
            <?php endif; ?>
        </div>
    </footer>
    
    <script src="assets/js/scripts.js"></script>
</body>
</html>