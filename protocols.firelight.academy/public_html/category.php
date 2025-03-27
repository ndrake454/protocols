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

// Get category ID from URL
$category_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$category_id) {
    redirect('index.php');
}

// Get category details
$db->query("SELECT * FROM categories WHERE id = :id");
$db->bind(':id', $category_id);
$category = $db->single();

if (!$category) {
    redirect('index.php');
}

// Get protocols in this category
$db->query("SELECT * FROM protocols WHERE category_id = :category_id AND is_published = 1 ORDER BY protocol_number");
$db->bind(':category_id', $category_id);
$protocols = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['name']); ?> Protocols - Northern Colorado</title>
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
            <span><?= htmlspecialchars($category['name']); ?></span>
        </div>
    </nav>
    
    <main class="container">
        <section class="category-header">
            <h2><?= htmlspecialchars($category['category_number']); ?>. <?= htmlspecialchars($category['name']); ?></h2>
            <p><?= htmlspecialchars($category['description']); ?></p>
        </section>
        
        <section class="protocol-list">
            <?php if (empty($protocols)): ?>
                <div class="no-results">
                    <p>No protocols found in this category.</p>
                </div>
            <?php else: ?>
                <div class="protocol-grid">
                    <?php foreach ($protocols as $protocol): ?>
                        <div class="protocol-card">
                            <h3><?= htmlspecialchars($protocol['protocol_number']); ?>. <?= htmlspecialchars($protocol['title']); ?></h3>
                            <?php if (!empty($protocol['description'])): ?>
                                <p><?= htmlspecialchars($protocol['description']); ?></p>
                            <?php endif; ?>
                            <div class="card-footer">
                                <span class="updated-date">Updated: <?= format_date($protocol['last_updated']); ?></span>
                                <a href="protocol.php?id=<?= $protocol['id']; ?>" class="btn">View Protocol</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
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