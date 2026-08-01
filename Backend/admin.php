<?php
/**
 * admin.php — Menu Item Management Panel
 */
session_start();
$page_title = 'Admin Panel — La Maison';
$page_desc  = 'Manage your restaurant menu items.';

require_once '../includes/db.php';

$items         = $pdo->query("SELECT * FROM menu_items ORDER BY sort_order ASC, created_at ASC")->fetchAll();
$total_items   = count($items);
$visible_items = count(array_filter($items, fn($i) => $i['is_visible']));
$hidden_items  = $total_items - $visible_items;
$categories    = array_unique(array_column($items, 'category'));
$cat_count     = count($categories);

$status     = $_GET['status'] ?? '';
$message    = htmlspecialchars($_GET['msg'] ?? '', ENT_QUOTES, 'UTF-8');
$filter_cat = $_GET['filter'] ?? 'all';
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
    /* ══════════════════════════════════════════
       ADMIN PAGE — SCOPED STYLES (no conflicts)
       ══════════════════════════════════════════ */

    /* Override global table styles inside .adm-table-wrap only */
    .adm-table-wrap table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }
    .adm-table-wrap thead th {
        background: rgba(232,169,60,0.08);
        color: #e8a93c;
        font-weight: 600;
        padding: 14px 16px;
        text-align: left;
        font-size: 0.75rem;
        letter-spacing: 1px;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        white-space: nowrap;
    }
    .adm-table-wrap tbody tr {
        border-bottom: 1px solid rgba(255,255,255,0.04);
        transition: background 0.2s;
    }
    .adm-table-wrap tbody tr:last-child { border-bottom: none; }
    .adm-table-wrap tbody tr:hover { background: rgba(232,169,60,0.04); }
    .adm-table-wrap tbody td {
        padding: 10px 16px;
        color: #7a8090;
        vertical-align: middle;
    }

    /* ── Page wrap ── */
    .adm-page { max-width: 1280px; margin: 0 auto; padding: 0 24px 80px; }

    /* ── Stats grid ── */
    .adm-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }
    @media (max-width: 900px) { .adm-stats { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 500px) { .adm-stats { grid-template-columns: 1fr; } }

    .adm-stat {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: 0.3s;
    }
    .adm-stat:hover { border-color: rgba(232,169,60,0.25); transform: translateY(-3px); }
    .adm-stat-ico {
        font-size: 1.8rem;
        width: 50px; height: 50px;
        border-radius: 14px;
        background: rgba(232,169,60,0.1);
        border: 1px solid rgba(232,169,60,0.2);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .adm-stat-val {
        font-size: 1.9rem;
        font-weight: 700;
        font-family: 'Playfair Display', serif;
        color: #f0f1f3;
        line-height: 1;
    }
    .adm-stat-lbl {
        font-size: 0.8rem;
        color: #7a8090;
        margin-top: 3px;
        letter-spacing: 0.3px;
    }

    /* ── Toolbar ── */
    .adm-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 20px;
    }
    .adm-toolbar-r { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .adm-filters { display: flex; gap: 8px; flex-wrap: wrap; }
    .adm-add {
        background: linear-gradient(135deg, #e8a93c, #c4891e);
        color: #0c0e11;
        border: none;
        border-radius: 50px;
        padding: 11px 24px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(232,169,60,0.3);
        transition: 0.3s;
        white-space: nowrap;
    }
    .adm-add:hover { box-shadow: 0 6px 24px rgba(232,169,60,0.45); transform: translateY(-1px); }

    /* ── Table wrap ── */
    .adm-table-wrap {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 18px;
        overflow: hidden;
        overflow-x: auto;
    }

    /* ── Thumbnail ── */
    .adm-thumb {
        width: 54px; height: 54px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.07);
        flex-shrink: 0;
    }
    .adm-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

    /* ── Name cell ── */
    .adm-nc strong { display: block; color: #f0f1f3; font-size: 0.92rem; }
    .adm-nc small  { color: rgba(122,128,144,0.55); font-size: 0.72rem; font-family: monospace; }

    /* ── Category pill ── */
    .adm-cat {
        display: inline-block;
        background: rgba(232,169,60,0.08);
        border: 1px solid rgba(232,169,60,0.15);
        color: #e8a93c;
        padding: 3px 10px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ── Veg/Non-Veg pill ── */
    .adm-pill {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 0.75rem; font-weight: 500;
        padding: 3px 10px; border-radius: 50px;
    }
    .pill-veg    { background: rgba(46,213,115,0.1); border: 1px solid rgba(46,213,115,0.25); color: #2ed573; }
    .pill-nonveg { background: rgba(255,71,87,0.1);  border: 1px solid rgba(255,71,87,0.25);  color: #ff4757; }

    /* ── Price ── */
    .adm-price { color: #e8a93c !important; font-weight: 700; white-space: nowrap; }

    /* ── Hidden row dimming ── */
    .row-hidden { opacity: 0.45; }
    .row-hidden:hover { opacity: 0.8; }

    /* ── Toggle switch ── */
    .adm-toggle-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: none; border: none; cursor: pointer;
        font-family: 'Outfit', sans-serif; font-size: 0.8rem; font-weight: 600;
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
    .tog-on  .tog-lbl  { color: #2ed573; }
    .tog-off .tog-track { background: rgba(255,71,87,0.15); box-shadow: inset 0 0 0 1px rgba(255,71,87,0.4); }
    .tog-off .tog-knob  { background: #ff4757; transform: translateX(0); box-shadow: 0 2px 6px rgba(255,71,87,0.45); }
    .tog-off .tog-lbl   { color: #ff4757; }

    /* ── Action buttons cell ── */
    .adm-actions-cell { white-space: nowrap; }
    .adm-btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 7px 14px; border-radius: 8px;
        font-family: 'Outfit', sans-serif; font-size: 0.8rem; font-weight: 600;
        cursor: pointer; border: 1px solid; transition: 0.25s; background: transparent;
        margin-right: 6px;
    }
    .adm-btn:last-child { margin-right: 0; }
    .adm-btn-edit   { color: #e8a93c; border-color: rgba(232,169,60,0.3); }
    .adm-btn-edit:hover  { background: rgba(232,169,60,0.12); border-color: #e8a93c; }
    .adm-btn-del    { color: #ff4757; border-color: rgba(255,71,87,0.3); }
    .adm-btn-del:hover   { background: rgba(255,71,87,0.12); border-color: #ff4757; }

    /* ══════════════════════════════════════════
       MODALS
       ══════════════════════════════════════════ */
    .adm-backdrop {
        display: none;
        position: fixed; inset: 0; z-index: 2000;
        background: rgba(0,0,0,0.72);
        backdrop-filter: blur(6px);
        align-items: center; justify-content: center;
        padding: 16px;
        overflow-y: auto;
    }
    .adm-backdrop.open { display: flex; }

    .adm-modal-box {
        background: #13161b;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        width: 100%; max-width: 640px;
        padding: 36px;
        position: relative;
        box-shadow: 0 24px 80px rgba(0,0,0,0.65);
        animation: modalIn 0.28s ease;
        margin: auto;
    }
    .adm-modal-sm { max-width: 420px; text-align: center; }
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(18px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .adm-modal-close {
        position: absolute; top: 14px; right: 16px;
        background: none; border: none; color: #7a8090; font-size: 1.7rem;
        cursor: pointer; width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; transition: 0.2s;
    }
    .adm-modal-close:hover { background: rgba(255,255,255,0.07); color: #f0f1f3; }

    .modal-ico { font-size: 2.2rem; display: block; margin-bottom: 10px; }
    .modal-title {
        font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700;
        margin-bottom: 6px;
        background: linear-gradient(135deg, #fff 40%, #e8a93c 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .modal-sub { color: #7a8090; font-size: 0.9rem; margin-bottom: 28px; }

    /* ── Form inside modal ── */
    .adm-form { display: flex; flex-direction: column; gap: 16px; }
    .adm-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 500px) { .adm-row2 { grid-template-columns: 1fr; } }
    .adm-fgroup { display: flex; flex-direction: column; gap: 5px; }
    .adm-fgroup label {
        font-size: 0.78rem; font-weight: 600; letter-spacing: 0.5px;
        color: #7a8090; text-transform: uppercase;
    }
    .adm-fgroup input,
    .adm-fgroup textarea,
    .adm-fgroup select {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 8px;
        color: #f0f1f3;
        font-family: 'Outfit', sans-serif;
        font-size: 0.93rem;
        padding: 11px 14px;
        outline: none;
        width: 100%;
        transition: border-color 0.2s;
    }
    .adm-fgroup input:focus,
    .adm-fgroup textarea:focus,
    .adm-fgroup select:focus { border-color: rgba(232,169,60,0.45); }
    .adm-fgroup input::placeholder,
    .adm-fgroup textarea::placeholder { color: rgba(122,128,144,0.5); }
    .adm-fgroup textarea { resize: vertical; min-height: 80px; }
    .adm-fgroup select {
        cursor: pointer; appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' fill='%237a8090'%3E%3Cpath d='M1 1l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center;
        padding-right: 32px;
    }
    .adm-fgroup select option { background: #13161b; color: #f0f1f3; }
    .adm-hint { font-size: 0.73rem; color: rgba(122,128,144,0.55); margin-top: 2px; }
    input[disabled], input[readonly] { opacity: 0.55; cursor: not-allowed; }

    /* ── Checkbox ── */
    .adm-check-wrap {
        display: flex; align-items: center; gap: 10px;
        cursor: pointer; padding-top: 24px;
        font-size: 0.9rem; color: #f0f1f3; font-weight: 500;
    }
    .adm-check-wrap input { display: none; }
    .adm-check-box {
        width: 22px; height: 22px;
        border: 2px solid rgba(255,255,255,0.15);
        border-radius: 6px; position: relative; transition: 0.2s; flex-shrink: 0;
    }
    .adm-check-wrap input:checked + .adm-check-box {
        background: #e8a93c; border-color: #e8a93c;
    }
    .adm-check-wrap input:checked + .adm-check-box::after {
        content: '✓'; position: absolute; top: 50%; left: 50%;
        transform: translate(-50%,-50%); color: #0c0e11; font-size: 0.78rem; font-weight: 800;
    }

    /* ── Upload area ── */
    .adm-upload {
        position: relative; border: 2px dashed rgba(255,255,255,0.1);
        border-radius: 12px; cursor: pointer; min-height: 130px;
        transition: 0.25s; overflow: hidden;
    }
    .adm-upload:hover, .adm-upload.drag { border-color: rgba(232,169,60,0.45); background: rgba(232,169,60,0.04); }
    .adm-upload input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width:100%; height:100%; }
    .adm-upload-prompt {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 6px; padding: 28px 20px; color: #7a8090; font-size: 0.88rem; pointer-events: none;
    }
    .adm-upload-prompt span.ico { font-size: 2rem; opacity: 0.6; }
    .adm-upload-prompt small { font-size: 0.75rem; color: rgba(122,128,144,0.5); }
    .adm-upload-preview { display: none; padding: 10px; position: relative; }
    .adm-upload-preview img { width:100%; max-height:200px; object-fit:cover; border-radius:8px; }
    .adm-upload-preview .rm {
        position: absolute; top: 16px; right: 16px; width: 26px; height: 26px;
        background: rgba(255,71,87,0.9); border: none; border-radius: 50%;
        color: white; font-size: 0.85rem; cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: 0.2s; z-index: 2;
    }
    .adm-upload-preview .rm:hover { background: #ff4757; transform: scale(1.1); }
    .cur-img { display: none; align-items: center; gap: 10px; margin-top: 8px; }
    .cur-img img { width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid rgba(255,255,255,0.07); }
    .cur-img span { font-size: 0.76rem; color: rgba(122,128,144,0.55); }

    /* ── Modal actions row ── */
    .adm-mactions {
        display: flex; gap: 12px; justify-content: flex-end;
        margin-top: 10px; padding-top: 18px;
        border-top: 1px solid rgba(255,255,255,0.06);
    }
    .adm-cancel {
        background: transparent; border: 1px solid rgba(255,255,255,0.12);
        border-radius: 50px; padding: 11px 26px;
        color: #7a8090; font-family: 'Outfit', sans-serif; font-size: 0.9rem; font-weight: 600;
        cursor: pointer; transition: 0.2s;
    }
    .adm-cancel:hover { border-color: rgba(255,255,255,0.25); color: #f0f1f3; }
    .adm-save {
        background: linear-gradient(135deg, #e8a93c, #c4891e);
        border: none; border-radius: 50px; padding: 11px 28px;
        color: #0c0e11; font-family: 'Outfit', sans-serif; font-size: 0.9rem; font-weight: 700;
        cursor: pointer; box-shadow: 0 4px 18px rgba(232,169,60,0.3); transition: 0.2s;
    }
    .adm-save:hover { box-shadow: 0 6px 24px rgba(232,169,60,0.45); transform: translateY(-1px); }
    .adm-del-confirm {
        background: linear-gradient(135deg, #ff4757, #d63031);
        border: none; border-radius: 50px; padding: 11px 28px;
        color: white; font-family: 'Outfit', sans-serif; font-size: 0.9rem; font-weight: 700;
        cursor: pointer; box-shadow: 0 4px 18px rgba(255,71,87,0.3); transition: 0.2s;
    }
    .adm-del-confirm:hover { box-shadow: 0 6px 24px rgba(255,71,87,0.45); transform: translateY(-1px); }

    /* ── Toast ── */
    .adm-toast-wrap { position:fixed; top:88px; right:24px; z-index:3000; pointer-events:none; }
    .adm-toast {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 20px; border-radius: 12px; font-size: 0.9rem; font-weight: 500;
        backdrop-filter: blur(16px); box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        animation: toastIn 0.35s ease-out, toastOut 0.4s ease-in 3.5s forwards;
        pointer-events: all;
    }
    .adm-toast.ok  { background: rgba(46,213,115,0.12); border: 1px solid rgba(46,213,115,0.3); color: #2ed573; }
    .adm-toast.err { background: rgba(255,71,87,0.12);  border: 1px solid rgba(255,71,87,0.3);  color: #ff4757; }
    @keyframes toastIn  { from { opacity:0; transform:translateX(40px); } to { opacity:1; transform:none; } }
    @keyframes toastOut { from { opacity:1; } to { opacity:0; pointer-events:none; } }

    /* ── Empty state ── */
    .adm-empty { text-align:center; padding:60px 20px; color:#7a8090; }
    .adm-empty span { font-size:3rem; display:block; margin-bottom:14px; }
    </style>
</head>
<body>

<!-- ── Nav (re-use existing nav styles) ──────────────────────── -->
<nav class="main-nav">
    <div class="nav-inner">
        <a href="../index.html" class="nav-logo">
            <img src="../Image_Content/image.svg" alt="La Maison Logo" class="logo-icon">
            <span class="logo-text">La Maison</span>
        </a>
        <ul class="nav-links">
            <li><a href="../index.html">Home</a></li>
            <li><a href="menu.php">Menu</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="admin.php" class="active">Admin</a></li>
        </ul>
    </div>
</nav>

<!-- ── Toast ──────────────────────────────────────────────────── -->
<?php if ($status): ?>
<div class="adm-toast-wrap">
    <div class="adm-toast <?= $status === 'success' ? 'ok' : 'err' ?>">
        <span><?= $status === 'success' ? '✅' : '⚠️' ?></span>
        <span><?= $message ?></span>
    </div>
</div>
<?php endif; ?>

<!-- ── Hero ───────────────────────────────────────────────────── -->
<header class="hero page-hero">
    <div class="hero-content">
        <div class="hero-tag">🔧 Management</div>
        <h1>Admin Panel</h1>
        <p class="hero-subtitle">Manage your entire menu — add, edit, hide, and organise dishes</p>
        <div class="divider"><span>✦</span></div>
    </div>
</header>

<!-- ── Main content ───────────────────────────────────────────── -->
<div class="adm-page">

    <!-- Stats -->
    <div class="adm-stats">
        <div class="adm-stat">
            <div class="adm-stat-ico">🍽️</div>
            <div><div class="adm-stat-val"><?= $total_items ?></div><div class="adm-stat-lbl">Total Items</div></div>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-ico">👁️</div>
            <div><div class="adm-stat-val"><?= $visible_items ?></div><div class="adm-stat-lbl">Visible</div></div>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-ico">🙈</div>
            <div><div class="adm-stat-val"><?= $hidden_items ?></div><div class="adm-stat-lbl">Hidden</div></div>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-ico">📂</div>
            <div><div class="adm-stat-val"><?= $cat_count ?></div><div class="adm-stat-lbl">Categories</div></div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="adm-toolbar">
        <a href="menu.php" class="back-link">← Back to Menu</a>
        <div class="adm-toolbar-r">
            <div class="adm-filters">
                <a href="admin.php?filter=all" class="cat-btn <?= $filter_cat === 'all' ? 'active' : '' ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="admin.php?filter=<?= urlencode($cat) ?>"
                       class="cat-btn <?= $filter_cat === $cat ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="adm-add" onclick="openAdd()">＋ Add New Item</button>
        </div>
    </div>

    <!-- Table -->
    <div class="adm-table-wrap">
        <?php if ($total_items === 0): ?>
            <div class="adm-empty"><span>🍽️</span><p>No items yet. Click "Add New Item" to begin.</p></div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:70px">Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Type</th>
                    <th style="width:55px">Order</th>
                    <th style="width:130px">Visibility</th>
                    <th style="width:190px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                if ($filter_cat !== 'all' && $item['category'] !== $filter_cat) continue;
            ?>
                <tr class="<?= $item['is_visible'] ? '' : 'row-hidden' ?>">
                    <td>
                        <div class="adm-thumb">
                            <img src="<?= htmlspecialchars($item['image_path']) ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 onerror="this.src='../Image_Content/image.svg'">
                        </div>
                    </td>
                    <td>
                        <div class="adm-nc">
                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                            <small>ID: <?= htmlspecialchars($item['id']) ?></small>
                        </div>
                    </td>
                    <td><span class="adm-cat"><?= htmlspecialchars($item['category']) ?></span></td>
                    <td class="adm-price">₹<?= number_format($item['price'], 2) ?></td>
                    <td>
                        <span class="adm-pill <?= $item['badge'] === 'Veg' ? 'pill-veg' : 'pill-nonveg' ?>">
                            <?= $item['badge'] === 'Veg' ? '🟢' : '🔴' ?> <?= htmlspecialchars($item['badge']) ?>
                        </span>
                    </td>
                    <td style="color:#7a8090;font-family:monospace"><?= intval($item['sort_order']) ?></td>
                    <td>
                        <button class="adm-toggle-btn <?= $item['is_visible'] ? 'tog-on' : 'tog-off' ?>"
                                data-id="<?= htmlspecialchars($item['id'], ENT_QUOTES) ?>"
                                onclick="toggleVis(this)">
                            <span class="tog-track"><span class="tog-knob"></span></span>
                            <span class="tog-lbl"><?= $item['is_visible'] ? 'Visible' : 'Hidden' ?></span>
                        </button>
                    </td>
                    <td class="adm-actions-cell">
                        <button class="adm-btn adm-btn-edit"
                                onclick='openEdit(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            ✏️ Edit
                        </button>
                        <button class="adm-btn adm-btn-del"
                                onclick="openDel('<?= htmlspecialchars($item['id'], ENT_QUOTES) ?>','<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>')">
                            🗑️ Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL — Add / Edit Item
     ════════════════════════════════════════════ -->
<div class="adm-backdrop" id="modal-edit">
    <div class="adm-modal-box">
        <button class="adm-modal-close" onclick="closeEdit()" title="Close">&times;</button>
        <span class="modal-ico" id="me-icon">🍽️</span>
        <div class="modal-title" id="me-title">Add New Item</div>
        <div class="modal-sub"  id="me-sub">Fill in the details below</div>

        <form method="POST" action="admin_actions.php" enctype="multipart/form-data" class="adm-form" id="edit-form">
            <input type="hidden" name="action"   id="f-action" value="add_item">

            <div class="adm-row2">
                <!-- ID -->
                <div class="adm-fgroup">
                    <label for="f-id">Item ID *</label>
                    <input type="text" id="f-id" name="item_id"
                           placeholder="e.g. s4, m5" required maxlength="20"
                           pattern="[a-zA-Z0-9_]+" title="Letters, numbers and underscores only">
                    <span class="adm-hint">Unique code — letters, numbers, underscores</span>
                </div>
                <!-- Category -->
                <div class="adm-fgroup">
                    <label for="f-cat">Category *</label>
                    <select id="f-cat" name="item_category" required>
                        <option value="">Select…</option>
                        <option value="Starters">🥗 Starters</option>
                        <option value="Mains">🍖 Mains</option>
                        <option value="Desserts">🍮 Desserts</option>
                        <option value="Drinks">🥤 Drinks</option>
                    </select>
                </div>
            </div>

            <!-- Name -->
            <div class="adm-fgroup">
                <label for="f-name">Dish Name *</label>
                <input type="text" id="f-name" name="item_name"
                       placeholder="e.g. Grilled Salmon Fillet" required maxlength="150">
            </div>

            <!-- Description -->
            <div class="adm-fgroup">
                <label for="f-desc">Description *</label>
                <textarea id="f-desc" name="item_description" rows="3" required
                          placeholder="Ingredients, preparation, flavour notes…"></textarea>
            </div>

            <div class="adm-row2">
                <!-- Price -->
                <div class="adm-fgroup">
                    <label for="f-price">Price (₹) *</label>
                    <input type="number" id="f-price" name="item_price"
                           placeholder="e.g. 280" required min="1" step="0.01">
                </div>
                <!-- Badge -->
                <div class="adm-fgroup">
                    <label for="f-badge">Type</label>
                    <select id="f-badge" name="item_badge">
                        <option value="Veg">🟢 Veg</option>
                        <option value="Non-Veg">🔴 Non-Veg</option>
                    </select>
                </div>
            </div>

            <div class="adm-row2">
                <!-- Sort order -->
                <div class="adm-fgroup">
                    <label for="f-sort">Sort Order</label>
                    <input type="number" id="f-sort" name="item_sort_order" value="0" min="0">
                    <span class="adm-hint">Lower = appears first</span>
                </div>
                <!-- Visible -->
                <div class="adm-fgroup" style="justify-content:flex-end">
                    <label class="adm-check-wrap">
                        <input type="checkbox" id="f-visible" name="item_visible" checked>
                        <span class="adm-check-box"></span>
                        <span>Visible on menu</span>
                    </label>
                </div>
            </div>

            <!-- Image upload -->
            <div class="adm-fgroup">
                <label for="f-img">Dish Image</label>
                <div class="adm-upload" id="upload-zone">
                    <div class="adm-upload-prompt" id="upload-prompt">
                        <span class="ico">📷</span>
                        <span>Click to upload or drag & drop</span>
                        <small>JPG, PNG, WebP, GIF, SVG — Max 5 MB</small>
                    </div>
                    <div class="adm-upload-preview" id="upload-preview">
                        <img id="preview-img" src="" alt="Preview">
                        <button type="button" class="rm" onclick="clearPreview()">✕</button>
                    </div>
                    <input type="file" id="f-img" name="item_image" accept="image/*"
                           onchange="previewImg(this)">
                </div>
                <div class="cur-img" id="cur-img-wrap">
                    <span>Current:</span>
                    <img id="cur-img" src="" alt="">
                </div>
            </div>

            <!-- Actions -->
            <div class="adm-mactions">
                <button type="button" class="adm-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="adm-save" id="save-btn">＋ Add Item</button>
            </div>
        </form>
    </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL — Confirm Delete
     ════════════════════════════════════════════ -->
<div class="adm-backdrop" id="modal-del">
    <div class="adm-modal-box adm-modal-sm">
        <span class="modal-ico">🗑️</span>
        <div class="modal-title">Delete Item?</div>
        <p id="del-desc" style="color:#7a8090;margin-bottom:24px;font-size:0.92rem;line-height:1.6">
            This action cannot be undone.
        </p>
        <form method="POST" action="admin_actions.php">
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="item_id" id="del-id" value="">
            <div class="adm-mactions" style="justify-content:center">
                <button type="button" class="adm-cancel" onclick="closeDel()">Cancel</button>
                <button type="submit" class="adm-del-confirm">🗑️ Delete Permanently</button>
            </div>
        </form>
    </div>
</div>

<script>
/* ── Open / close helpers ─────────────────────────────────── */
function openAdd() {
    document.getElementById('me-icon').textContent  = '🍽️';
    document.getElementById('me-title').textContent = 'Add New Item';
    document.getElementById('me-sub').textContent   = 'Fill in the details below';
    document.getElementById('f-action').value       = 'add_item';
    document.getElementById('save-btn').textContent = '＋ Add Item';

    const form = document.getElementById('edit-form');
    form.reset();
    document.getElementById('f-id').removeAttribute('readonly');
    document.getElementById('f-id').style.opacity = '1';
    document.getElementById('f-visible').checked  = true;
    clearPreview();
    document.getElementById('cur-img-wrap').style.display = 'none';

    document.getElementById('modal-edit').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openEdit(item) {
    document.getElementById('me-icon').textContent  = '✏️';
    document.getElementById('me-title').textContent = 'Edit Item';
    document.getElementById('me-sub').textContent   = 'Modify the details below';
    document.getElementById('f-action').value       = 'update_item';
    document.getElementById('save-btn').textContent = '💾 Save Changes';

    document.getElementById('f-id').value       = item.id;
    document.getElementById('f-id').setAttribute('readonly', true);
    document.getElementById('f-id').style.opacity = '0.55';
    document.getElementById('f-name').value     = item.name;
    document.getElementById('f-cat').value      = item.category;
    document.getElementById('f-desc').value     = item.description;
    document.getElementById('f-price').value    = item.price;
    document.getElementById('f-badge').value    = item.badge;
    document.getElementById('f-sort').value     = item.sort_order || 0;
    document.getElementById('f-visible').checked = (item.is_visible == 1);

    clearPreview();
    const curWrap = document.getElementById('cur-img-wrap');
    if (item.image_path) {
        document.getElementById('cur-img').src = item.image_path;
        curWrap.style.display = 'flex';
    } else {
        curWrap.style.display = 'none';
    }

    document.getElementById('modal-edit').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeEdit() {
    document.getElementById('modal-edit').classList.remove('open');
    document.body.style.overflow = '';
}

function openDel(id, name) {
    document.getElementById('del-id').value    = id;
    document.getElementById('del-desc').textContent =
        'Are you sure you want to delete "' + name + '"? This cannot be undone.';
    document.getElementById('modal-del').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDel() {
    document.getElementById('modal-del').classList.remove('open');
    document.body.style.overflow = '';
}

/* ── Close on backdrop click ─────────────────────────────── */
document.querySelectorAll('.adm-backdrop').forEach(bd => {
    bd.addEventListener('click', e => {
        if (e.target === bd) {
            bd.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
});

/* ── Close on Escape ─────────────────────────────────────── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.adm-backdrop.open').forEach(bd => {
            bd.classList.remove('open');
            document.body.style.overflow = '';
        });
    }
});

/* ── Image preview ───────────────────────────────────────── */
function previewImg(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-img').src = e.target.result;
        document.getElementById('upload-preview').style.display = 'block';
        document.getElementById('upload-prompt').style.display  = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}

function clearPreview() {
    document.getElementById('preview-img').src = '';
    document.getElementById('upload-preview').style.display = 'none';
    document.getElementById('upload-prompt').style.display  = 'flex';
    document.getElementById('f-img').value = '';
}

/* ── Drag & drop ─────────────────────────────────────────── */
const zone = document.getElementById('upload-zone');
if (zone) {
    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag'); });
    zone.addEventListener('dragleave', ()  => zone.classList.remove('drag'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag');
        const files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('f-img').files = files;
            previewImg(document.getElementById('f-img'));
        }
    });
}

/* ── AJAX visibility toggle (no page reload) ──────────────── */
function toggleVis(btn) {
    const id   = btn.dataset.id;
    const row  = btn.closest('tr');
    const lbl  = btn.querySelector('.tog-lbl');
    const isOn = btn.classList.contains('tog-on');

    btn.disabled = true;

    const fd = new FormData();
    fd.append('action',  'toggle_visibility');
    fd.append('item_id', id);

    fetch('admin_actions.php', {
        method:  'POST',
        headers: { 'Accept': 'application/json' },
        body:    fd
    })
    .then(res => res.json())
    .then(data => {
        if (!data.ok) throw new Error(data.msg || 'Server error');
        /* flip UI */
        if (isOn) {
            btn.classList.replace('tog-on', 'tog-off');
            lbl.textContent = 'Hidden';
            row.classList.add('row-hidden');
            showToast('Item hidden from menu.', false);
            updateStat('visible', -1);
            updateStat('hidden',  +1);
        } else {
            btn.classList.replace('tog-off', 'tog-on');
            lbl.textContent = 'Visible';
            row.classList.remove('row-hidden');
            showToast('Item is now visible on menu.', true);
            updateStat('visible', +1);
            updateStat('hidden',  -1);
        }
    })
    .catch(err => showToast('Toggle failed — ' + err.message, false))
    .finally(() => { btn.disabled = false; });
}

/* update the stat-card counters */
function updateStat(which, delta) {
    const cards = document.querySelectorAll('.adm-stat');
    cards.forEach(c => {
        const lbl = c.querySelector('.adm-stat-lbl');
        if (lbl && lbl.textContent.trim().toLowerCase() === which) {
            const val = c.querySelector('.adm-stat-val');
            val.textContent = Math.max(0, parseInt(val.textContent) + delta);
        }
    });
}

/* lightweight toast – no page reload needed */
function showToast(msg, ok) {
    const wrap = document.createElement('div');
    wrap.style.cssText = 'position:fixed;top:88px;right:24px;z-index:3000';
    wrap.innerHTML = `<div style="
        display:flex;align-items:center;gap:10px;
        padding:14px 20px;border-radius:12px;font-size:0.9rem;font-weight:500;
        backdrop-filter:blur(16px);box-shadow:0 8px 32px rgba(0,0,0,0.4);
        animation:toastIn .35s ease;
        background:${ok ? 'rgba(46,213,115,0.12)' : 'rgba(255,71,87,0.12)'};
        border:1px solid ${ok ? 'rgba(46,213,115,0.3)' : 'rgba(255,71,87,0.3)'};
        color:${ok ? '#2ed573' : '#ff4757'};
    ">${ok ? '✅' : '⚠️'} ${msg}</div>`;
    document.body.appendChild(wrap);
    setTimeout(() => wrap.remove(), 3500);
}
</script>

<?php include '../includes/footer.php'; ?>
