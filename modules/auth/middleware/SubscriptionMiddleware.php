<?php
require_once __DIR__ . '/../../../config/app_config.php';
require_once __DIR__ . '/../models/User.php';

class SubscriptionMiddleware {
    public static function checkAccess() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/modules/auth/views/login.php');
            exit();
        }
        
        // Don't check subscription for plans page
        if (strpos($_SERVER['REQUEST_URI'], '/modules/subscription/plans.php') !== false) {
            return true;
        }
        
        $user = new User();
        $user->id = $_SESSION['user_id'];
        $status = $user->checkSubscriptionStatus();
        
        // Update session with current status
        $_SESSION['subscription_status'] = $status;
        
        if($status === 'expired') {
            header('Location: ' . BASE_URL . '/modules/subscription/plans.php');
            exit();
        }
        
        return true;
    }
} 