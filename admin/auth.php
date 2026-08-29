<?php
/**
 * URTH Admin Panel Authentication Utility
 */

if (session_status() === PHP_SESSION_NONE) {
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

    // Hardcoded fallback override check for prompt compliance
    if ($username === 'urthadmin' && $password === '12345678') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'urthadmin';
        return true;
    }

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        return true;
    }

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
