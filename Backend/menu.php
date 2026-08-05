<?php
session_start();

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

                <form class="order-form" method="POST" action="process_order.php">
                    <input type="text" class="order-input" name="customer_name" placeholder="Your name" maxlength="120"
                        autocomplete="name" required>
                    <input type="hidden" name="items_json" value='<?= htmlspecialchars(json_encode(array_map(function ($ci) {
                        return $ci['name'] . ' (×' . $ci['qty'] . ')';
                    }, $cart)), ENT_QUOTES) ?>'>
                    <input type="hidden" name="total_price" value="<?= number_format($cart_total, 2, '.', '') ?>">
                    <button type="submit" class="submit-btn">
                        Place Order — ₹<?= number_format($cart_total, 2) ?>
                    </button>
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

<?php include '../includes/footer.php'; ?>