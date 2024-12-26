<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Prescription.php';
require_once '../clinic_settings/models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "View Prescription";
$message = '';
$error = '';

// Get prescription ID
$prescriptionId = $_GET['id'] ?? 0;

// Initialize models
$prescription = new Prescription();
$settings = new Settings();

// Get prescription data
$prescriptionData = $prescription->getById($prescriptionId, $_SESSION['user_id']);
if (!$prescriptionData) {
    header('Location: index.php?error=' . urlencode('Prescription not found'));
    exit;
}

// Get clinic settings
$clinicSettings = $settings->getSettings($_SESSION['user_id']);

// Decode medications
$medications = json_decode($prescriptionData['medications'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .card { border: none !important; }
            .card-body { padding: 0 !important; }
        }
        .prescription-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .clinic-logo {
            max-height: 80px;
            object-fit: contain;
        }
        .doctor-info {
            font-size: 0.9rem;
            color: #444;
        }
        .prescription-content {
            min-height: 600px;
        }
        .prescription-footer {
            border-top: 1px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 2rem;
        }
        .doctor-signature {
            max-height: 60px;
            object-fit: contain;
        }
        .rx-symbol {
            font-size: 1.5rem;
            color: #0d6efd;
            font-weight: bold;
        }
        .medication-table th {
            background-color: #f8f9fa;
        }
        .clinic-contact {
            font-size: 0.85rem;
            color: #666;
        }
        .toast {
            min-width: 300px;
        }
        .toast-body {
            display: flex;
            align-items: center;
        }
        #emailSpinner {
            margin-right: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="no-print">
        <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    </div>
    
    <div class="container mt-4 mb-5">
        <div class="row mb-4 no-print">
            <div class="col-md-6">
                <h2><i class="fas fa-prescription me-2"></i><?php echo $pageTitle; ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary me-2" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button type="button" class="btn btn-info me-2" id="emailButton" onclick="sendEmail(<?php echo $prescriptionId; ?>)">
                    <i class="fas fa-envelope me-1"></i> Email to Patient
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <!-- Prescription Content -->
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <!-- Header -->
                <div class="prescription-header">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <?php if (!empty($clinicSettings['logo_path'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $clinicSettings['logo_path']; ?>" 
                                     alt="Clinic Logo" class="clinic-logo">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <h3 class="mb-1"><?php echo htmlspecialchars($clinicSettings['clinic_name'] ?? 'Clinic Name'); ?></h3>
                            <div class="doctor-info">
                                <p class="mb-1">
                                    <?php echo htmlspecialchars($clinicSettings['doctor_name'] ?? ''); ?>
                                    <?php if (!empty($clinicSettings['degree'])): ?>
                                        <span class="ms-2"><?php echo htmlspecialchars($clinicSettings['degree']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($clinicSettings['specialization'])): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($clinicSettings['specialization']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($clinicSettings['license_number'])): ?>
                                    <p class="mb-1">Reg. No: <?php echo htmlspecialchars($clinicSettings['license_number']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3 clinic-contact text-end">
                            <?php if (!empty($clinicSettings['phone'])): ?>
                                <p class="mb-1"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($clinicSettings['phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($clinicSettings['email'])): ?>
                                <p class="mb-1"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($clinicSettings['email']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($clinicSettings['address'])): ?>
                                <p class="mb-1"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($clinicSettings['address']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Prescription Content -->
                <div class="prescription-content">
                    <!-- Patient Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Patient Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($prescriptionData['patient_name']); ?></p>
                            <?php if (!empty($prescriptionData['patient_email'])): ?>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($prescriptionData['patient_email']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('F j, Y', strtotime($prescriptionData['created_at'])); ?></p>
                            <?php if ($prescriptionData['appointment_date']): ?>
                                <p class="mb-1">
                                    <strong>Appointment:</strong> 
                                    <?php echo date('F j, Y', strtotime($prescriptionData['appointment_date'])); ?>
                                    at <?php echo date('g:i A', strtotime($prescriptionData['appointment_time'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Diagnosis -->
                    <?php if (!empty($prescriptionData['diagnosis'])): ?>
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Diagnosis</h6>
                        <p><?php echo nl2br(htmlspecialchars($prescriptionData['diagnosis'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Medications -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">
                            <span class="rx-symbol">℞</span> Medications
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered medication-table">
                                <thead>
                                    <tr>
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medications as $med): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($med['name']); ?></td>
                                            <td><?php echo htmlspecialchars($med['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                                            <td><?php echo htmlspecialchars($med['duration']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <?php if (!empty($prescriptionData['instructions'])): ?>
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Instructions</h6>
                        <p><?php echo nl2br(htmlspecialchars($prescriptionData['instructions'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Notes -->
                    <?php if (!empty($prescriptionData['notes'])): ?>
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Additional Notes</h6>
                        <p><?php echo nl2br(htmlspecialchars($prescriptionData['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <div class="prescription-footer">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="clinic-contact">
                                <?php if (!empty($clinicSettings['website'])): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-globe me-1"></i> 
                                        <?php echo htmlspecialchars($clinicSettings['website']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php 
                                // Display social media links
                                $socialMedia = json_decode($clinicSettings['social_media_links'] ?? '[]', true);
                                if (!empty($socialMedia)): 
                                ?>
                                    <div class="social-media-links">
                                        <?php foreach ($socialMedia as $platform => $link): 
                                            if (empty($link)) continue;
                                            
                                            // Get appropriate icon for each platform
                                            $icon = match(strtolower($platform)) {
                                                'facebook' => 'fab fa-facebook',
                                                'twitter' => 'fab fa-twitter',
                                                'instagram' => 'fab fa-instagram',
                                                'linkedin' => 'fab fa-linkedin',
                                                'youtube' => 'fab fa-youtube',
                                                default => 'fas fa-link'
                                            };
                                        ?>
                                            <span class="me-3">
                                                <i class="<?php echo $icon; ?> me-1"></i>
                                                <?php echo htmlspecialchars($link); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if (!empty($clinicSettings['signature_path'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $clinicSettings['signature_path']; ?>" 
                                     alt="Doctor's Signature" class="doctor-signature mb-2">
                            <?php endif; ?>
                            <p class="mb-0">Doctor's Signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="emailToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-spinner fa-spin me-2" id="emailSpinner"></i>
                    <span id="toastMessage">Sending email...</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function sendEmail(id) {
        if (confirm('Send prescription details to the patient?')) {
            // Get elements
            const emailButton = document.getElementById('emailButton');
            const toast = document.getElementById('emailToast');
            const toastMessage = document.getElementById('toastMessage');
            const emailSpinner = document.getElementById('emailSpinner');
            
            // Create toast instance
            const toastInstance = new bootstrap.Toast(toast);
            
            // Disable button and show loading state
            emailButton.disabled = true;
            emailButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';
            
            // Show initial toast
            toast.classList.add('bg-info');
            toast.classList.remove('bg-success', 'bg-danger');
            toastMessage.textContent = 'Sending email...';
            emailSpinner.style.display = 'inline-block';
            toastInstance.show();

            fetch('send_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success toast
                    toast.classList.remove('bg-info');
                    toast.classList.add('bg-success');
                    toastMessage.textContent = 'Email sent successfully!';
                    emailSpinner.style.display = 'none';
                    toastInstance.show();
                } else {
                    throw new Error(data.error || 'Failed to send email');
                }
            })
            .catch(error => {
                // Show error toast
                toast.classList.remove('bg-info');
                toast.classList.add('bg-danger');
                toastMessage.textContent = 'Error: ' + error.message;
                emailSpinner.style.display = 'none';
                toastInstance.show();
            })
            .finally(() => {
                // Reset button state
                emailButton.disabled = false;
                emailButton.innerHTML = '<i class="fas fa-envelope me-1"></i> Email to Patient';
                
                // Hide toast after 5 seconds
                setTimeout(() => {
                    toastInstance.hide();
                }, 5000);
            });
        }
    }
    </script>
</body>
</html> 