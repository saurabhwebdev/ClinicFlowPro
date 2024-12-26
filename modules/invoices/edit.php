<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Invoice.php';
require_once '../patients/models/Patient.php';
require_once '../appointments/models/Appointment.php';
require_once '../clinic_settings/models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Edit Invoice";
$message = '';
$error = '';

// Get invoice ID
$invoiceId = $_GET['id'] ?? 0;

// Initialize models
$invoice = new Invoice();
$patient = new Patient();
$appointment = new Appointment();
$settings = new Settings();

// Get invoice data
$invoiceData = $invoice->getById($invoiceId, $_SESSION['user_id']);
if (!$invoiceData) {
    header('Location: index.php?error=' . urlencode('Invoice not found'));
    exit;
}

// Get all patients for dropdown
$patients = $patient->getAll($_SESSION['user_id']);

// Get clinic settings
$clinicSettings = $settings->getSettings($_SESSION['user_id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['patient_id'])) {
            throw new Exception('Patient is required');
        }

        if (empty($_POST['items'])) {
            throw new Exception('At least one item is required');
        }

        // Set invoice properties
        $invoice->id = $invoiceId;
        $invoice->user_id = $_SESSION['user_id'];
        $invoice->patient_id = $_POST['patient_id'];
        $invoice->appointment_id = !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
        $invoice->status = $_POST['status'] ?? 'draft';
        $invoice->due_date = $_POST['due_date'];
        $invoice->notes = $_POST['notes'];
        $invoice->items = json_encode($_POST['items']);

        // Calculate totals
        $totals = $invoice->calculateTotals();
        $invoice->subtotal = $totals['subtotal'];
        $invoice->tax_rate = $_POST['tax_rate'] ?? 0;
        $invoice->tax_amount = $totals['tax_amount'];
        $invoice->discount_type = $_POST['discount_type'] ?? 'fixed';
        $invoice->discount_value = $_POST['discount_value'] ?? 0;
        $invoice->discount_amount = $totals['discount_amount'];
        $invoice->total_amount = $totals['total_amount'];

        // Update invoice
        if ($invoice->update()) {
            header('Location: index.php?message=' . urlencode('Invoice updated successfully'));
            exit;
        } else {
            throw new Exception('Failed to update invoice');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

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
        .item-row {
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
            padding: 1rem;
        }
        .remove-item {
            color: #dc3545;
            cursor: pointer;
        }
        .remove-item:hover {
            color: #bb2d3b;
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4 mb-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-edit me-2"></i><?php echo $pageTitle; ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="view.php?id=<?php echo $invoiceId; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Invoice
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form id="invoiceForm" method="POST" class="needs-validation" novalidate>
                    <!-- Patient and Basic Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Patient <span class="text-danger">*</span></label>
                                <select class="form-select" name="patient_id" id="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php foreach ($patients as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" 
                                                <?php echo $p['id'] == $invoiceData['patient_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Related Appointment</label>
                                <select class="form-select" name="appointment_id" id="appointment_id">
                                    <option value="">Select Appointment</option>
                                    <!-- Appointments will be loaded dynamically -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="due_date" required 
                                       value="<?php echo $invoiceData['due_date']; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="draft" <?php echo $invoiceData['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="sent" <?php echo $invoiceData['status'] === 'sent' ? 'selected' : ''; ?>>Sent</option>
                                    <option value="paid" <?php echo $invoiceData['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="cancelled" <?php echo $invoiceData['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Items -->
                    <h5 class="mb-3">Invoice Items</h5>
                    <div id="itemsContainer">
                        <!-- Items will be added here dynamically -->
                    </div>
                    <button type="button" class="btn btn-outline-primary mb-4" onclick="addItem()">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>

                    <!-- Totals -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="4" 
                                          placeholder="Additional notes or payment instructions"><?php echo htmlspecialchars($invoiceData['notes']); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Tax Rate (%)</label>
                                        <input type="number" class="form-control" name="tax_rate" 
                                               value="<?php echo $invoiceData['tax_rate']; ?>" 
                                               min="0" max="100" step="0.01" onchange="calculateTotals()">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Discount</label>
                                        <div class="input-group">
                                            <select class="form-select" name="discount_type" style="width: 40%;" 
                                                    onchange="calculateTotals()">
                                                <option value="fixed" <?php echo $invoiceData['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                                                <option value="percentage" <?php echo $invoiceData['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                                            </select>
                                            <input type="number" class="form-control" name="discount_value" 
                                                   value="<?php echo $invoiceData['discount_value']; ?>" 
                                                   min="0" step="0.01" onchange="calculateTotals()">
                                        </div>
                                    </div>
                                    <div class="border-top pt-3 mt-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span id="subtotal"><?php echo number_format($invoiceData['subtotal'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Tax:</span>
                                            <span id="tax"><?php echo number_format($invoiceData['tax_amount'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Discount:</span>
                                            <span id="discount"><?php echo number_format($invoiceData['discount_amount'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total:</span>
                                            <span id="total"><?php echo number_format($invoiceData['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let itemCount = 0;
    const existingItems = <?php echo $invoiceData['items']; ?>;

    function addItem(item = null) {
        const container = document.getElementById('itemsContainer');
        const itemHtml = `
            <div class="item-row" id="item-${itemCount}">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="items[${itemCount}][description]" 
                               value="${item?.description || ''}" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="items[${itemCount}][quantity]" 
                               value="${item?.quantity || 1}" min="1" required onchange="calculateTotals()">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="items[${itemCount}][unit_price]" 
                               value="${item?.unit_price || ''}" min="0" step="0.01" required onchange="calculateTotals()">
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger" onclick="removeItem(${itemCount})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', itemHtml);
        itemCount++;
        calculateTotals();
    }

    function removeItem(index) {
        document.getElementById(`item-${index}`).remove();
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;
        const currency = '<?php echo htmlspecialchars($clinicSettings['currency'] ?? 'USD'); ?>';
        
        // Calculate subtotal
        document.querySelectorAll('.item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
            const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
            subtotal += quantity * unitPrice;
        });

        // Calculate tax
        const taxRate = parseFloat(document.querySelector('input[name="tax_rate"]').value);
        const taxAmount = subtotal * (taxRate / 100);

        // Calculate discount
        const discountType = document.querySelector('select[name="discount_type"]').value;
        const discountValue = parseFloat(document.querySelector('input[name="discount_value"]').value);
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = subtotal * (discountValue / 100);
        } else {
            discountAmount = discountValue;
        }

        // Calculate total
        const total = subtotal + taxAmount - discountAmount;

        // Update display with currency
        document.getElementById('subtotal').textContent = `${currency} ${subtotal.toFixed(2)}`;
        document.getElementById('tax').textContent = `${currency} ${isNaN(taxAmount) ? '0.00' : taxAmount.toFixed(2)}`;
        document.getElementById('discount').textContent = `${currency} ${isNaN(discountAmount) ? '0.00' : discountAmount.toFixed(2)}`;
        document.getElementById('total').textContent = `${currency} ${total.toFixed(2)}`;
    }

    // Load appointments when patient is selected
    document.getElementById('patient_id').addEventListener('change', function() {
        loadAppointments(this.value);
    });

    function loadAppointments(patientId) {
        const appointmentSelect = document.getElementById('appointment_id');
        
        if (!patientId) {
            appointmentSelect.innerHTML = '<option value="">Select Appointment</option>';
            return;
        }

        fetch(`get_appointments.php?patient_id=${patientId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Select Appointment</option>';
                data.forEach(apt => {
                    const selected = apt.id == <?php echo json_encode($invoiceData['appointment_id']); ?> ? 'selected' : '';
                    options += `<option value="${apt.id}" ${selected}>${apt.date} ${apt.time} - ${apt.service}</option>`;
                });
                appointmentSelect.innerHTML = options;
            })
            .catch(error => console.error('Error loading appointments:', error));
    }

    // Form validation
    document.getElementById('invoiceForm').addEventListener('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        this.classList.add('was-validated');
    });

    // Load existing items on page load
    window.addEventListener('DOMContentLoaded', function() {
        // Load existing items
        existingItems.forEach(item => addItem(item));
        
        // Load appointments for the selected patient
        const patientId = document.getElementById('patient_id').value;
        if (patientId) {
            loadAppointments(patientId);
        }
        
        // Calculate initial totals
        calculateTotals();
    });
    </script>
</body>
</html> 