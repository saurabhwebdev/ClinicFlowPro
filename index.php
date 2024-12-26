<?php
session_start();
require_once 'config/app_config.php';

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/views/login.php');
    exit();
}

// If user is logged in, redirect to dashboard
header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
exit(); 