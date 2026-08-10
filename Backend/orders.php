<?php
// orders.php — Premium Order Dashboard
require_once '../includes/auth.php';
auth_require(['admin', 'viewer']); // Both roles may view orders

$page_title = 'Order Dashboard — Obsidian';
$page_desc = 'Real-time order tracking dashboard for Obsidian restaurant.';
$_auth_role = auth_role();
$_auth_user = auth_user();

require_once '../includes/db.php';
include '../includes/header.php';

$orders = $pdo->query(
    "SELECT id, order_number, customer_name, items, total_price, order_time, scheduled_at, utr_number FROM orders ORDER BY order_time DESC"
)->fetchAll();

// ── Compute stats ──────────────────────────────────────────────
$total_orders = count($orders);
$max_order_id = empty($orders) ? 0 : max(array_column($orders, 'id'));
$grand_total = array_sum(array_column($orders, 'total_price'));
$avg_order = $total_orders > 0 ? $grand_total / $total_orders : 0;

// Most popular item
$item_counts = [];
foreach ($orders as $o) {
    $arr = json_decode($o['items'], true) ?? [];
    foreach ($arr as $item) {
        $clean = trim($item);
        if ($clean)
            $item_counts[$clean] = ($item_counts[$clean] ?? 0) + 1;
    }
}
arsort($item_counts);
$top_item = $item_counts ? array_key_first($item_counts) : '—';

// Orders today
$today_count = 0;
foreach ($orders as $o) {
    if (substr($o['order_time'], 0, 10) === date('Y-m-d'))
        $today_count++;
}

// ── Delete / status flash ──────────────────────────────────────
$del_status = $_GET['del'] ?? '';
$del_msg = $_GET['msg'] ?? '';
?>

<!-- ── Toast (delete feedback) ──────────────────────────────── -->
<?php if ($del_status): ?>
    <div class="toast-wrap" id="od-toast-wrap">
        <div class="toast <?= $del_status === 'success' ? 'success' : 'error' ?>">
            <span class="toast-icon"><?= $del_status === 'success' ? '✅' : '⚠️' ?></span>
            <span><?= htmlspecialchars($del_msg) ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- ── Auth badge (floating, shown on all auth pages) ──────────── -->
<div style="position:fixed;top:80px;right:20px;z-index:900;display:flex;gap:10px;align-items:center;">
    <?php if ($_auth_role === 'viewer'): ?>
    <span style="font-size:.72rem;color:#63b3ed;font-weight:600;background:rgba(99,179,237,.1);border:1px solid rgba(99,179,237,.25);padding:5px 12px;border-radius:20px;">
        👁️ Viewer
    </span>
    <?php endif; ?>
    <span style="font-size:.72rem;color:#e8a93c;font-weight:600;background:rgba(232,169,60,.1);border:1px solid rgba(232,169,60,.25);padding:5px 12px;border-radius:20px;">
        👤 <?= htmlspecialchars($_auth_user) ?>
    </span>
    <a href="logout.php" style="font-size:.72rem;color:#ff6b7a;font-weight:600;background:rgba(255,71,87,.08);border:1px solid rgba(255,71,87,.2);padding:5px 12px;border-radius:20px;text-decoration:none;transition:.2s;" onmouseover="this.style.background='rgba(255,71,87,.18)'" onmouseout="this.style.background='rgba(255,71,87,.08)'">
        🚪 Logout
    </a>
</div>

<?php if ($_auth_role === 'admin'): ?>
<!-- ── Confirm-Delete Modal (admin only) ─────────────────────────── -->
<div class="od-modal-backdrop" id="od-modal-backdrop" aria-hidden="true">
    <div class="od-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="od-modal-icon">🍽️</div>
        <h3 id="modal-title">Mark as Served?</h3>
        <p id="od-modal-desc">This will permanently remove order from the dashboard.</p>
        <div class="od-modal-actions">
            <button class="od-modal-cancel" id="od-modal-cancel">Cancel</button>
            <form method="POST" action="delete_order.php" id="od-delete-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="order_id" id="modal-order-id" value="">
                <button type="submit" class="od-modal-confirm">✓ Served — Remove</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Hero ─────────────────────────────────────────────────── -->
