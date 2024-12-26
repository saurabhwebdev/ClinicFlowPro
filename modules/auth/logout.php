<?php
require_once '../../config/app_config.php';
session_start();
session_destroy();
header('Location: ' . BASE_URL . '/modules/auth/views/login.php');
exit(); 