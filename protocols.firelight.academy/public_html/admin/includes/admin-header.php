<header class="admin-header">
    <div class="admin-header-container">
        <div class="logo-wrapper">
            <a href="<?= ADMIN_URL; ?>/index.php">
                <img src="<?= ASSETS_URL; ?>/images/logo.png" alt="Northern Colorado Prehospital Protocols" class="admin-logo">
                <span class="admin-title">Protocol Admin</span>
            </a>
        </div>
        
        <div class="admin-nav">
            <div class="user-dropdown">
                <button class="dropdown-trigger">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="dropdown-arrow"></span>
                </button>
                <div class="dropdown-menu">
                    <a href="<?= ADMIN_URL; ?>/users/profile.php">My Profile</a>
                    <a href="<?= ADMIN_URL; ?>/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>