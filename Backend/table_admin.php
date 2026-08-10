<?php
/**
 * Backend/table_admin.php — Table Reservation Management Dashboard
 * Admin-only page. Requires login.
 */
require_once '../includes/auth.php';
auth_require(['admin']);

require_once '../includes/db.php';

$page_title = 'Table Management — La Maison Admin';

// Fetch all tables
$tables = $pdo->query(
    "SELECT id, name, zone, capacity, status, x, y FROM `tables` ORDER BY zone, id"
)->fetchAll();

$total    = count($tables);
$free     = count(array_filter($tables, fn($t) => $t['status'] === 'FREE'));
$reserved = $total - $free;
$main_cnt = count(array_filter($tables, fn($t) => $t['zone'] === 'Main'));
$patio_cnt= count(array_filter($tables, fn($t) => $t['zone'] === 'Patio'));

// Auto release expired reservations
$pdo->exec("UPDATE `reservations` SET status = 'EXPIRED' WHERE status = 'CONFIRMED' AND TIMESTAMP(res_date, start_time) + INTERVAL 15 MINUTE < NOW()");

// Fetch reservations log
$reservations_log = $pdo->query(
    "SELECT r.*, t.name AS table_name, t.zone 
     FROM `reservations` r
     JOIN `tables` t ON r.table_id = t.id
     ORDER BY r.res_date DESC, r.start_time DESC
     LIMIT 50"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,700;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
    /* ════════════════════════════════════════════
       TABLE ADMIN — SCOPED STYLES
       ════════════════════════════════════════════ */

    .ta-page {
        max-width: 1280px;
        margin: 0 auto;
        padding: calc(var(--nav-height) + 40px) 24px 80px;
    }

    /* ── Back link ── */
    .ta-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--muted);
        font-size: 0.84rem;
        font-weight: 600;
        margin-bottom: 28px;
        transition: color 0.2s;
        text-decoration: none;
    }
    .ta-back:hover { color: var(--text); }

    /* ── Page title ── */
    .ta-title-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 28px;
    }
    .ta-title {
        font-family: var(--font-display);
        font-size: clamp(1.6rem, 4vw, 2.2rem);
        font-weight: 700;
        background: linear-gradient(135deg, #fff 40%, var(--gold) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .ta-title-sub {
        font-size: 0.82rem;
        color: var(--muted);
        margin-top: 4px;
    }

    /* ── Link to public page ── */
    .ta-public-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(46,213,115,0.1);
        border: 1px solid rgba(46,213,115,0.25);
        color: #2ed573;
        font-family: var(--font-body);
        font-size: 0.84rem;
        font-weight: 600;
        padding: 9px 18px;
        border-radius: 50px;
        cursor: pointer;
        transition: 0.2s;
        text-decoration: none;
        white-space: nowrap;
    }
    .ta-public-btn:hover { background: rgba(46,213,115,0.18); }

    /* ── Stats ── */
    .ta-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }
    @media (max-width: 900px) { .ta-stats { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 480px) { .ta-stats { grid-template-columns: 1fr 1fr; } }

    .ta-stat {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 16px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: border-color 0.25s, transform 0.25s;
    }
    .ta-stat:hover { border-color: rgba(232,169,60,0.22); transform: translateY(-2px); }
    .ta-stat-ico {
        font-size: 1.5rem;
        width: 46px; height: 46px;
        border-radius: 12px;
        background: rgba(232,169,60,0.08);
        border: 1px solid rgba(232,169,60,0.18);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .ta-stat-val {
        font-size: 1.7rem;
        font-weight: 700;
        font-family: var(--font-display);
        color: var(--text);
        line-height: 1;
    }
    .ta-stat-lbl {
        font-size: 0.75rem;
        color: var(--muted);
        margin-top: 3px;
    }
    .ta-stat-val.green { color: #2ed573; }
    .ta-stat-val.red   { color: #ff4757; }
    .ta-stat-val.blue  { color: #818cf8; }

    /* ── Toolbar ── */
    .ta-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .ta-filters { display: flex; gap: 8px; flex-wrap: wrap; }

    .ta-filter-btn {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.09);
        color: var(--muted);
        border-radius: 50px;
        padding: 7px 16px;
        font-family: var(--font-body);
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }
    .ta-filter-btn:hover,
    .ta-filter-btn.active {
        background: rgba(232,169,60,0.1);
        border-color: rgba(232,169,60,0.3);
        color: var(--gold);
    }

    .ta-view-toggle {
        display: flex;
        gap: 4px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 4px;
    }
    .ta-view-btn {
        background: none;
        border: none;
        color: var(--muted);
        font-size: 1rem;
        width: 34px; height: 34px;
        border-radius: 7px;
        cursor: pointer;
        transition: 0.2s;
        display: flex; align-items: center; justify-content: center;
    }
    .ta-view-btn.active { background: rgba(232,169,60,0.15); color: var(--gold); }

    /* ── Cards grid ── */
    .ta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }

    .ta-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 18px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
        position: relative;
        overflow: hidden;
    }
    .ta-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 18px 18px 0 0;
        background: linear-gradient(90deg, rgba(232,169,60,0.5), rgba(232,169,60,0.1));
        opacity: 0;
        transition: opacity 0.25s;
    }
    .ta-card:hover { border-color: rgba(232,169,60,0.2); transform: translateY(-3px); box-shadow: 0 8px 32px rgba(0,0,0,0.25); }
    .ta-card:hover::before { opacity: 1; }

    /* ── Card status visual states ── */
    .ta-card.card-free {
        background: rgba(46,213,115,0.05);
        border-color: rgba(46,213,115,0.18);
    }
    .ta-card.card-free::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 18px;
        box-shadow: inset 0 0 0 1px rgba(46,213,115,0.12);
        pointer-events: none;
    }
    .ta-card.card-free:hover { border-color: rgba(46,213,115,0.35); }

    .ta-card.card-reserved {
        background: rgba(255,71,87,0.04);
        border-color: rgba(255,71,87,0.16);
    }
    .ta-card.card-reserved:hover { border-color: rgba(255,71,87,0.28); }

    .ta-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
    }
    .ta-card-name {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1;
    }
    .ta-zone-pill {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 3px 10px;
        border-radius: 50px;
        white-space: nowrap;
    }
    .ta-zone-main  { background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25); color: #818cf8; }
    .ta-zone-patio { background: rgba(46,213,115,0.10); border: 1px solid rgba(46,213,115,0.25); color: #2ed573; }

    .ta-card-seats {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        align-items: center;
    }
    .ta-seat-ico { font-size: 0.9rem; }
    .ta-cap-lbl {
        font-size: 0.78rem;
        color: var(--muted);
        margin-left: 2px;
    }

    .ta-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding-top: 12px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    .ta-status-pill {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 50px;
        transition: 0.25s;
    }
    .ta-status-free     { background: rgba(46,213,115,0.1);  border: 1px solid rgba(46,213,115,0.3);  color: #2ed573; }
    .ta-status-reserved { background: rgba(255,71,87,0.08);  border: 1px solid rgba(255,71,87,0.3);   color: #ff4757; }

    /* ── Toggle switch (reused from existing admin style) ── */
    .adm-toggle-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: none; border: none; cursor: pointer;
        font-family: var(--font-body); font-size: 0.8rem; font-weight: 600;
        padding: 4px 0; transition: opacity 0.2s;
        vertical-align: middle;
    }
    .adm-toggle-btn:hover { opacity: 0.78; }
    .adm-toggle-btn:disabled { cursor: wait; opacity: 0.5; }
    .tog-track {
        width: 42px; height: 24px; border-radius: 12px;
        position: relative; transition: background 0.3s; flex-shrink: 0;
        display: inline-block;
    }
    .tog-knob {
        position: absolute; top: 4px; left: 4px;
        width: 16px; height: 16px; border-radius: 50%; transition: 0.3s ease;
    }
    .tog-on .tog-track { background: rgba(46,213,115,0.25); box-shadow: inset 0 0 0 1px rgba(46,213,115,0.5); }
    .tog-on .tog-knob  { background: #2ed573; transform: translateX(18px); box-shadow: 0 2px 6px rgba(46,213,115,0.55); }
    .tog-off .tog-track { background: rgba(255,71,87,0.15); box-shadow: inset 0 0 0 1px rgba(255,71,87,0.4); }
    .tog-off .tog-knob  { background: #ff4757; transform: translateX(0); box-shadow: 0 2px 6px rgba(255,71,87,0.45); }

    /* ── List view ── */
    .ta-list { display: none; }
    .ta-list.visible { display: block; }
    .ta-grid.hidden  { display: none; }

    .ta-list-wrap {
        background: rgba(255,255,255,0.025);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 18px;
        overflow: hidden;
        overflow-x: auto;
    }
    .ta-list-wrap table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        min-width: 540px;
    }
    .ta-list-wrap thead th {
        background: rgba(232,169,60,0.07);
        color: var(--gold);
        font-weight: 700;
        padding: 13px 16px;
        text-align: left;
        font-size: 0.73rem;
        letter-spacing: 1px;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .ta-list-wrap tbody tr {
        border-bottom: 1px solid rgba(255,255,255,0.04);
        transition: background 0.2s;
    }
    .ta-list-wrap tbody tr:last-child { border-bottom: none; }
    .ta-list-wrap tbody tr:hover { background: rgba(232,169,60,0.03); }
    .ta-list-wrap tbody td {
        padding: 10px 16px;
        color: var(--muted);
        vertical-align: middle;
    }
    .ta-list-name { color: var(--text) !important; font-weight: 700; font-size: 0.95rem; }
    .ta-list-cap  { font-weight: 600; color: var(--text) !important; }

    /* ── Empty state ── */
    .ta-empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--muted);
        grid-column: 1 / -1;
    }
    .ta-empty span { font-size: 2.8rem; display: block; margin-bottom: 12px; }

    /* ── Toast ── */
    .ta-toast {
        position: fixed;
        bottom: 28px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: rgba(19,22,27,0.97);
        border: 1px solid rgba(46,213,115,0.4);
        border-radius: 14px;
        padding: 12px 22px;
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text);
        z-index: 9999;
        box-shadow: 0 8px 32px rgba(0,0,0,0.55);
        transition: transform 0.38s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s;
        opacity: 0;
        pointer-events: none;
    }
    .ta-toast.visible { transform: translateX(-50%) translateY(0); opacity: 1; }
    .ta-toast-msg { color: #2ed573; }
    .ta-toast.err  { border-color: rgba(255,71,87,0.4); }
    .ta-toast.err .ta-toast-msg { color: #ff4757; }

    @media (max-width: 640px) {
        .ta-page { padding: calc(var(--nav-height) + 24px) 14px 60px; }
        .ta-grid  { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .ta-card  { padding: 14px; }
    }
    </style>
</head>
<body>

<!-- Inline nav (standalone admin — no header.php nav) -->
<nav class="main-nav">
    <div class="nav-inner">
        <a href="../index.html" class="nav-logo">
            <img src="../Image_Content/image.svg" alt="La Maison Logo" class="logo-icon">
            <span class="logo-text">La Maison</span>
        </a>
        <ul class="nav-links" style="display:flex;list-style:none;gap:24px;align-items:center;">
            <li><a href="admin.php" style="color:var(--muted);font-size:0.9rem;font-weight:500;">← Menu Admin</a></li>
            <li><a href="logout.php" style="color:var(--muted);font-size:0.9rem;font-weight:500;">Logout</a></li>
        </ul>
    </div>
</nav>

<main class="ta-page">

    <!-- Back link -->
    <a href="admin.php" class="ta-back">← Back to Admin Dashboard</a>

    <!-- Title row -->
    <div class="ta-title-row">
        <div>
            <div class="ta-title">🪑 Table Management</div>
            <div class="ta-title-sub">Toggle reservation status in real time</div>
        </div>
        <a href="../Frontend/reservations.php" target="_blank" class="ta-public-btn">
            🌐 View Public Floor Plan
        </a>
    </div>

    <!-- Stats bar -->
    <div class="ta-stats">
        <div class="ta-stat">
            <div class="ta-stat-ico">🪑</div>
            <div>
                <div class="ta-stat-val" id="stat-total"><?= $total ?></div>
                <div class="ta-stat-lbl">Total Tables</div>
            </div>
        </div>
        <div class="ta-stat">
            <div class="ta-stat-ico">✅</div>
            <div>
                <div class="ta-stat-val green" id="stat-free"><?= $free ?></div>
                <div class="ta-stat-lbl">Available</div>
            </div>
        </div>
        <div class="ta-stat">
            <div class="ta-stat-ico">🔒</div>
            <div>
                <div class="ta-stat-val red" id="stat-reserved"><?= $reserved ?></div>
                <div class="ta-stat-lbl">Reserved</div>
            </div>
        </div>
        <div class="ta-stat">
            <div class="ta-stat-ico">🏛️</div>
            <div>
                <div class="ta-stat-val blue"><?= $main_cnt ?> / <?= $patio_cnt ?></div>
                <div class="ta-stat-lbl">Main / Patio</div>
            </div>
        </div>
    </div>

    <!-- Toolbar: filters + view toggle -->
    <div class="ta-toolbar">
        <div class="ta-filters" id="ta-filters">
            <button class="ta-filter-btn active" data-filter="all">All Zones</button>
            <button class="ta-filter-btn" data-filter="zone-Main">🏛️ Main</button>
            <button class="ta-filter-btn" data-filter="zone-Patio">🌿 Patio</button>
            <button class="ta-filter-btn" data-filter="status-free">✅ Free</button>
            <button class="ta-filter-btn" data-filter="status-reserved">🔒 Reserved</button>
        </div>
        <div class="ta-view-toggle">
            <button class="ta-view-btn active" id="btn-grid" title="Grid view">⊞</button>
            <button class="ta-view-btn" id="btn-list" title="List view">☰</button>
        </div>
    </div>

    <!-- Grid view -->
    <div class="ta-grid" id="ta-grid">
        <?php foreach ($tables as $t): ?>
        <?php
            $status_cls = strtolower($t['status']); // 'free' or 'reserved'
            $tog_class  = $status_cls === 'free' ? 'tog-on' : 'tog-off';
            $zone_cls   = 'ta-zone-' . strtolower($t['zone']);
        ?>
        <div class="ta-card card-<?= $status_cls ?>"
             data-id="<?= $t['id'] ?>"
             data-zone="<?= $t['zone'] ?>"
             data-status="<?= $status_cls ?>"
             id="card-<?= $t['id'] ?>"
             data-zone-raw="<?= strtolower($t['zone']) ?>"
             data-status-raw="<?= $status_cls ?>"
             data-capacity="<?= $t['capacity'] ?>"
             data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>">
            <div class="ta-card-top">
                <div class="ta-card-name"><?= htmlspecialchars($t['name']) ?></div>
                <span class="ta-zone-pill <?= $zone_cls ?>"><?= htmlspecialchars($t['zone']) ?></span>
            </div>
            <div class="ta-card-seats">
                <?php for ($i = 0; $i < $t['capacity']; $i++): ?>
                <span class="ta-seat-ico">🪑</span>
                <?php endfor; ?>
                <span class="ta-cap-lbl"><?= $t['capacity'] ?> seats</span>
            </div>
            <div class="ta-card-footer">
                <span class="ta-status-pill ta-status-<?= $status_cls ?>" id="pill-<?= $t['id'] ?>">
                    <?= $t['status'] ?>
                </span>
                <button class="adm-toggle-btn <?= $tog_class ?>"
                        id="toggle-<?= $t['id'] ?>"
                        data-id="<?= $t['id'] ?>"
                        data-status="<?= $status_cls ?>"
                        aria-label="Toggle status for <?= htmlspecialchars($t['name']) ?>">
                    <span class="tog-track"><span class="tog-knob"></span></span>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="ta-empty" id="grid-empty" style="display:none;">
            <span>🔍</span>
            <p>No tables match this filter.</p>
        </div>
    </div>

    <!-- List view -->
    <div class="ta-list" id="ta-list">
        <div class="ta-list-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Zone</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Toggle</th>
                    </tr>
                </thead>
                <tbody id="ta-list-body">
                    <?php foreach ($tables as $t): ?>
                    <?php
                        $status_cls = strtolower($t['status']);
                        $tog_class  = $status_cls === 'free' ? 'tog-on' : 'tog-off';
                        $zone_cls   = 'ta-zone-' . strtolower($t['zone']);
                    ?>
                    <tr data-id="<?= $t['id'] ?>"
                        data-zone="<?= $t['zone'] ?>"
                        data-status="<?= $status_cls ?>"
                        id="row-<?= $t['id'] ?>">
                        <td class="ta-list-name"><?= htmlspecialchars($t['name']) ?></td>
                        <td><span class="ta-zone-pill <?= $zone_cls ?>"><?= htmlspecialchars($t['zone']) ?></span></td>
                        <td class="ta-list-cap"><?= $t['capacity'] ?> seats</td>
                        <td>
                            <span class="ta-status-pill ta-status-<?= $status_cls ?>" id="list-pill-<?= $t['id'] ?>">
                                <?= $t['status'] ?>
                            </span>
                        </td>
                        <td>
                            <button class="adm-toggle-btn <?= $tog_class ?>"
                                    id="list-toggle-<?= $t['id'] ?>"
                                    data-id="<?= $t['id'] ?>"
                                    data-status="<?= $status_cls ?>"
                                    aria-label="Toggle status for <?= htmlspecialchars($t['name']) ?>">
                                <span class="tog-track"><span class="tog-knob"></span></span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customer Booking Records Table -->
    <div style="margin-top: 48px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h2 style="font-family:'Playfair Display',serif;font-size:1.35rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:10px;">
                <span>📋</span> Customer Booking Log & Cancellation Pass Keys
            </h2>
            <span style="font-size:0.78rem;color:var(--muted);">Total Logged: <?= count($reservations_log) ?></span>
        </div>

        <div class="ta-list-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Customer</th>
                        <th>Contact Email & Phone</th>
                        <th>Date & Time Slot</th>
                        <th>Guests</th>
                        <th>Status</th>
                        <th>Cancellation Token</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations_log)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:32px;color:var(--muted);">
                            No customer reservations found yet.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reservations_log as $r): ?>
                    <?php
                        $st_color = match($r['status']) {
                            'CONFIRMED' => '#2ed573',
                            'CANCELLED' => '#ff4757',
                            'EXPIRED'   => '#ffa500',
                            default     => '#7a8090'
                        };
                    ?>
                    <tr>
                        <td class="ta-list-name"><?= htmlspecialchars($r['table_name']) ?> <small style="color:var(--muted);">(<?= htmlspecialchars($r['zone']) ?>)</small></td>
                        <td style="color:var(--text);font-weight:600;"><?= htmlspecialchars($r['customer_name']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($r['customer_email']) ?></div>
                            <small style="color:var(--muted);"><?= htmlspecialchars($r['customer_phone']) ?></small>
                        </td>
                        <td>
                            <strong style="color:var(--text);"><?= htmlspecialchars($r['res_date']) ?></strong><br>
                            <small style="color:var(--gold);"><?= substr($r['start_time'], 0, 5) ?> - <?= substr($r['end_time'], 0, 5) ?> (<?= $r['duration_hours'] ?>h)</small>
                        </td>
                        <td><?= (int)$r['guests'] ?> Guests</td>
                        <td>
                            <span style="font-size:0.72rem;font-weight:700;letter-spacing:0.5px;padding:3px 10px;border-radius:50px;color:<?= $st_color ?>;background:<?= $st_color ?>15;border:1px solid <?= $st_color ?>40;">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                        <td>
                            <code style="font-family:monospace;font-size:0.75rem;color:var(--gold);background:rgba(232,169,60,0.1);padding:3px 8px;border-radius:6px;">
                                <?= htmlspecialchars($r['cancellation_token']) ?>
                            </code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Toast -->
<div class="ta-toast" id="ta-toast" role="status">
    <span class="ta-toast-ico">✅</span>
    <span class="ta-toast-msg" id="ta-toast-msg">Status updated</span>
</div>

<script>
// ── Table Admin — JS ─────────────────────────────────────────────
(function () {
    'use strict';

    const API = '../api/tables_status.php';

    // ── View Toggle ──────────────────────────────────────────────
    const gridView  = document.getElementById('ta-grid');
    const listView  = document.getElementById('ta-list');
    const btnGrid   = document.getElementById('btn-grid');
    const btnList   = document.getElementById('btn-list');

    btnGrid.addEventListener('click', () => {
        gridView.classList.remove('hidden');
        listView.classList.remove('visible');
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
    });
    btnList.addEventListener('click', () => {
        gridView.classList.add('hidden');
        listView.classList.add('visible');
        btnList.classList.add('active');
        btnGrid.classList.remove('active');
    });

    // ── Filter ───────────────────────────────────────────────────
    const filterBtns = document.querySelectorAll('.ta-filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilter(btn.dataset.filter);
        });
    });

    function applyFilter(filter) {
        const cards = gridView.querySelectorAll('.ta-card');
        const rows  = document.querySelectorAll('#ta-list-body tr[data-id]');

        let visibleCards = 0;
        cards.forEach(card => {
            const match = matchFilter(card, filter);
            card.style.display = match ? '' : 'none';
            if (match) visibleCards++;
        });

        const empty = document.getElementById('grid-empty');
        empty.style.display = visibleCards === 0 ? '' : 'none';

        rows.forEach(row => {
            row.style.display = matchFilter(row, filter) ? '' : 'none';
        });
    }

    function matchFilter(el, filter) {
        if (filter === 'all') return true;
        const [type, val] = filter.split('-');
        if (type === 'zone')   return el.dataset.zone   === val;
        if (type === 'status') return el.dataset.status === val;
        return true;
    }

    // ── Toggle Status ────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.adm-toggle-btn');
        if (!btn) return;

        const id         = btn.dataset.id;
        const curStatus  = btn.dataset.status; // 'free' or 'reserved'
        const newStatus  = curStatus === 'free' ? 'RESERVED' : 'FREE';
        const newStatusLow = newStatus.toLowerCase();

        btn.disabled = true;

        try {
            const res = await fetch(API + '?id=' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            const data = await res.json();

            if (!data.success) throw new Error(data.error || 'Unknown error');

            // Update ALL toggle buttons for this table (grid + list)
            document.querySelectorAll(`.adm-toggle-btn[data-id="${id}"]`).forEach(b => {
                b.dataset.status = newStatusLow;
                b.classList.remove('tog-on', 'tog-off');
                b.classList.add(newStatusLow === 'free' ? 'tog-on' : 'tog-off');
                b.disabled = false;
            });

            // Update card/row data attributes, status pills, and card visual class
            ['card', 'row'].forEach(prefix => {
                const el = document.getElementById(prefix + '-' + id);
                if (el) {
                    el.dataset.status = newStatusLow;
                    // Swap visual state class on the grid card
                    if (prefix === 'card') {
                        if (newStatusLow === 'free') {
                            el.classList.remove('card-reserved');
                            el.classList.add('card-free');
                        } else {
                            el.classList.remove('card-free');
                            el.classList.add('card-reserved');
                        }
                    }
                }
            });

            ['pill', 'list-pill'].forEach(prefix => {
                const pill = document.getElementById(prefix + '-' + id);
                if (pill) {
                    pill.textContent = newStatus;
                    pill.classList.remove('ta-status-free', 'ta-status-reserved');
                    pill.classList.add('ta-status-' + newStatusLow);
                }
            });

            // Update counters
            const freeEl = document.getElementById('stat-free');
            const resEl  = document.getElementById('stat-reserved');
            const freeNum = parseInt(freeEl.textContent, 10);
            const resNum  = parseInt(resEl.textContent, 10);
            if (newStatusLow === 'reserved') {
                freeEl.textContent = freeNum - 1;
                resEl.textContent  = resNum  + 1;
            } else {
                freeEl.textContent = freeNum + 1;
                resEl.textContent  = Math.max(0, resNum - 1);
            }

            // Re-apply current active filter
            const activeFilter = document.querySelector('.ta-filter-btn.active');
            if (activeFilter) applyFilter(activeFilter.dataset.filter);

            showToast('✅', data.data.name + ' → ' + newStatus, false);
        } catch (err) {
            showToast('❌', 'Update failed: ' + err.message, true);
            document.querySelectorAll(`.adm-toggle-btn[data-id="${id}"]`).forEach(b => { b.disabled = false; });
        }
    });

    // ── Toast ────────────────────────────────────────────────────
    const toast    = document.getElementById('ta-toast');
    const toastMsg = document.getElementById('ta-toast-msg');
    const toastIco = toast.querySelector('.ta-toast-ico');
    let toastTimer;

    function showToast(ico, msg, isError) {
        clearTimeout(toastTimer);
        toastIco.textContent = ico;
        toastMsg.textContent = msg;
        toast.classList.toggle('err', isError);
        toast.classList.add('visible');
        toastTimer = setTimeout(() => toast.classList.remove('visible'), 3500);
    }
})();
</script>
</body>
</html>
