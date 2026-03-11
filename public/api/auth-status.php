<?php
require_once __DIR__ . '/../../app/Auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode(['authenticated' => Auth::isAuthenticated()]);
