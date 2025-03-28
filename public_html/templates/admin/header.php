<?php
/**
 * Admin Header Template
 * 
 * This file contains the header template for the admin pages.
 */

// Include required files if not already included
if (!function_exists('require_admin')) {
    require_once '../includes/config.php';
    require_once '../includes/functions.php';
    require_once '../includes/auth.php';
}

// Ensure user is logged in
require_admin();

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo SITE_NAME; ?><?php echo isset($page_title) ? ' - ' . $page_title : ''; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo assets_url('css/admin.css'); ?>">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo assets_url('css/' . $css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo assets_url('images/favicon.ico'); ?>" type="image/x-icon">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark text-light">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            
            <ul class="list-unstyled components">
                <li class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url(); ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <li class="<?php echo in_array($current_page, ['protocols/index.php', 'protocols/create.php', 'protocols/edit.php']) ? 'active' : ''; ?>">
                    <a href="#protocolsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-file-medical"></i> Protocols
                    </a>
                    <ul class="collapse list-unstyled <?php echo in_array($current_page, ['protocols/index.php', 'protocols/create.php', 'protocols/edit.php']) ? 'show' : ''; ?>" id="protocolsSubmenu">
                        <li>
                            <a href="<?php echo admin_url('protocols/index.php'); ?>">
                                <i class="fas fa-list"></i> All Protocols
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo admin_url('protocols/create.php'); ?>">
                                <i class="fas fa-plus"></i> Add New
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="<?php echo in_array($current_page, ['categories/index.php', 'categories/create.php', 'categories/edit.php']) ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('categories/index.php'); ?>">
                        <i class="fas fa-folder"></i> Categories
                    </a>
                </li>
                
                <li class="<?php echo in_array($current_page, ['provider-levels/index.php', 'provider-levels/create.php', 'provider-levels/edit.php']) ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('provider-levels/index.php'); ?>">
                        <i class="fas fa-user-md"></i> Provider Levels
                    </a>
                </li>
            </ul>
            
            <ul class="list-unstyled">
                <li>
                    <a href="<?php echo site_url(); ?>" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Site
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('logout.php'); ?>">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex">
                        <span class="navbar-text me-3">
                            Welcome, <?php echo db_escape_html(get_current_username()); ?>
                        </span>
                    </div>
                </div>
            </nav>
            
            <div class="content-inner p-4">
                <?php if (isset($page_title)): ?>
                    <h1 class="mb-4"><?php echo db_escape_html($page_title); ?></h1>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                <?php endif; ?>