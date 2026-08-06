<?php
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_lifetime', 86400);
    @ini_set('session.gc_maxlifetime', 86400);
    session_start();
}

$page_title = 'Our Menu — La Maison';
$page_desc = 'Browse La Maison\'s curated fine-dining menu — starters, mains, desserts, and drinks. Order online.';

/* ── Cart Actions (PHP-based, no JS) ─────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cart_action'] ?? '';

    if ($action === 'add') {
        $id = $_POST['item_id'] ?? '';
        $name = $_POST['item_name'] ?? '';
        $price = floatval($_POST['item_price'] ?? 0);
        if ($id && $name && $price > 0) {
            if (!isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] = [
                    'id' => $id,
                    'name' => $name,
                    'price' => $price,
                    'qty' => 1,
                ];
            } else {
                $_SESSION['cart'][$id]['qty'] += 1;
            }
        }
        // Redirect to prevent form resubmission (PRG pattern)
        $cat = $_POST['current_category'] ?? 'all';
        header('Location: menu.php?cat=' . urlencode($cat) . '#card-' . urlencode($id));
        exit;
    }

    if ($action === 'remove') {
        $id = $_POST['item_id'] ?? '';
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] -= 1;
            if ($_SESSION['cart'][$id]['qty'] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        }
        header('Location: menu.php?cat=' . urlencode($_POST['current_category'] ?? 'all'));
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: menu.php');
        exit;
    }
}

// Initialize cart if empty
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = $_SESSION['cart'];
$cart_count = array_sum(array_column($cart, 'qty'));
$cart_total = 0;
foreach ($cart as $ci) {
    $cart_total += $ci['price'] * $ci['qty'];
}

/* ── Flash message from process_order.php ─────────────────── */
$status = $_GET['status'] ?? '';
$message = htmlspecialchars($_GET['msg'] ?? '', ENT_QUOTES, 'UTF-8');

/* ── Category filter (via GET param) ─────────────────────── */
$active_cat = $_GET['cat'] ?? 'all';

/* ── Menu Data (from database) ────────────────────────────── */
require_once '../includes/db.php';
$menu_raw = $pdo->query(
    "SELECT * FROM menu_items WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC"
)->fetchAll();

// Map DB column names to template keys
$menu = array_map(function ($row) {
    return [
        'id' => $row['id'],
        'category' => $row['category'],
        'name' => $row['name'],
        'desc' => $row['description'],
        'price' => (float) $row['price'],
        'badge' => $row['badge'],
        'img' => $row['image_path'],
    ];
}, $menu_raw);

$categories = array_unique(array_column($menu, 'category'));

include '../includes/header.php';
?>

<!-- ── Toast notification ──────────────────────────────────── -->
<?php if ($status === 'success'): ?>
    <div class="toast-wrap" id="toastWrap">
        <div class="toast success" role="alert">
            <span class="toast-icon">✅</span>
            <span><?= $message ?></span>
        </div>
    </div>
<?php elseif ($status === 'error'): ?>
    <div class="toast-wrap" id="toastWrap">
        <div class="toast error" role="alert">
            <span class="toast-icon">⚠️</span>
            <span><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- ── Hero ───────────────────────────────────────────────── -->
<header class="hero page-hero">
    <div class="hero-content">
        <div class="hero-tag">🍽 Our Menu</div>
        <h1>Exquisite Flavours</h1>
        <p class="hero-subtitle">Each dish crafted with the finest ingredients and presented with artistic flair</p>
        <div class="divider"><span>✦</span></div>
    </div>
</header>



<!-- ── Category Tabs (via links, no JS) ──────────────────── -->
<nav class="category-nav" aria-label="Menu categories">
    <a href="menu.php?cat=all" class="cat-btn <?= $active_cat === 'all' ? 'active' : '' ?>">🍽 All</a>
    <?php foreach ($categories as $cat):
        $emoji = match ($cat) {
            'Starters' => '🥗',
            'Mains' => '🍖',
            'Desserts' => '🍮',
            'Drinks' => '🥤',
            default => '🍴'
        };
        ?>
        <a href="menu.php?cat=<?= urlencode($cat) ?>" class="cat-btn <?= $active_cat === $cat ? 'active' : '' ?>">
            <?= $emoji ?>     <?= htmlspecialchars($cat) ?>
        </a>
    <?php endforeach; ?>
