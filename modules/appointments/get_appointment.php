<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Appointment.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $appointment = new Appointment();
    $appointmentData = $appointment->getById($_GET['id'], $_SESSION['user_id']);
    
    if ($appointmentData) {
        echo json_encode([
            'success' => true,
            'data' => $appointmentData
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Appointment not found'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
} 