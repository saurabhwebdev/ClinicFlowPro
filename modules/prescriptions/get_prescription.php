<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Prescription.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('Prescription ID is required');
    }

    $prescription = new Prescription();
    $prescriptionData = $prescription->getById($id, $_SESSION['user_id']);

    if (!$prescriptionData) {
        throw new Exception('Prescription not found');
    }

    echo json_encode($prescriptionData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 