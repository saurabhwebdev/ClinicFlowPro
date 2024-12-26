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
    
    // Set appointment properties
    $appointment->user_id = $_SESSION['user_id'];
    $appointment->patient_id = $_POST['patient_id'];
    $appointment->title = $_POST['title'];
    $appointment->date = $_POST['date'];
    $appointment->time = $_POST['time'];
    $appointment->duration = $_POST['duration'];
    $appointment->status = 'scheduled';
    $appointment->notes = $_POST['notes'] ?? '';
    
    // Validate working hours
    $validation = $appointment->validateWorkingHours($_SESSION['user_id']);
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $validation['message']]);
        exit;
    }
    
    if ($appointment->create()) {
        echo json_encode([
            'success' => true,
            'message' => 'Appointment created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to create appointment'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
} 