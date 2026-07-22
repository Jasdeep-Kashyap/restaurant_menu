<?php
// support.php — Customer Support Page
$page_title = 'Customer Support — La Maison';
$page_desc  = 'Need help? Reach our support team, browse FAQs, or submit a support ticket at La Maison.';

$ticket_sent  = false;
$ticket_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['support_submit'])) {
    $s_name    = trim($_POST['s_name']    ?? '');
    $s_email   = trim($_POST['s_email']   ?? '');
    $s_order   = trim($_POST['s_order']   ?? '');
    $s_topic   = trim($_POST['s_topic']   ?? '');
    $s_message = trim($_POST['s_message'] ?? '');

    if ($s_name === '' || $s_email === '' || $s_message === '') {
        $ticket_error = 'Please fill in your name, email, and message.';
    } elseif (!filter_var($s_email, FILTER_VALIDATE_EMAIL)) {
        $ticket_error = 'Please enter a valid email address.';
    } else {
        // In production: save to DB or send email
        $ticket_sent = true;
    }
}

include 'includes/header.php';
?>

<!-- ── Hero ─────────────────────────────────────────────────── -->
<header class="hero page-hero sp-hero">
    <div class="hero-content">
        <div class="hero-tag">We're Here For You</div>
        <h1>Customer Support</h1>
        <p class="hero-subtitle">Questions, feedback, or concerns — our team responds within 2 hours</p>
        <div class="divider"><span>✦</span></div>
    </div>
</header>