</nav>

<!-- ── Menu Grid ─────────────────────────────────────────── -->
<main>
    <div class="menu-container" id="menuGrid">
        <?php foreach ($menu as $item):
            // Filter by category
            if ($active_cat !== 'all' && $item['category'] !== $active_cat)
                continue;

            $price_fmt = '₹' . number_format($item['price'], 2);
            $in_cart = isset($cart[$item['id']]);
            $cart_qty = $in_cart ? $cart[$item['id']]['qty'] : 0;
            ?>
            <article class="menu-card" id="card-<?= htmlspecialchars($item['id']) ?>">
                <div class="img-wrapper">
                    <img class="card-img" src="<?= htmlspecialchars($item['img']) ?>"
                        alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                    <?php if ($item['badge']): ?>
                        <span class="badge"><?= htmlspecialchars($item['badge']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <div class="card-meta">
                        <h3 class="card-title"><?= htmlspecialchars($item['name']) ?></h3>
                        <span class="card-price"><?= $price_fmt ?></span>
                    </div>
                    <p class="card-desc"><?= htmlspecialchars($item['desc']) ?></p>

                    <div class="card-actions">
                        <?php if ($in_cart): ?>
                            <div class="qty-controls">
                                <form method="POST" action="menu.php">
                                    <input type="hidden" name="cart_action" value="remove">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">
                                    <input type="hidden" name="current_category" value="<?= htmlspecialchars($active_cat) ?>">
                                    <button type="submit" class="qty-btn" title="Remove one">−</button>
                                </form>
                                <span class="qty-display"><?= $cart_qty ?></span>
                                <form method="POST" action="menu.php">
                                    <input type="hidden" name="cart_action" value="add">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">
                                    <input type="hidden" name="item_name"
                                        value="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>">
                                    <input type="hidden" name="item_price" value="<?= $item['price'] ?>">
                                    <input type="hidden" name="current_category" value="<?= htmlspecialchars($active_cat) ?>">
                                    <button type="submit" class="qty-btn" title="Add one">+</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="menu.php">
                                <input type="hidden" name="cart_action" value="add">
                                <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">
                                <input type="hidden" name="item_name"
                                    value="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>">
                                <input type="hidden" name="item_price" value="<?= $item['price'] ?>">
                                <input type="hidden" name="current_category" value="<?= htmlspecialchars($active_cat) ?>">
                                <button type="submit" class="add-btn">
                                    <span class="btn-icon">＋</span>
                                    <span class="btn-text">Add to Order</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>

<!-- ── Cart Pill + Drawer (shown when items exist) ─────────── -->
<?php if ($cart_count > 0): ?>
    <input type="checkbox" id="cartToggle">
    <div class="cart-pill-wrap">

        <!-- Drawer (above the pill) -->
        <div class="cart-drawer">
            <div class="cart-drawer-inner">
                <div class="cart-info">
                    <span class="cart-count-badge"><?= $cart_count ?></span>
                    <span class="cart-label">Your Order</span>
                    <span class="cart-total">₹<?= number_format($cart_total, 2) ?></span>
                </div>

                <ul class="cart-items-list">
                    <?php foreach ($cart as $ci): ?>
                        <li>
                            <span class="cart-item-name"><?= htmlspecialchars($ci['name']) ?></span>
                            <span class="cart-item-qty">×<?= $ci['qty'] ?></span>
                            <span class="cart-item-price">₹<?= number_format($ci['price'] * $ci['qty'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- ═══ MULTI-STEP CHECKOUT ═══════════════════════ -->
                <form class="order-form" id="checkoutForm" method="POST" action="process_order.php">
                    <input type="hidden" name="items_json" value='<?= htmlspecialchars(json_encode(array_map(function ($ci) {
                        return $ci['name'] . ' (×' . $ci['qty'] . ')';
                    }, $cart)), ENT_QUOTES) ?>'>
                    <input type="hidden" name="total_price" value="<?= number_format($cart_total, 2, '.', '') ?>">
                    <input type="hidden" name="payment_confirmed" id="paymentConfirmed" value="0">
                    <input type="hidden" name="utr_number" id="utrNumber" value="">
                    <input type="hidden" name="scheduled_date" id="hiddenSchedDate" value="">
                    <input type="hidden" name="scheduled_time" id="hiddenSchedTime" value="">

                    <!-- ── STEP 1: Details & Schedule ────────────── -->
                    <div id="checkoutStep1">
                        <input type="text" class="order-input" name="customer_name" id="custName"
                            placeholder="Your name" maxlength="120" autocomplete="name" required>

                        <!-- Schedule toggle -->
                        <div class="ck-schedule-section">
                            <label class="ck-toggle-row" id="schedToggleLabel">
                                <span class="ck-toggle-text">📅 Schedule for later?</span>
                                <span class="ck-toggle-switch" id="schedToggle" role="checkbox" aria-checked="false" tabindex="0">
                                    <span class="ck-toggle-knob"></span>
                                </span>
                            </label>
                            <div class="ck-schedule-fields" id="schedFields" style="display:none;">
                                <input type="date" class="ck-input" id="schedDate" min="" max="">
                                <input type="time" class="ck-input" id="schedTime" value="12:00">
                            </div>
                        </div>

                        <button type="button" class="submit-btn" id="btnToPayment">
                            Proceed to UPI Payment →
                        </button>
                    </div>

                    <!-- ── STEP 2: Interactive UPI Payment Gateway ─── -->
                    <div id="checkoutStep2" style="display:none;">
                        <div class="ck-upi-card">
                            <div class="ck-upi-header">
                                <span class="ck-upi-icon">💳</span>
                                <span class="ck-upi-title">UPI Payment Gateway</span>
                            </div>

                            <div class="ck-upi-body">
                                <div class="ck-upi-amount-badge">
                                    <span class="ck-amount-label">Amount Payable</span>
                                    <span class="ck-upi-amount">₹<?= number_format($cart_total, 2) ?></span>
                                </div>

                                <!-- App Selector Tabs -->
                                <div class="ck-app-tabs">
                                    <button type="button" class="ck-app-tab active" data-app="qr">QR Code</button>
                                    <button type="button" class="ck-app-tab" data-app="gpay">GPay</button>
                                    <button type="button" class="ck-app-tab" data-app="phonepe">PhonePe</button>
                                    <button type="button" class="ck-app-tab" data-app="paytm">Paytm</button>
                                </div>

                                <!-- QR Display Area -->
                                <div class="ck-qr-box" id="qrBox">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode('upi://pay?pa=jskkashyap1910@oksbi&pn=La%20Maison&am=' . number_format($cart_total, 2, '.', '') . '&cu=INR') ?>" 
                                         alt="UPI QR Code" class="ck-qr-img" width="160" height="160">
                                    <div class="ck-upi-vpa">VPA: <strong>jskkashyap1910@oksbi</strong></div>
                                </div>

                                <!-- App Intent Button Area (shown when app tab selected) -->
                                <div class="ck-app-intent-box" id="appBox" style="display:none;">
                                    <p class="ck-intent-desc" id="appIntentDesc">Open app to pay ₹<?= number_format($cart_total, 2) ?></p>
                                    <a href="upi://pay?pa=jskkashyap1910@oksbi&pn=La%20Maison&am=<?= number_format($cart_total, 2, '.', '') ?>&cu=INR" 
                                       class="ck-intent-btn" id="appIntentBtn" target="_blank">
                                        Open UPI App 🚀
                                    </a>
                                </div>

                                <!-- Verification Method: UTR / Reference ID -->
                                <div class="ck-verify-section">
                                    <label for="utrInput" class="ck-verify-label">
                                        <span>Authentication / UTR Verification</span>
                                        <span class="ck-req">*Required</span>
                                    </label>
                                    <div class="ck-utr-wrap">
                                        <input type="text" class="ck-input ck-utr-input" id="utrInput" 
                                               placeholder="Enter 12-digit UTR / Ref No." 
                                               maxlength="16" autocomplete="off">
                                        <button type="button" class="ck-auto-utr-btn" id="btnAutoUtr" title="Generate test UTR for instant demo">
                                            Demo UTR
                                        </button>
                                    </div>
                                    <div class="ck-utr-hint">Find the 12-digit UTR / Ref ID in your GPay / PhonePe / Paytm transaction receipt.</div>
                                </div>

                                <!-- Bank Verification Loader -->
                                <div class="ck-bank-status" id="bankStatus" style="display:none;">
                                    <div class="ck-spinner" id="bankSpinner"></div>
                                    <span id="bankStatusMsg">Connecting to Bank Gateway…</span>
                                </div>
                            </div>

                            <div class="ck-upi-schedule-note" id="upiSchedNote" style="display:none;"></div>
                        </div>

                        <button type="button" class="submit-btn ck-btn-confirm" id="btnConfirmPayment">
                            Verify & Submit Order ✓
                        </button>
                        <button type="button" class="ck-btn-back" id="btnBackToStep1">
                            ← Back to Details
                        </button>
                    </div>
                </form>

                <form method="POST" action="menu.php" class="clear-form">
                    <input type="hidden" name="cart_action" value="clear">
                    <button type="submit" class="clear-btn">Clear Cart</button>
                </form>
            </div>
        </div>

        <!-- Pill toggle button -->
        <label for="cartToggle" class="cart-pill" id="cartPill" aria-label="Toggle cart">
            <span> Items in Cart</span>
            <span class="pill-count"><?= $cart_count ?></span>
            <span class="pill-chevron">▲</span>
        </label>

    </div>
