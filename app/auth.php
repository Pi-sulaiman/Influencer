<?php
/**
 * Admin authentication with login throttling.
 */

function admin_user()
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $user = one('SELECT id, name, email, created_at, last_login_at FROM admins WHERE id = ?', [(int) $_SESSION['admin_id']]);
        if (!$user) {
            unset($_SESSION['admin_id']);
        }
    }
    return $user;
}

function require_admin()
{
    if (!admin_user()) {
        $next = request_path();
        redirect('admin/login' . ($next && $next !== '/admin' ? '?next=' . rawurlencode($next) : ''));
    }
    // Admin pages are private: never cache, never index.
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');
}

function login_locked()
{
    $since = date('Y-m-d H:i:s', time() - 15 * 60);
    $count = (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND attempted_at > ?', [ip_hash(), $since]);
    return $count >= 6;
}

function attempt_login($email, $password)
{
    if (login_locked()) {
        return 'Too many attempts. Please wait 15 minutes and try again.';
    }
    $admin = one('SELECT * FROM admins WHERE email = ?', [strtolower(trim($email))]);
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        insert('login_attempts', ['ip_hash' => ip_hash(), 'attempted_at' => now()]);
        usleep(400000);
        return 'That email and password do not match.';
    }
    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        update('admins', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$admin['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    update('admins', ['last_login_at' => now()], 'id = ?', [$admin['id']]);
    q('DELETE FROM login_attempts WHERE ip_hash = ?', [ip_hash()]);
    return true;
}

function logout()
{
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}
