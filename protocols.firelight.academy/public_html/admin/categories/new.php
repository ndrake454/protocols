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
    flash_message('You do not have permission to create categories.', 'danger');
    redirect('../index.php');
}

// Get the highest category number for reference
$db->query('SELECT MAX(CAST(category_number AS UNSIGNED)) as max_number FROM categories');
$result = $db->single();
$suggested_number = ($result && $result['max_number']) ? $result['max_number'] + 1000 : 1000;

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
    
    // Check if category number already exists
    $db->query('SELECT id FROM categories WHERE category_number = :category_number');
    $db->bind(':category_number', $category_number);
    if ($db->single()) {
        $errors[] = 'A category with this number already exists.';
    }
    
    // If no errors, insert the new category
    if (empty($errors)) {
        $db->query('INSERT INTO categories (category_number, name, description, sort_order) VALUES (:category_number, :name, :description, :sort_order)');
        $db->bind(':category_number', $category_number);
        $db->bind(':name', $name);
        $db->bind(':description', $description);
        $db->bind(':sort_order', $sort_order);
        
        if ($db->execute()) {
            flash_message('Category created successfully.', 'success');
            redirect('index.php');
        } else {
            flash_message('Error creating category.', 'danger');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Category - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="icon" href="../../assets/images/favicon.ico">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Add New Category</h1>
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
                        <input type="text" id="category_number" name="category_number" required value="<?= isset($_POST['category_number']) ? htmlspecialchars($_POST['category_number']) : $suggested_number; ?>">
                        <p class="help-text">Typically a multiple of 1000 (e.g., 1000, 2000, 3000)</p>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" id="name" name="name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" rows="3"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="<?= isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : $suggested_number / 1000; ?>">
                        <p class="help-text">Lower numbers appear first. Leave as suggested if unsure.</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </main>
    </div>
    
    <?php include '../includes/admin-footer.php'; ?>
    
    <script src="../../assets/js/admin.js"></script>
</body>
</html>