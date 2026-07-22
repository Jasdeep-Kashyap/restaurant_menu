<?php
$page_title = 'La Maison — Fine Dining Restaurant';
$page_desc = 'Welcome to La Maison. Experience exquisite fine dining with crafted dishes, premium ingredients, and an unforgettable atmosphere.';
include 'includes/header.php';
?>

<!-- ── Hero Section ────────────────────────────────────────── -->
<section class="hero home-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="hero-tag">Est. 2008 · Fine Dining</div>
        <h1>La Maison</h1>
        <p class="hero-subtitle">Where culinary artistry meets timeless elegance</p>
        <div class="divider"><span></span></div>
        <div class="hero-cta">
            <a href="menu.php" class="btn btn-primary">View Our Menu</a>
            <a href="contact.php" class="btn btn-outline">Reserve a Table</a>
        </div>
    </div>
</section>

<!-- ── Welcome Section ─────────────────────────────────────── -->
<section class="section welcome-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Welcome</span>
            <h2>A Culinary Journey Awaits</h2>
            <p class="section-subtitle">Step into a world where every bite is a masterpiece, every sip a revelation, and
                every moment an unforgettable memory.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🍳</div>
                <h3>Master Chefs</h3>
                <p>Our award-winning culinary team crafts each dish with precision, passion, and creativity that pushes
                    the boundaries of taste.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌿</div>
                <h3>Fresh Ingredients</h3>
                <p>We source only the finest seasonal produce from local farms and trusted suppliers to ensure
                    extraordinary quality.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🍷</div>
                <h3>Fine Wines</h3>
                <p>Our sommelier curates an exclusive collection of wines from the world's most prestigious vineyards
                    and cellars.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Featured Dishes ─────────────────────────────────────── -->
<section class="section featured-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Signature</span>
            <h2>Featured Dishes</h2>
            <p class="section-subtitle">Handpicked favourites from our chef's curated selection</p>
        </div>

        <div class="featured-grid">
            <div class="featured-card">
                <div class="featured-img-wrap">
                    <img src="Grilled Salmon.jfif"
                        alt="Grilled Salmon Fillet" loading="lazy">
                    <span class="featured-badge">Chef's Pick</span>
                </div>
                <div class="featured-body">
                    <div class="featured-meta">
                        <h3>Grilled Salmon Fillet</h3>
                        <span class="featured-price">₹520.00</span>
                    </div>
                    <p>Atlantic salmon, herb-crusted, served on saffron risotto with asparagus and lemon beurre blanc.
                    </p>
                    <a href="menu.php" class="btn btn-small">Order Now →</a>
                </div>
            </div>

            <div class="featured-card">
                <div class="featured-img-wrap">
                    <img src="Soup.jfif"
                        alt="Soup" loading="lazy">
                    <span class="featured-badge">Premium</span>
                </div>
                <div class="featured-body">
                    <div class="featured-meta">
                        <h3>Creamy Mushroom Soup</h3>
                        <span class="featured-price">₹120.00</span>
                    </div>
                    <p>Velvety blend of wild forest mushrooms with truffle oil, cream, and fresh thyme croutons.</p>
                    <a href="menu.php" class="btn btn-small">Order Now →</a>
                </div>
            </div>

            <div class="featured-card">
                <div class="featured-img-wrap">
                    <img src="tiramisu.jfif"
                        alt="Classic Tiramisu" loading="lazy">
                    <span class="featured-badge">Popular</span>
                </div>
                <div class="featured-body">
                    <div class="featured-meta">
                        <h3>Classic Tiramisu</h3>
                        <span class="featured-price">₹150.00</span>
                    </div>
                    <p>Mascarpone cream layered with espresso-soaked ladyfingers, dusted with Valrhona cocoa.</p>
                    <a href="menu.php" class="btn btn-small">Order Now →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── Testimonials ────────────────────────────────────────── -->
<section class="section testimonials-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Reviews</span>
            <h2>What Our Guests Say</h2>
        </div>

        <div class="testimonial-grid">
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p>"An absolutely remarkable dining experience. The salmon was cooked to perfection and the ambiance was
                    unparalleled."</p>
                <div class="testimonial-author">
                    <strong>Bonnie Blue</strong>
                    <span>Famous Personality,England</span>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p>"La Maison consistently delivers extraordinary flavours. The tenderloin steak is the best I've had in
                    Europe."</p>
                <div class="testimonial-author">
                    <strong>James Whitfield</strong>
                    <span>Regular Guest</span>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p>"From the moment you walk in, you know you're somewhere special. Every detail is thoughtfully
                    curated."</p>
                <div class="testimonial-author">
                    <strong>Maria Rossi</strong>
                    <span>Travel Blogger</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA Banner ──────────────────────────────────────────── -->
<section class="cta-banner">
    <div class="container">
        <h2>Ready for an Unforgettable Evening?</h2>
        <p>Reserve your table today or explore our full menu online.</p>
        <div class="cta-buttons">
            <a href="menu.php" class="btn btn-primary">Browse Menu</a>
            <a href="contact.php" class="btn btn-outline">Make a Reservation</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>