<?php
// Shared authentication helpers for admin pages.
// The session is started once here so all protected admin screens use the same
// authenticated state without duplicating logic across files.
require_once __DIR__ . '/session.php';

require_once __DIR__ . '/../config/db.php';

/**
 * Validate admin credentials against the existing admins table.
 * The schema stores username + password_hash, so the login flow should use the
 * existing columns rather than adding a new email field or changing the table.
 */
function adminLogin($username, $password)
{
    $username = trim((string)$username);
    $password = (string)$password;

    if ($username === '' || $password === '') {
        return false;
    }

    $pdo = getPDO();

    // Use prepared SQL so the submitted username is bound safely and never
    // concatenated into the query string.
    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, full_name
         FROM admins
         WHERE username = ? AND status = ?
         LIMIT 1'
    );
    $stmt->execute([$username, 'active']);
    $admin = $stmt->fetch();

    if (!$admin) {
        return false;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    // Rotate the session identifier after successful authentication to reduce
    // fixation attacks and keep the admin session fresh.
    require_once __DIR__ . '/session.php';

    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['last_activity'] = time();

    return $admin;
}

/**
 * Protect admin routes from unauthenticated access.
 */
function requireAdmin()
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: /NAAQSH/admin/login.php');
        exit;
    }
}

/**
 * Clear the admin session and invalidate the current session cookie.
 */
function adminLogout()
{
    require_once __DIR__ . '/session.php';

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Customer login and registration helpers can be added similarly.
 */
