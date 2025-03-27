<aside class="admin-sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="<?= strpos($_SERVER['PHP_SELF'], 'index.php') !== false ? 'active' : ''; ?>">
                <a href="<?= ADMIN_URL; ?>/index.php">
                    <span class="nav-icon dashboard-icon"></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <li class="<?= strpos($_SERVER['PHP_SELF'], 'categories') !== false ? 'active' : ''; ?>">
                <a href="<?= ADMIN_URL; ?>/categories/index.php">
                    <span class="nav-icon categories-icon"></span>
                    <span class="nav-text">Categories</span>
                </a>
            </li>
            
            <li class="<?= strpos($_SERVER['PHP_SELF'], '/protocols/') !== false ? 'active' : ''; ?>">
                <a href="<?= ADMIN_URL; ?>/protocols/index.php">
                    <span class="nav-icon protocols-icon"></span>
                    <span class="nav-text">Protocols</span>
                </a>
            </li>
            
            <?php if (has_permission('admin')): ?>
                <li class="<?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>">
                    <a href="<?= ADMIN_URL; ?>/users/index.php">
                        <span class="nav-icon users-icon"></span>
                        <span class="nav-text">Users</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="<?= strpos($_SERVER['PHP_SELF'], '/revisions/') !== false ? 'active' : ''; ?>">
                <a href="<?= ADMIN_URL; ?>/revisions/index.php">
                    <span class="nav-icon revisions-icon"></span>
                    <span class="nav-text">Revisions</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?= BASE_URL; ?>" target="_blank" class="view-site-link">
            <span class="view-icon"></span>
            <span>View Protocol Site</span>
        </a>
    </div>
</aside>