<?php endif; ?>

<!-- ── Checkout Inline Styles ──────────────────────────────── -->
<style>
    /* ── Cart Drawer Solid Background & Sizing ─────────────── */
    .cart-pill-wrap {
        position: fixed !important;
        bottom: 24px !important;
        right: 24px !important;
        z-index: 999999 !important;
    }
    .cart-drawer {
        width: min(390px, calc(100vw - 32px)) !important;
        background: #14161f !important; /* 100% Opaque Solid Dark — no bleed through */
        border: 1px solid rgba(232, 169, 60, 0.4) !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.95), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
        margin-bottom: 14px !important;
    }
    #cartToggle:checked ~ .cart-pill-wrap .cart-drawer {
        max-height: calc(88vh - 70px) !important;
        overflow-y: auto !important;
        scrollbar-width: thin;
        scrollbar-color: rgba(232, 169, 60, 0.4) transparent;
    }
    .cart-drawer::-webkit-scrollbar {
        width: 6px;
    }
    .cart-drawer::-webkit-scrollbar-thumb {
        background: rgba(232, 169, 60, 0.4);
        border-radius: 3px;
    }

    /* ── Form Inputs Solid Styling ─────────────────────────── */
    .order-input {
        background: #1d212c !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #e2e6f0 !important;
        font-weight: 500 !important;
    }
    .order-input::placeholder {
        color: #717786 !important;
    }
    .ck-input {
        background: #1d212c !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #e2e6f0 !important;
    }
    .ck-upi-card {
        background: #181b24 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .ck-app-tabs {
        background: #101218 !important;
    }

    /* ── Schedule Section ──────────────────────────────────── */
    .ck-schedule-section { margin: 12px 0 16px; }
    .ck-toggle-row {
        display: flex; align-items: center; justify-content: space-between;
        cursor: pointer; padding: 8px 0; user-select: none;
    }
    .ck-toggle-text { font-size: .84rem; color: #adb5bd; font-weight: 500; }
    .ck-toggle-switch {
        width: 42px; height: 24px;
        background: rgba(255,255,255,.08);
        border-radius: 12px; position: relative;
        transition: background .25s;
        border: 1px solid rgba(255,255,255,.1);
    }
    .ck-toggle-switch[aria-checked="true"] {
        background: rgba(232,169,60,.35);
        border-color: rgba(232,169,60,.5);
    }
    .ck-toggle-knob {
        position: absolute; top: 2px; left: 2px;
        width: 18px; height: 18px;
        background: #e2e6f0; border-radius: 50%;
        transition: transform .25s;
        box-shadow: 0 1px 4px rgba(0,0,0,.3);
    }
    .ck-toggle-switch[aria-checked="true"] .ck-toggle-knob {
        transform: translateX(18px);
        background: #e8a93c;
    }
    .ck-schedule-fields {
        display: flex; gap: 8px; margin-top: 10px;
        animation: ckFadeIn .25s ease;
    }
    @keyframes ckFadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    .ck-input {
        flex: 1; padding: 9px 12px;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 8px; color: #e2e6f0;
        font-family: 'Outfit', sans-serif; font-size: .85rem;
        outline: none; transition: border-color .2s;
    }
    .ck-input:focus { border-color: rgba(232,169,60,.5); }

    /* ── UPI Gateway Card ──────────────────────────────────── */
    .ck-upi-card {
        background: rgba(255,255,255,.03);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 14px; overflow: hidden;
        margin-bottom: 14px;
        animation: ckFadeIn .3s ease;
    }
    .ck-upi-header {
        display: flex; align-items: center; gap: 8px;
        padding: 12px 16px;
        background: linear-gradient(90deg, rgba(232,169,60,.15), rgba(46,213,115,.08));
        border-bottom: 1px solid rgba(232,169,60,.15);
    }
    .ck-upi-icon { font-size: 1.2rem; }
    .ck-upi-title {
        font-size: .82rem; font-weight: 700;
        color: #e8a93c; letter-spacing: .6px; text-transform: uppercase;
    }
    .ck-upi-body { padding: 16px; text-align: center; }

    .ck-upi-amount-badge {
        display: flex; flex-direction: column; align-items: center;
        background: rgba(0,0,0,.25); border: 1px solid rgba(255,255,255,.06);
        border-radius: 10px; padding: 10px 14px; margin-bottom: 14px;
    }
    .ck-amount-label { font-size: .7rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; }
    .ck-upi-amount { font-size: 1.6rem; font-weight: 700; color: #2ed573; font-family: 'Playfair Display', serif; }

    /* App Tabs */
    .ck-app-tabs { display: flex; gap: 4px; margin-bottom: 14px; background: rgba(0,0,0,.3); padding: 4px; border-radius: 8px; }
    .ck-app-tab {
        flex: 1; padding: 6px 0; border: none; background: transparent; color: #9ca3af;
        font-family: 'Outfit', sans-serif; font-size: .74rem; font-weight: 600;
        border-radius: 6px; cursor: pointer; transition: .2s;
    }
    .ck-app-tab.active { background: rgba(232,169,60,.2); color: #e8a93c; border: 1px solid rgba(232,169,60,.3); }

    /* QR Box */
    .ck-qr-box { padding: 10px; background: #fff; border-radius: 12px; display: inline-block; margin-bottom: 14px; }
    .ck-qr-img { border-radius: 6px; display: block; }
    .ck-upi-vpa { margin-top: 8px; font-size: .72rem; color: #1f2937; }

    /* Intent Box */
    .ck-app-intent-box { padding: 20px 10px; background: rgba(255,255,255,.02); border-radius: 10px; margin-bottom: 14px; }
    .ck-intent-desc { font-size: .82rem; color: #d1d5db; margin-bottom: 12px; }
    .ck-intent-btn {
        display: inline-block; padding: 10px 20px; background: #e8a93c; color: #000;
        font-weight: 700; font-size: .85rem; border-radius: 8px; text-decoration: none; transition: .2s;
    }
    .ck-intent-btn:hover { background: #f0bf66; transform: translateY(-1px); }

    /* UTR Verification Section */
    .ck-verify-section {
        text-align: left; background: rgba(0,0,0,.2); padding: 12px; border-radius: 10px;
        border: 1px solid rgba(255,255,255,.05); margin-top: 6px;
    }
    .ck-verify-label { display: flex; justify-content: space-between; font-size: .75rem; font-weight: 600; color: #e2e6f0; margin-bottom: 6px; }
    .ck-req { color: #ff4757; font-size: .7rem; }
    .ck-utr-wrap { display: flex; gap: 6px; }
    .ck-utr-input { font-family: monospace; letter-spacing: 1px; font-size: .9rem; font-weight: 600; }
    .ck-auto-utr-btn {
        padding: 0 10px; background: rgba(232,169,60,.12); border: 1px solid rgba(232,169,60,.3);
        color: #e8a93c; border-radius: 8px; font-size: .72rem; font-weight: 600; cursor: pointer;
        white-space: nowrap; transition: .2s;
    }
    .ck-auto-utr-btn:hover { background: rgba(232,169,60,.25); }
    .ck-utr-hint { font-size: .68rem; color: #6b7280; margin-top: 5px; }

    /* Bank Status Animation */
    .ck-bank-status {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        padding: 10px; margin-top: 10px; background: rgba(46,213,115,.08);
        border: 1px solid rgba(46,213,115,.2); border-radius: 8px;
        font-size: .78rem; color: #2ed573; font-weight: 600;
    }
    .ck-spinner {
        width: 14px; height: 14px; border: 2px solid rgba(46,213,115,.3);
        border-top-color: #2ed573; border-radius: 50%; animation: ckSpin .8s linear infinite;
    }
    @keyframes ckSpin { to { transform: rotate(360deg); } }

    .ck-upi-schedule-note {
        padding: 8px 16px 12px; font-size: .78rem; color: #63b3ed;
        text-align: center; border-top: 1px solid rgba(255,255,255,.06);
    }

    /* Buttons */
    .ck-btn-confirm {
        background: linear-gradient(135deg, #2ed573 0%, #26c06a 100%) !important;
        box-shadow: 0 4px 16px rgba(46,213,115,.2) !important;
    }
    .ck-btn-confirm:hover { box-shadow: 0 6px 24px rgba(46,213,115,.35) !important; }
    .ck-btn-back {
        width: 100%; padding: 10px; background: none; border: 1px solid rgba(255,255,255,.1);
        border-radius: 10px; margin-top: 8px; color: #adb5bd; font-family: 'Outfit', sans-serif;
        font-size: .84rem; font-weight: 500; cursor: pointer; transition: .2s;
    }
    .ck-btn-back:hover { border-color: rgba(232,169,60,.35); color: #e8a93c; }

    @keyframes ckShake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
    .ck-shake { animation: ckShake .3s ease; }
</style>

<!-- ── Checkout JS ─────────────────────────────────────────── -->
<script>
(function() {
    const step1 = document.getElementById('checkoutStep1');
    const step2 = document.getElementById('checkoutStep2');
    if (!step1) return; // no cart

    const form            = document.getElementById('checkoutForm');
    const custName        = document.getElementById('custName');
    const schedToggle     = document.getElementById('schedToggle');
    const schedFields     = document.getElementById('schedFields');
    const schedDate       = document.getElementById('schedDate');
    const schedTime       = document.getElementById('schedTime');
    const hiddenDate      = document.getElementById('hiddenSchedDate');
    const hiddenTime      = document.getElementById('hiddenSchedTime');
    const paymentField    = document.getElementById('paymentConfirmed');
    const utrHidden       = document.getElementById('utrNumber');
    const utrInput        = document.getElementById('utrInput');
    const btnAutoUtr      = document.getElementById('btnAutoUtr');
    const btnToPayment    = document.getElementById('btnToPayment');
    const btnConfirm      = document.getElementById('btnConfirmPayment');
    const btnBack         = document.getElementById('btnBackToStep1');
    const upiSchedNote    = document.getElementById('upiSchedNote');
    const bankStatus      = document.getElementById('bankStatus');
    const bankStatusMsg   = document.getElementById('bankStatusMsg');
    const bankSpinner     = document.getElementById('bankSpinner');

    const appTabs         = document.querySelectorAll('.ck-app-tab');
    const qrBox           = document.getElementById('qrBox');
    const appBox          = document.getElementById('appBox');
    const appIntentDesc   = document.getElementById('appIntentDesc');

    let scheduling = false;

    // App Tab Switching
    appTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            appTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const app = tab.dataset.app;
            if (app === 'qr') {
                qrBox.style.display = 'inline-block';
                appBox.style.display = 'none';
            } else {
                qrBox.style.display = 'none';
                appBox.style.display = 'block';
                const appNames = { gpay: 'Google Pay', phonepe: 'PhonePe', paytm: 'Paytm' };
                appIntentDesc.textContent = `Pay directly using ${appNames[app] || 'UPI App'}`;
            }
        });
    });

    // Auto-generate realistic UTR for demo testing
    btnAutoUtr.addEventListener('click', () => {
        const randomUtr = '9876' + Math.floor(10000000 + Math.random() * 90000000);
        utrInput.value = randomUtr;
        utrInput.style.borderColor = '#2ed573';
    });

    // Set date picker bounds (today → +7 days)
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    const maxDate = new Date(today);
    maxDate.setDate(maxDate.getDate() + 7);
    const maxStr = maxDate.toISOString().split('T')[0];
    schedDate.min = todayStr;
    schedDate.max = maxStr;
    schedDate.value = todayStr;

    // Schedule toggle
    function toggleSchedule() {
        scheduling = !scheduling;
        schedToggle.setAttribute('aria-checked', scheduling);
        schedFields.style.display = scheduling ? 'flex' : 'none';
    }
    schedToggle.addEventListener('click', toggleSchedule);
    schedToggle.addEventListener('keydown', e => { if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); toggleSchedule(); } });

    // Step 1 → Step 2
    btnToPayment.addEventListener('click', function() {
        if (!custName.value.trim()) {
            custName.focus();
            custName.classList.add('ck-shake');
            setTimeout(() => custName.classList.remove('ck-shake'), 350);
            return;
        }

        if (scheduling) {
            if (!schedDate.value || !schedTime.value) {
                schedFields.classList.add('ck-shake');
                setTimeout(() => schedFields.classList.remove('ck-shake'), 350);
                return;
            }
            hiddenDate.value = schedDate.value;
            hiddenTime.value = schedTime.value;

            const dt = new Date(schedDate.value + 'T' + schedTime.value);
            const opts = { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
            upiSchedNote.textContent = '📅 Scheduled: ' + dt.toLocaleDateString('en-IN', opts);
            upiSchedNote.style.display = 'block';
        } else {
            hiddenDate.value = '';
            hiddenTime.value = '';
            upiSchedNote.style.display = 'none';
        }

        step1.style.display = 'none';
        step2.style.display = 'block';
    });

    // Back to Step 1
    btnBack.addEventListener('click', function() {
        step2.style.display = 'none';
        step1.style.display = 'block';
    });

    // Confirm Payment with UTR Verification sequence
    btnConfirm.addEventListener('click', function() {
        const utrVal = utrInput.value.trim();
        
        // Format check: minimum 8 alphanumeric chars, ideally 12 digits
        if (!utrVal || utrVal.length < 8) {
            utrInput.focus();
            utrInput.classList.add('ck-shake');
            utrInput.style.borderColor = '#ff4757';
            setTimeout(() => utrInput.classList.remove('ck-shake'), 350);
            return;
        }

        btnConfirm.disabled = true;
        btnBack.disabled = true;
        bankStatus.style.display = 'flex';
        bankStatusMsg.textContent = 'Connecting to NPCI / Bank Gateway…';

        // Simulated live bank verification steps
        setTimeout(() => {
            bankStatusMsg.textContent = `Verifying UTR #${utrVal} with Bank…`;
        }, 1000);

        setTimeout(() => {
            bankSpinner.style.display = 'none';
            bankStatusMsg.textContent = 'Payment Verified & Approved! ✓';
            bankStatus.style.background = 'rgba(46,213,115,.2)';
            
            paymentField.value = '1';
            utrHidden.value = utrVal;

            setTimeout(() => {
                form.submit();
            }, 600);
        }, 2200);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>