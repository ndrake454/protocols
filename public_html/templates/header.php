<?php
/**
 * Header Template
 * 
 * This file contains the header template for the public-facing pages.
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?><?php echo isset($page_title) ? ' - ' . $page_title : ''; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo assets_url('css/main.css'); ?>">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo assets_url('css/' . $css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo assets_url('images/favicon.ico'); ?>" type="image/x-icon">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="<?php echo site_url(); ?>">
                    <?php echo SITE_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="<?php echo site_url(); ?>">
                                Home
                            </a>
                        </li>
                        
                        <?php $categories = get_categories(); ?>
                        <?php if (!empty($categories)): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarProtocols" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Protocols
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarProtocols">
                                    <?php foreach ($categories as $category): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo site_url('category.php?id=' . $category['id']); ?>">
                                                <?php echo db_escape_html($category['name']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <form class="d-flex" action="<?php echo site_url('search.php'); ?>" method="get">
                        <input class="form-control me-2" type="search" name="q" placeholder="Search protocols..." aria-label="Search">
                        <button class="btn btn-outline-light" type="submit">Search</button>
                    </form>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <?php if (isset($page_title)): ?>
            <h1 class="mb-4"><?php echo db_escape_html($page_title); ?></h1>
        <?php endif; ?>