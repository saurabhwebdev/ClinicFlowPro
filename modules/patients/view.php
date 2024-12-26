<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Patient.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Patient Details";
$message = '';
$error = '';

// Initialize Patient model
$patient = new Patient();

// Get patient ID from URL
$patientId = $_GET['id'] ?? 0;

// Get patient data
$patientData = $patient->getOne($patientId); // Pass the ID parameter
if (!$patientData) {
    header('Location: index.php?error=' . urlencode('Patient not found'));
    exit;
}

// Handle email form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    if (empty($patientData['email'])) {
        $error = 'Patient email address is not available.';
    } else {
        $subject = $_POST['email_subject'] ?? '';
        $message = $_POST['email_message'] ?? '';
        
        if (empty($subject) || empty($message)) {
            $error = 'Subject and message are required.';
        } else {
            $patient->email = $patientData['email'];
            if ($patient->sendEmail($subject, $message)) {
                $message = 'Email sent successfully.';
            } else {
                $error = 'Failed to send email. Please try again.';
            }
        }
    }
}
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
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .loading-text {
        color: white;
        margin-top: 10px;
        font-weight: 500;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="text-center">
            <div class="spinner"></div>
            <div class="loading-text">Sending email...</div>
        </div>
    </div>

    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12 d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user me-2"></i><?php echo $pageTitle; ?></h2>
                <div>
                    <a href="edit.php?id=<?php echo $patientData['id']; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-1"></i> Edit Patient
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Patient Information -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Patient Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($patientData['first_name'] . ' ' . $patientData['last_name']); ?></p>
                                <p><strong>Email:</strong> 
                                    <?php if ($patientData['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($patientData['email']); ?>">
                                            <?php echo htmlspecialchars($patientData['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($patientData['phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Gender:</strong> <?php echo ucfirst($patientData['gender']); ?></p>
                                <p><strong>Date of Birth:</strong> <?php echo date('F j, Y', strtotime($patientData['date_of_birth'])); ?></p>
                                <p><strong>Added On:</strong> <?php echo date('F j, Y', strtotime($patientData['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-bold">Address</h6>
                            <p><?php echo nl2br(htmlspecialchars($patientData['address'] ?: 'Not provided')); ?></p>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-bold">Medical History</h6>
                            <p><?php echo nl2br(htmlspecialchars($patientData['medical_history'] ?: 'No medical history recorded')); ?></p>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-bold">Notes</h6>
                            <p><?php echo nl2br(htmlspecialchars($patientData['notes'] ?: 'No notes available')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <!-- Email Form -->
                <?php if ($patientData['email']): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-envelope me-2"></i>Send Email
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="emailForm" onsubmit="return showLoading()">
                                <div class="mb-3">
                                    <label for="email_subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="email_subject" name="email_subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email_message" class="form-label">Message</label>
                                    <textarea class="form-control" id="email_message" name="email_message" rows="4" required></textarea>
                                </div>
                                <button type="submit" name="send_email" class="btn btn-primary" id="sendEmailBtn">
                                    <i class="fas fa-paper-plane me-1"></i> Send Email
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Actions Card -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="edit.php?id=<?php echo $patientData['id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-1"></i> Edit Patient
                            </a>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="confirmDelete(<?php echo $patientData['id']; ?>)">
                                <i class="fas fa-trash me-1"></i> Delete Patient
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmDelete(patientId) {
        if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
            window.location.href = `delete.php?id=${patientId}`;
        }
    }

    // Update the email form submission
    document.getElementById('emailForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent form from submitting normally
        
        const overlay = document.querySelector('.loading-overlay');
        const sendBtn = document.querySelector('#sendEmailBtn');
        const subject = document.querySelector('#email_subject').value;
        const message = document.querySelector('#email_message').value;
        
        // Show loading overlay
        overlay.style.display = 'flex';
        sendBtn.disabled = true;
        
        // Create form data
        const formData = new FormData();
        formData.append('send_email', '1');
        formData.append('email_subject', subject);
        formData.append('email_message', message);
        
        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Create a temporary div to parse the response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Check for success or error messages in the response
            const successAlert = tempDiv.querySelector('.alert-success');
            const errorAlert = tempDiv.querySelector('.alert-danger');
            
            // Hide loading overlay
            overlay.style.display = 'none';
            sendBtn.disabled = false;
            
            // Clear existing alerts
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            
            // Show new alert based on response
            if (successAlert) {
                // Success - clear the form
                document.getElementById('emailForm').reset();
                showAlert('success', successAlert.textContent);
            } else if (errorAlert) {
                // Error
                showAlert('danger', errorAlert.textContent);
            } else {
                // Fallback error message
                showAlert('danger', 'An unexpected error occurred. Please try again.');
            }
        })
        .catch(error => {
            // Hide loading overlay and show error
            overlay.style.display = 'none';
            sendBtn.disabled = false;
            showAlert('danger', 'Failed to send email. Please try again.');
            console.error('Error:', error);
        });
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert alert before the main content
        const container = document.querySelector('.row');
        container.parentNode.insertBefore(alertDiv, container);
        
        // Auto-hide alert after 5 seconds
        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s ease';
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }
    </script>
</body>
</html> 