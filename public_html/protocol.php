<?php
/**
 * Protocol Page
 * 
 * This page displays a specific protocol with its sections and blocks.
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get protocol ID from URL
$protocol_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no protocol ID is provided, redirect to homepage
if ($protocol_id <= 0) {
    redirect(site_url());
}

// Get protocol information
$protocol = get_protocol($protocol_id);

// If protocol doesn't exist, redirect to homepage
if (!$protocol) {
    redirect(site_url());
}

// Get protocol sections
$sections = get_protocol_sections($protocol_id);

// Get provider levels for the legend
$provider_levels = get_provider_levels();

// Set page title
$page_title = $protocol['title'];

// Set extra CSS
$extra_css = ['protocol.css'];

// Include header
include 'templates/header.php';
?>

<div class="protocol-page">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo site_url(); ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo site_url('category.php?id=' . $protocol['category_id']); ?>"><?php echo db_escape_html($protocol['category_name']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo db_escape_html($protocol['title']); ?></li>
        </ol>
    </nav>
    
    <div class="protocol-header">
        <h1 class="protocol-title"><?php echo db_escape_html($protocol['prefix'] . '.' . $protocol['id'] . ' ' . $protocol['title']); ?></h1>
        <p class="protocol-category">Category: <?php echo db_escape_html($protocol['category_name']); ?></p>
        <?php if (!empty($protocol['description'])): ?>
            <div class="protocol-description mb-4">
                <?php echo format_text($protocol['description']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Provider Level Legend -->
        <div class="provider-legend">
            <h4>Provider Levels</h4>
            <div class="provider-levels">
                <?php foreach ($provider_levels as $level): ?>
                    <div class="provider-level <?php echo strtolower($level['abbreviation']); ?>">
                        <?php echo db_escape_html($level['abbreviation']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php if (!empty($sections)): ?>
        <?php foreach ($sections as $section): ?>
            <?php
            // Get blocks for this section
            $blocks = get_section_blocks($section['id']);
            
            // Determine the section type and render accordingly
            $section_type = $section['section_type'];
            ?>
            
            <?php if ($section_type == 'standard' || $section_type == 'checklist'): ?>
                <!-- Standard Section or Checklist -->
                <div class="section mb-4">
                    <div class="section-header">
                        <h2><?php echo db_escape_html($section['title']); ?></h2>
                    </div>
                    <div class="checklist-items">
                        <?php if (!empty($blocks)): ?>
                            <?php foreach ($blocks as $block): ?>
                                <?php
                                // Get provider levels for this block
                                $provider_levels = get_block_provider_levels($block['id']);
                                
                                // Create provider bar
                                $provider_bar = '';
                                foreach ($provider_levels as $level) {
                                    $provider_bar .= '<div class="provider-segment ' . strtolower($level['abbreviation']) . '" style="width: ' . (100 / count($provider_levels)) . '%"></div>';
                                }
                                ?>
                                
                                <?php if ($section_type == 'checklist'): ?>
                                    <div class="checklist-item" data-info="<?php echo db_escape_html($block['detailed_info']); ?>">
                                        <div class="provider-bar">
                                            <?php echo $provider_bar; ?>
                                        </div>
                                        <input type="checkbox" id="block-<?php echo $block['id']; ?>">
                                        <label for="block-<?php echo $block['id']; ?>"><?php echo $block['content']; ?></label>
                                    </div>
                                <?php else: ?>
                                    <div class="block-content" data-info="<?php echo db_escape_html($block['detailed_info']); ?>">
                                        <div class="provider-bar">
                                            <?php echo $provider_bar; ?>
                                        </div>
                                        <?php echo $block['content']; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No content in this section.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($section_type == 'flowchart'): ?>
                <!-- Flowchart Section -->
                <div class="section mb-4">
                    <div class="section-header">
                        <h2><?php echo db_escape_html($section['title']); ?></h2>
                    </div>
                    <div class="flowchart">
                        <?php if (!empty($blocks)): ?>
                            <?php foreach ($blocks as $block): ?>
                                <?php
                                // Get provider levels for this block
                                $provider_levels = get_block_provider_levels($block['id']);
                                
                                // Create provider bar
                                $provider_bar = '';
                                foreach ($provider_levels as $level) {
                                    $provider_bar .= '<div class="provider-segment ' . strtolower($level['abbreviation']) . '" style="width: ' . (100 / count($provider_levels)) . '%"></div>';
                                }
                                
                                // Determine the block type
                                $block_type = $block['block_type'];
                                ?>
                                
                                <?php if ($block_type == 'flowstep'): ?>
                                    <div class="flow-step" data-info="<?php echo db_escape_html($block['detailed_info']); ?>">
                                        <div class="provider-bar">
                                            <?php echo $provider_bar; ?>
                                        </div>
                                        <?php echo $block['content']; ?>
                                    </div>
                                    <div class="flow-arrow"></div>
                                <?php elseif ($block_type == 'decision'): ?>
                                    <div class="decision-box" data-info="<?php echo db_escape_html($block['detailed_info']); ?>">
                                        <div class="provider-bar">
                                            <?php echo $provider_bar; ?>
                                        </div>
                                        <?php echo $block['content']; ?>
                                    </div>
                                    
                                    <?php if (!empty($block['yes_target_id']) || !empty($block['no_target_id'])): ?>
                                        <div class="yes-no-container">
                                            <div class="yes-path">
                                                <div class="path-label">Yes</div>
                                                <div class="flow-arrow"></div>
                                                <?php
                                                // Get the yes target block if it exists
                                                if (!empty($block['yes_target_id'])) {
                                                    $yes_target = db_get_row("SELECT * FROM blocks WHERE id = ?", [$block['yes_target_id']], 'i');
                                                    if ($yes_target) {
                                                        echo '<div class="protocol-link" data-info="' . db_escape_html($yes_target['detailed_info']) . '">';
                                                        echo $yes_target['content'];
                                                        echo '</div>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="no-path">
                                                <div class="path-label">No</div>
                                                <div class="flow-arrow"></div>
                                                <?php
                                                // Get the no target block if it exists
                                                if (!empty($block['no_target_id'])) {
                                                    $no_target = db_get_row("SELECT * FROM blocks WHERE id = ?", [$block['no_target_id']], 'i');
                                                    if ($no_target) {
                                                        echo '<div class="protocol-link" data-info="' . db_escape_html($no_target['detailed_info']) . '">';
                                                        echo $no_target['content'];
                                                        echo '</div>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($block_type == 'action'): ?>
                                    <div class="action-list" data-info="<?php echo db_escape_html($block['detailed_info']); ?>">
                                        <div class="provider-bar">
                                            <?php echo $provider_bar; ?>
                                        </div>
                                        <ul>
                                            <li><?php echo $block['content']; ?></li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No content in this flowchart.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($section_type == 'assessment'): ?>
                <!-- Assessment Section -->
                <div class="section mb-4">
                    <div class="section-header">
                        <h2><?php echo db_escape_html($section['title']); ?></h2>
                    </div>
                    <div class="assessment-boxes">
                        <?php if (!empty($blocks)): ?>
                            <?php 
                            // Group blocks by their content if they're similar
                            $grouped_blocks = [];
                            foreach ($blocks as $block) {
                                $grouped_blocks[$block['content']][] = $block;
                            }
                            ?>
                            
                            <?php foreach ($grouped_blocks as $content => $blocks_group): ?>
                                <div class="assessment-box" data-info="<?php echo db_escape_html($blocks_group[0]['detailed_info']); ?>">
                                    <div class="assessment-title"><?php echo $content; ?></div>
                                    <ul class="assessment-criteria">
                                        <?php foreach ($blocks_group as $sub_block): ?>
                                            <?php if (!empty($sub_block['detailed_info'])): ?>
                                                <li data-info="<?php echo db_escape_html($sub_block['detailed_info']); ?>">
                                                    <?php echo $sub_block['content']; ?>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No assessment criteria in this section.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <p class="note-disclaimer">This is not intended to be a comprehensive guide. Always follow local protocols and medical direction.</p>
    <?php else: ?>
        <div class="alert alert-info">
            <p>No content has been added to this protocol yet.</p>
        </div>
    <?php endif; ?>
</div>

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

<?php
// Include footer
include 'templates/footer.php';
?>