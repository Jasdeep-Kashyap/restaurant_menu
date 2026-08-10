<?php
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_lifetime', 86400);
    @ini_set('session.gc_maxlifetime', 86400);
    session_start();
}
require_once '../includes/auth.php'; // For csrf_token() helper

$page_title = 'Our Menu — Obsidian';
$page_desc = 'Browse Obsidian\'s curated fine-dining menu — starters, mains, desserts, and drinks. Order online.';

/* ── Cart Actions ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cart_action'] ?? '';

    if (in_array($action, ['add', 'remove', 'clear'])) {
        $id = $_POST['item_id'] ?? '';
        $name = $_POST['item_name'] ?? '';
        $price = floatval($_POST['item_price'] ?? 0);
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if ($action === 'add' && $id && $name && $price > 0) {
            if (!isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] = [
                    'id' => $id, 'name' => $name, 'price' => $price, 'qty' => 1
                ];
            } else {
                $_SESSION['cart'][$id]['qty'] += 1;
            }
        } elseif ($action === 'remove' && $id) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty'] -= 1;
                if ($_SESSION['cart'][$id]['qty'] <= 0) {
                    unset($_SESSION['cart'][$id]);
                }
            }
        } elseif ($action === 'clear') {
            $_SESSION['cart'] = [];
        }

        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($is_ajax) {
            $cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));
            $cart_total = 0;
            foreach ($_SESSION['cart'] as $ci) {
                $cart_total += $ci['price'] * $ci['qty'];
            }
            
            $in_cart = isset($_SESSION['cart'][$id]);
            $cart_qty = $in_cart ? $_SESSION['cart'][$id]['qty'] : 0;
            $active_cat = htmlspecialchars($_POST['current_category'] ?? 'all', ENT_QUOTES);
            
            if ($in_cart) {
                $card_actions_html = '
                <div class="qty-controls">
                    <form method="POST" action="menu.php" class="ajax-cart-form">
                        <input type="hidden" name="cart_action" value="remove">
                        <input type="hidden" name="item_id" value="'.htmlspecialchars($id).'">
                        <input type="hidden" name="item_name" value="'.htmlspecialchars($name, ENT_QUOTES).'">
                        <input type="hidden" name="item_price" value="'.$price.'">
                        <input type="hidden" name="current_category" value="'.$active_cat.'">
                        <button type="submit" class="qty-btn" title="Remove one">−</button>
                    </form>
                    <span class="qty-display">'.$cart_qty.'</span>
                    <form method="POST" action="menu.php" class="ajax-cart-form">
                        <input type="hidden" name="cart_action" value="add">
                        <input type="hidden" name="item_id" value="'.htmlspecialchars($id).'">
                        <input type="hidden" name="item_name" value="'.htmlspecialchars($name, ENT_QUOTES).'">
                        <input type="hidden" name="item_price" value="'.$price.'">
                        <input type="hidden" name="current_category" value="'.$active_cat.'">
                        <button type="submit" class="qty-btn" title="Add one">+</button>
                    </form>
                </div>';
            } else {
                $card_actions_html = '
                <form method="POST" action="menu.php" class="ajax-cart-form">
                    <input type="hidden" name="cart_action" value="add">
                    <input type="hidden" name="item_id" value="'.htmlspecialchars($id).'">
                    <input type="hidden" name="item_name" value="'.htmlspecialchars($name, ENT_QUOTES).'">
                    <input type="hidden" name="item_price" value="'.$price.'">
                    <input type="hidden" name="current_category" value="'.$active_cat.'">
                    <button type="submit" class="add-btn">
                        <span class="btn-icon">＋</span>
                        <span class="btn-text">Add to Order</span>
                    </button>
                </form>';
            }

            $cart_items_html = '';
            foreach ($_SESSION['cart'] as $ci) {
                $cart_items_html .= '<li>
                    <span class="cart-item-name">'.htmlspecialchars($ci['name']).'</span>
                    <span class="cart-item-qty">×'.$ci['qty'].'</span>
                    <span class="cart-item-price">₹'.number_format($ci['price'] * $ci['qty'], 2).'</span>
                </li>';
            }
            
            $items_json = json_encode(array_values(array_map(function ($ci) {
                return $ci['name'] . ' (×' . $ci['qty'] . ')';
            }, $_SESSION['cart'])));

            header('Content-Type: application/json');
            echo json_encode([
                'cart_count' => $cart_count,
                'cart_total' => '₹' . number_format($cart_total, 2),
                'cart_items_html' => $cart_items_html,
                'card_actions_html' => $card_actions_html,
                'item_id' => $id,
                'items_json' => $items_json
            ]);
            exit;
        }

        // Fallback for non-AJAX (PRG pattern)
        $cat = $_POST['current_category'] ?? 'all';
        $anchor = $id ? '#card-' . urlencode($id) : '';
        header('Location: menu.php?cat=' . urlencode($cat) . $anchor);
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

                    <div class="card-actions" id="actions-<?= htmlspecialchars($item['id']) ?>">
                        <?php if ($in_cart): ?>
                            <div class="qty-controls">
                                <form method="POST" action="menu.php" class="ajax-cart-form">
                                    <input type="hidden" name="cart_action" value="remove">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">
                                    <input type="hidden" name="item_name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>">
                                    <input type="hidden" name="item_price" value="<?= $item['price'] ?>">
                                    <input type="hidden" name="current_category" value="<?= htmlspecialchars($active_cat) ?>">
                                    <button type="submit" class="qty-btn" title="Remove one">−</button>
                                </form>
                                <span class="qty-display"><?= $cart_qty ?></span>
                                <form method="POST" action="menu.php" class="ajax-cart-form">
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
                            <form method="POST" action="menu.php" class="ajax-cart-form">
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

<!-- ── Cart Pill + Drawer ─────────── -->
    <input type="checkbox" id="cartToggle">
    <div class="cart-pill-wrap" id="cartPillWrap" style="display: <?= $cart_count > 0 ? 'flex' : 'none' ?>;">

        <!-- Drawer (above the pill) -->
        <div class="cart-drawer">
            <div class="cart-drawer-inner">
                <div class="cart-info">
                    <span class="cart-count-badge" id="cartCountBadge"><?= $cart_count ?></span>
                    <span class="cart-label">Your Order</span>
                    <span class="cart-total" id="cartTotal">₹<?= number_format($cart_total, 2) ?></span>
                </div>

                <ul class="cart-items-list" id="cartItemsList">
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
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="items_json" id="hiddenItemsJson" value='<?= htmlspecialchars(json_encode(array_map(function ($ci) {
                        return $ci['name'] . ' (×' . $ci['qty'] . ')';
                    }, $cart)), ENT_QUOTES) ?>'>
                    <!-- total_price removed: server recalculates from $_SESSION['cart'] -->
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
                            Proceed to Cashfree Payment →
                        </button>
                    </div>

                    <!-- ── STEP 2: Cashfree Payment Gateway ─── -->
                    <div id="checkoutStep2" style="display:none;">
                        <div class="ck-upi-card">
                            <div class="ck-upi-header">
                                <span class="ck-upi-icon">💳</span>
                                <span class="ck-upi-title">Cashfree Payment Gateway</span>
                            </div>

                            <div class="ck-upi-body">
                                <div class="ck-upi-amount-badge">
                                    <span class="ck-amount-label">Amount Payable</span>
                                    <span class="ck-upi-amount">₹<?= number_format($cart_total, 2) ?></span>
                                </div>

                                <!-- Cashfree PG Display Area -->
                                <div class="ck-cashfree-box" id="cashfreeBox" style="padding:6px 0;text-align:left;">
                                    <div style="background:rgba(232,169,60,0.08);border:1px solid rgba(232,169,60,0.25);border-radius:12px;padding:14px;color:#e0e6ed;font-size:0.82rem;">
                                        <div style="font-weight:700;color:#e8a93c;margin-bottom:6px;display:flex;align-items:center;gap:6px;font-size:0.92rem;">
                                            <span>⚡ Secure Cashfree Checkout</span>
                                        </div>
                                        <p style="margin:0 0 10px 0;line-height:1.4;color:#a0aec0;font-size:0.78rem;">Pay seamlessly via Cashfree supporting UPI, Cards, NetBanking, Wallets & PayLater.</p>
                                        
                                        <div style="font-size:0.72rem;color:#e8a93c;margin-bottom:6px;font-weight:600;">SANDBOX TEST PRESETS:</div>
                                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:14px;">
                                            <button type="button" class="cf-chip-btn" data-vpa="testsuccess@gocash">UPI: testsuccess@gocash</button>
                                            <button type="button" class="cf-chip-btn" data-vpa="4706131211212123">Card: 4706...123</button>
                                            <button type="button" class="cf-chip-btn" data-vpa="3333">Bank Code: 3333</button>
                                        </div>

                                        <button type="button" id="btnPayCashfree" style="width:100%;padding:12px;background:linear-gradient(135deg,#e8a93c,#d48817);border:none;border-radius:10px;color:#121418;font-weight:700;cursor:pointer;font-size:0.95rem;transition:.2s;box-shadow:0 4px 16px rgba(232,169,60,.25);">
                                            Proceed to Cashfree Payment 🚀
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="ck-upi-schedule-note" id="upiSchedNote" style="display:none;"></div>
                        </div>

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
        <label for="cartToggle" class="cart-pill" aria-label="Toggle cart">
            <span> Items in Cart</span>
            <span class="pill-count" id="pillCount"><?= $cart_count ?></span>
            <span class="pill-chevron">▲</span>
        </label>

    </div>

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
    .cf-chip-btn {
        background: rgba(232,169,60,0.12); border: 1px solid rgba(232,169,60,0.3);
        border-radius: 6px; color: #e8a93c; font-size: 0.7rem; padding: 3px 8px;
        font-family: 'Outfit', sans-serif; cursor: pointer; transition: .2s;
    }
    .cf-chip-btn:hover { background: rgba(232,169,60,0.25); color: #fff; }

    @keyframes ckShake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
    .ck-shake { animation: ckShake .3s ease; }
</style>

<!-- ── Cashfree SDK v3 ─────────────────────────────────────── -->
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>

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

    const cashfreeBox     = document.getElementById('cashfreeBox');
    const btnPayCashfree  = document.getElementById('btnPayCashfree');
    const cfChips         = document.querySelectorAll('.cf-chip-btn');

    cfChips.forEach(chip => {
        chip.addEventListener('click', () => {
            if (utrInput) {
                utrInput.value = chip.dataset.vpa;
                utrInput.style.borderColor = '#e8a93c';
            }
        });
    });

    let scheduling = false;

    if (btnPayCashfree) {
        btnPayCashfree.addEventListener('click', function() {
            const nameVal = custName ? custName.value.trim() : '';
            if (!nameVal) {
                step2.style.display = 'none';
                step1.style.display = 'block';
                custName.focus();
                custName.classList.add('ck-shake');
                setTimeout(() => custName.classList.remove('ck-shake'), 350);
                return;
            }

            btnPayCashfree.disabled = true;
            btnPayCashfree.textContent = 'Connecting Cashfree Gateway…';

            const payload = {
                customer_name: nameVal,
                total_price: <?= number_format($cart_total, 2, '.', '') ?>,
                items_json: document.querySelector('input[name="items_json"]').value,
                scheduled_date: hiddenDate ? hiddenDate.value : '',
                scheduled_time: hiddenTime ? hiddenTime.value : ''
            };

            fetch('create_cashfree_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btnPayCashfree.disabled = false;
                btnPayCashfree.textContent = 'Proceed to Cashfree Payment 🚀';

                if (data.success) {
                    if (typeof Cashfree !== 'undefined' && data.payment_session_id && !data.payment_session_id.startsWith('session_mock_')) {
                        const cfMode = (data.environment === 'production') ? 'production' : 'sandbox';
                        const cashfree = Cashfree({ mode: cfMode });
                        cashfree.checkout({
                            paymentSessionId: data.payment_session_id,
                            redirectTarget: "_self"
                        });
                    } else {
                        // Sandbox Demo Mode: redirect directly to cashfree_return.php
                        window.location.href = 'cashfree_return.php?order_id=' + encodeURIComponent(data.cf_order_id);
                    }
                } else {
                    alert('Cashfree Order Creation Failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                btnPayCashfree.disabled = false;
                btnPayCashfree.textContent = 'Proceed to Cashfree Payment 🚀';
                alert('Error connecting to Cashfree checkout server.');
            });
        });
    }

    // Prevent direct HTML form submission on ENTER key
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (step1.style.display !== 'none') {
            btnToPayment.click();
        } else if (step2.style.display !== 'none' && btnPayCashfree) {
            btnPayCashfree.click();
        }
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

    if (btnToPayment) {
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
    }

    // Back to Step 1
    if (btnBack) {
        btnBack.addEventListener('click', function() {
            step2.style.display = 'none';
            step1.style.display = 'block';
        });
    }

    // Confirm Payment with UTR Verification sequence
    if (btnConfirm) {
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
    }
})();

// ── AJAX Cart Logic ─────────────────────────────────────────
document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('ajax-cart-form')) {
        e.preventDefault();
        
        const form = e.target;
        
        // Visual feedback (optional but nice for smoothness)
        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.style.opacity = '0.5';
        
        fetch('menu.php', {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (btn) btn.style.opacity = '1';
            
            // Update specific card buttons
            const actionsContainer = document.getElementById('actions-' + data.item_id);
            if (actionsContainer) actionsContainer.innerHTML = data.card_actions_html;
            
            // Update cart bubble and total
            const countBadge = document.getElementById('cartCountBadge');
            const totalText = document.getElementById('cartTotal');
            const pillCount = document.getElementById('pillCount');
            
            if (countBadge) countBadge.textContent = data.cart_count;
            if (totalText) totalText.textContent = data.cart_total;
            if (pillCount) pillCount.textContent = data.cart_count;
            
            // Update items list inside drawer
            const itemsList = document.getElementById('cartItemsList');
            if (itemsList) itemsList.innerHTML = data.cart_items_html;
            
            // Update hidden checkout JSON
            const hiddenJson = document.getElementById('hiddenItemsJson');
            if (hiddenJson) hiddenJson.value = data.items_json;
            
            // Show or hide the cart pill based on count
            const pillWrap = document.getElementById('cartPillWrap');
            if (pillWrap) {
                pillWrap.style.display = data.cart_count > 0 ? 'flex' : 'none';
                if (data.cart_count === 0) {
                    const toggle = document.getElementById('cartToggle');
                    if (toggle) toggle.checked = false;
                }
            }
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            if (btn) btn.style.opacity = '1';
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>