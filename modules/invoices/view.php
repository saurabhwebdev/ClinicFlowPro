<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Invoice.php';
require_once '../clinic_settings/models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "View Invoice";
$message = '';
$error = '';

// Get invoice ID
$invoiceId = $_GET['id'] ?? 0;

// Initialize models
$invoice = new Invoice();
$settings = new Settings();

// Get invoice data
$invoiceData = $invoice->getById($invoiceId, $_SESSION['user_id']);
if (!$invoiceData) {
    header('Location: index.php?error=' . urlencode('Invoice not found'));
    exit;
}

// Get clinic settings
$clinicSettings = $settings->getSettings($_SESSION['user_id']);

// Decode items
$items = json_decode($invoiceData['items'], true);
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
        .invoice-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .clinic-logo {
            max-height: 80px;
            object-fit: contain;
        }
        .clinic-info {
            font-size: 0.9rem;
            color: #444;
        }
        .invoice-content {
            min-height: 600px;
        }
        .invoice-footer {
            border-top: 1px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 2rem;
        }
        .status-badge {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
        }
        .table-items th {
            background-color: #f8f9fa;
        }
        .total-section {
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
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
        <!-- Action Buttons -->
        <div class="row mb-4 no-print">
            <div class="col-md-6">
                <h2><i class="fas fa-file-invoice-dollar me-2"></i><?php echo $pageTitle; ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary me-2" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button type="button" class="btn btn-info me-2" id="emailButton" onclick="sendEmail(<?php echo $invoiceId; ?>)">
                    <i class="fas fa-envelope me-1"></i> Email to Patient
                </button>
                <a href="edit.php?id=<?php echo $invoiceId; ?>" class="btn btn-secondary me-2">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <!-- Invoice Content -->
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <!-- Header -->
                <div class="invoice-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <?php if (!empty($clinicSettings['logo_path'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $clinicSettings['logo_path']; ?>" 
                                     alt="Clinic Logo" class="clinic-logo mb-3">
                            <?php endif; ?>
                            <h3 class="mb-1"><?php echo htmlspecialchars($clinicSettings['clinic_name'] ?? 'Clinic Name'); ?></h3>
                            <div class="clinic-info">
                                <?php if (!empty($clinicSettings['address'])): ?>
                                    <p class="mb-1"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($clinicSettings['address']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($clinicSettings['phone'])): ?>
                                    <p class="mb-1"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($clinicSettings['phone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($clinicSettings['email'])): ?>
                                    <p class="mb-1"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($clinicSettings['email']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h4 class="text-primary mb-2">INVOICE</h4>
                            <h5 class="mb-3">#<?php echo htmlspecialchars($invoiceData['invoice_number']); ?></h5>
                            <?php
                            $statusClass = match($invoiceData['status']) {
                                'paid' => 'success',
                                'sent' => 'warning',
                                'draft' => 'secondary',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?> status-badge mb-2">
                                <?php echo ucfirst($invoiceData['status']); ?>
                            </span>
                            <div class="mt-3">
                                <p class="mb-1">Date: <?php echo date('M d, Y', strtotime($invoiceData['created_at'])); ?></p>
                                <p class="mb-1">Due Date: <?php echo date('M d, Y', strtotime($invoiceData['due_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted mb-3">Bill To</h5>
                        <div class="patient-details">
                            <h5 class="mb-2"><?php echo htmlspecialchars($invoiceData['patient_name']); ?></h5>
                            
                            <?php if (!empty($invoiceData['patient_address'])): ?>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($invoiceData['patient_address']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($invoiceData['patient_email'])): ?>
                                <p class="mb-1">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($invoiceData['patient_email']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($invoiceData['patient_phone'])): ?>
                                <p class="mb-1">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($invoiceData['patient_phone']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($invoiceData['appointment_date'])): ?>
                                <p class="mb-1 mt-3">
                                    <i class="fas fa-calendar-check me-2 text-muted"></i>
                                    Related Appointment: <?php 
                                        echo date('M d, Y', strtotime($invoiceData['appointment_date'])) . ' at ' . 
                                             date('h:i A', strtotime($invoiceData['appointment_time'])); 
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="text-muted mb-3">Invoice Details</h5>
                        <p class="mb-1">Invoice #: <?php echo htmlspecialchars($invoiceData['invoice_number']); ?></p>
                        <p class="mb-1">Date: <?php echo date('M d, Y', strtotime($invoiceData['created_at'])); ?></p>
                        <p class="mb-1">Due Date: <?php echo date('M d, Y', strtotime($invoiceData['due_date'])); ?></p>
                        <?php
                        $statusClass = match($invoiceData['status']) {
                            'paid' => 'success',
                            'sent' => 'warning',
                            'draft' => 'secondary',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?> status-badge mt-2">
                            <?php echo ucfirst($invoiceData['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-items">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Quantity</th>
                                <th class="text-end" style="width: 150px;">Unit Price</th>
                                <th class="text-end" style="width: 150px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row">
                    <div class="col-md-6">
                        <?php if (!empty($invoiceData['notes'])): ?>
                            <div class="mb-4">
                                <h6 class="text-muted mb-2">Notes</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($invoiceData['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="total-section">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($invoiceData['subtotal'], 2); ?></span>
                            </div>
                            <?php if (isset($invoiceData['tax_rate']) && floatval($invoiceData['tax_rate']) > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (<?php echo number_format($invoiceData['tax_rate'], 2); ?>%)</span>
                                    <span><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($invoiceData['tax_amount'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($invoiceData['discount_value']) && floatval($invoiceData['discount_value']) > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount 
                                        <?php if ($invoiceData['discount_type'] === 'percentage'): ?>
                                            (<?php echo number_format($invoiceData['discount_value'], 2); ?>%)
                                        <?php endif; ?>
                                    </span>
                                    <span>-<?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($invoiceData['discount_amount'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                <strong>Total</strong>
                                <strong><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($invoiceData['total_amount'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="invoice-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <?php if (!empty($clinicSettings['payment_methods'])): ?>
                                <h6 class="text-muted mb-2">Payment Methods</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($clinicSettings['payment_methods']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if (!empty($clinicSettings['signature_path'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $clinicSettings['signature_path']; ?>" 
                                     alt="Doctor's Signature" class="doctor-signature mb-2" style="max-height: 60px;">
                            <?php endif; ?>
                            <p class="mb-0">Authorized Signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="emailToast" class="toast align-items-center text-white border-0" role="alert" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-spinner fa-spin me-2" id="emailSpinner"></i>
                    <span id="toastMessage">Sending email...</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function sendEmail(id) {
        if (confirm('Send invoice to the patient?')) {
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