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

// Get all protocol categories
$db->query("SELECT * FROM categories ORDER BY sort_order, category_number");
$categories = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northern Colorado Prehospital Protocols</title>
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
    
    <main class="container">
        <section class="intro">
            <h2>EMS Protocol System</h2>
            <p>Welcome to the Northern Colorado Prehospital Protocols system. Select a protocol category below to begin.</p>
            <?php echo flash_message(); ?>
        </section>
        
        <section class="protocol-categories">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <h3><?= htmlspecialchars($category['category_number']); ?>. <?= htmlspecialchars($category['name']); ?></h3>
                    <p><?= htmlspecialchars($category['description']); ?></p>
                    <a href="category.php?id=<?= $category['id']; ?>" class="btn">View Protocols</a>
                </div>
            <?php endforeach; ?>
        </section>
        
        <section class="quick-links">
            <h3>Quick Links</h3>
            <div class="links-grid">
                <a href="/respdistress.php" class="quick-link">
                    <span class="icon respiratory-icon"></span>
                    <span class="link-text">Adult Respiratory Distress</span>
                </a>
                <a href="/intubation.php" class="quick-link">
                    <span class="icon intubation-icon"></span>
                    <span class="link-text">Intubation Checklist</span>
                </a>
                <a href="/surgicalcric.php" class="quick-link">
                    <span class="icon cric-icon"></span>
                    <span class="link-text">Surgical Cricothyrotomy</span>
                </a>
                <!-- Additional quick links as needed -->
            </div>
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