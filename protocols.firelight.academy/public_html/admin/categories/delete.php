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
    flash_message('You do not have permission to delete categories.', 'danger');
    redirect('../index.php');
}

// Get category ID from URL
$category_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$category_id) {
    flash_message('No category specified.', 'danger');
    redirect('index.php');
}

// Check if category exists
$db->query('SELECT * FROM categories WHERE id = :id');
$db->bind(':id', $category_id);
$category = $db->single();

if (!$category) {
    flash_message('Category not found.', 'danger');
    redirect('index.php');
}

// Check if category has protocols
$db->query('SELECT COUNT(*) as count FROM protocols WHERE category_id = :category_id');
$db->bind(':category_id', $category_id);
$protocol_count = $db->single()['count'];

if ($protocol_count > 0) {
    flash_message('Cannot delete category with protocols. Please move or delete all protocols in this category first.', 'danger');
    redirect('index.php');
}

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $db->query('DELETE FROM categories WHERE id = :id');
    $db->bind(':id', $category_id);
    
    if ($db->execute()) {
        flash_message('Category deleted successfully.', 'success');
    } else {
        flash_message('Error deleting category.', 'danger');
    }
    
    redirect('index.php');
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['confirm'])) {
    // Display confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Category - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Delete Category</h1>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline">Back to Categories</a>
                </div>
            </div>
            
            <div class="confirmation-box">
                <h2>Are you sure you want to delete this category?</h2>
                <p><strong>Category:</strong> <?= htmlspecialchars($category['name']); ?> (<?= htmlspecialchars($category['category_number']); ?>)</p>
                <p class="warning">This action cannot be undone.</p>
                
                <div class="confirmation-actions">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <a href="delete.php?id=<?= $category_id; ?>&confirm=yes" class="btn btn-danger">Delete Category</a>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/admin-footer.php'; ?>
    
    <script src="../../assets/js/admin.js"></script>
</body>
</html>
<?php
} else {
    // Invalid request method
    redirect('index.php');
}
?>