<div class="sp-wrap">

    <!-- ── Quick-contact strip ──────────────────────────────── -->
    <div class="sp-quick-grid">
        <a href="tel:+33142685300" class="sp-quick-card" id="sp-qc-phone">
            <div class="sp-quick-icon">📞</div>
            <div class="sp-quick-body">
                <div class="sp-quick-title">Call Us</div>
                <div class="sp-quick-sub">+33 1 42 68 53 00</div>
            </div>
            <span class="sp-quick-arrow">→</span>
        </a>
        <a href="mailto:support@lamaison.com" class="sp-quick-card" id="sp-qc-email">
            <div class="sp-quick-icon">✉️</div>
            <div class="sp-quick-body">
                <div class="sp-quick-title">Email Support</div>
                <div class="sp-quick-sub">support@lamaison.com</div>
            </div>
            <span class="sp-quick-arrow">→</span>
        </a>
        <div class="sp-quick-card sp-quick-chat" id="sp-qc-chat">
            <div class="sp-quick-icon">💬</div>
            <div class="sp-quick-body">
                <div class="sp-quick-title">Live Chat</div>
                <div class="sp-quick-sub sp-chat-status">
                    <span class="sp-online-dot"></span>
                    Online now · Avg. reply &lt;5 min
                </div>
            </div>
            <span class="sp-quick-arrow">→</span>
        </div>
        <div class="sp-quick-card" id="sp-qc-hours">
            <div class="sp-quick-icon">🕐</div>
            <div class="sp-quick-body">
                <div class="sp-quick-title">Support Hours</div>
                <div class="sp-quick-sub">Mon–Sun · 9am – 11pm</div>
            </div>
        </div>
    </div>

    <!-- ── Two-column layout ─────────────────────────────────── -->
    <div class="sp-main-grid">

        <!-- ── LEFT: FAQ ─────────────────────────────────────── -->
        <section class="sp-faq-col">
            <h2 class="sp-section-title">Frequently Asked Questions</h2>

            <?php
            $faqs = [
                ['q' => 'How do I modify or cancel my order?',
                 'a' => 'Please call us immediately at <strong>+33 1 42 68 53 00</strong> or speak to a staff member at the counter. Orders can be modified within 5 minutes of placement, before preparation begins.'],
                ['q' => 'What if I have a food allergy?',
                 'a' => 'We take allergies very seriously. Please inform our staff when ordering. Our kitchen can accommodate most common allergies — just mention it in the special instructions or call ahead so we can prepare safely.'],
                ['q' => 'Can I get a refund for my order?',
                 'a' => 'Yes. If your order is incorrect or unsatisfactory, please inform your server immediately. Refunds or replacements are offered at the manager\'s discretion. We always aim to make things right.'],
                ['q' => 'How does online ordering work?',
                 'a' => 'Browse our <a href="menu.php" style="color:var(--gold)">Menu</a>, add items to your cart, enter your name, and click "Place Order". Your order is sent directly to our kitchen — no account required.'],
                ['q' => 'Do you offer reservations?',
                 'a' => 'Absolutely! You can book a table via our <a href="contact.php" style="color:var(--gold)">Contact page</a> or call us directly. We recommend booking at least 24 hours in advance for weekends.'],
                ['q' => 'Are takeaway and delivery available?',
                 'a' => 'Yes — we offer takeaway for all menu items. For delivery, please call us directly or use our partner platforms. Delivery radius is 5 km from the restaurant.'],
                ['q' => 'Is there parking available?',
                 'a' => 'There is street parking available on Rue de la Paix and a paid car park one block away on Avenue de l'Opéra, open until midnight.'],
                ['q' => 'How can I provide feedback about my experience?',
                 'a' => 'We love hearing from you! Use the support ticket form on this page, email us, or leave a review on Google. Your feedback helps us improve every dish and every visit.'],
            ];
            ?>

            <div class="sp-faq-list">
                <?php foreach ($faqs as $i => $faq): ?>
                <div class="sp-faq-item" id="faq-<?= $i ?>">
                    <button class="sp-faq-q" aria-expanded="false" aria-controls="faq-a-<?= $i ?>">
                        <span><?= htmlspecialchars($faq['q']) ?></span>
                        <span class="sp-faq-chevron" aria-hidden="true">›</span>
                    </button>
                    <div class="sp-faq-a" id="faq-a-<?= $i ?>" role="region">
                        <p><?= $faq['a'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ── RIGHT: Ticket Form ────────────────────────────── -->
        <section class="sp-form-col">
            <h2 class="sp-section-title">Submit a Support Ticket</h2>
            <p class="sp-form-desc">Can't find your answer above? Drop us a message and we'll reply within 2 hours.</p>

            <?php if ($ticket_sent): ?>
            <div class="sp-success-box" id="sp-success">
                <div class="sp-success-icon">✅</div>
                <h3>Ticket Received!</h3>
                <p>Thanks, <strong><?= htmlspecialchars($s_name) ?></strong>! We've got your message and will reply to <em><?= htmlspecialchars($s_email) ?></em> shortly. Typical response time is under 2 hours.</p>
                <a href="support.php" class="sp-btn sp-btn-outline">Submit Another</a>
            </div>
            <?php else: ?>

            <?php if ($ticket_error): ?>
            <div class="sp-error-box">⚠️ <?= htmlspecialchars($ticket_error) ?></div>
            <?php endif; ?>

            <form method="POST" action="support.php#sp-ticket-form" class="sp-form" id="sp-ticket-form">

                <div class="sp-form-row">
                    <div class="sp-form-group">
                        <label for="s_name">Your Name <span class="sp-required">*</span></label>
                        <input
                            type="text"
                            id="s_name"
                            name="s_name"
                            required
                            placeholder="Full name"
                            value="<?= htmlspecialchars($_POST['s_name'] ?? '') ?>"
                            autocomplete="name"
                        >
                    </div>
                    <div class="sp-form-group">
                        <label for="s_email">Email Address <span class="sp-required">*</span></label>
                        <input
                            type="email"
                            id="s_email"
                            name="s_email"
                            required
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($_POST['s_email'] ?? '') ?>"
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="sp-form-row">
                    <div class="sp-form-group">
                        <label for="s_order">Order # <span class="sp-hint">(if applicable)</span></label>
                        <input
                            type="text"
                            id="s_order"
                            name="s_order"
                            placeholder="e.g. #42"
                            value="<?= htmlspecialchars($_POST['s_order'] ?? '') ?>"
                        >
                    </div>
                    <div class="sp-form-group">
                        <label for="s_topic">Topic <span class="sp-required">*</span></label>
                        <select id="s_topic" name="s_topic" required>
                            <option value="" disabled <?= empty($_POST['s_topic']) ? 'selected' : '' ?>>Select a topic…</option>
                            <option value="Order Issue"     <?= ($_POST['s_topic'] ?? '') === 'Order Issue'     ? 'selected' : '' ?>>Order Issue</option>
                            <option value="Billing / Refund"<?= ($_POST['s_topic'] ?? '') === 'Billing / Refund'? 'selected' : '' ?>>Billing / Refund</option>
                            <option value="Allergy / Dietary"<?= ($_POST['s_topic'] ?? '') === 'Allergy / Dietary'? 'selected' : '' ?>>Allergy / Dietary</option>
                            <option value="Reservation"    <?= ($_POST['s_topic'] ?? '') === 'Reservation'    ? 'selected' : '' ?>>Reservation</option>
                            <option value="Compliment"     <?= ($_POST['s_topic'] ?? '') === 'Compliment'     ? 'selected' : '' ?>>Compliment 🙌</option>
                            <option value="Complaint"      <?= ($_POST['s_topic'] ?? '') === 'Complaint'      ? 'selected' : '' ?>>Complaint</option>
                            <option value="Other"          <?= ($_POST['s_topic'] ?? '') === 'Other'          ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="sp-form-group">
                    <label for="s_message">Message <span class="sp-required">*</span></label>
                    <textarea
                        id="s_message"
                        name="s_message"
                        rows="5"
                        required
                        placeholder="Describe your issue or question in detail…"
                    ><?= htmlspecialchars($_POST['s_message'] ?? '') ?></textarea>
                    <div class="sp-char-counter"><span id="sp-char-count">0</span> / 1000</div>
                </div>

                <button type="submit" name="support_submit" class="sp-btn sp-btn-primary" id="sp-submit-btn">
                    <span class="sp-btn-icon">📨</span>
                    Send Support Ticket
                </button>
            </form>
            <?php endif; ?>
        </section>

    </div><!-- /.sp-main-grid -->

    <!-- ── Live chat teaser ──────────────────────────────────── -->
    <div class="sp-chat-banner" id="sp-chat-banner">
        <div class="sp-chat-banner-left">
            <div class="sp-chat-avatars">
                <div class="sp-chat-av" style="background:linear-gradient(135deg,#e8a93c,#c4891e)">A</div>
                <div class="sp-chat-av" style="background:linear-gradient(135deg,#a29bfe,#6c5ce7)">M</div>
                <div class="sp-chat-av" style="background:linear-gradient(135deg,#fd79a8,#e84393)">S</div>
            </div>
            <div>
                <div class="sp-chat-banner-title">Our support team is online</div>
                <div class="sp-chat-banner-sub">
                    <span class="sp-online-dot"></span>
                    Average response time: <strong>under 5 minutes</strong>
                </div>
            </div>
        </div>
        <button class="sp-btn sp-btn-chat" id="sp-chat-open-btn" onclick="alert('Live chat feature coming soon! Call us at +33 1 42 68 53 00 for immediate help.')">
            💬 Start Live Chat
        </button>
    </div>

</div><!-- /.sp-wrap -->

<?php include 'includes/footer.php'; ?>

<style>
/* ── Support Page Styles ─────────────────────────────────────── */

.sp-hero { padding-bottom: 40px; }

.sp-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 24px 100px;
}

/* ── Quick Contact Strip ──────────────────────────────────────── */
.sp-quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 56px;
}

