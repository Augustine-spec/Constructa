<?php
/**
 * Session Check and Authentication Helper
 * Include this file at the top of protected pages
 */

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin($redirectTo = 'login.html') {
    if (!isLoggedIn()) {
        header("Location: ../" . $redirectTo);
        exit();
    }
}

function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'homeowner'
    ];
}

function logout() {
    session_start();
    session_unset();
    session_destroy();
    header("Location: ../landingpage.html");
    exit();
}
?>