<header class="hero page-hero od-hero">
    <div class="hero-content">
        <div class="hero-tag">Live Dashboard</div>
        <h1>Order History</h1>
        <p class="hero-subtitle">Every order, every guest — presented beautifully</p>
        <div class="divider"><span>✦</span></div>
    </div>
</header>

<div class="od-wrap">


    <!-- ── Toolbar ─────────────────────────────────────────── -->
    <div class="od-toolbar">
        <a href="menu.php" class="back-link">← Back to Menu</a>

        <div class="od-controls">
            <div class="od-search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="od-search" class="od-search" placeholder="Search by name or item…"
                    autocomplete="off">
                <button class="search-clear" id="od-clear" aria-label="Clear search">✕</button>
            </div>

            <select id="od-sort" class="od-select">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="highest">Highest total</option>
                <option value="lowest">Lowest total</option>
            </select>
        </div>
    </div>

    <!-- ── Results summary ────────────────────────────────── -->
    <p class="od-count-label" id="od-count-label">
        Showing <strong><?= $total_orders ?></strong> order<?= $total_orders !== 1 ? 's' : '' ?>
    </p>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <span>🍽</span>
            <p>No orders yet. Go place one from the <a href="menu.php" style="color:var(--gold)">menu</a>!</p>
        </div>
    <?php else: ?>

        <!-- ── Order Cards Grid ───────────────────────────────── -->
        <div class="od-grid" id="od-grid">
            <?php foreach ($orders as $i => $order):
                $items_arr = json_decode($order['items'], true) ?? [];
                $dt = new DateTime($order['order_time']);
                $date_fmt = $dt->format('M j, Y');
                $time_fmt = $dt->format('g:i A');
                $items_searchable = implode(' ', $items_arr);
                $o_num = (int) ($order['order_number'] ?? 0);
                $sched = $order['scheduled_at'] ?? null;
                $sched_fmt = $sched ? (new DateTime($sched))->format('M j, g:i A') : '';
                $utr_num = $order['utr_number'] ?? '';
                ?>
                <div class="od-card" style="--delay:<?= min($i * 0.06, 0.6) ?>s"
                    data-name="<?= htmlspecialchars(strtolower($order['customer_name'])) ?>"
                    data-items="<?= htmlspecialchars(strtolower($items_searchable)) ?>"
                    data-total="<?= (float) $order['total_price'] ?>" data-time="<?= strtotime($order['order_time']) ?>"
                    id="card-<?= (int) $order['id'] ?>">

                    <!-- Card header -->
                    <div class="od-card-header">
                        <div class="od-card-avatar">
                            <?= mb_strtoupper(mb_substr($order['customer_name'], 0, 1)) ?>
                        </div>
                        <div class="od-card-info">
                            <div class="od-customer-name">
                                <?= htmlspecialchars($order['customer_name']) ?>
                                <?php if ($o_num > 0): ?>
                                    <span style="display:inline-block;font-size:.68rem;font-weight:700;color:#e8a93c;background:rgba(232,169,60,.12);border:1px solid rgba(232,169,60,.2);border-radius:6px;padding:2px 8px;margin-left:6px;vertical-align:middle;letter-spacing:.3px;">Order #<?= $o_num ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="od-datetime">
                                <span class="od-date"><?= $date_fmt ?></span>
                                <span class="od-time-dot">·</span>
                                <span class="od-time"><?= $time_fmt ?></span>
                            </div>
                            <?php if ($utr_num): ?>
                            <div style="font-size:.74rem;color:#2ed573;margin-top:3px;font-weight:500;">💳 UTR: <code style="font-family:monospace;background:rgba(46,213,115,.1);padding:1px 5px;border-radius:4px;"><?= htmlspecialchars($utr_num) ?></code></div>
                            <?php endif; ?>
                            <?php if ($sched): ?>
                            <div style="font-size:.74rem;color:#63b3ed;margin-top:3px;">📅 Scheduled: <?= htmlspecialchars($sched_fmt) ?></div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- Items list -->
                    <div class="od-items-section">
                        <div class="od-items-label">Items Ordered</div>
                        <ul class="od-items-ul">
                            <?php foreach ($items_arr as $item): ?>
                                <li class="od-item-chip"><?= htmlspecialchars(trim($item)) ?></li>
                            <?php endforeach; ?>
                            <?php if (empty($items_arr)): ?>
                                <li class="od-item-chip od-item-raw"><?= htmlspecialchars($order['items']) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Footer with price + served button -->
                    <div class="od-card-footer">
                        <div class="od-item-count"><?= count($items_arr) ?> item<?= count($items_arr) !== 1 ? 's' : '' ?></div>
                        <div class="od-card-footer-right">
                            <div class="od-price">₹<?= number_format((float) $order['total_price'], 2) ?></div>
                            <?php if ($_auth_role === 'admin'): ?>
                            <button class="od-serve-btn" data-id="<?= (int) $order['id'] ?>"
                                data-label="#<?= (int) $order['id'] ?> — <?= htmlspecialchars($order['customer_name']) ?>"
                                aria-label="Mark order #<?= (int) $order['id'] ?> as served"
                                title="Mark as served &amp; remove">
                                Served?
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- No results message -->
        <div class="od-no-results" id="od-no-results" style="display:none">
            <span>🔍</span>
            <p>No orders match your search.</p>
        </div>



    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

