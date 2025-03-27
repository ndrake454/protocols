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
    flash_message('You do not have permission to manage protocols.', 'danger');
    redirect('../index.php');
}

// Get all categories for filter dropdown
$db->query('SELECT * FROM categories ORDER BY sort_order, category_number');
$categories = $db->resultSet();

// Filter variables
$category_filter = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_NUMBER_INT);
$status_filter = filter_input(INPUT_GET, 'status');
$search_term = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);

// Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
$items_per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $items_per_page;

// Build query based on filters
$query = 'SELECT p.*, c.name as category_name, u.username as updated_by_name 
          FROM protocols p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN users u ON p.updated_by = u.id
          WHERE 1=1 ';

$count_query = 'SELECT COUNT(*) as total FROM protocols WHERE 1=1 ';
$params = [];

if ($category_filter) {
    $query .= ' AND p.category_id = :category_id';
    $count_query .= ' AND category_id = :category_id';
    $params[':category_id'] = $category_filter;
}

if ($status_filter === 'published') {
    $query .= ' AND p.is_published = 1';
    $count_query .= ' AND is_published = 1';
} elseif ($status_filter === 'draft') {
    $query .= ' AND p.is_published = 0';
    $count_query .= ' AND is_published = 0';
}

if ($search_term) {
    $search_term = '%' . $search_term . '%';
    $query .= ' AND (p.title LIKE :search_term OR p.protocol_number LIKE :search_term OR p.description LIKE :search_term)';
    $count_query .= ' AND (title LIKE :search_term OR protocol_number LIKE :search_term OR description LIKE :search_term)';
    $params[':search_term'] = $search_term;
}

// Get total count for pagination
$db->query($count_query);
foreach ($params as $param => $value) {
    $db->bind($param, $value);
}
$total_items = $db->single()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get protocols with sorting
$sort_by = filter_input(INPUT_GET, 'sort_by') ?: 'protocol_number';
$sort_dir = filter_input(INPUT_GET, 'sort_dir') ?: 'asc';

// Validate sort parameters
$valid_sort_fields = ['protocol_number', 'title', 'category_name', 'last_updated', 'is_published'];
if (!in_array($sort_by, $valid_sort_fields)) {
    $sort_by = 'protocol_number';
}

$valid_sort_dirs = ['asc', 'desc'];
if (!in_array($sort_dir, $valid_sort_dirs)) {
    $sort_dir = 'asc';
}

$query .= " ORDER BY $sort_by $sort_dir ";
$query .= " LIMIT $offset, $items_per_page";

$db->query($query);
foreach ($params as $param => $value) {
    $db->bind($param, $value);
}
$protocols = $db->resultSet();

// Generate sort URLs
function getSortUrl($field, $current_sort_by, $current_sort_dir) {
    $url = $_SERVER['PHP_SELF'] . '?';
    
    $params = $_GET;
    $params['sort_by'] = $field;
    
    // Toggle direction if already sorting by this field
    if ($current_sort_by === $field) {
        $params['sort_dir'] = ($current_sort_dir === 'asc') ? 'desc' : 'asc';
    } else {
        $params['sort_dir'] = 'asc';
    }
    
    return $url . http_build_query($params);
}

// Generate sort indicator
function getSortIndicator($field, $current_sort_by, $current_sort_dir) {
    if ($current_sort_by !== $field) {
        return '';
    }
    
    return ($current_sort_dir === 'asc') ? ' ▲' : ' ▼';
}

