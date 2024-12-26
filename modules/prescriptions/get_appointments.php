<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once '../appointments/models/Appointment.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

header('Content-Type: application/json');

try {
    $patientId = $_GET['patient_id'] ?? null;
    if (!$patientId) {
        throw new Exception('Patient ID is required');
    }

    $appointment = new Appointment();
    $appointments = $appointment->getAll($_SESSION['user_id'], [
        'patient_id' => $patientId,
        'status' => 'completed'
    ]);

    echo json_encode($appointments);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 