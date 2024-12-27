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

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Appointment History
                        </h5>
                        <a href="../appointments/index.php?patient_id=<?php echo $patientId; ?>" 
                           class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i>New Appointment
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once '../appointments/models/Appointment.php';
                        $appointment = new Appointment();
                        $appointments = $appointment->getAll($_SESSION['user_id'], ['patient_id' => $patientId]);
                        
                        if (empty($appointments)): ?>
                            <p class="text-muted text-center mb-0">
                                <i class="fas fa-info-circle me-1"></i>No appointments found for this patient
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Title</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $apt): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium">
                                                        <?php echo date('M j, Y', strtotime($apt['date'])); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo date('g:i A', strtotime($apt['time'])); ?>
                                                    </small>
                                                </td>
                                                <td><?php echo htmlspecialchars($apt['title']); ?></td>
                                                <td><?php echo $apt['duration']; ?> mins</td>
                                                <td>
                                                    <?php 
                                                    $statusClass = match($apt['status']) {
                                                        'scheduled' => 'primary',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger',
                                                        'rescheduled' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($apt['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="../appointments/index.php?id=<?php echo $apt['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($apt['status'] === 'scheduled'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="updateStatus(<?php echo $apt['id']; ?>, 'completed')"
                                                                    title="Mark as Completed">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="updateStatus(<?php echo $apt['id']; ?>, 'cancelled')"
                                                                    title="Cancel Appointment">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-prescription me-2"></i>Prescription History
                        </h5>
                        <a href="../prescriptions/index.php?patient_id=<?php echo $patientId; ?>" 
                           class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i>New Prescription
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once '../prescriptions/models/Prescription.php';
                        $prescription = new Prescription();
                        $prescriptions = $prescription->getAll($_SESSION['user_id'], ['patient_id' => $patientId]);
                        
                        if (empty($prescriptions)): ?>
                            <p class="text-muted text-center mb-0">
                                <i class="fas fa-info-circle me-1"></i>No prescriptions found for this patient
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Diagnosis</th>
                                            <th>Medications</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prescriptions as $rx): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium">
                                                        <?php echo date('M j, Y', strtotime($rx['created_at'])); ?>
                                                    </div>
                                                    <?php if ($rx['appointment_date']): ?>
                                                        <small class="text-muted">
                                                            Appointment: <?php echo date('M j, Y', strtotime($rx['appointment_date'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($rx['diagnosis']); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $medications = json_decode($rx['medications'], true);
                                                    if (!empty($medications)): 
                                                        foreach ($medications as $med): ?>
                                                            <div class="small">
                                                                <strong><?php echo htmlspecialchars($med['name']); ?></strong>
                                                                - <?php echo htmlspecialchars($med['dosage']); ?>
                                                                (<?php echo htmlspecialchars($med['frequency']); ?>)
                                                            </div>
                                                        <?php endforeach;
                                                    endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="../prescriptions/view.php?id=<?php echo $rx['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-info"
                                                                onclick="sendPrescriptionEmail(<?php echo $rx['id']; ?>)"
                                                                title="Email Prescription">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Invoice History
                        </h5>
                        <a href="../invoices/create.php?patient_id=<?php echo $patientId; ?>" 
                           class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i>New Invoice
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once '../invoices/models/Invoice.php';
                        $invoice = new Invoice();
                        $invoices = $invoice->getAll($_SESSION['user_id'], ['patient_id' => $patientId]);
                        
                        if (empty($invoices)): ?>
                            <p class="text-muted text-center mb-0">
                                <i class="fas fa-info-circle me-1"></i>No invoices found for this patient
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoices as $inv): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium">
                                                        <?php echo htmlspecialchars($inv['invoice_number']); ?>
                                                    </div>
                                                    <?php if ($inv['appointment_date']): ?>
                                                        <small class="text-muted">
                                                            Appointment: <?php echo date('M j, Y', strtotime($inv['appointment_date'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($inv['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <strong>
                                                        <?php echo number_format($inv['total_amount'], 2); ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $statusClass = match($inv['status']) {
                                                        'paid' => 'success',
                                                        'pending' => 'warning',
                                                        'overdue' => 'danger',
                                                        'cancelled' => 'secondary',
                                                        default => 'primary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($inv['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="../invoices/view.php?id=<?php echo $inv['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="View Invoice">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-info"
                                                                onclick="sendInvoiceEmail(<?php echo $inv['id']; ?>)"
                                                                title="Email Invoice">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>
                                                        <?php if ($inv['status'] === 'pending'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="markAsPaid(<?php echo $inv['id']; ?>)"
                                                                    title="Mark as Paid">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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

    function updateStatus(appointmentId, status) {
        if (confirm(`Are you sure you want to mark this appointment as ${status}?`)) {
            fetch('../appointments/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${appointmentId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to update appointment status');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        }
    }

    function sendPrescriptionEmail(prescriptionId) {
        if (confirm('Send prescription details to the patient?')) {
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('../prescriptions/send_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${prescriptionId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Prescription sent successfully!');
                } else {
                    throw new Error(data.error || 'Failed to send prescription');
                }
            })
            .catch(error => {
                showAlert('danger', error.message);
            })
            .finally(() => {
                // Reset button state
                button.disabled = false;
                button.innerHTML = originalHtml;
            });
        }
    }

    function sendInvoiceEmail(invoiceId) {
        if (confirm('Send invoice to the patient?')) {
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('../invoices/send_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${invoiceId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Invoice sent successfully!');
                } else {
                    throw new Error(data.error || 'Failed to send invoice');
                }
            })
            .catch(error => {
                showAlert('danger', error.message);
            })
            .finally(() => {
                // Reset button state
                button.disabled = false;
                button.innerHTML = originalHtml;
            });
        }
    }

    function markAsPaid(invoiceId) {
        if (confirm('Mark this invoice as paid?')) {
            fetch('../invoices/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${invoiceId}&status=paid`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    throw new Error(data.error || 'Failed to update invoice status');
                }
            })
            .catch(error => {
                showAlert('danger', error.message);
            });
        }
    }
    </script>
</body>
</html> 