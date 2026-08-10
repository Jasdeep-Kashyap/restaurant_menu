<?php
// Page visibility guard
$_ps_file = __DIR__ . '/../includes/page_settings.json';
$_ps = file_exists($_ps_file) ? (json_decode(file_get_contents($_ps_file), true) ?? []) : [];
if (isset($_ps['contact']) && !$_ps['contact']) {
    http_response_code(404);
    $error_page = __DIR__ . '/../errors/404.php';
    if (file_exists($error_page)) require $error_page;
    else echo '<h1>404 Not Found</h1>';
    exit;
}

$page_title = 'Contact Us — Obsidian';
$page_desc = 'Get in touch with Obsidian. Find our address, opening hours, phone number, or send us a message to book a table.';

require_once '../includes/db.php';
require_once '../includes/auth.php';

$form_sent = false;
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $guests = intval($_POST['guests'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $msg = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '') {
        $form_error = 'Please provide at least your name and email.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO contact_messages (name, email, phone, guests, preferred_date, message)
                 VALUES (:name, :email, :phone, :guests, :preferred_date, :message)"
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone ?: null,
                ':guests' => $guests > 0 ? $guests : null,
                ':preferred_date' => $date ?: null,
                ':message' => $msg ?: null,
            ]);
            $form_sent = true;
        } catch (PDOException $e) {
            $form_error = 'Sorry, something went wrong. Please try again later.';
        }
    }
}


include '../includes/header.php';
?>

<!-- ── Hero ───────────────────────────────────────────────── -->
<header class="hero page-hero">
    <div class="hero-content">
        <div class="hero-tag">Get In Touch</div>
        <h1>Contact Us</h1>
        <p class="hero-subtitle">We'd love to hear from you — reach out or book a table</p>
        <div class="divider"><span>✦</span></div>
    </div>
</header>

<!-- ── Contact Content ────────────────────────────────────── -->
<section class="section contact-section">
    <div class="container contact-grid">

        <!-- Info Column -->
        <div class="contact-info">
            <div class="contact-info-card">
                <div class="contact-icon">📍</div>
                <h3>Visit Us</h3>
                <p>Plot No. 21, Ward 5/B, Adipur,<br>Gandhidham, Gujarat 370205</p>
            </div>
            <div class="contact-info-card">
                <div class="contact-icon">📞</div>
                <h3>Call Us</h3>
                <p>+91 90994-XXXXX</p>
            </div>
            <div class="contact-info-card">
                <div class="contact-icon">✉️</div>
                <h3>Email Us</h3>
                <p>hello@obsidian.com</p>
            </div>
            <div class="contact-info-card">
                <div class="contact-icon">🕐</div>
                <h3>Opening Hours</h3>
                <p>Mon – Fri: 11am – 10pm<br>Sat: 10am – 11pm<br>Sun: 10am – 9pm</p>
            </div>
        </div>

        <!-- Form Column -->
        <div class="contact-form-wrap">
            <?php if ($form_sent): ?>
                <div class="form-success">
                    <span class="success-icon">✅</span>
                    <h3>Thank You, <?= htmlspecialchars($name) ?>!</h3>
                    <p>We've received your message and will get back to you shortly. If you've requested a reservation,
                        we'll confirm by email.</p>
                    <a href="contact.php" class="btn btn-outline" style="margin-top:20px;">Send Another Message</a>
                </div>
            <?php else: ?>
                <h2>Send Us a Message</h2>
                <p class="form-subtitle">Whether it's a reservation or a question — we're here to help.</p>

                <?php if ($form_error): ?>
                    <div class="form-error-msg"><?= htmlspecialchars($form_error) ?></div>
                <?php endif; ?>

                <form method="POST" action="contact.php" class="contact-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required placeholder="Your full name"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required placeholder="you@example.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" placeholder="+33 ..."
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="guests">Guests</label>
                            <input type="number" id="guests" name="guests" min="1" max="20" placeholder="Number of guests"
                                value="<?= htmlspecialchars($_POST['guests'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date">Preferred Date</label>
                        <input type="date" id="date" name="date" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="4"
                            placeholder="Tell us about your occasion or any special requests..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="contact_submit" class="btn btn-primary btn-full">Send Message</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Map placeholder ────────────────────────────────────── -->
<section class="map-section">
    <div class="map-placeholder">
        <span>📍</span>
        <p>Plot No. 21, Ward 5/B, Adipur,<br>Gandhidham, Gujarat 370205</p>
    </div>
</section>

<?php include '../includes/footer.php'; ?>