<style>
    /* ── Orders Dashboard Styles ─────────────────────────────────── */

    .od-hero {
        padding-bottom: 40px;
    }

    .od-wrap {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 24px 100px;
    }

    /* ── Stats Grid ─────────────────────────────────────────────── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 18px;
        margin-bottom: 40px;
    }

    .stat-card {
        position: relative;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 22px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        overflow: hidden;
        backdrop-filter: blur(10px);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        animation: floatIn 0.5s ease both;
    }

    .stat-card:nth-child(1) {
        animation-delay: .05s
    }

    .stat-card:nth-child(2) {
        animation-delay: .10s
    }

    .stat-card:nth-child(3) {
        animation-delay: .15s
    }

    .stat-card:nth-child(4) {
        animation-delay: .20s
    }

    .stat-card:nth-child(5) {
        animation-delay: .25s
    }

    .stat-card:hover {
        transform: translateY(-4px);
        border-color: color-mix(in srgb, var(--accent) 35%, transparent);
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.35), 0 0 20px color-mix(in srgb, var(--accent) 15%, transparent);
    }

    .stat-glow {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle 80px at 0% 100%, color-mix(in srgb, var(--accent) 10%, transparent), transparent 70%);
        pointer-events: none;
    }

    .stat-icon {
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--accent);
        line-height: 1.1;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .od-top-item {
        font-size: 1rem !important;
    }

    .stat-label {
        font-size: 0.78rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-top: 4px;
    }

    /* ── Toolbar ────────────────────────────────────────────────── */
    .od-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 20px;
    }

    .od-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .od-search-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-icon {
        position: absolute;
        left: 14px;
        font-size: 0.95rem;
        pointer-events: none;
        z-index: 1;
    }

    .od-search {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: var(--text);
        font-family: 'Outfit', sans-serif;
        font-size: 0.92rem;
        padding: 10px 36px 10px 40px;
        outline: none;
        width: 260px;
        transition: border-color 0.25s, background 0.25s;
    }

    .od-search::placeholder {
        color: var(--muted);
    }

    .od-search:focus {
        border-color: rgba(232, 169, 60, 0.45);
        background: rgba(255, 255, 255, 0.07);
    }

    .search-clear {
        position: absolute;
        right: 10px;
        background: none;
        border: none;
        color: var(--muted);
        cursor: pointer;
        font-size: 0.8rem;
        padding: 4px;
        opacity: 0;
        transition: opacity 0.2s, color 0.2s;
        pointer-events: none;
    }

    .search-clear.visible {
        opacity: 1;
        pointer-events: all;
    }

    .search-clear:hover {
        color: var(--error);
    }

    .od-select {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: var(--text);
        font-family: 'Outfit', sans-serif;
        font-size: 0.88rem;
        padding: 10px 36px 10px 14px;
        outline: none;
        cursor: pointer;
        transition: border-color 0.25s;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237a8090' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }

    .od-select:focus {
        border-color: rgba(232, 169, 60, 0.45);
    }

    .od-count-label {
        font-size: 0.88rem;
        color: var(--muted);
        margin-bottom: 22px;
    }

    /* ── Cards Grid ─────────────────────────────────────────────── */
    .od-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 22px;
        margin-bottom: 28px;
    }

    .od-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 18px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        backdrop-filter: blur(10px);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, opacity 0.35s ease;
        animation: floatIn 0.5s ease both;
        animation-delay: var(--delay, 0s);
        position: relative;
    }

    .od-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.03), transparent 60%);
        pointer-events: none;
    }

    .od-card:hover {
        transform: translateY(-6px);
        border-color: rgba(232, 169, 60, 0.25);
        box-shadow: 0 18px 48px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(232, 169, 60, 0.1);
    }

    /* Removing animation */
    .od-card.removing {
        opacity: 0;
        transform: scale(0.92) translateY(10px);
        pointer-events: none;
    }

    /* ── Card Header ─── */
    .od-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 22px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .od-card-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(232, 169, 60, 0.3), rgba(232, 169, 60, 0.08));
        border: 1px solid rgba(232, 169, 60, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--gold);
        flex-shrink: 0;
    }

    .od-card-info {
        flex: 1;
        min-width: 0;
    }

    .od-customer-name {
        font-weight: 600;
        font-size: 1rem;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .od-datetime {
        font-size: 0.8rem;
        color: var(--muted);
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .od-time-dot {
        color: rgba(255, 255, 255, 0.2);
    }

    .od-order-id {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--muted);
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 6px;
        padding: 4px 10px;
        flex-shrink: 0;
        font-family: monospace;
        letter-spacing: 0.5px;
    }

    /* ── Items Section ─── */
    .od-items-section {
        padding: 16px 22px;
        flex: 1;
    }

    .od-items-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--muted);
        margin-bottom: 10px;
        font-weight: 600;
    }

    .od-items-ul {
        list-style: none;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .od-item-chip {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 50px;
        padding: 4px 12px;
        font-size: 0.82rem;
        color: var(--text);
        transition: background 0.2s, border-color 0.2s;
    }

    .od-card:hover .od-item-chip {
        background: rgba(232, 169, 60, 0.07);
        border-color: rgba(232, 169, 60, 0.18);
    }

    /* ── Card Footer ─── */
    .od-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 22px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(0, 0, 0, 0.15);
        gap: 12px;
    }

    .od-card-footer-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .od-item-count {
        font-size: 0.83rem;
        color: var(--muted);
        flex: 1;
    }

    .od-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--gold);
        white-space: nowrap;
    }

    /* ── Serve / Remove button ─── */
    .od-serve-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgb(1, 10, 5);
        border-radius: 8px;
        color: var(--success);
        font-family: 'Outfit', sans-serif;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 6px 13px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s, transform 0.15s, box-shadow 0.2s;
        white-space: nowrap;
        letter-spacing: 0.3px;
    }

    .od-serve-btn:hover {
        background: rgba(46, 213, 115, 0.15);
        border-color: rgba(46, 213, 115, 0.6);
        box-shadow: 0 4px 16px rgba(46, 213, 115, 0.2);
        transform: translateY(-1px);
    }

    .od-serve-btn:active {
        transform: scale(0.96);
    }

    /* ── Summary Bar ─────────────────────────────────────────────── */
    .od-summary-bar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        font-size: 0.88rem;
        color: var(--muted);
        padding: 16px 4px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .od-summary-sep {
        color: rgba(255, 255, 255, 0.15);
    }

    .od-summary-total {
        color: var(--gold);
        font-size: 1rem;
    }

    .od-summary-count {
        font-weight: 500;
        color: var(--text);
    }

    /* ── Empty / No results ──────────────────────────────────────── */
    .od-no-results {
        text-align: center;
        padding: 60px 20px;
        color: var(--muted);
    }

    .od-no-results span {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 14px;
    }

    /* ── Confirm Modal ───────────────────────────────────────────── */
    .od-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.72);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        z-index: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }

    .od-modal-backdrop.open {
        opacity: 1;
        pointer-events: all;
    }

    .od-modal {
        background: #1a1d24;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 40px 36px;
        width: min(420px, calc(100vw - 40px));
        text-align: center;
        box-shadow: 0 32px 80px rgba(0, 0, 0, 0.7);
        transform: translateY(20px) scale(0.96);
        transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .od-modal-backdrop.open .od-modal {
        transform: translateY(0) scale(1);
    }

    .od-modal-icon {
        font-size: 3rem;
        margin-bottom: 16px;
    }

    .od-modal h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: var(--text);
        margin-bottom: 10px;
    }

    .od-modal p {
        font-size: 0.92rem;
        color: var(--muted);
        line-height: 1.6;
        margin-bottom: 28px;
    }

    .od-modal p strong {
        color: var(--text);
    }

    .od-modal-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .od-modal-cancel {
        flex: 1;
        padding: 13px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: var(--muted);
        font-family: 'Outfit', sans-serif;
        font-size: 0.95rem;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .od-modal-cancel:hover {
        background: rgba(255, 255, 255, 0.09);
        color: var(--text);
    }

    .od-modal-confirm {
        flex: 1.4;
        padding: 13px;
        background: linear-gradient(135deg, rgba(46, 213, 115, 0.25), rgba(46, 213, 115, 0.1));
        border: 1px solid rgba(46, 213, 115, 0.4);
        border-radius: 10px;
        color: var(--success);
        font-family: 'Outfit', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
    }

    .od-modal-confirm:hover {
        background: rgba(46, 213, 115, 0.25);
        box-shadow: 0 4px 20px rgba(46, 213, 115, 0.25);
        transform: translateY(-1px);
    }

    /* ── Responsive ──────────────────────────────────────────────── */
    @media (max-width: 640px) {
        .od-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .od-controls {
            width: 100%;
        }

        .od-search {
            width: 100%;
        }

        .od-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .stats-grid .stat-card:last-child {
            grid-column: span 2;
        }
    }
</style>

<script>
    (function () {
        /* ── Search / Sort ───────────────────────────────────────── */
        const grid = document.getElementById('od-grid');
        const cards = grid ? [...grid.querySelectorAll('.od-card')] : [];
        const searchEl = document.getElementById('od-search');
        const clearBtn = document.getElementById('od-clear');
        const sortEl = document.getElementById('od-sort');
        const countLabel = document.getElementById('od-count-label');
        const noResults = document.getElementById('od-no-results');
        const sumCount = document.getElementById('od-summary-count');
        const sumTotal = document.getElementById('od-summary-total');

        function formatMoney(n) {
            return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function applyFilters() {
            const q = (searchEl.value || '').toLowerCase().trim();
            const sort = sortEl.value;

            clearBtn.classList.toggle('visible', q.length > 0);

            let visible = cards.filter(c => {
                if (c.style.display === 'none' && c.classList.contains('removing')) return false;
                if (!q) return c.style.display !== 'none' || !c.classList.contains('removing');
                return c.dataset.name.includes(q) || c.dataset.items.includes(q);
            }).filter(c => !c.classList.contains('removing'));

            visible.sort((a, b) => {
                if (sort === 'newest') return b.dataset.time - a.dataset.time;
                if (sort === 'oldest') return a.dataset.time - b.dataset.time;
                if (sort === 'highest') return b.dataset.total - a.dataset.total;
                if (sort === 'lowest') return a.dataset.total - b.dataset.total;
                return 0;
            });

            cards.forEach(c => { if (!c.classList.contains('removing')) { c.style.display = 'none'; c.style.order = ''; } });

            visible.forEach((c, i) => {
                c.style.display = '';
                c.style.order = i;
            });

            const revTotal = visible.reduce((s, c) => s + parseFloat(c.dataset.total), 0);
            const n = visible.length;

            countLabel.innerHTML = `Showing <strong>${n}</strong> order${n !== 1 ? 's' : ''}${q ? ' matching <em>"' + q + '"</em>' : ''}`;
            if (sumCount) sumCount.textContent = `${n} order${n !== 1 ? 's' : ''}`;
            if (sumTotal) sumTotal.textContent = formatMoney(revTotal);

            if (noResults) noResults.style.display = n === 0 ? 'block' : 'none';
            const bar = document.getElementById('od-summary-bar');
            if (bar) bar.style.display = n === 0 ? 'none' : 'flex';
        }

        if (searchEl) searchEl.addEventListener('input', applyFilters);
        if (sortEl) sortEl.addEventListener('change', applyFilters);
        if (clearBtn) clearBtn.addEventListener('click', () => {
            searchEl.value = '';
            applyFilters();
            searchEl.focus();
        });

        /* ── Confirm Modal ───────────────────────────────────────── */
        const backdrop = document.getElementById('od-modal-backdrop');
        const cancelBtn = document.getElementById('od-modal-cancel');
        const orderIdInput = document.getElementById('modal-order-id');
        const orderLabel = document.getElementById('modal-order-label');

        function openModal(id, label) {
            orderIdInput.value = id;
            if (orderLabel) orderLabel.textContent = label;
            backdrop.classList.add('open');
            backdrop.setAttribute('aria-hidden', 'false');
            cancelBtn.focus();
        }

        function closeModal() {
            backdrop.classList.remove('open');
            backdrop.setAttribute('aria-hidden', 'true');
        }

        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', e => { if (e.target === backdrop) closeModal(); });

        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        // Wire "Served" buttons
        document.querySelectorAll('.od-serve-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                openModal(btn.dataset.id, btn.dataset.label);
            });
        });

        /* ── Auto-dismiss toast ──────────────────────────────────── */
        const toastWrap = document.getElementById('od-toast-wrap');
        if (toastWrap) {
            setTimeout(() => {
                toastWrap.style.transition = 'opacity 0.5s';
                toastWrap.style.opacity = '0';
                setTimeout(() => toastWrap.remove(), 500);
            }, 3500);
        }

        /* ── Stat value entrance animation ──────────────────────── */
        document.querySelectorAll('.stat-value').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(8px)';
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 200 + Math.random() * 300);
        });
        // Check for new orders every 15 seconds
        const initialMaxId = <?= $max_order_id ?? 0 ?>;
        const initialCount = <?= $total_orders ?? 0 ?>;

        setInterval(async function () {
            if (!document.getElementById('od-search').value && !document.getElementById('od-modal-backdrop').classList.contains('open')) {
                try {
                    const res = await fetch('check_orders.php');
                    const data = await res.json();
                    if (data.max_id > initialMaxId || data.count !== initialCount) {
                        window.location.href = window.location.pathname;
                    }
                } catch (e) {
                    console.error('Failed to check for new orders:', e);
                }
            }
        }, 15000);
    })();
</script>