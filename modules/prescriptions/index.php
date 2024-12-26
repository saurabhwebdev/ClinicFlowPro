<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Prescription.php';
require_once '../patients/models/Patient.php';
require_once '../appointments/models/Appointment.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Prescriptions";
$message = '';
$error = '';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Initialize models
$prescription = new Prescription();

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get prescriptions with pagination
$filters = [];
if (!empty($search)) {
    $filters['search'] = $search;
}

$total_prescriptions = $prescription->getCount($_SESSION['user_id'], $filters);
$total_pages = ceil($total_prescriptions / $per_page);
$offset = ($page - 1) * $per_page;

$prescriptions = $prescription->getAllPaginated($_SESSION['user_id'], $filters, $offset, $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-prescription me-2"></i><?php echo $pageTitle; ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#prescriptionModal">
                    <i class="fas fa-plus me-1"></i> New Prescription
                </button>
            </div>
        </div>

        <!-- Search -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search prescriptions by patient name, diagnosis..." 
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

        <!-- Prescriptions List -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($prescriptions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-prescription fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No prescriptions found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Diagnosis</th>
                                    <th>Medications</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prescriptions as $p): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($p['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($p['diagnosis'], 0, 50)) . (strlen($p['diagnosis']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <?php 
                                            $medications = json_decode($p['medications'], true);
                                            echo count($medications) . ' medication(s)';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view.php?id=<?php echo $p['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        onclick="editPrescription(<?php echo $p['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="sendEmail(<?php echo $p['id']; ?>)">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deletePrescription(<?php echo $p['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Prescription pagination" class="mt-4">
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

    <!-- Prescription Modal -->
    <div class="modal fade" id="prescriptionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="prescriptionForm" action="create.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient</label>
                                <select class="form-select" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php foreach ($patients as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Related Appointment</label>
                                <select class="form-select" name="appointment_id">
                                    <option value="">Select Appointment</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <textarea class="form-control" name="diagnosis" rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Medications</label>
                            <div id="medicationsList">
                                <!-- Medication items will be added here -->
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addMedication()">
                                <i class="fas fa-plus me-1"></i> Add Medication
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Instructions</label>
                            <textarea class="form-control" name="instructions" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="prescriptionForm" class="btn btn-primary">Save Prescription</button>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    // Initialize date picker
    flatpickr('input[type="date"]', {
        dateFormat: "Y-m-d"
    });

    // Medication counter
    let medicationCount = 0;

    function addMedication() {
        const medicationHtml = `
            <div class="card mb-2 medication-item">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" 
                                   name="medications[${medicationCount}][name]" 
                                   placeholder="Medication name" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" 
                                   name="medications[${medicationCount}][dosage]" 
                                   placeholder="Dosage" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" 
                                   name="medications[${medicationCount}][frequency]" 
                                   placeholder="Frequency" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" 
                                   name="medications[${medicationCount}][duration]" 
                                   placeholder="Duration" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    onclick="this.closest('.medication-item').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('medicationsList').insertAdjacentHTML('beforeend', medicationHtml);
        medicationCount++;
    }

    // Add first medication field by default
    addMedication();

    // Update appointments dropdown when patient is selected
    document.querySelector('select[name="patient_id"]').addEventListener('change', function() {
        const patientId = this.value;
        if (patientId) {
            fetch(`get_appointments.php?patient_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    const appointmentSelect = document.querySelector('select[name="appointment_id"]');
                    appointmentSelect.innerHTML = '<option value="">Select Appointment</option>';
                    
                    data.forEach(appointment => {
                        appointmentSelect.innerHTML += `
                            <option value="${appointment.id}">
                                ${appointment.date} ${appointment.time} - ${appointment.title}
                            </option>
                        `;
                    });
                });
        }
    });

    function viewPrescription(id) {
        window.location.href = `view.php?id=${id}`;
    }

    function editPrescription(id) {
        fetch(`get_prescription.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                const form = document.getElementById('prescriptionForm');
                form.action = 'edit.php';
                
                // Add ID field
                form.insertAdjacentHTML('afterbegin', `
                    <input type="hidden" name="id" value="${id}">
                `);
                
                // Set form values
                form.querySelector('select[name="patient_id"]').value = data.patient_id;
                form.querySelector('select[name="appointment_id"]').value = data.appointment_id || '';
                form.querySelector('textarea[name="diagnosis"]').value = data.diagnosis;
                form.querySelector('textarea[name="instructions"]').value = data.instructions;
                form.querySelector('textarea[name="notes"]').value = data.notes;
                
                // Clear existing medications
                document.getElementById('medicationsList').innerHTML = '';
                
                // Add medications
                const medications = JSON.parse(data.medications || '[]');
                medications.forEach(med => {
                    medicationCount++;
                    const medicationHtml = `
                        <div class="card mb-2 medication-item">
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" 
                                               name="medications[${medicationCount}][name]" 
                                               value="${med.name}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" 
                                               name="medications[${medicationCount}][dosage]" 
                                               value="${med.dosage}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" 
                                               name="medications[${medicationCount}][frequency]" 
                                               value="${med.frequency}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" 
                                               name="medications[${medicationCount}][duration]" 
                                               value="${med.duration}" required>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="this.closest('.medication-item').remove()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('medicationsList').insertAdjacentHTML('beforeend', medicationHtml);
                });
                
                // Update modal title
                document.querySelector('#prescriptionModal .modal-title').textContent = 'Edit Prescription';
                
                // Show modal
                new bootstrap.Modal(document.getElementById('prescriptionModal')).show();
            });
    }

    function deletePrescription(id) {
        if (confirm('Are you sure you want to delete this prescription? This action cannot be undone.')) {
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
        if (confirm('Send prescription details to the patient?')) {
            // Get elements
            const emailButton = document.querySelector(`button[onclick="sendEmail(${id})"]`);
            const toast = document.getElementById('emailToast');
            const toastMessage = document.getElementById('toastMessage');
            const emailSpinner = document.getElementById('emailSpinner');
            
            // Create toast instance
            const toastInstance = new bootstrap.Toast(toast);
            
            // Disable button and show loading state
            emailButton.disabled = true;
            emailButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
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
                emailButton.innerHTML = '<i class="fas fa-envelope"></i>';
                
                // Hide toast after 5 seconds
                setTimeout(() => {
                    toastInstance.hide();
                }, 5000);
            });
        }
    }

    // Update the form submission in the JavaScript section
    document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Submit the form
        this.submit();
    });
    </script>
</body>
</html> 