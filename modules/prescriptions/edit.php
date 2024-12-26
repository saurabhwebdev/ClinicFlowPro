<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Prescription.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $prescription = new Prescription();
    
    // Set prescription properties
    $prescription->id = $_POST['id'] ?? null;
    $prescription->user_id = $_SESSION['user_id'];
    $prescription->diagnosis = $_POST['diagnosis'] ?? '';
    $prescription->instructions = $_POST['instructions'] ?? '';
    $prescription->notes = $_POST['notes'] ?? '';
    
    // Handle medications array
    $medications = [];
    if (isset($_POST['medications']) && is_array($_POST['medications'])) {
        foreach ($_POST['medications'] as $med) {
            if (!empty($med['name'])) {
                $medications[] = [
                    'name' => $med['name'],
                    'dosage' => $med['dosage'] ?? '',
                    'frequency' => $med['frequency'] ?? '',
                    'duration' => $med['duration'] ?? ''
                ];
            }
        }
    }
    $prescription->medications = json_encode($medications);
    
    if ($prescription->update()) {
        // Redirect to index page with success message
        header('Location: index.php?message=' . urlencode('Prescription updated successfully'));
        exit;
    } else {
        throw new Exception('Failed to update prescription');
    }
    
} catch (Exception $e) {
    // Redirect to index page with error message
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
} 