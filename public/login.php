<?php
require_once __DIR__ . '/../app/Auth.php';

// If already authenticated, redirect to dashboard
if (Auth::isAuthenticated()) {
    header('Location: /dashboard');
    exit;
}

// Capture redirect param (relative paths only) and pass to view
$redirectParam = $_GET['redirect'] ?? '';
if ($redirectParam && (strpos($redirectParam, '/') !== 0 || strpos($redirectParam, '//') === 0)) {
    $redirectParam = '';
}

// Display login page
require_once __DIR__ . '/../resources/views/login.php';
