<?php
/**
 * Frontend/reservations.php — Restaurant Floor Plan Reservation System
 * Cinema-style floor plan: rectangular tables with chair marks, zoned rooms.
 */
require_once '../includes/db.php';

$page_title = 'Reserve a Table — Restaurant Management System';
$page_desc  = 'Book your perfect table at Restaurant Management System. Interactive floor plan with real-time slot availability.';

$tables       = $pdo->query("SELECT id, name, zone, capacity, status, x, y FROM `tables` ORDER BY zone, id")->fetchAll();
$main_tables  = array_values(array_filter($tables, fn($t) => $t['zone'] === 'Main'));
$patio_tables = array_values(array_filter($tables, fn($t) => $t['zone'] === 'Patio'));
$free_count   = count(array_filter($tables, fn($t) => $t['status'] === 'FREE'));
$reserved_count = count($tables) - $free_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <meta name="description" content="<?= $page_desc ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,700;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?= @filemtime(__DIR__ . '/../css/style.css') ?>">
    <style>
    /* ══════════════════════════════════════════════════
       RESERVATIONS — FULL RESTAURANT FLOOR PLAN UI
       ══════════════════════════════════════════════════ */

    /* ── Hero ── */
    .res-hero {
        padding: calc(var(--nav-height) + 36px) 24px 22px;
        text-align: center;
        max-width: 720px;
        margin: 0 auto;
    }
    .res-hero-kicker {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        color: var(--gold);
        border: 1px solid rgba(232,169,60,0.3);
        background: rgba(232,169,60,0.07);
        padding: 5px 14px;
        border-radius: 50px;
        margin-bottom: 12px;
    }
    .res-hero h1 {
        font-family: var(--font-display);
        font-size: clamp(1.8rem, 4vw, 2.6rem);
        font-weight: 700;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #fff 40%, var(--gold) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .res-hero p { color: var(--muted); font-size: 0.9rem; line-height: 1.5; margin-bottom: 16px; }

    /* ── Availability stats bar ── */
    .res-stats-bar {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .res-legend-dot {
        display: flex; align-items: center; gap: 7px;
        font-size: 0.82rem; color: var(--muted);
    }
    .dot { width: 11px; height: 11px; border-radius: 50%; flex-shrink: 0; }
    .dot-free     { background: #2ed573; box-shadow: 0 0 7px rgba(46,213,115,0.7); }
    .dot-reserved { background: #4a4f5c; }
    .dot-selected { background: var(--gold); box-shadow: 0 0 7px var(--gold-glow); }
    .res-legend-dot strong { color: var(--text); }

    /* ── Slot bar ── */
    .slot-bar-wrap { max-width: 1340px; margin: 0 auto 22px; padding: 0 20px; }
    .slot-bar {
        background: rgba(19,22,27,0.92);
        border: 1px solid rgba(232,169,60,0.25);
        border-radius: 16px;
        padding: 13px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        box-shadow: 0 8px 28px rgba(0,0,0,0.4);
        backdrop-filter: blur(14px);
    }
    .slot-title {
        font-family: var(--font-display);
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    .slot-inputs { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; flex: 1; justify-content: flex-end; }
    .slot-field {
        display: flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 9px;
        padding: 6px 11px;
        transition: border-color 0.2s;
    }
    .slot-field:focus-within { border-color: rgba(232,169,60,0.4); }
    .slot-field label { font-size: 0.67rem; font-weight: 700; text-transform: uppercase; color: var(--gold); letter-spacing: 0.4px; white-space: nowrap; }
    .slot-field input, .slot-field select {
        background: transparent; border: none; color: var(--text);
        font-family: var(--font-body); font-size: 0.85rem; outline: none; cursor: pointer;
    }
    .slot-field select option { background: #13161b; }

    .btn-cancel-trigger {
        background: rgba(255,71,87,0.08);
        border: 1px solid rgba(255,71,87,0.28);
        color: #ff6b7a;
        font-family: var(--font-body);
        font-size: 0.8rem;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 50px;
        cursor: pointer;
        transition: 0.2s;
        white-space: nowrap;
    }
    .btn-cancel-trigger:hover { background: rgba(255,71,87,0.18); border-color: #ff4757; }

    /* ══════════════════════════════════════════════════
       MAIN LAYOUT
       ══════════════════════════════════════════════════ */
    .res-outer {
        max-width: 1340px;
        margin: 0 auto;
        padding: 0 20px 80px;
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 22px;
        align-items: start;
    }
    @media (max-width: 1080px) { .res-outer { grid-template-columns: 1fr; } }

    /* ══════════════════════════════════════════════════
       FLOOR PLAN
       ══════════════════════════════════════════════════ */
    .floor-plan {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    /* Room wrapper — border with floor texture */
    .floor-rooms {
        display: flex;
        gap: 0;
        background: rgba(8,9,12,0.6);
        border: 2px solid rgba(255,255,255,0.12);
        border-radius: 18px;
        overflow: hidden;
        position: relative;
        /* subtle floor pattern */
        background-image:
            radial-gradient(circle, rgba(255,255,255,0.015) 1px, transparent 1px);
        background-size: 28px 28px;
    }

    /* Main Dining room */
    .room-main {
        flex: 1;
        padding: 22px 20px 20px;
        border-right: 2px solid rgba(255,255,255,0.1);
        position: relative;
    }
    .room-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: rgba(232,169,60,0.6);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Tables inside Main — 2 columns */
    .main-table-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px 20px;
    }

    /* Patio room — narrower side column */
    .room-patio {
        width: 140px;
        flex-shrink: 0;
        padding: 22px 14px 20px;
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .patio-table-col {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /* ══════════════════════════════════════════════════
       TABLE CARD — RECTANGULAR with chair marks
       ══════════════════════════════════════════════════ */
    .res-table {
        position: relative;
        border-radius: 8px;
        padding: 12px 16px 10px;
        cursor: pointer;
        user-select: none;
        transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.22s, border-color 0.22s, background 0.22s;
        border: 2px solid transparent;
        /* chair marks via ::before and ::after */
    }

    /* Chair marks top and bottom */
    .res-table::before,
    .res-table::after {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        height: 5px;
        border-radius: 3px;
        transition: background 0.22s;
    }
    .res-table::before { top: -7px;  width: 55%; }
    .res-table::after  { bottom: -7px; width: 55%; }

    /* Patio tables narrower */
    .room-patio .res-table::before,
    .room-patio .res-table::after { width: 60%; }

    /* FREE state */
    .res-table.free {
        background: rgba(46,213,115,0.08);
        border-color: rgba(46,213,115,0.32);
    }
    .res-table.free::before,
    .res-table.free::after { background: rgba(46,213,115,0.4); }
    .res-table.free:hover {
        background: rgba(46,213,115,0.14);
        border-color: rgba(46,213,115,0.6);
        transform: translateY(-3px);
        box-shadow: 0 6px 22px rgba(46,213,115,0.2);
    }

    /* RESERVED state */
    .res-table.reserved {
        background: rgba(255,71,87,0.05);
        border-color: rgba(255,71,87,0.2);
        cursor: pointer;
        opacity: 0.85;
    }
    .res-table.reserved::before,
    .res-table.reserved::after { background: rgba(255,71,87,0.25); }
    .res-table.reserved:hover {
        background: rgba(255,71,87,0.12);
        border-color: rgba(255,71,87,0.45);
        transform: translateY(-2px);
    }

    /* SELECTED state */
    .res-table.selected {
        background: rgba(232,169,60,0.13);
        border-color: var(--gold);
        transform: translateY(-3px);
        box-shadow: 0 8px 26px rgba(232,169,60,0.28);
    }
    .res-table.selected::before,
    .res-table.selected::after { background: rgba(232,169,60,0.55); }

    /* Table number */
    .table-num {
        font-family: var(--font-display);
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text);
        display: block;
        margin-bottom: 4px;
        line-height: 1;
    }
    .res-table.reserved .table-num { color: var(--muted); }
    .res-table.selected .table-num { color: var(--gold); }

    /* Seat row */
    .seat-row {
        display: flex;
        gap: 3px;
        align-items: center;
        margin-bottom: 3px;
    }
    .s-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: rgba(46,213,115,0.45);
        transition: background 0.2s;
    }
    .res-table.reserved .s-dot { background: rgba(255,255,255,0.13); }
    .res-table.selected .s-dot { background: rgba(232,169,60,0.6); }

    .table-cap {
        font-size: 0.66rem;
        color: rgba(46,213,115,0.7);
        font-weight: 600;
        margin-top: 2px;
        display: block;
    }
    .res-table.reserved .table-cap { color: var(--muted); }
    .res-table.selected .table-cap { color: rgba(232,169,60,0.8); }

    .res-table .lock-icon {
        position: absolute;
        top: 5px; right: 7px;
        font-size: 0.6rem;
        opacity: 0.5;
    }

    /* ── Legend bar ── */
    .floor-legend {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        padding: 12px 20px;
        background: rgba(0,0,0,0.18);
        border: 2px solid rgba(255,255,255,0.06);
        border-top: none;
        border-radius: 0 0 18px 18px;
    }
    .legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--muted); }
    .leg-box {
        width: 18px; height: 12px;
        border-radius: 4px;
        border: 1.5px solid transparent;
        flex-shrink: 0;
    }
    .leg-box.free { background: rgba(46,213,115,0.1); border-color: rgba(46,213,115,0.4); }
    .leg-box.reserved { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.1); opacity: 0.6; }
    .leg-box.selected { background: rgba(232,169,60,0.12); border-color: var(--gold); }

    /* ══════════════════════════════════════════════════
       LIVE BOOKINGS TICKER
       ══════════════════════════════════════════════════ */
    .live-bookings {
        margin-top: 18px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 16px;
        overflow: hidden;
    }
    .live-bookings-hdr {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px 12px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        gap: 10px;
    }
    .live-hdr-left {
        display: flex;
        align-items: center;
        gap: 9px;
    }
    .live-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #2ed573;
        animation: livePulse 1.6s ease-in-out infinite;
        flex-shrink: 0;
    }
    @keyframes livePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.45; transform: scale(1.5); }
    }
    .live-hdr-title {
        font-family: var(--font-display);
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text);
    }
    .live-refresh-badge {
        font-size: 0.68rem;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .refresh-spin {
        display: inline-block;
        transition: transform 0.4s;
    }
    .refresh-spin.spinning { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .live-bookings-body { padding: 6px 0; }
    .booking-ticker-row {
        display: grid;
        grid-template-columns: 60px 1fr auto;
        align-items: center;
        gap: 12px;
        padding: 9px 18px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        transition: background 0.15s;
    }
    .booking-ticker-row:last-child { border-bottom: none; }
    .booking-ticker-row:hover { background: rgba(255,255,255,0.02); }
    .booking-ticker-row.is-current {
        background: rgba(232,169,60,0.05);
        border-left: 3px solid rgba(232,169,60,0.5);
    }
    .btick-table {
        font-family: var(--font-display);
        font-size: 1rem;
        font-weight: 700;
        color: var(--gold);
    }
    .btick-info { min-width: 0; }
    .btick-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .btick-meta { font-size: 0.72rem; color: var(--muted); }
    .btick-time {
        text-align: right;
        white-space: nowrap;
    }
    .btick-time-val { font-size: 0.8rem; font-weight: 700; color: var(--text); }
    .btick-time-sub { font-size: 0.67rem; color: var(--muted); }
    .btick-now {
        display: inline-block;
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #2ed573;
        background: rgba(46,213,115,0.12);
        border: 1px solid rgba(46,213,115,0.3);
        padding: 1px 7px;
        border-radius: 50px;
    }
    .live-empty {
        text-align: center;
        padding: 28px 20px;
        color: var(--muted);
        font-size: 0.85rem;
    }
    .live-empty span { font-size: 2rem; display: block; margin-bottom: 8px; }
    .live-countdown {
        text-align: center;
        padding: 8px 18px 10px;
        font-size: 0.68rem;
        color: rgba(122,128,144,0.45);
        border-top: 1px solid rgba(255,255,255,0.04);
    }
    #live-timer-bar {
        height: 2px;
        background: linear-gradient(90deg, var(--gold), transparent);
        border-radius: 2px;
        transition: width 1s linear;
    }

    /* ══════════════════════════════════════════════════
       SIDEBAR
       ══════════════════════════════════════════════════ */
    .res-sidebar {
        position: sticky;
        top: calc(var(--nav-height) + 16px);
        background: rgba(19,22,27,0.96);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        overflow: hidden;
        backdrop-filter: blur(20px);
        transition: box-shadow 0.3s, border-color 0.3s;
    }
    .res-sidebar.has-selection {
        box-shadow: 0 8px 40px rgba(232,169,60,0.18);
        border-color: rgba(232,169,60,0.25);
    }
    .res-sidebar-hdr {
        padding: 20px 22px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .res-sidebar-hdr h2 {
        font-family: var(--font-display);
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 3px;
    }
    .res-sidebar-hdr p { color: var(--muted); font-size: 0.82rem; }
    .res-sidebar-empty {
        padding: 44px 22px;
        text-align: center;
    }
    .res-sidebar-empty .empty-ico { font-size: 2.6rem; display: block; margin-bottom: 10px; opacity: 0.45; }
    .res-sidebar-empty p { color: var(--muted); font-size: 0.84rem; line-height: 1.6; }

    .res-detail { display: none; padding: 20px 22px; }
    .res-detail.visible { display: block; }

    .detail-zone-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
        color: var(--gold); background: rgba(232,169,60,0.08);
        border: 1px solid rgba(232,169,60,0.2);
        padding: 3px 10px; border-radius: 50px; margin-bottom: 12px;
    }
    .detail-table-name {
        font-family: var(--font-display);
        font-size: 1.9rem; font-weight: 700; color: var(--text);
        margin-bottom: 4px; line-height: 1;
    }
    .detail-cap-row {
        display: flex; align-items: center; gap: 7px;
        margin-bottom: 18px; color: var(--muted); font-size: 0.84rem;
    }
    .big-seat-dot {
        width: 14px; height: 14px; border-radius: 50%;
        background: rgba(232,169,60,0.25); border: 2px solid rgba(232,169,60,0.5);
    }

    /* Form */
    .res-form { display: flex; flex-direction: column; gap: 12px; }
    .res-fgroup { display: flex; flex-direction: column; gap: 4px; }
    .res-fgroup label {
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.5px;
        text-transform: uppercase; color: var(--muted);
    }
    .res-fgroup input, .res-fgroup select {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 9px;
        color: var(--text); font-family: var(--font-body); font-size: 0.9rem;
        padding: 10px 13px; outline: none; width: 100%;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .res-fgroup input:focus, .res-fgroup select:focus {
        border-color: rgba(232,169,60,0.5);
        box-shadow: 0 0 0 3px rgba(232,169,60,0.09);
    }
    .res-fgroup input::placeholder { color: rgba(122,128,144,0.45); }
    .res-fgroup select option { background: #13161b; }
    .res-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .res-cta {
        width: 100%; background: linear-gradient(135deg, var(--gold), var(--gold-dark));
        color: #0c0e11; border: none; border-radius: 11px;
        padding: 14px 20px; font-family: var(--font-body); font-size: 0.92rem; font-weight: 700;
        cursor: pointer; box-shadow: 0 4px 18px rgba(232,169,60,0.32);
        transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
        display: flex; align-items: center; justify-content: center; gap: 7px;
        margin-top: 4px;
    }
    .res-cta:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 7px 24px rgba(232,169,60,0.48); }
    .res-cta:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    /* ── Modals ── */
    .modal-backdrop {
        display: none; position: fixed; inset: 0; z-index: 3000;
        background: rgba(0,0,0,0.8); backdrop-filter: blur(8px);
        align-items: center; justify-content: center; padding: 16px; overflow-y: auto;
    }
    .modal-backdrop.open { display: flex; }
    .modal-box {
        background: #13161b;
        border: 1px solid rgba(232,169,60,0.28);
        border-radius: 20px; width: 100%; max-width: 500px;
        padding: 30px; position: relative;
        box-shadow: 0 24px 80px rgba(0,0,0,0.7);
        animation: modalIn 0.26s ease;
        margin: auto;
    }
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(16px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-close {
        position: absolute; top: 14px; right: 16px;
        background: none; border: none; color: var(--muted); font-size: 1.5rem;
        cursor: pointer; width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center; border-radius: 50%;
        transition: 0.2s;
    }
    .modal-close:hover { background: rgba(255,255,255,0.07); color: var(--text); }
    .modal-icon { font-size: 2.4rem; display: block; text-align: center; margin-bottom: 8px; }
    .modal-title { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--text); text-align: center; margin-bottom: 20px; }

    .confirm-rows {
        background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 10px;
        margin-bottom: 18px; font-size: 0.86rem;
    }
    .confirm-row { display: flex; justify-content: space-between; gap: 10px; }
    .cf-lbl { color: var(--muted); }
    .cf-val { font-weight: 600; color: var(--text); text-align: right; }

    .policy-badge {
        background: rgba(255,165,0,0.08); border-left: 3px solid #ffa500;
        padding: 9px 13px; border-radius: 6px;
        font-size: 0.78rem; color: #ffc107; margin-bottom: 18px; line-height: 1.45;
    }
    .token-card {
        background: rgba(232,169,60,0.07); border: 1px dashed rgba(232,169,60,0.35);
        border-radius: 11px; padding: 14px; text-align: center; margin-bottom: 18px;
    }
    .token-lbl { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gold); margin-bottom: 6px; }
    .token-val { font-family: monospace; font-size: 0.88rem; color: #2ed573; font-weight: 700; word-break: break-all; }
    .btn-copy {
        margin-top: 8px; background: rgba(232,169,60,0.18); border: 1px solid rgba(232,169,60,0.3);
        color: var(--gold); font-size: 0.75rem; font-weight: 600; padding: 4px 12px;
        border-radius: 50px; cursor: pointer; transition: 0.2s;
    }
    .btn-copy:hover { background: rgba(232,169,60,0.3); }

    /* Cancel modal form */
    .cancel-form { display: flex; flex-direction: column; gap: 13px; }
    .cf-input {
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.09);
        border-radius: 9px; color: var(--text); font-family: var(--font-body); font-size: 0.9rem;
        padding: 10px 13px; outline: none; width: 100%;
        transition: border-color 0.2s;
    }
    .cf-input:focus { border-color: rgba(232,169,60,0.5); }
    .cf-input::placeholder { color: rgba(122,128,144,0.45); }
    .cf-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 3px; display: block; }
    .btn-cancel-confirm {
        width: 100%; background: linear-gradient(135deg, #ff4757, #d63031);
        color: #fff; border: none; border-radius: 11px; padding: 13px;
        font-family: var(--font-body); font-size: 0.9rem; font-weight: 700;
        cursor: pointer; transition: 0.2s;
    }
    .btn-cancel-confirm:hover { transform: translateY(-1px); box-shadow: 0 5px 18px rgba(255,71,87,0.35); }
    .btn-cancel-confirm:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Toast */
    .res-toast {
        position: fixed; bottom: 26px; left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: rgba(19,22,27,0.97); border: 1px solid rgba(46,213,115,0.4);
        border-radius: 14px; padding: 12px 22px;
        display: flex; align-items: center; gap: 9px;
        font-size: 0.88rem; font-weight: 600; color: var(--text);
        z-index: 9999; box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s;
        opacity: 0; pointer-events: none;
    }
    .res-toast.visible { transform: translateX(-50%) translateY(0); opacity: 1; }

    @media (max-width: 700px) {
        .main-table-grid { grid-template-columns: 1fr 1fr; gap: 10px 12px; }
        .room-patio { width: 100px; }
        .floor-rooms { flex-direction: column; }
        .room-main { border-right: none; border-bottom: 2px solid rgba(255,255,255,0.1); }
        .room-patio { width: 100%; }
        .patio-table-col { flex-direction: row; flex-wrap: wrap; }
    }
    </style>
</head>
<body>
<?php $current_page = 'reservations.php'; require_once '../includes/header.php'; ?>

<!-- ── Hero ── -->
<div class="res-hero">
    <span class="res-hero-kicker">🍽️ Table Reservations</span>
    <h1>Reserve Your Table</h1>
    <p>Browse our floor plan below. Green tables are available for the selected time window.</p>
    <div class="res-stats-bar">
        <div class="res-legend-dot"><span class="dot dot-free"></span><strong id="stat-free"><?= $free_count ?></strong> Available</div>
        <div class="res-legend-dot"><span class="dot dot-reserved"></span><strong id="stat-reserved"><?= $reserved_count ?></strong> Reserved</div>
        <div class="res-legend-dot"><span class="dot dot-selected"></span> Selected</div>
        <button class="btn-cancel-trigger" onclick="openModal('modal-cancel')">❌ Manage / Cancel</button>
    </div>
</div>

<!-- ── Slot selector bar ── -->
<div class="slot-bar-wrap">
    <div class="slot-bar">
        <div class="slot-title">⏰ Booking Window</div>
        <div class="slot-inputs">
            <div class="slot-field">
                <label for="slot-date">Date</label>
                <input type="date" id="slot-date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="slot-field">
                <label for="slot-time">Arrival</label>
                <input type="time" id="slot-time" value="19:00" min="10:00" max="23:00" step="900"
                    style="background:transparent;border:none;color:var(--text);font-family:var(--font-body);font-size:0.85rem;outline:none;cursor:pointer;">
            </div>
            <div class="slot-field">
                <label for="slot-dur">Duration</label>
                <select id="slot-dur">
                    <option value="1">1 Hour</option>
                    <option value="1.5">1.5 Hours</option>
                    <option value="2" selected>2 Hours</option>
                    <option value="2.5">2.5 Hours</option>
                    <option value="3">3 Hours</option>
                    <option value="4">4 Hours</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ── Main content area ── -->
<div class="res-outer">

    <!-- Floor plan column -->
    <div class="floor-plan">

        <!-- Room outline -->
        <div class="floor-rooms">

            <!-- Main Dining Room -->
            <div class="room-main">
                <div class="room-label">🏛️ Main Dining</div>
                <div class="main-table-grid" id="zone-main">
                    <?php foreach ($main_tables as $t): ?>
                    <?php $cls = strtolower($t['status']); $tid = (int)$t['id']; $cap = (int)$t['capacity']; ?>
                    <div class="res-table <?= $cls ?>"
                         id="table-<?= $tid ?>"
                         data-id="<?= $tid ?>"
                         data-name="<?= htmlspecialchars($t['name']) ?>"
                         data-zone="Main"
                         data-capacity="<?= $cap ?>"
                         data-status="<?= $cls ?>"
                         <?= $cls === 'reserved' ? 'aria-disabled="true"' : 'tabindex="0" role="button"' ?>>
                        <?php if ($cls === 'reserved'): ?><span class="lock-icon">🔒</span><?php endif; ?>
                        <span class="table-num"><?= htmlspecialchars($t['name']) ?></span>
                        <div class="seat-row">
                            <?php for ($i = 0; $i < min($cap, 6); $i++): ?><span class="s-dot"></span><?php endfor; ?>
                        </div>
                        <span class="table-cap"><?= $cap ?> seats</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Patio Room (side column) -->
            <div class="room-patio">
                <div class="room-label" style="font-size:0.62rem;">🌿 Patio</div>
                <div class="patio-table-col" id="zone-patio">
                    <?php foreach ($patio_tables as $t): ?>
                    <?php $cls = strtolower($t['status']); $tid = (int)$t['id']; $cap = (int)$t['capacity']; ?>
                    <div class="res-table <?= $cls ?>"
                         id="table-<?= $tid ?>"
                         data-id="<?= $tid ?>"
                         data-name="<?= htmlspecialchars($t['name']) ?>"
                         data-zone="Patio"
                         data-capacity="<?= $cap ?>"
                         data-status="<?= $cls ?>"
                         <?= $cls === 'reserved' ? 'aria-disabled="true"' : 'tabindex="0" role="button"' ?>>
                        <?php if ($cls === 'reserved'): ?><span class="lock-icon">🔒</span><?php endif; ?>
                        <span class="table-num" style="font-size:0.9rem;"><?= htmlspecialchars($t['name']) ?></span>
                        <div class="seat-row">
                            <?php for ($i = 0; $i < min($cap, 4); $i++): ?><span class="s-dot"></span><?php endfor; ?>
                        </div>
                        <span class="table-cap" style="font-size:0.6rem;"><?= $cap ?> seats</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /floor-rooms -->

        <!-- Legend -->
        <div class="floor-legend">
            <div class="legend-item"><span class="leg-box free"></span> Available</div>
            <div class="legend-item"><span class="leg-box reserved"></span> Reserved</div>
            <div class="legend-item"><span class="leg-box selected"></span> Selected</div>
        </div>

        <!-- ── Live Bookings Ticker ── -->
        <div class="live-bookings" id="live-bookings-panel">
            <div class="live-bookings-hdr">
                <div class="live-hdr-left">
                    <span class="live-dot"></span>
                    <span class="live-hdr-title">Today's Live Bookings</span>
                </div>
                <div class="live-refresh-badge">
                    <span class="refresh-spin" id="refresh-spin">⟳</span>
                    Refreshes every 30s
                </div>
            </div>
            <div class="live-bookings-body" id="live-body">
                <!-- populated by JS -->
                <div class="live-empty"><span>📋</span>Loading bookings…</div>
            </div>
            <div class="live-countdown" id="live-countdown">
                <div id="live-timer-bar" style="width:100%;"></div>
            </div>
        </div>

    </div><!-- /floor-plan -->

    <!-- ── Sidebar ── -->
    <aside class="res-sidebar" id="res-sidebar">
        <div class="res-sidebar-hdr">
            <h2>Reservation Details</h2>
            <p>Click any table to view schedule & book</p>
        </div>
        <div class="res-sidebar-empty" id="sidebar-empty">
            <span class="empty-ico">🪑</span>
            <p>No table selected.<br>Click any table on the floor plan to view its schedule & reserve.</p>
        </div>
        <div class="res-detail" id="res-detail">
            <span class="detail-zone-badge" id="detail-zone">Zone</span>
            <div class="detail-table-name" id="detail-name">—</div>
            <div class="detail-cap-row">
                <div id="detail-seats"></div>
                <span id="detail-cap-text"></span>
            </div>



            <form class="res-form" id="res-form" novalidate>
                <input type="hidden" id="selected-id" value="">
                <div class="res-fgroup">
                    <label>Full Name *</label>
                    <input type="text" id="f-name" placeholder="John Doe" required>
                </div>
                <div class="res-fgroup">
                    <label>Email Address *</label>
                    <input type="email" id="f-email" placeholder="john@example.com" required>
                </div>
                <div class="res-fgroup">
                    <label>Phone Number *</label>
                    <input type="tel" id="f-phone" placeholder="+91 98765 43210" required>
                </div>
                <div class="res-row2">
                    <div class="res-fgroup">
                        <label>Guests</label>
                        <select id="f-guests">
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                        </select>
                    </div>
                    <div class="res-fgroup">
                        <label>Window</label>
                        <input type="text" id="f-window" value="2h" readonly style="opacity:0.75;cursor:default;">
                    </div>
                </div>
                <button type="submit" class="res-cta" id="res-submit">
                    <span>🍽️</span> Confirm Reservation
                </button>
            </form>


        </div>
    </aside>

</div><!-- /res-outer -->

<!-- ── Confirmation Modal ── -->
<div class="modal-backdrop" id="modal-confirm">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-confirm')">&times;</button>
        <span class="modal-icon">🎉</span>
        <div class="modal-title">Reservation Confirmed!</div>
        <div class="confirm-rows">
            <div class="confirm-row"><span class="cf-lbl">Table & Zone</span><span class="cf-val" id="cf-table">—</span></div>
            <div class="confirm-row"><span class="cf-lbl">Guest</span><span class="cf-val" id="cf-name">—</span></div>
            <div class="confirm-row"><span class="cf-lbl">Contact</span><span class="cf-val" id="cf-contact">—</span></div>
            <div class="confirm-row"><span class="cf-lbl">Date & Slot</span><span class="cf-val" id="cf-slot">—</span></div>
            <div class="confirm-row"><span class="cf-lbl">Table Released At</span><span class="cf-val" id="cf-end" style="color:var(--gold);">—</span></div>
        </div>
        <div class="policy-badge">
            ⏱️ <strong>Auto-Release:</strong> If no show after 15 mins (<span id="cf-grace">—</span>), the table is freed automatically.
        </div>
        <div class="token-card">
            <div class="token-lbl">Your Cancellation Pass Key</div>
            <div class="token-val" id="cf-token">—</div>
            <button class="btn-copy" onclick="copyToken()">📋 Copy Token</button>
        </div>
        <button class="res-cta" onclick="closeModal('modal-confirm')" style="border-radius:11px;">Done</button>
    </div>
</div>

<!-- ── Cancellation Modal ── -->
<div class="modal-backdrop" id="modal-cancel">
    <div class="modal-box" style="border-color:rgba(255,71,87,0.3);">
        <button class="modal-close" onclick="closeModal('modal-cancel')">&times;</button>
        <span class="modal-icon">❌</span>
        <div class="modal-title">Cancel a Booking</div>
        <p style="text-align:center;color:var(--muted);font-size:0.84rem;margin-bottom:20px;">Enter your cancellation token and email to free your table slot.</p>
        <form class="cancel-form" id="cancel-form">
            <div>
                <label class="cf-label">Cancellation Token *</label>
                <input class="cf-input" type="text" id="cn-token" placeholder="e.g. afc5da053f42…" required style="font-family:monospace;">
            </div>
            <div>
                <label class="cf-label">Email (Optional)</label>
                <input class="cf-input" type="email" id="cn-email" placeholder="john@example.com">
            </div>
            <button type="submit" class="btn-cancel-confirm" id="cn-submit">Confirm Cancellation</button>
        </form>
    </div>
</div>

<!-- ── Full Table Schedule Modal ── -->
<div class="modal-backdrop" id="modal-table-schedule">
    <div class="modal-box" style="max-width:620px;">
        <button class="modal-close" onclick="closeModal('modal-table-schedule')">&times;</button>
        <span class="modal-icon">📋</span>
        <div class="modal-title" id="ts-modal-title">Table Reservation History</div>
        <p style="text-align:center;color:var(--muted);font-size:0.84rem;margin-bottom:16px;" id="ts-modal-sub">
            All logged reservations for this table
        </p>
        <div id="ts-modal-list" style="display:flex;flex-direction:column;gap:10px;max-height:360px;overflow-y:auto;padding-right:6px;">
            <div style="text-align:center;padding:24px;color:var(--muted);">Select a table to view history</div>
        </div>
    </div>
</div>

<!-- ── Toast ── -->
<div class="res-toast" id="res-toast">
    <span id="toast-ico">✅</span>
    <span id="toast-msg">Done</span>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
(function () {
    'use strict';

    const API_TABLES    = '../api/tables.php';
    const API_CREATE    = '../api/reservations/create.php';
    const API_CANCEL    = '../api/reservations/cancel.php';
    const API_TODAY     = '../api/reservations/today.php';
    const API_TABLE_RES = '../api/reservations/table_reservations.php';

    let selectedId = null;


    // ── Elements ─────────────────────────────────────────────────
    const slotDate     = document.getElementById('slot-date');
    const slotTime     = document.getElementById('slot-time');
    const slotDur      = document.getElementById('slot-dur');
    const statFree     = document.getElementById('stat-free');
    const statReserved = document.getElementById('stat-reserved');
    const sidebar      = document.getElementById('res-sidebar');
    const sideEmpty    = document.getElementById('sidebar-empty');
    const sideDetail   = document.getElementById('res-detail');
    const detailZone   = document.getElementById('detail-zone');
    const detailName   = document.getElementById('detail-name');
    const detailSeats  = document.getElementById('detail-seats');
    const detailCap    = document.getElementById('detail-cap-text');
    const hiddenId     = document.getElementById('selected-id');
    const form         = document.getElementById('res-form');
    const submitBtn    = document.getElementById('res-submit');
    const fWindow      = document.getElementById('f-window');

    // ── Slot bar sync ─────────────────────────────────────────────
    function syncWindowLabel() {
        fWindow.value = slotDur.value + 'h';
    }
    slotDate.addEventListener('change', () => { refreshFloor(); syncWindowLabel(); });
    slotTime.addEventListener('change', () => { refreshFloor(); syncWindowLabel(); });
    slotDur.addEventListener('change',  () => { refreshFloor(); syncWindowLabel(); });
    syncWindowLabel();

    // ── Refresh floor plan availability ──────────────────────────
    async function refreshFloor() {
        try {
            const currentSelected = selectedId;
            const r = await fetch(`${API_TABLES}?date=${slotDate.value}&time=${slotTime.value}&duration=${slotDur.value}`);
            const j = await r.json();
            if (!j.success) return;

            let freeN = 0, resN = 0;
            j.data.forEach(t => {
                const el = document.getElementById('table-' + t.id);
                if (!el) return;
                const isRes = t.status === 'RESERVED';
                isRes ? resN++ : freeN++;

                const wasSelected = parseInt(currentSelected) === t.id;
                const newCls = isRes ? 'reserved' : 'free';
                el.className = `res-table ${newCls}`;
                el.dataset.status = newCls;

                if (wasSelected) {
                    el.classList.add('selected');
                }

                // Chair mark lock icon
                const lock = el.querySelector('.lock-icon');
                if (isRes && !lock) {
                    const l = document.createElement('span');
                    l.className = 'lock-icon'; l.textContent = '🔒';
                    el.prepend(l);
                } else if (!isRes && lock) { lock.remove(); }

                el.setAttribute('tabindex', '0');
                el.setAttribute('aria-disabled', 'false');
            });

            statFree.textContent     = freeN;
            statReserved.textContent = resN;
            attachHandlers();

            if (selectedId) {
                const currentEl = document.getElementById('table-' + selectedId);
                if (currentEl) {
                    const isReservedNow = currentEl.dataset.status === 'reserved';
                    submitBtn.disabled = isReservedNow;
                    submitBtn.style.opacity = isReservedNow ? '0.5' : '1';
                    submitBtn.innerHTML = isReservedNow ? '🔒 Reserved for this Slot' : '<span>🍽️</span> Confirm Reservation';
                }
            }
        } catch (err) { console.warn('Floor refresh error:', err); }
    }

    function attachHandlers() {
        document.querySelectorAll('.res-table').forEach(el => {
            el.onclick = onTableClick;
            el.onkeydown = e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onTableClick.call(el, e); } };
        });
    }

    function deselectTable() {
        if (selectedId) {
            const prev = document.getElementById('table-' + selectedId);
            if (prev) prev.classList.remove('selected');
        }
        selectedId = null;
        sideDetail.classList.remove('visible');
        sideEmpty.style.display = '';
        sidebar.classList.remove('has-selection');
    }

    function onTableClick(e) {
        const el = e.currentTarget || this;
        const tid = el.dataset.id;
        const isReserved = el.dataset.status === 'reserved';

        if (selectedId) {
            const prev = document.getElementById('table-' + selectedId);
            if (prev && prev !== el) prev.classList.remove('selected');
        }
        el.classList.add('selected');
        selectedId = tid;

        const cap  = parseInt(el.dataset.capacity, 10);
        detailZone.textContent = '📍 ' + el.dataset.zone + ' Zone';
        detailName.textContent = el.dataset.name;
        hiddenId.value = selectedId;

        detailSeats.innerHTML = '';
        for (let i = 0; i < cap; i++) {
            const d = document.createElement('span');
            d.className = 'big-seat-dot';
            detailSeats.appendChild(d);
        }
        detailCap.textContent = ' ' + cap + ' seats';

        if (isReserved) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.innerHTML = '🔒 Reserved for this Slot';
        } else {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.innerHTML = '<span>🍽️</span> Confirm Reservation';
        }

        sideEmpty.style.display = 'none';
        sideDetail.classList.add('visible');
        sidebar.classList.add('has-selection');

        if (window.innerWidth < 1080) sidebar.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Per-table schedule loader ──────────────────────────────────
    async function loadTableSchedule(tableId, date) {
        const listEl = document.getElementById('table-schedule-list');
        if (!listEl) return;

        listEl.innerHTML = '<div style="text-align:center;padding:10px;color:var(--muted);">Loading schedule…</div>';

        try {
            const res = await fetch(`${API_TABLE_RES}?table_id=${tableId}&date=${date}`);
            const j   = await res.json();

            if (!j.success || j.data.length === 0) {
                listEl.innerHTML = `
                    <div style="background:rgba(46,213,115,0.06);border:1px solid rgba(46,213,115,0.2);color:#2ed573;padding:10px 12px;border-radius:10px;text-align:center;font-size:0.8rem;">
                        ✨ No reservations for ${j.table ? j.table.name : 'this table'} on ${date}. Table is free all day!
                    </div>
                `;
            } else {
                listEl.innerHTML = j.data.map(r => `
                    <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:9px 12px;display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <div>
                            <strong style="color:var(--text);font-size:0.84rem;display:block;">${r.start_fmt} – ${r.end_fmt} (${r.duration_hours}h)</strong>
                            <span style="font-size:0.75rem;color:var(--muted);">${r.customer_name} · ${r.guests} guests</span>
                        </div>
                        <span style="font-size:0.67rem;font-weight:700;padding:2px 8px;border-radius:50px;color:${r.status === 'CONFIRMED' ? '#2ed573' : (r.status === 'CANCELLED' ? '#ff4757' : '#ffa500')};background:${r.status === 'CONFIRMED' ? 'rgba(46,213,115,0.12)' : 'rgba(255,71,87,0.12)'};">
                            ${r.status}
                        </span>
                    </div>
                `).join('');
            }

            fetchFullHistory(tableId);
        } catch (err) {
            listEl.innerHTML = '<div style="color:#ff6b7a;padding:8px;text-align:center;">Failed to load table schedule.</div>';
        }
    }

    async function fetchFullHistory(tableId) {
        const modalTitle = document.getElementById('ts-modal-title');
        const modalSub   = document.getElementById('ts-modal-sub');
        const modalList  = document.getElementById('ts-modal-list');
        if (!modalList) return;

        try {
            const res = await fetch(`${API_TABLE_RES}?table_id=${tableId}`);
            const j   = await res.json();
            if (!j.success) return;

            modalTitle.textContent = `Schedule & Log: ${j.table.name} (${j.table.zone} Zone)`;
            modalSub.textContent   = `Total Logged Bookings: ${j.count} · Capacity: ${j.table.capacity} Seats`;

            if (j.data.length === 0) {
                modalList.innerHTML = '<div style="text-align:center;padding:24px;color:var(--muted);">No booking history recorded for this table yet.</div>';
            } else {
                modalList.innerHTML = j.data.map(r => `
                    <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div>
                            <strong style="color:var(--text);font-size:0.9rem;">${r.res_date}</strong>
                            <div style="color:var(--gold);font-size:0.8rem;font-weight:600;margin-top:2px;">${r.start_fmt} – ${r.end_fmt} (${r.duration_hours}h)</div>
                            <div style="color:var(--muted);font-size:0.78rem;margin-top:2px;">Guest: <strong>${r.customer_name}</strong> (${r.guests} guests) · ${r.customer_phone}</div>
                        </div>
                        <span style="font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:50px;color:${r.status === 'CONFIRMED' ? '#2ed573' : (r.status === 'CANCELLED' ? '#ff4757' : '#ffa500')};background:${r.status === 'CONFIRMED' ? 'rgba(46,213,115,0.12)' : 'rgba(255,71,87,0.12)'};">
                            ${r.status}
                        </span>
                    </div>
                `).join('');
            }
        } catch (e) { /* silent */ }
    }

    // ── Reservation form ─────────────────────────────────────────
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const name  = document.getElementById('f-name').value.trim();
        const email = document.getElementById('f-email').value.trim();
        const phone = document.getElementById('f-phone').value.trim();
        if (!name || !email || !phone) { showToast('⚠️', 'Name, email, and phone are required.', true); return; }
        if (!selectedId) { showToast('⚠️', 'Please select a table first.', true); return; }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Confirming…';

        try {
            const res = await fetch(API_CREATE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    table_id: selectedId,
                    customer_name: name,
                    customer_email: email,
                    customer_phone: phone,
                    res_date: slotDate.value,
                    start_time: slotTime.value,
                    duration_hours: slotDur.value,
                    guests: document.getElementById('f-guests').value
                })
            });
            const j = await res.json();

            if (j.success) {
                const d = j.data;
                document.getElementById('cf-table').textContent   = `${d.table_name} · ${d.zone} Zone`;
                document.getElementById('cf-name').textContent    = d.customer_name;
                document.getElementById('cf-contact').textContent = `${d.customer_email} · ${d.customer_phone}`;
                document.getElementById('cf-slot').textContent    = `${d.res_date} @ ${d.eta_time} (${d.duration_hours}h)`;
                document.getElementById('cf-end').textContent     = d.end_time;
                document.getElementById('cf-grace').textContent   = d.grace_deadline;
                document.getElementById('cf-token').textContent   = d.cancellation_token;
                document.getElementById('cn-token').value = d.cancellation_token;
                document.getElementById('cn-email').value = d.customer_email;

                openModal('modal-confirm');
                deselectTable();
                refreshFloor();
                loadTodayBookings();
                showToast('🎉', 'Table reserved successfully!');
                form.reset();
                syncWindowLabel();
            } else {
                showToast('❌', j.error || 'Booking failed.', true);
            }
        } catch (err) { showToast('❌', 'Network error.', true); }
        finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>🍽️</span> Confirm Reservation';
        }
    });

    // ── Cancellation form ─────────────────────────────────────────
    document.getElementById('cancel-form').addEventListener('submit', async e => {
        e.preventDefault();
        const token = document.getElementById('cn-token').value.trim();
        const email = document.getElementById('cn-email').value.trim();
        const btn   = document.getElementById('cn-submit');
        if (!token) { showToast('⚠️', 'Cancellation token is required.', true); return; }

        btn.disabled = true; btn.textContent = 'Cancelling…';
        try {
            const res = await fetch(API_CANCEL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cancellation_token: token, customer_email: email })
            });
            const j = await res.json();
            if (j.success) {
                closeModal('modal-cancel');
                refreshFloor();
                loadTodayBookings();
                showToast('✅', j.message || 'Reservation cancelled.');
            } else { showToast('❌', j.error || 'Invalid token.', true); }
        } catch (err) { showToast('❌', 'Network error.', true); }
        finally { btn.disabled = false; btn.textContent = 'Confirm Cancellation'; }
    });

    // ── Modal helpers ─────────────────────────────────────────────
    window.openModal  = id => document.getElementById(id).classList.add('open');
    window.closeModal = id => document.getElementById(id).classList.remove('open');
    window.copyToken  = () => {
        navigator.clipboard.writeText(document.getElementById('cf-token').textContent)
            .then(() => showToast('📋', 'Token copied!'));
    };

    // ── Toast ─────────────────────────────────────────────────────
    function showToast(ico, msg, isError = false) {
        document.getElementById('toast-ico').textContent = ico;
        document.getElementById('toast-msg').textContent = msg;
        const t = document.getElementById('res-toast');
        t.style.borderColor = isError ? 'rgba(255,71,87,0.4)' : 'rgba(46,213,115,0.4)';
        t.classList.add('visible');
        setTimeout(() => t.classList.remove('visible'), 4000);
    }

    // ══════════════════════════════════════════════
    // LIVE BOOKINGS TICKER
    // ══════════════════════════════════════════════
    const REFRESH_MS = 30000;
    let timerInterval, timerRemaining = REFRESH_MS / 1000;

    async function loadTodayBookings() {
        const spin = document.getElementById('refresh-spin');
        spin.classList.add('spinning');
        try {
            const res = await fetch(API_TODAY);
            const j   = await res.json();
            renderBookings(j);
        } catch (e) { /* silent */ }
        finally { spin.classList.remove('spinning'); }
    }

    function renderBookings(j) {
        const body = document.getElementById('live-body');
        if (!j.success || j.count === 0) {
            body.innerHTML = '<div class="live-empty"><span>📋</span>No confirmed bookings for today yet.</div>';
            return;
        }
        body.innerHTML = j.data.map(r => `
            <div class="booking-ticker-row ${r.is_current ? 'is-current' : ''}">
                <div class="btick-table">${r.table_name}</div>
                <div class="btick-info">
                    <div class="btick-name">${r.customer_name}</div>
                    <div class="btick-meta">${r.zone} · ${r.guests} guests</div>
                </div>
                <div class="btick-time">
                    <div class="btick-time-val">${r.start_fmt}</div>
                    <div class="btick-time-sub">→ ${r.end_fmt}</div>
                    ${r.is_current ? '<span class="btick-now">NOW</span>' : ''}
                </div>
            </div>
        `).join('');
    }

    function startCountdown() {
        clearInterval(timerInterval);
        timerRemaining = REFRESH_MS / 1000;
        timerInterval = setInterval(() => {
            timerRemaining--;
            const pct = (timerRemaining / (REFRESH_MS / 1000)) * 100;
            const bar = document.getElementById('live-timer-bar');
            if (bar) bar.style.width = pct + '%';
            if (timerRemaining <= 0) {
                loadTodayBookings();
                timerRemaining = REFRESH_MS / 1000;
            }
        }, 1000);
    }

    // ── Boot ──────────────────────────────────────────────────────
    refreshFloor();
    loadTodayBookings();
    startCountdown();
})();
</script>
</body>
</html>
