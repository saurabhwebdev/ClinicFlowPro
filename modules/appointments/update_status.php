<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Appointment.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $appointment = new Appointment();
    $appointment->id = $_POST['id'];
    $appointment->user_id = $_SESSION['user_id'];
    $appointment->status = $_POST['status'];
    
    if ($appointment->updateStatus()) {
        echo json_encode([
            'success' => true,
            'message' => 'Appointment status updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to update appointment status'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
} 