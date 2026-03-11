<?php
require_once __DIR__ . '/../../app/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        header('Location: /login?error=required');
        exit;
    }
    
    // Attempt login
    if (Auth::login($username, $password)) {
        $redirect = $_POST['redirect'] ?? '';
        // Only allow relative paths to prevent open redirect
        if ($redirect && strpos($redirect, '/') === 0 && strpos($redirect, '//') !== 0) {
            header('Location: ' . $redirect);
        } else {
            header('Location: /dashboard');
        }
        exit;
    } else {
        header('Location: /login?error=invalid');
        exit;
    }
} else {
    header('Location: /login');
    exit;
}
