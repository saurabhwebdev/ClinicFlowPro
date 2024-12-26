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
    $prescription->id = $_POST['id'] ?? null;
    $prescription->user_id = $_SESSION['user_id'];
    
    if ($prescription->delete()) {
        // Redirect to index page with success message
        header('Location: index.php?message=' . urlencode('Prescription deleted successfully'));
        exit;
    } else {
        throw new Exception('Failed to delete prescription');
    }
    
} catch (Exception $e) {
    // Redirect to index page with error message
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
} 