.sp-quick-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 22px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    backdrop-filter: blur(10px);
    text-decoration: none;
    color: inherit;
    transition: transform 0.28s ease, border-color 0.28s, box-shadow 0.28s;
    cursor: pointer;
    animation: floatIn 0.5s ease both;
}

.sp-quick-card:nth-child(1){animation-delay:.05s}
.sp-quick-card:nth-child(2){animation-delay:.10s}
.sp-quick-card:nth-child(3){animation-delay:.15s}
.sp-quick-card:nth-child(4){animation-delay:.20s}

.sp-quick-card:hover {
    transform: translateY(-4px);
    border-color: rgba(232,169,60,0.3);
    box-shadow: 0 12px 36px rgba(0,0,0,0.35), 0 0 20px rgba(232,169,60,0.06);
}

.sp-quick-chat:hover {
    border-color: rgba(46,213,115,0.35);
    box-shadow: 0 12px 36px rgba(0,0,0,0.35), 0 0 20px rgba(46,213,115,0.08);
}

.sp-quick-icon { font-size: 1.8rem; flex-shrink: 0; }

.sp-quick-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--text);
}

.sp-quick-sub {
    font-size: 0.8rem;
    color: var(--muted);
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sp-quick-arrow {
    margin-left: auto;
    color: var(--muted);
    font-size: 1.2rem;
    transition: transform 0.2s, color 0.2s;
    flex-shrink: 0;
}

.sp-quick-card:hover .sp-quick-arrow {
    transform: translateX(4px);
    color: var(--gold);
}

/* Online dot */
.sp-online-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    background: var(--success);
    border-radius: 50%;
    animation: sp-pulse 2s ease-in-out infinite;
    flex-shrink: 0;
}

