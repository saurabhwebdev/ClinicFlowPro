<?php
require_once '../../config/app_config.php';
require_once 'controllers/AuthController.php';

if (!isset($_GET['token'])) {
    header('Location: ' . BASE_URL);
    exit();
}

$auth = new AuthController();
$result = $auth->verifyEmail($_GET['token']);

session_start();
if ($result['success']) {
    $_SESSION['message'] = 'Email verified successfully! You can now login.';
} else {
    $_SESSION['message'] = $result['error'] ?? 'Invalid or expired verification token.';
}

header('Location: ' . BASE_URL . '/modules/auth/views/login.php');
exit(); 