<?php
require_once __DIR__ . '/../config/session.php';

if (!empty($_SESSION['admin_id'])) {
    log_activity($pdo, 'logout', "{$_SESSION['admin_name']} logged out");
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: ' . BASE_URL . 'auth/login.php');
exit;
