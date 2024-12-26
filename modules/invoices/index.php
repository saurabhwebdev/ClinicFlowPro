<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Invoice.php';
require_once '../patients/models/Patient.php';
require_once '../appointments/models/Appointment.php';
require_once '../clinic_settings/models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Invoices";
$message = '';
$error = '';

// Get messages from URL parameters
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Initialize models
$invoice = new Invoice();
$settings = new Settings();

// Get clinic settings
$clinicSettings = $settings->getSettings($_SESSION['user_id']);

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Number of invoices per page
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get invoices with pagination
$filters = [];
if (!empty($search)) {
    $filters['search'] = $search;
}

$total_invoices = $invoice->getCount($_SESSION['user_id'], $filters);
$total_pages = ceil($total_invoices / $per_page);
$offset = ($page - 1) * $per_page;

$invoices = $invoice->getAllPaginated($_SESSION['user_id'], $filters, $offset, $per_page);

// Calculate totals
$totalAmount = 0;
$totalPaid = 0;
$totalPending = 0;
foreach ($invoices as $inv) {
    $totalAmount += $inv['total_amount'];
    if ($inv['status'] === 'paid') {
        $totalPaid += $inv['total_amount'];
    } elseif ($inv['status'] === 'sent') {
        $totalPending += $inv['total_amount'];
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
        .status-badge {
            width: 80px;
            text-align: center;
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
        .overdue {
            color: #dc3545;
            font-weight: 500;
        }
        .summary-card {
            border-left: 4px solid;
        }
        .summary-card.total {
            border-left-color: #0d6efd;
        }
        .summary-card.paid {
            border-left-color: #198754;
        }
        .summary-card.pending {
            border-left-color: #ffc107;
        }
    </style>
</head>
<body>
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Header with Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-file-invoice-dollar me-2"></i><?php echo $pageTitle; ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> New Invoice
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm summary-card total">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Amount</h6>
                        <h3 class="mb-0"><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($totalAmount, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm summary-card paid">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Paid</h6>
                        <h3 class="mb-0"><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($totalPaid, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm summary-card pending">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Pending</h6>
                        <h3 class="mb-0"><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($totalPending, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Replace filters with search -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search invoices by number, patient name, or amount..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Invoices List -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($invoices)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No invoices found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($inv['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($inv['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?> <?php echo number_format($inv['total_amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = match($inv['status']) {
                                                'paid' => 'success',
                                                'sent' => 'warning',
                                                'draft' => 'secondary',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                                <?php echo ucfirst($inv['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($inv['due_date']) {
                                                $dueDate = strtotime($inv['due_date']);
                                                $isOverdue = $dueDate < time() && $inv['status'] !== 'paid';
                                                echo '<span class="' . ($isOverdue ? 'overdue' : '') . '">';
                                                echo date('M d, Y', $dueDate);
                                                echo '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewInvoice(<?php echo $inv['id']; ?>)"
                                                        title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        onclick="editInvoice(<?php echo $inv['id']; ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="sendEmail(<?php echo $inv['id']; ?>)"
                                                        title="Email">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteInvoice(<?php echo $inv['id']; ?>)"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Add pagination -->
                    <nav aria-label="Invoice pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    Previous
                                </a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
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

    <div class="modal fade" id="newInvoiceModal" tabindex="-1" aria-labelledby="newInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newInvoiceModalLabel">Create New Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Would you like to:</p>
                    <div class="d-grid gap-2">
                        <a href="create.php" class="btn btn-primary">
                            <i class="fas fa-file me-1"></i> Create Blank Invoice
                        </a>
                        <a href="create.php?from_appointment=1" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-plus me-1"></i> Create from Appointment
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewInvoice(id) {
        window.location.href = `view.php?id=${id}`;
    }

    function editInvoice(id) {
        window.location.href = `edit.php?id=${id}`;
    }

    function deleteInvoice(id) {
        if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = id;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function sendEmail(id) {
        if (confirm('Send invoice to the patient?')) {
            // Get elements
            const emailButton = document.querySelector(`button[onclick="sendEmail(${id})"]`);
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
                toast.classList.remove('bg-info');
                toast.classList.add('bg-danger');
                toastMessage.textContent = 'Error: ' + error.message;
                emailSpinner.style.display = 'none';
                toastInstance.show();
            })
            .finally(() => {
                emailButton.disabled = false;
                emailButton.innerHTML = '<i class="fas fa-envelope me-1"></i>';
                
                setTimeout(() => {
                    toastInstance.hide();
                }, 5000);
            });
        }
    }
    </script>
</body>
</html> 