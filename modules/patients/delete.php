<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Patient.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

// Initialize Patient model
$patient = new Patient();
$patient->id = $_GET['id'] ?? 0;
$patient->user_id = $_SESSION['user_id'];

// Verify patient exists and belongs to user
if (!$patient->getOne()) {
    header('Location: index.php?error=' . urlencode('Patient not found'));
    exit;
}

// Delete patient
if ($patient->delete()) {
    header('Location: index.php?message=' . urlencode('Patient deleted successfully'));
} else {
    header('Location: index.php?error=' . urlencode('Failed to delete patient'));
}
exit; 