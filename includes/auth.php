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
 *
 * Security features:
 *  - Secure session cookie flags (HttpOnly, SameSite=Strict)
 *  - CSRF token generation and verification
 *  - Brute-force lockout (5 attempts → 5-minute cooldown)
 *  - Open-redirect prevention on return URLs
 */

// ── Credential store ──────────────────────────────────────────
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

// Brute-force settings
const AUTH_MAX_ATTEMPTS  = 5;
const AUTH_LOCKOUT_SECS  = 300; // 5 minutes

// ── Session Helpers ───────────────────────────────────────────

/**
 * Configure secure cookie parameters BEFORE calling session_start().
 * Must be called once, before any output.
 */
function auth_set_cookie_params(): void {
    $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,   // HTTPS-only when available
        'httponly' => true,       // No JavaScript access
        'samesite' => 'Strict',   // No cross-site sending
    ]);
}

/**
 * Start session safely (idempotent). Applies secure cookie params first.
 */
function auth_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        auth_set_cookie_params();
        session_start();
    }
}

// ── CSRF Helpers ──────────────────────────────────────────────

/**
 * Return the current session CSRF token, generating one if absent.
 */
function csrf_token(): string {
    auth_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token submitted in a POST request.
 * Terminates with HTTP 403 if invalid.
 */
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        // Show custom error page if it exists, otherwise plain text
        $error_page = __DIR__ . '/../errors/403.php';
        if (file_exists($error_page)) {
            require $error_page;
        } else {
            echo 'Forbidden: Invalid or missing CSRF token.';
        }
        exit;
    }
}

// ── Brute-Force Lockout ───────────────────────────────────────

/**
 * Check if the given username is currently locked out.
 * Returns true if locked, false if allowed.
 */
function auth_is_locked(string $username): bool {
    auth_session_start();
    $key  = 'bf_' . md5($username);
    $data = $_SESSION[$key] ?? null;
    if (!$data) {
        return false;
    }
    if ($data['attempts'] >= AUTH_MAX_ATTEMPTS) {
        $elapsed = time() - $data['last_attempt'];
        if ($elapsed < AUTH_LOCKOUT_SECS) {
            return true;
        }
        // Lockout expired — reset
        unset($_SESSION[$key]);
    }
    return false;
}

/**
 * Return remaining lockout seconds for a username (0 if not locked).
 */
function auth_lockout_remaining(string $username): int {
    auth_session_start();
    $key  = 'bf_' . md5($username);
    $data = $_SESSION[$key] ?? null;
    if (!$data || $data['attempts'] < AUTH_MAX_ATTEMPTS) {
        return 0;
    }
    $remaining = AUTH_LOCKOUT_SECS - (time() - $data['last_attempt']);
    return max(0, (int) $remaining);
}

/**
 * Record a failed login attempt for rate-limiting purposes.
 */
function auth_record_failure(string $username): void {
    auth_session_start();
    $key  = 'bf_' . md5($username);
    $data = $_SESSION[$key] ?? ['attempts' => 0, 'last_attempt' => 0];

    // Reset counter if previous lockout expired
    if ($data['attempts'] >= AUTH_MAX_ATTEMPTS &&
        (time() - $data['last_attempt']) >= AUTH_LOCKOUT_SECS) {
        $data = ['attempts' => 0, 'last_attempt' => 0];
    }

    $data['attempts']++;
    $data['last_attempt'] = time();
    $_SESSION[$key] = $data;
}

/**
 * Clear the failure record for a username (called on successful login).
 */
function auth_clear_failures(string $username): void {
    auth_session_start();
    unset($_SESSION['bf_' . md5($username)]);
}

// ── Auth Logic ────────────────────────────────────────────────

/**
 * Attempt login. Returns role string on success, false on failure.
 * Enforces brute-force lockout.
 */
function auth_attempt(string $username, string $password): string|false {
    if (auth_is_locked($username)) {
        return false;
    }

    $users = AUTH_USERS;
    if (!isset($users[$username])) {
        // Record failure against a generic bucket to prevent username enumeration
        auth_record_failure('__unknown__');
        return false;
    }

    if (password_verify($password, $users[$username]['hash'])) {
        auth_clear_failures($username);
        return $users[$username]['role'];
    }

    auth_record_failure($username);
    return false;
}

/**
 * Require the user to be authenticated and have one of the allowed roles.
 * Redirects to login.php with a sanitised return URL on failure.
 *
 * @param string[] $allowed_roles  e.g. ['admin'] or ['admin','viewer']
 */
function auth_require(array $allowed_roles = ['admin', 'viewer']): void {
    auth_session_start();

    $logged_in = !empty($_SESSION['auth_user']) && !empty($_SESSION['auth_role']);

    if (!$logged_in || !in_array($_SESSION['auth_role'], $allowed_roles, true)) {
        // Build a safe same-origin return URL (fix MED-3: reject //evil.com pattern)
        $raw    = $_SERVER['REQUEST_URI'] ?? '';
        $parsed = parse_url($raw);
        // Only allow relative paths — reject anything with a host component
        $safe_return = (isset($parsed['path']) && !isset($parsed['host']))
            ? urlencode($raw)
            : '';

        header('Location: ../Backend/login.php' . ($safe_return ? '?return=' . $safe_return : ''));
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
