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
    flash_message('You do not have permission to manage categories.', 'danger');
    redirect('../index.php');
}

// Get all categories
$db->query('SELECT c.*, COUNT(p.id) as protocol_count 
            FROM categories c 
            LEFT JOIN protocols p ON c.id = p.category_id 
            GROUP BY c.id 
            ORDER BY c.sort_order, c.category_number');
$categories = $db->resultSet();

// Handle sort order update if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $db->beginTransaction();
    
    try {
        foreach ($_POST['sort_order'] as $id => $order) {
            $db->query('UPDATE categories SET sort_order = :order WHERE id = :id');
            $db->bind(':order', $order);
            $db->bind(':id', $id);
            $db->execute();
        }
        
        $db->endTransaction();
        flash_message('Category order updated successfully.', 'success');
        redirect($_SERVER['PHP_SELF']);
    } catch (Exception $e) {
        $db->cancelTransaction();
        flash_message('Error updating category order: ' . $e->getMessage(), 'danger');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Manage Categories</h1>
                <div class="header-actions">
                    <a href="new.php" class="btn btn-primary">Add New Category</a>
                </div>
            </div>
            
            <?php echo flash_message(); ?>
            
            <?php if (empty($categories)): ?>
                <div class="info-box">
                    <p>No categories found. Create your first category to get started organizing protocols.</p>
                </div>
            <?php else: ?>
                <form method="POST" id="sort-form">
                    <div class="table-actions">
                        <button type="submit" name="update_order" class="btn btn-secondary btn-sm">Update Order</button>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Order</th>
                                    <th style="width: 120px;">Number</th>
                                    <th>Name</th>
                                    <th style="width: 120px;">Protocols</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-categories">
                                <?php foreach ($categories as $category): ?>
                                <tr data-id="<?= $category['id']; ?>">
                                    <td>
                                        <input type="number" name="sort_order[<?= $category['id']; ?>]" value="<?= $category['sort_order']; ?>" class="order-input" min="0">
                                    </td>
                                    <td><?= htmlspecialchars($category['category_number']); ?></td>
                                    <td><?= htmlspecialchars($category['name']); ?></td>
                                    <td class="text-center"><?= $category['protocol_count']; ?></td>
                                    <td class="actions-cell">
                                        <a href="edit.php?id=<?= $category['id']; ?>" class="action-btn edit-btn" title="Edit Category">
                                            <span class="sr-only">Edit</span>
                                        </a>
                                        <a href="../../category.php?id=<?= $category['id']; ?>" class="action-btn view-btn" target="_blank" title="View Category">
                                            <span class="sr-only">View</span>
                                        </a>
                                        <?php if ($category['protocol_count'] == 0): ?>
                                        <a href="delete.php?id=<?= $category['id']; ?>" class="action-btn delete-btn" title="Delete Category" onclick="return confirm('Are you sure you want to delete this category?');">
                                            <span class="sr-only">Delete</span>
                                        </a>
                                        <?php else: ?>
                                        <span class="action-btn delete-btn disabled" title="Cannot delete category with protocols" style="opacity: 0.5; cursor: not-allowed;">
                                            <span class="sr-only">Delete</span>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                
                <div class="info-box">
                    <h3>Managing Categories</h3>
                    <ul>
                        <li>Use the number inputs to change the display order of categories.</li>
                        <li>Click "Update Order" to save your changes.</li>
                        <li>Categories with protocols cannot be deleted. You must first move or delete all protocols in the category.</li>
                    </ul>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <?php include '../includes/admin-footer.php'; ?>
    
    <script src="../../assets/js/admin.js"></script>
</body>
</html>