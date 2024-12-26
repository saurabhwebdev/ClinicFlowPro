<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once '../appointments/models/Appointment.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

header('Content-Type: application/json');

try {
    // Validate patient ID
    $patientId = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    if (!$patientId) {
        throw new Exception('Invalid patient ID');
    }

    // Initialize appointment model
    $appointment = new Appointment();
    
    // Get appointments for the patient
    // Only get appointments from the last 3 months and upcoming ones
    $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
    $appointments = $appointment->getByPatient(
        $_SESSION['user_id'], 
        $patientId, 
        [
            'start_date' => $threeMonthsAgo,
            'status' => ['completed', 'confirmed'] // Only show completed and confirmed appointments
        ]
    );

    // Format appointments for display
    $formattedAppointments = array_map(function($apt) {
        return [
            'id' => $apt['id'],
            'date' => date('M d, Y', strtotime($apt['date'])),
            'time' => date('h:i A', strtotime($apt['time'])),
            'service' => $apt['service_name'] ?? 'General Appointment',
            'status' => $apt['status']
        ];
    }, $appointments);

    // Sort appointments by date (most recent first)
    usort($formattedAppointments, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo json_encode($formattedAppointments);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 