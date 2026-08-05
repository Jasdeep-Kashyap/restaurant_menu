<?php
/**
 * auth.php — Session-based authentication helper
 *
 * Roles:
 *   admin  → full access (admin.php + orders.php)
 *   viewer → read-only  (orders.php only)
 *
 * Credentials are stored as bcrypt hashes (PASSWORD_BCRYPT, cost 10).
 * To regenerate:  password_hash('your_password', PASSWORD_BCRYPT)
 */

// ── Credential store ──────────────────────────────────────────────────────────
const AUTH_USERS = [
    'Dunes_canteen' => [
        'hash' => '$2y$10$NcbYJK6h3A8K7yqfXnWen.lOBUS9KG8mT7/Ud5QugLG.1NmUzmNRK',
        'role' => 'admin',
    ],
    'viewer' => [
        'hash' => '$2y$10$jBJaIG0cCCseB4NNsduUi.zI2/P31GTcyiuM1G39hqbToqS.jLyqa',
        'role' => 'viewer',
    ],
];

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Start session safely (idempotent).
 */
function auth_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Attempt login. Returns role string on success, false on failure.
 */
function auth_attempt(string $username, string $password): string|false {
    $users = AUTH_USERS;
    if (!isset($users[$username])) {
        return false;
    }
    if (password_verify($password, $users[$username]['hash'])) {
        return $users[$username]['role'];
    }
    return false;
}

/**
 * Require the user to be authenticated and have one of the allowed roles.
 * Redirects to login.php with a return URL on failure.
 *
 * @param string[] $allowed_roles  e.g. ['admin'] or ['admin','viewer']
 */
function auth_require(array $allowed_roles = ['admin', 'viewer']): void {
    auth_session_start();

    $logged_in = !empty($_SESSION['auth_user']) && !empty($_SESSION['auth_role']);

    if (!$logged_in || !in_array($_SESSION['auth_role'], $allowed_roles, true)) {
        $return = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ../Backend/login.php?return=' . $return);
        exit;
    }
}

/**
 * Return the currently authenticated user's role, or null if not logged in.
 */
function auth_role(): ?string {
    auth_session_start();
    return $_SESSION['auth_role'] ?? null;
}

/**
 * Return the currently authenticated username, or null if not logged in.
 */
function auth_user(): ?string {
    auth_session_start();
    return $_SESSION['auth_user'] ?? null;
}

/**
 * Destroy the session and redirect to login.
 */
function auth_logout(): void {
    auth_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ../Backend/login.php?logged_out=1');
    exit;
}
