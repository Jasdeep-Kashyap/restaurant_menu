<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Server Error — La Maison</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,500&display=swap" rel="stylesheet">
    <style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --gold:       #e8a93c;
    --gold-light: #f0bf66;
    --gold-dim:   rgba(232,169,60,.18);
    --bg:         #0d0e12;
    --card:       #1a1d26;
    --border:     rgba(255,255,255,.07);
    --text:       #e2e6f0;
    --muted:      #6b7280;
    --radius:     20px;
}

html, body {
    height: 100%;
    font-family: 'Outfit', system-ui, sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow: hidden;
}

/* Animated background */
.error-bg {
    position: fixed; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 80% 50% at 20% 20%, rgba(232,169,60,.07) 0%, transparent 60%),
        radial-gradient(ellipse 60% 60% at 80% 80%, rgba(138,92,246,.05) 0%, transparent 60%),
        var(--bg);
}
.error-bg::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.025) 1px, transparent 0);
    background-size: 40px 40px;
    animation: gridFloat 20s linear infinite;
}
@keyframes gridFloat {
    0%   { transform: translateY(0); }
    100% { transform: translateY(-40px); }
}

/* Orbs */
.orb {
    position: fixed; border-radius: 50%;
    filter: blur(80px); pointer-events: none; z-index: 0;
    animation: orbPulse 8s ease-in-out infinite alternate;
}
.orb-1 { width: 420px; height: 420px; top: -110px; left: -110px; background: rgba(255,71,87,.05); }
.orb-2 { width: 320px; height: 320px; bottom: -90px; right: -90px; background: rgba(138,92,246,.05); animation-delay: 4s; }
@keyframes orbPulse {
    from { transform: scale(1);   opacity: .5; }
    to   { transform: scale(1.2); opacity: 1;  }
}

/* Layout */
.error-wrap {
    position: relative; z-index: 1;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.error-card {
    width: 100%;
    max-width: 480px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 52px 44px 44px;
    text-align: center;
    box-shadow:
        0 0 0 1px rgba(232,169,60,.04),
        0 30px 80px rgba(0,0,0,.55),
        inset 0 1px 0 rgba(255,255,255,.05);
    animation: cardIn .5s cubic-bezier(.22,1,.36,1) both;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(28px) scale(.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}

.error-icon {
    font-size: 3.2rem;
    margin-bottom: 16px;
    display: block;
    animation: iconBounce 2.5s ease-in-out infinite;
}
@keyframes iconBounce {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-8px); }
}

.error-code {
    font-family: 'Playfair Display', serif;
    font-size: 5rem;
    font-weight: 700;
    line-height: 1;
    /* 500-specific: red tint */
    background: linear-gradient(135deg, #ff4757, #ff6b81);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 12px;
    letter-spacing: -2px;
}

h1 {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 14px;
}

.error-desc {
    font-size: .9rem;
    color: var(--muted);
    line-height: 1.65;
    max-width: 360px;
    margin: 0 auto 32px;
}

/* Buttons */
.error-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 24px;
    border-radius: 10px;
    font-family: 'Outfit', sans-serif;
    font-size: .88rem;
    font-weight: 600;
    text-decoration: none;
    letter-spacing: .3px;
    transition: transform .15s, box-shadow .2s, opacity .2s;
    cursor: pointer;
}
.btn:hover { transform: translateY(-2px); }

.btn-primary {
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: #0d0e12;
    box-shadow: 0 4px 20px rgba(232,169,60,.3);
}
.btn-primary:hover { box-shadow: 0 8px 30px rgba(232,169,60,.45); }

.btn-outline {
    background: rgba(255,255,255,.04);
    color: var(--text);
    border: 1px solid var(--border);
}
.btn-outline:hover { background: rgba(255,255,255,.08); border-color: rgba(232,169,60,.3); }

/* Divider */
.error-divider {
    height: 1px;
    background: var(--border);
    margin: 28px 0 20px;
}

.error-footer {
    font-size: .72rem;
    color: var(--muted);
    letter-spacing: .5px;
    margin-top: 28px;
}
    </style>
</head>
<body>
<div class="error-bg"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="error-wrap">
    <div class="error-card">
        <div class="error-icon">⚙️</div>
        <div class="error-code">500</div>
        <h1>Internal Server Error</h1>
        <p class="error-desc">Something went wrong on our end. Our team has been notified. Please try again in a moment — your order is safe.</p>
        <div class="error-divider"></div>
        <div class="error-actions">
            <a href="javascript:history.back()" class="btn btn-primary">← Try Again</a>
            <a href="/img/restaurant_menu/index.html" class="btn btn-outline">Home</a>
        </div>
        <div class="error-footer">La Maison · Error 500</div>
    </div>
</div>
</body>
</html>
