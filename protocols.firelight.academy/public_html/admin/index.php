<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$db = new Database();

// Start session
session_start([
    'name' => SESSION_NAME,
    'cookie_lifetime' => SESSION_LIFETIME
]);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];
$db->query('SELECT * FROM users WHERE id = :id');
$db->bind(':id', $user_id);
$user = $db->single();

// Get protocol statistics
$db->query('SELECT COUNT(*) as total FROM protocols');
$total_protocols = $db->single()['total'];

$db->query('SELECT COUNT(*) as published FROM protocols WHERE is_published = 1');
$published_protocols = $db->single()['published'];

$db->query('SELECT COUNT(*) as draft FROM protocols WHERE is_published = 0');
$draft_protocols = $db->single()['draft'];

$db->query('SELECT COUNT(*) as total FROM categories');
$total_categories = $db->single()['total'];

// Get recent protocols
$db->query('SELECT p.*, c.name AS category_name, u.username AS updated_by_name
            FROM protocols p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.updated_by = u.id
            ORDER BY p.last_updated DESC
            LIMIT 5');
$recent_protocols = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Northern Colorado Protocols</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="../assets/images/favicon.ico">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <a href="protocols/new.php" class="btn btn-primary">Create New Protocol</a>
                </div>
            </div>
            
            <?php echo flash_message(); ?>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_protocols; ?></div>
                    <div class="stat-label">Total Protocols</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $published_protocols; ?></div>
                    <div class="stat-label">Published</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $draft_protocols; ?></div>
                    <div class="stat-label">Drafts</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $total_categories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>Recently Updated Protocols</h2>
                        <a href="protocols/index.php" class="view-all">View All</a>
                    </div>
                    
                    <div class="section-content">
                        <?php if (empty($recent_protocols)): ?>
                            <p class="no-results">No protocols found.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Updated By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_protocols as $protocol): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($protocol['title']); ?></td>
                                            <td><?= htmlspecialchars($protocol['category_name']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $protocol['is_published'] ? 'published' : 'draft'; ?>">
                                                    <?= $protocol['is_published'] ? 'Published' : 'Draft'; ?>
                                                </span>
                                            </td>
                                            <td><?= format_date($protocol['last_updated']); ?></td>
                                            <td><?= htmlspecialchars($protocol['updated_by_name']); ?></td>
                                            <td class="actions-cell">
                                                <a href="protocols/edit.php?id=<?= $protocol['id']; ?>" class="action-btn edit-btn" title="Edit Protocol">
                                                    <span class="sr-only">Edit</span>
                                                </a>
                                                <a href="../protocol.php?id=<?= $protocol['id']; ?>" class="action-btn view-btn" title="View Protocol" target="_blank">
                                                    <span class="sr-only">View</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>
                
                <section class="dashboard-section">
                    <div class="section-header">
                        <h2>Quick Links</h2>
                    </div>
                    
                    <div class="section-content">
                        <div class="quick-links-grid">
                            <a href="categories/index.php" class="quick-link-card">
                                <div class="icon categories-icon"></div>
                                <div class="link-text">Manage Categories</div>
                            </a>
                            
                            <a href="protocols/index.php" class="quick-link-card">
                                <div class="icon protocols-icon"></div>
                                <div class="link-text">Manage Protocols</div>
                            </a>
                            
                            <?php if (has_permission('admin')): ?>
                                <a href="users/index.php" class="quick-link-card">
                                    <div class="icon users-icon"></div>
                                    <div class="link-text">Manage Users</div>
                                </a>
                            <?php endif; ?>
                            
                            <a href="revisions/index.php" class="quick-link-card">
                                <div class="icon revisions-icon"></div>
                                <div class="link-text">Revision History</div>
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <?php include 'includes/admin-footer.php'; ?>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>