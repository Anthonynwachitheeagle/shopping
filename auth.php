<?php
// ============================================
//  Admin Authentication Helper
//  Included by all admin PHP pages
// ============================================

require_once __DIR__ . '/config.php';
session_start();

// ── Check if logged in ──
function requireLogin(): void {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

// ── Login ──
function attemptLogin(string $username, string $password): bool {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $username;
        return true;
    }
    return false;
}

// ── Logout ──
function logout(): void {
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}
