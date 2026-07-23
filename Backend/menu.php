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

/* ── Menu Data ────────────────────────────────────────────── */
$menu = [
    [
        'id' => 's1',
        'category' => 'Starters',
        'name' => 'Bruschetta',
        'desc' => 'Toasted sourdough topped with marinated cherry tomatoes, fresh basil, and a drizzle of aged balsamic.',
        'price' => 280.00,
        'badge' => 'Non-Veg',
        'img' => '../Image_Content/Bruschetta.jfif',
    ],
    [
        'id' => 's2',
        'category' => 'Starters',
        'name' => 'Creamy Mushroom Soup',
        'desc' => 'Velvety blend of wild forest mushrooms with truffle oil, cream, and fresh thyme croutons.',
        'price' => 120.00,
        'badge' => 'Veg',
        'img' => '../Image_Content/Soup.jfif',
    ],
    [
        'id' => 's3',
        'category' => 'Starters',
        'name' => 'Calamari Fritti',
        'desc' => 'Lightly dusted and fried squid rings served with house spicy marinara and lemon wedge.',
        'price' => 350.00,
        'badge' => 'Non-Veg',
        'img' => '../Image_Content/Calamari_fritti.jfif',
    ],
    [
        'id' => 'm1',
        'category' => 'Mains',
        'name' => 'Grilled Salmon Fillet',
        'desc' => 'Atlantic salmon, herb-crusted, served on saffron risotto with asparagus and lemon beurre blanc.',
        'price' => 520.00,
        'badge' => 'Non-Veg',
        'img' => '../Image_Content/Grilled Salmon.jfif',
    ],
    [
        'id' => 'm2',
        'category' => 'Mains',
        'name' => 'Spaghetti',
        'desc' => '220g prime-cut fillet, pan-seared with garlic butter, served with truffle fries and roasted veggies.',
        'price' => 320.00,
        'badge' => 'Veg',
        'img' => '../Image_Content/Spaghetti.jfif',
    ],
    [
        'id' => 'm3',
        'category' => 'Mains',
        'name' => 'Wild Mushroom Pasta',
        'desc' => 'Fresh tagliatelle tossed with porcini, cremini, and shiitake mushrooms in a garlic white wine cream sauce.',
        'price' => 175.00,
        'badge' => 'Veg',
        'img' => '../Image_Content/Pasta.jfif',
    ],
    [
        'id' => 'm4',
        'category' => 'Mains',
        'name' => 'Chicken Milanese',
        'desc' => 'Crispy breaded chicken breast served with arugula salad, shaved parmesan, and cherry tomatoes.',
        'price' => 190.00,
        'badge' => 'Non-Veg',
        'img' => 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=700&q=80',
    ],
    [
        'id' => 'd1',
        'category' => 'Desserts',
        'name' => 'Classic Tiramisu',
        'desc' => 'Mascarpone cream layered with espresso-soaked ladyfingers, dusted with Valrhona cocoa.',
        'price' => 150.00,
        'badge' => 'Veg',
        'img' => '../Image_Content/tiramisu.jfif',
    ],
    [
        'id' => 'd2',
        'category' => 'Desserts',
        'name' => 'Warm Chocolate Fondant',
        'desc' => 'Dark chocolate lava cake with a molten centre, served with vanilla bean ice cream.',
        'price' => 105.00,
        'badge' => 'Veg',
        'img' => '../Image_Content/warm chocolate.jfif',
    ],
    [
        'id' => 'dr1',
        'category' => 'Drinks',
        'name' => 'Fresh Citrus Lemonade',
        'desc' => 'Hand-pressed lemons with mint, agave syrup, and sparkling water. Refreshingly vibrant.',
        'price' => 50.00,
        'badge' => 'Veg',
        'img' => 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=700&q=80',
    ],
    [
        'id' => 'dr2',
        'category' => 'Drinks',
        'name' => 'Matcha Latte',
        'desc' => 'Premium ceremonial-grade matcha whisked with oat milk, served over ice with honey.',
        'price' => 65.00,
        'badge' => 'Veg',
        'img' => 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?w=700&q=80',
    ],
];

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

<!-- ── Cart Summary Panel (shown when items exist) ────────── -->
<?php if ($cart_count > 0): ?>
    <div class="cart-summary">
        <div class="cart-summary-inner">
            <div class="cart-info">
                <span class="cart-count-badge"><?= $cart_count ?></span>
                <span class="cart-label">Your Order</span>
                <span class="cart-total">₹<?= number_format($cart_total, 2) ?></span>
            </div>

            <div class="cart-details">
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
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>