@keyframes sp-pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50%      { opacity: 0.6; transform: scale(0.8); }
}

/* ── Two-column main grid ────────────────────────────────────── */
.sp-main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: start;
    margin-bottom: 48px;
}

/* ── Section title ─── */
.sp-section-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    color: var(--text);
    margin-bottom: 6px;
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

/* ── FAQ Accordion ───────────────────────────────────────────── */
.sp-faq-list { margin-top: 20px; }

.sp-faq-item {
    border-bottom: 1px solid rgba(255,255,255,0.05);
    overflow: hidden;
}

.sp-faq-q {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 4px;
    background: none;
    border: none;
    color: var(--text);
    font-family: 'Outfit', sans-serif;
    font-size: 0.97rem;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
    transition: color 0.2s;
}

.sp-faq-q:hover { color: var(--gold); }

.sp-faq-q[aria-expanded="true"] { color: var(--gold); }

.sp-faq-chevron {
    font-size: 1.3rem;
    color: var(--muted);
    transition: transform 0.3s ease, color 0.2s;
    flex-shrink: 0;
    display: inline-block;
}

.sp-faq-q[aria-expanded="true"] .sp-faq-chevron {
    transform: rotate(90deg);
    color: var(--gold);
}

.sp-faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.38s cubic-bezier(0.4,0,0.2,1), padding 0.3s;
}

.sp-faq-a.open { max-height: 320px; }

.sp-faq-a p {
    padding: 0 4px 18px;
    color: var(--muted);
    font-size: 0.9rem;
    line-height: 1.75;
}

/* ── Form ────────────────────────────────────────────────────── */
.sp-form-desc {
    font-size: 0.88rem;
    color: var(--muted);
    margin-bottom: 22px;
    margin-top: 6px;
}

.sp-form { display: flex; flex-direction: column; gap: 16px; }

.sp-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.sp-form-group { display: flex; flex-direction: column; gap: 6px; }

.sp-form-group label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.sp-required { color: var(--error); }
.sp-hint { color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0; }

.sp-form-group input,
.sp-form-group select,
.sp-form-group textarea {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 10px;
    color: var(--text);
    font-family: 'Outfit', sans-serif;
    font-size: 0.92rem;
    padding: 11px 14px;
    outline: none;
    width: 100%;
    transition: border-color 0.25s, background 0.25s;
    resize: vertical;
}

.sp-form-group input::placeholder,
.sp-form-group textarea::placeholder { color: var(--muted); }

.sp-form-group input:focus,
.sp-form-group select:focus,
.sp-form-group textarea:focus {
    border-color: rgba(232,169,60,0.45);
    background: rgba(255,255,255,0.06);
}

.sp-form-group select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237a8090' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
    cursor: pointer;
}

.sp-form-group select option { background: #1a1d24; color: var(--text); }

.sp-char-counter {
    font-size: 0.75rem;
    color: var(--muted);
    text-align: right;
    margin-top: 4px;
}

/* ── Buttons ─── */
.sp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 10px;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.97rem;
    cursor: pointer;
    transition: all 0.25s ease;
    border: none;
    text-decoration: none;
    padding: 14px 24px;
}

.sp-btn-primary {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
    color: #0c0e11;
    box-shadow: 0 4px 18px rgba(232,169,60,0.3);
    width: 100%;
}

.sp-btn-primary:hover {
    box-shadow: 0 6px 28px rgba(232,169,60,0.45);
    transform: translateY(-1px);
}

.sp-btn-primary:active { transform: scale(0.98); }

.sp-btn-outline {
    background: transparent;
    border: 1px solid rgba(232,169,60,0.4);
    color: var(--gold);
    padding: 11px 22px;
}

.sp-btn-outline:hover {
    background: rgba(232,169,60,0.08);
    border-color: var(--gold);
}

.sp-btn-chat {
    background: rgba(46,213,115,0.15);
    border: 1px solid rgba(46,213,115,0.4);
    color: var(--success);
    padding: 12px 24px;
    flex-shrink: 0;
}

