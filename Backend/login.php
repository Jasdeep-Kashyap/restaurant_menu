<?php
/**
 * login.php — Authenticated login page for La Maison backend
 */
require_once '../includes/auth.php';

auth_session_start();

// Already logged in → send to appropriate page
if (!empty($_SESSION['auth_user'])) {
    $dest = $_SESSION['auth_role'] === 'admin' ? 'admin.php' : 'orders.php';
    header('Location: ' . $dest);
    exit;
}

$error        = '';
$locked_secs  = 0;
$logged_out   = isset($_GET['logged_out']);

// Safe return URL — reject host-relative (//evil.com) and absolute URLs
$raw_return = $_GET['return'] ?? '';
$parsed_ret = parse_url($raw_return);
$return_url = (isset($parsed_ret['path']) && !isset($parsed_ret['host']))
    ? htmlspecialchars($raw_return, ENT_QUOTES, 'UTF-8')
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check lockout BEFORE attempting password verify
    if (auth_is_locked($username)) {
        $locked_secs = auth_lockout_remaining($username);
        $error = 'Too many failed attempts. Please wait ' . ceil($locked_secs / 60) . ' minute(s) before trying again.';
    } else {
        $role = auth_attempt($username, $password);

        if ($role !== false) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['auth_user'] = $username;
            $_SESSION['auth_role'] = $role;

            if ($return_url) {
                header('Location: ' . $return_url);
            } else {
                header('Location: ' . ($role === 'admin' ? 'admin.php' : 'orders.php'));
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login — La Maison</title>
    <meta name="description" content="Secure staff login portal for La Maison restaurant management.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,700;1,500&display=swap" rel="stylesheet">
    <style>
        /* ─── Reset & Base ──────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold:       #e8a93c;
            --gold-light: #f0bf66;
            --gold-dim:   rgba(232,169,60,.18);
            --bg:         #0d0e12;
            --surface:    #14161c;
            --card:       #1a1d26;
            --border:     rgba(255,255,255,.07);
            --text:       #e2e6f0;
            --muted:      #6b7280;
            --error:      #ff4757;
            --success:    #2ed573;
            --radius:     14px;
        }

        html, body {
            height: 100%;
            font-family: 'Outfit', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
        }

        /* ─── Animated Background ───────────────────────────── */
        .login-bg {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 20%, rgba(232,169,60,.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 60% at 80% 80%, rgba(138,92,246,.06) 0%, transparent 60%),
                var(--bg);
        }

        .login-bg::before {
            content: '';
            position: absolute; inset: 0;
            background-image:
                radial-gradient(circle at 1px 1px, rgba(255,255,255,.03) 1px, transparent 0);
            background-size: 40px 40px;
            animation: gridFloat 20s linear infinite;
        }

        @keyframes gridFloat {
            0%   { transform: translateY(0); }
            100% { transform: translateY(-40px); }
        }

        /* ─── Floating orbs ─────────────────────────────────── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            animation: orbPulse 8s ease-in-out infinite alternate;
        }
        .orb-1 { width: 400px; height: 400px; top: -100px; left: -100px; background: rgba(232,169,60,.07); animation-delay: 0s; }
        .orb-2 { width: 300px; height: 300px; bottom: -80px; right: -80px; background: rgba(138,92,246,.06); animation-delay: 3s; }
        @keyframes orbPulse {
            from { transform: scale(1);   opacity: .6; }
            to   { transform: scale(1.2); opacity: 1;  }
        }

        /* ─── Wrapper ───────────────────────────────────────── */
        .login-wrap {
            position: relative; z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* ─── Card ──────────────────────────────────────────── */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 44px 40px 40px;
            box-shadow:
                0 0 0 1px rgba(232,169,60,.04),
                0 30px 80px rgba(0,0,0,.5),
                inset 0 1px 0 rgba(255,255,255,.05);
            animation: cardIn .5s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1);   }
        }

        /* ─── Brand ─────────────────────────────────────────── */
        .brand {
            text-align: center;
            margin-bottom: 36px;
        }
        .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px; height: 64px;
            border-radius: 16px;
            background: var(--gold-dim);
            border: 1px solid rgba(232,169,60,.25);
            margin-bottom: 18px;
            font-size: 26px;
            box-shadow: 0 8px 24px rgba(232,169,60,.1);
        }
        .brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: .3px;
            line-height: 1.2;
        }
        .brand p {
            font-size: .82rem;
            color: var(--muted);
            margin-top: 6px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        /* ─── Divider ───────────────────────────────────────── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .divider span {
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .divider em {
            font-size: .72rem;
            color: var(--muted);
            font-style: normal;
            letter-spacing: .8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* ─── Alerts ────────────────────────────────────────── */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: .84rem;
            font-weight: 500;
            margin-bottom: 24px;
            animation: alertIn .3s ease both;
        }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            background: rgba(255,71,87,.1);
            border: 1px solid rgba(255,71,87,.25);
            color: #ff6b7a;
        }
        .alert-success {
            background: rgba(46,213,115,.08);
            border: 1px solid rgba(46,213,115,.2);
            color: #2ed573;
        }

        /* ─── Form ──────────────────────────────────────────── */
        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            font-size: .76rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .8px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            opacity: .45;
            pointer-events: none;
        }
        .form-input {
            width: 100%;
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 14px 13px 42px;
            font-family: 'Outfit', sans-serif;
            font-size: .92rem;
            color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form-input::placeholder { color: rgba(255,255,255,.2); }
        .form-input:focus {
            border-color: rgba(232,169,60,.5);
            background: rgba(232,169,60,.04);
            box-shadow: 0 0 0 3px rgba(232,169,60,.08);
        }

        /* Password toggle */
        .toggle-pwd {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; padding: 2px;
            color: var(--muted);
            font-size: 17px;
            transition: color .2s;
        }
        .toggle-pwd:hover { color: var(--gold); }

        /* ─── Submit ────────────────────────────────────────── */
        .btn-login {
            width: 100%;
            padding: 14px;
            margin-top: 8px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            border: none;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            color: #0d0e12;
            cursor: pointer;
            letter-spacing: .3px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(232,169,60,.25);
            position: relative;
            overflow: hidden;
        }
        .btn-login::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.15), transparent);
            opacity: 0;
            transition: opacity .2s;
        }
        .btn-login:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 8px 28px rgba(232,169,60,.35); }
        .btn-login:hover::after { opacity: 1; }
        .btn-login:active { transform: translateY(0); }

        /* ─── Footer note ───────────────────────────────────── */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: .75rem;
            color: var(--muted);
        }
        .login-footer span { color: var(--gold); font-weight: 500; }

        /* ─── Role badges (info row) ────────────────────────── */
        .role-row {
            display: flex;
            gap: 10px;
            margin-top: 24px;
        }
        .role-badge {
            flex: 1;
            text-align: center;
            padding: 9px 6px;
            border-radius: 8px;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .role-admin  { background: rgba(232,169,60,.1); border: 1px solid rgba(232,169,60,.2); color: var(--gold); }
        .role-viewer { background: rgba(99,179,237,.08); border: 1px solid rgba(99,179,237,.18); color: #63b3ed; }
        .role-badge small { display: block; font-size: .65rem; opacity: .7; margin-top: 2px; text-transform: none; font-weight: 400; letter-spacing: 0; }
    </style>
</head>
<body>

<div class="login-bg"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="login-wrap">
    <div class="login-card">

        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon">🍽️</div>
            <h1>La Maison</h1>
            <p>Staff Portal</p>
        </div>

        <!-- Divider -->
        <div class="divider">
            <span></span><em>Secure Sign-In</em><span></span>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error" role="alert" id="login-error">
                🔒 <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif ($logged_out): ?>
            <div class="alert alert-success" role="alert">
                ✅ You have been signed out successfully.
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <?php if ($return_url): ?>
                <input type="hidden" name="return" value="<?= $return_url ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input
                        id="username"
                        class="form-input"
                        type="text"
                        name="username"
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔑</span>
                    <input
                        id="password"
                        class="form-input"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-pwd" id="toggle-pwd" aria-label="Toggle password visibility" title="Show/hide password">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="btn-login">Sign In</button>
        </form>

        <!-- Role info -->
        <div class="role-row">
            <div class="role-badge role-admin">
                Admin
                <small>Full access</small>
            </div>
            <div class="role-badge role-viewer">
                Viewer
                <small>Orders read-only</small>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <span>La Maison</span> · Internal use only
        </div>

    </div><!-- /.login-card -->
</div><!-- /.login-wrap -->

<script>
    // Password visibility toggle
    const pwdInput  = document.getElementById('password');
    const toggleBtn = document.getElementById('toggle-pwd');
    toggleBtn.addEventListener('click', () => {
        const isText = pwdInput.type === 'text';
        pwdInput.type      = isText ? 'password' : 'text';
        toggleBtn.textContent = isText ? '👁️' : '🙈';
    });

    // Auto-focus first empty field
    const u = document.getElementById('username');
    const p = document.getElementById('password');
    (u.value ? p : u).focus();
</script>
</body>
</html>
