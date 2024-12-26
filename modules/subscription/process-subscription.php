<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once '../auth/models/User.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Add error logging
error_log('Subscription process started');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['subscriptionID']) || !isset($data['planType'])) {
    error_log('Invalid request data: ' . print_r($data, true));
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user = new User();
    $user->id = $_SESSION['user_id'];
    
    // Calculate subscription end date
    $endDate = new DateTime();
    if ($data['planType'] === 'yearly') {
        $endDate->modify('+1 year');
    } else {
        $endDate->modify('+1 month');
    }
    
    // Update user subscription
    $query = "UPDATE users SET 
              subscription_status = 'active',
              subscription_plan_id = :plan_id,
              subscription_ends_at = :end_date
              WHERE id = :user_id";
              
    $stmt = $user->getConnection()->prepare($query);
    
    $endDateStr = $endDate->format('Y-m-d H:i:s');
    error_log("Updating subscription for user {$_SESSION['user_id']} with plan {$data['subscriptionID']} ending at {$endDateStr}");
    
    $stmt->bindParam(':plan_id', $data['subscriptionID']);
    $stmt->bindParam(':end_date', $endDateStr);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Update session
        $_SESSION['subscription_status'] = 'active';
        error_log('Subscription updated successfully');
        echo json_encode(['success' => true]);
    } else {
        error_log('Database update failed: ' . print_r($stmt->errorInfo(), true));
        echo json_encode(['success' => false, 'error' => 'Failed to update subscription']);
    }
} catch (Exception $e) {
    error_log('Error in subscription process: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 