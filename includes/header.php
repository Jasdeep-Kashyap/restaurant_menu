<?php
// Determine the current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'La Maison — Fine Dining' ?></title>
    <meta name="description"
        content="<?= $page_desc ?? 'La Maison — Premium fine dining restaurant. Crafted with passion, every dish tells a story.' ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,700;1,500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ── Navigation ─────────────────────────────────────────── -->
    <nav class="main-nav">
        <div class="nav-inner">
            <a href="index.php" class="nav-logo">
                <img src="image.svg" alt="La Maison Logo" class="logo-icon">
                <span class="logo-text">La Maison</span>
            </a>

            <!-- Checkbox hack for mobile toggle (no JS) -->
            <input type="checkbox" id="navToggle" class="nav-toggle-checkbox">
            <label for="navToggle" class="nav-toggle-label" aria-label="Toggle menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </label>

            <ul class="nav-links">
                <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="menu.php" class="<?= $current_page === 'menu.php' ? 'active' : '' ?>">Menu</a></li>
                <li><a href="about.php" class="<?= $current_page === 'about.php' ? 'active' : '' ?>">About</a></li>
                <li><a href="contact.php" class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">Contact</a>
                </li>
                <li>
                    <a href="menu.php" class="nav-order-btn">
                        Order Now
                    </a>
                </li>
            </ul>
        </div>
    </nav>