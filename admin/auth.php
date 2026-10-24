<?php
/**
 * URTH Admin Panel Authentication Utility
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Check if current session is logged in as admin
 */
function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !empty($_SESSION['admin_username']);
}

/**
 * Enforce admin auth guard on admin pages.
 * Redirects to login.php if unauthenticated.
 */
function require_admin_auth(): void {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Authenticate admin user
 */
function authenticate_admin(string $username, string $password): bool {
    global $pdo;

    $username = trim($username);
    $attemptKey = hash('sha256', strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $now = time();
    $attemptStmt = $pdo->prepare("SELECT attempts, window_started, locked_until FROM admin_login_attempts WHERE attempt_key = :attempt_key");
    $attemptStmt->execute(['attempt_key' => $attemptKey]);
    $attempt = $attemptStmt->fetch();
    if ($attempt && (int)$attempt['locked_until'] > $now) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        $clearAttempts = $pdo->prepare("DELETE FROM admin_login_attempts WHERE attempt_key = :attempt_key");
        $clearAttempts->execute(['attempt_key' => $attemptKey]);
        return true;
    }

    $windowStarted = $attempt && $now - (int)$attempt['window_started'] < 900
        ? (int)$attempt['window_started']
        : $now;
    $attempts = $windowStarted === ($attempt ? (int)$attempt['window_started'] : 0)
        ? (int)$attempt['attempts'] + 1
        : 1;
    $lockedUntil = $attempts >= 5 ? $now + 900 : 0;
    $saveAttempt = $pdo->prepare("INSERT INTO admin_login_attempts (attempt_key, attempts, window_started, locked_until)
        VALUES (:attempt_key, :attempts, :window_started, :locked_until)
        ON CONFLICT(attempt_key) DO UPDATE SET attempts = excluded.attempts, window_started = excluded.window_started, locked_until = excluded.locked_until");
    $saveAttempt->execute([
        'attempt_key' => $attemptKey,
        'attempts' => $attempts,
        'window_started' => $windowStarted,
        'locked_until' => $lockedUntil
    ]);
    return false;
}

/**
 * Logout admin user
 */
function logout_admin(): void {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Generate and store a CSRF token in the session.
 * FIX #11: Provides CSRF protection for forms.
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the CSRF token submitted with a POST request.
 * FIX #11: Call this at the top of any POST handler.
 */
function validate_csrf_token(): bool {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';
    return !empty($stored) && hash_equals($stored, $submitted);
}