.sp-btn-chat:hover {
    background: rgba(46,213,115,0.25);
    box-shadow: 0 4px 20px rgba(46,213,115,0.25);
    transform: translateY(-1px);
}

/* ── Success & Error boxes ─── */
.sp-success-box {
    background: rgba(46,213,115,0.06);
    border: 1px solid rgba(46,213,115,0.22);
    border-radius: 16px;
    padding: 36px;
    text-align: center;
    animation: floatIn 0.4s ease both;
}

.sp-success-icon { font-size: 2.8rem; margin-bottom: 14px; }

.sp-success-box h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--success);
}

.sp-success-box p {
    color: var(--muted);
    font-size: 0.9rem;
    line-height: 1.7;
    margin-bottom: 20px;
}

.sp-error-box {
    background: rgba(255,71,87,0.08);
    border: 1px solid rgba(255,71,87,0.25);
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 0.9rem;
    color: var(--error);
    margin-bottom: 16px;
}

/* ── Live Chat Banner ────────────────────────────────────────── */
.sp-chat-banner {
    background: var(--card-bg);
    border: 1px solid rgba(46,213,115,0.2);
    border-radius: 18px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
    animation: floatIn 0.6s ease 0.4s both;
}

.sp-chat-banner::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle 200px at 0% 50%, rgba(46,213,115,0.05), transparent 70%);
    pointer-events: none;
}

.sp-chat-banner-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.sp-chat-avatars {
    display: flex;
    margin-right: 4px;
}

.sp-chat-av {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 2px solid var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    color: #fff;
    margin-left: -10px;
    flex-shrink: 0;
}

.sp-chat-av:first-child { margin-left: 0; }

.sp-chat-banner-title {
    font-weight: 600;
    font-size: 1rem;
    color: var(--text);
    margin-bottom: 4px;
}

.sp-chat-banner-sub {
    font-size: 0.82rem;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 820px) {
    .sp-main-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
}

@media (max-width: 640px) {
    .sp-quick-grid { grid-template-columns: 1fr 1fr; }
    .sp-form-row { grid-template-columns: 1fr; }
    .sp-chat-banner { flex-direction: column; align-items: flex-start; }
    .sp-chat-banner .sp-btn-chat { width: 100%; }
}

@media (max-width: 440px) {
    .sp-quick-grid { grid-template-columns: 1fr; }
}
</style>

<script>
(function () {
    /* ── FAQ Accordion ─────────────────────────────────────── */
    document.querySelectorAll('.sp-faq-q').forEach(btn => {
        btn.addEventListener('click', () => {
            const isOpen  = btn.getAttribute('aria-expanded') === 'true';
            const answer  = btn.nextElementSibling;
            const item    = btn.closest('.sp-faq-item');

            // Close all
            document.querySelectorAll('.sp-faq-q').forEach(b => {
                b.setAttribute('aria-expanded', 'false');
                b.nextElementSibling.classList.remove('open');
            });

            // Open clicked (toggle)
            if (!isOpen) {
                btn.setAttribute('aria-expanded', 'true');
                answer.classList.add('open');
                // Smooth scroll into view on mobile
                if (window.innerWidth < 820) {
                    setTimeout(() => item.scrollIntoView({behavior:'smooth', block:'nearest'}), 60);
                }
            }
        });
    });

    /* ── Textarea char counter ─────────────────────────────── */
    const textarea  = document.getElementById('s_message');
    const charCount = document.getElementById('sp-char-count');

    if (textarea && charCount) {
        const max = 1000;
        textarea.addEventListener('input', () => {
            const len = textarea.value.length;
            if (len > max) textarea.value = textarea.value.slice(0, max);
            charCount.textContent = Math.min(len, max);
            charCount.style.color = len >= max * 0.9 ? 'var(--error)' : 'var(--muted)';
        });
    }

    /* ── Submit button loading state ────────────────────────── */
    const form      = document.getElementById('sp-ticket-form');
    const submitBtn = document.getElementById('sp-submit-btn');

    if (form && submitBtn) {
        form.addEventListener('submit', () => {
            submitBtn.disabled   = true;
            submitBtn.innerHTML  = '<span class="sp-btn-icon">⏳</span> Sending…';
        });
    }

    /* ── Animate stat values entrance ──────────────────────── */
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.animation = 'floatIn 0.5s ease both';
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.sp-quick-card, .sp-faq-item').forEach(el => observer.observe(el));
})();
</script>