// Delete protocol if requested
if (isset($_GET['delete']) && $_GET['delete']) {
    $protocol_id = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);
    
    // Check if protocol exists
    $db->query('SELECT * FROM protocols WHERE id = :id');
    $db->bind(':id', $protocol_id);
    $protocol_to_delete = $db->single();
    
    if ($protocol_to_delete) {
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Delete all related data
            $db->query('DELETE FROM protocol_revisions WHERE protocol_id = :protocol_id');
            $db->bind(':protocol_id', $protocol_id);
            $db->execute();
            
            // Get all section IDs for this protocol
            $db->query('SELECT id FROM protocol_sections WHERE protocol_id = :protocol_id');
            $db->bind(':protocol_id', $protocol_id);
            $sections = $db->resultSet();
            
            foreach ($sections as $section) {
                // Delete all items for this section
                $db->query('DELETE FROM protocol_items WHERE section_id = :section_id');
                $db->bind(':section_id', $section['id']);
                $db->execute();
            }
            
            // Delete all sections
            $db->query('DELETE FROM protocol_sections WHERE protocol_id = :protocol_id');
            $db->bind(':protocol_id', $protocol_id);
            $db->execute();
            
            // Finally, delete the protocol
            $db->query('DELETE FROM protocols WHERE id = :id');
            $db->bind(':id', $protocol_id);
            $db->execute();
            
            // Commit transaction
            $db->endTransaction();
            
            flash_message('Protocol deleted successfully.', 'success');
        } catch (Exception $e) {
            // Rollback on error
            $db->cancelTransaction();
            flash_message('Error deleting protocol: ' . $e->getMessage(), 'danger');
        }
    } else {
        flash_message('Protocol not found.', 'danger');
    }
    
    // Redirect to remove the delete parameter
    $redirect_url = $_SERVER['PHP_SELF'];
    $params = $_GET;
    unset($params['delete']);
    if (!empty($params)) {
        $redirect_url .= '?' . http_build_query($params);
    }
    redirect($redirect_url);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Protocols - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Manage Protocols</h1>
                <div class="header-actions">
                    <a href="new.php" class="btn btn-primary">Add New Protocol</a>
                </div>
            </div>
            
            <?php echo flash_message(); ?>
            
            <div class="filter-container">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id']; ?>" <?= ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($category['category_number'] . '. ' . $category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="published" <?= ($status_filter === 'published') ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?= ($status_filter === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    
                    <div class="filter-group search-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_term ?? ''); ?>" placeholder="Search protocols...">
                        <button type="submit" class="btn btn-sm">Search</button>
                    </div>
                    
                    <?php if ($category_filter || $status_filter || $search_term): ?>
                        <a href="<?= $_SERVER['PHP_SELF']; ?>" class="btn btn-outline btn-sm">Clear Filters</a>
                    <?php endif; ?>
                    
                    <!-- Preserve sort parameters when filtering -->
                    <?php if (isset($_GET['sort_by'])): ?>
                        <input type="hidden" name="sort_by" value="<?= htmlspecialchars($_GET['sort_by']); ?>">
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['sort_dir'])): ?>
                        <input type="hidden" name="sort_dir" value="<?= htmlspecialchars($_GET['sort_dir']); ?>">
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (empty($protocols)): ?>
                <div class="info-box">
                    <p>No protocols found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table protocols-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= getSortUrl('protocol_number', $sort_by, $sort_dir); ?>" class="sort-link">
                                        Protocol Number<?= getSortIndicator('protocol_number', $sort_by, $sort_dir); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('title', $sort_by, $sort_dir); ?>" class="sort-link">
                                        Title<?= getSortIndicator('title', $sort_by, $sort_dir); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('category_name', $sort_by, $sort_dir); ?>" class="sort-link">
                                        Category<?= getSortIndicator('category_name', $sort_by, $sort_dir); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('last_updated', $sort_by, $sort_dir); ?>" class="sort-link">
                                        Last Updated<?= getSortIndicator('last_updated', $sort_by, $sort_dir); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= getSortUrl('is_published', $sort_by, $sort_dir); ?>" class="sort-link">
                                        Status<?= getSortIndicator('is_published', $sort_by, $sort_dir); ?>
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($protocols as $protocol): ?>
                            <tr>
                                <td><?= htmlspecialchars($protocol['protocol_number']); ?></td>
                                <td><?= htmlspecialchars($protocol['title']); ?></td>
                                <td><?= htmlspecialchars($protocol['category_name']); ?></td>
                                <td>
                                    <?= format_date($protocol['last_updated']); ?>
                                    <?php if ($protocol['updated_by_name']): ?>
                                        <span class="meta-info">by <?= htmlspecialchars($protocol['updated_by_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $protocol['is_published'] ? 'published' : 'draft'; ?>">
                                        <?= $protocol['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <a href="edit.php?id=<?= $protocol['id']; ?>" class="action-btn edit-btn" title="Edit Protocol">
                                        <span class="sr-only">Edit</span>
                                    </a>
                                    <a href="../../protocol.php?id=<?= $protocol['id']; ?>" class="action-btn view-btn" target="_blank" title="View Protocol">
                                        <span class="sr-only">View</span>
                                    </a>
                                    <a href="#" class="action-btn delete-btn" title="Delete Protocol" 
                                       onclick="confirmDelete(<?= $protocol['id']; ?>, '<?= htmlspecialchars(addslashes($protocol['title'])); ?>')">
                                        <span class="sr-only">Delete</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= $_SERVER['PHP_SELF']; ?>?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <div class="pagination-info">
                        Page <?= $page; ?> of <?= $total_pages; ?>
                    </div>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= $_SERVER['PHP_SELF']; ?>?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="table-summary">
                    Showing <?= count($protocols); ?> of <?= $total_items; ?> protocols
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the protocol <strong id="delete-protocol-title"></strong>?</p>
                <p class="warning">This action cannot be undone. All sections, items, and revision history will be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline close-modal">Cancel</button>
                <a href="#" id="confirm-delete-btn" class="btn btn-danger">Delete Protocol</a>
            </div>
        </div>
    </div>
    
    <?php include '../includes/admin-footer.php'; ?>
    
    <script src="../../assets/js/admin.js"></script>
    <script>
        // Delete confirmation modal
        function confirmDelete(id, title) {
            document.getElementById('delete-protocol-title').textContent = title;
            
            // Set the confirm button URL
            const confirmButton = document.getElementById('confirm-delete-btn');
            const currentUrl = window.location.href.split('?')[0]; // Get base URL without query parameters
            const queryParams = new URLSearchParams(window.location.search);
            queryParams.set('delete', id);
            confirmButton.href = currentUrl + '?' + queryParams.toString();
            
            // Show the modal
            const modal = document.getElementById('delete-modal');
            modal.style.display = 'block';
            
            return false;
        }
        
        // Close modal when clicking the close button or cancel
        document.querySelectorAll('.close-modal').forEach(function(element) {
            element.addEventListener('click', function() {
                document.getElementById('delete-modal').style.display = 'none';
            });
        });
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('delete-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>