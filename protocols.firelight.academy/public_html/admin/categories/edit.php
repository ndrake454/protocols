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
    flash_message('You do not have permission to edit categories.', 'danger');
    redirect('../index.php');
}

// Get category ID from URL
$category_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$category_id) {
    flash_message('No category specified.', 'danger');
    redirect('index.php');
}

// Get category details
$db->query('SELECT * FROM categories WHERE id = :id');
$db->bind(':id', $category_id);
$category = $db->single();

if (!$category) {
    flash_message('Category not found.', 'danger');
    redirect('index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $category_number = sanitize_input($_POST['category_number']);
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_SANITIZE_NUMBER_INT) ?: 0;
    
    // Validate input
    $errors = [];
    
    if (empty($category_number)) {
        $errors[] = 'Category number is required.';
    }
    
    if (empty($name)) {
        $errors[] = 'Category name is required.';
    }
    
    // Check if category number already exists (but ignore this category)
    $db->query('SELECT id FROM categories WHERE category_number = :category_number AND id != :id');
    $db->bind(':category_number', $category_number);
    $db->bind(':id', $category_id);
    if ($db->single()) {
        $errors[] = 'A different category with this number already exists.';
    }
    
    // If no errors, update the category
    if (empty($errors)) {
        $db->query('UPDATE categories SET category_number = :category_number, name = :name, description = :description, sort_order = :sort_order WHERE id = :id');
        $db->bind(':category_number', $category_number);
        $db->bind(':name', $name);
        $db->bind(':description', $description);
        $db->bind(':sort_order', $sort_order);
        $db->bind(':id', $category_id);
        
        if ($db->execute()) {
            flash_message('Category updated successfully.', 'success');
            redirect('index.php');
        } else {
            flash_message('Error updating category.', 'danger');
        }
    }
}

// Get protocol count for this category
$db->query('SELECT COUNT(*) as count FROM protocols WHERE category_id = :category_id');
$db->bind(':category_id', $category_id);
$protocol_count = $db->single()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Edit Category: <?= htmlspecialchars($category['name']); ?></h1>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline">Back to Categories</a>
                </div>
            </div>
            
            <?php 
            // Display error messages if any
            if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="form-container" data-validate="true">
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_number">Category Number</label>
                        <input type="text" id="category_number" name="category_number" required value="<?= htmlspecialchars($category['category_number']); ?>">
                        <p class="help-text">Typically a multiple of 1000 (e.g., 1000, 2000, 3000)</p>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($category['name']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($category['description']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="<?= htmlspecialchars($category['sort_order']); ?>">
                        <p class="help-text">Lower numbers appear first.</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
            
            <?php if ($protocol_count > 0): ?>
                <div class="related-items">
                    <h3>Protocols in this Category (<?= $protocol_count; ?>)</h3>
                    <?php
                    // Get protocols in this category
                    $db->query('SELECT id, protocol_number, title FROM protocols WHERE category_id = :category_id ORDER BY protocol_number');
                    $db->bind(':category_id', $category_id);
                    $protocols = $db->resultSet();
                    ?>
                    
                    <div class="table-container">
                        <table class="data-table compact">
                            <thead>
                                <tr>
                                    <th>Number</th>
                                    <th>Title</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($protocols as $protocol): ?>
                                <tr>
                                    <td><?= htmlspecialchars($protocol['protocol_number']); ?></td>
                                    <td><?= htmlspecialchars($protocol['title']); ?></td>
                                    <td class="actions-cell">
                                        <a href="../protocols/edit.php?id=<?= $protocol['id']; ?>" class="action-btn edit-btn" title="Edit Protocol">
                                            <span class="sr-only">Edit</span>
                                        </a>
                                        <a href="../../protocol.php?id=<?= $protocol['id']; ?>" class="action-btn view-btn" target="_blank" title="View Protocol">
                                            <span class="sr-only">View</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <p>This category currently has no protocols. <a href="../protocols/new.php">Create a protocol</a> in this category.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <?php include '../includes/admin-footer.php'; ?>
    
    <script src="../../assets/js/admin.js"></script>
</body>
</html>