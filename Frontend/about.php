<?php
$page_title = 'About Us — La Maison';
$page_desc  = 'Learn the story behind La Maison — our history, philosophy, and the passionate team behind every unforgettable dish.';
include '../includes/header.php';
?>

<!-- ── Hero ───────────────────────────────────────────────── -->
<header class="hero page-hero about-hero">
    <div class="hero-content">
        <div class="hero-tag">Our Story</div>
        <h1>About La Maison</h1>
        <p class="hero-subtitle">A legacy of flavour, crafted over decades</p>
        <div class="divider"><span>✦</span></div>
    </div>
</header>

<!-- ── Story Section ──────────────────────────────────────── -->
<section class="section about-story-section">
    <div class="container about-story-grid">
        <div class="about-story-img">
            <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=700&q=80" alt="Restaurant interior" loading="lazy">
        </div>
        <div class="about-story-text">
            <span class="section-tag">Since 2008</span>
            <h2>Where Passion Meets Plate</h2>
            <p>La Maison was born from a simple belief: that extraordinary food should create extraordinary moments. Founded in 2008 by Chef Antoine Moreau, our restaurant has grown from a small Parisian bistro into one of the city's most sought-after dining destinations.</p>
            <p>Every dish on our menu tells a story — from the sun-drenched farms where our ingredients are sourced, to the creative minds in our kitchen who transform them into edible art. We honour traditional French techniques while embracing bold, modern expressions of flavour.</p>
            <p>Today, La Maison is more than a restaurant. It is an experience — a place where memories are made over shared plates, fine wines, and the warmth of genuine hospitality.</p>
        </div>
    </div>
</section>

<!-- ── Values ─────────────────────────────────────────────── -->
<section class="section values-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Philosophy</span>
            <h2>Our Values</h2>
        </div>

        <div class="values-grid">
            <div class="value-card">
                <div class="value-number">01</div>
                <h3>Quality First</h3>
                <p>We never compromise on ingredients. Every component is hand-selected for freshness, provenance, and flavour intensity.</p>
            </div>
            <div class="value-card">
                <div class="value-number">02</div>
                <h3>Artisan Craft</h3>
                <p>Our chefs are artisans. Every sauce, every garnish, every plate is assembled with the precision and care of a masterpiece.</p>
            </div>
            <div class="value-card">
                <div class="value-number">03</div>
                <h3>Warm Hospitality</h3>
                <p>We believe dining is about connection. Our team ensures every guest feels welcomed, valued, and truly at home.</p>
            </div>
            <div class="value-card">
                <div class="value-number">04</div>
                <h3>Sustainability</h3>
                <p>From reducing waste to partnering with local farmers, we are committed to protecting the planet that feeds us.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Chef Section ───────────────────────────────────────── -->
<section class="section chef-section">
    <div class="container about-story-grid reverse">
        <div class="about-story-text">
            <span class="section-tag">Head Chef</span>
            <h2>Chef Antoine Moreau</h2>
            <p>With over 25 years in the culinary world, Chef Antoine holds two Michelin stars and has trained under some of Europe's most celebrated masters. His approach blends classical French technique with modern innovation, always letting the ingredients shine.</p>
            <p>"Cooking is an act of love. Every ingredient has a soul, and our job is to bring it to life on the plate." — Chef Antoine</p>
        </div>
        <div class="about-story-img">
            <img src="https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=700&q=80" alt="Chef Antoine Moreau in the kitchen" loading="lazy">
        </div>
    </div>
</section>

<!-- ── CTA ─────────────────────────────────────────────────── -->
<section class="cta-banner">
    <div class="container">
        <h2>Come Experience La Maison</h2>
        <p>Book a table and let us create an unforgettable evening for you.</p>
        <div class="cta-buttons">
            <a href="../Backend/menu.php" class="btn btn-primary">View Menu</a>
            <a href="contact.php" class="btn btn-outline">Reserve a Table</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
