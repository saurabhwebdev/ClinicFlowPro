<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Appointment.php';
require_once '../patients/models/Patient.php';
require_once '../clinic_settings/models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Appointments";
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
$appointment = new Appointment();
$settings = new Settings();
$patient = new Patient();
$patients = $patient->getAll($_SESSION['user_id']);

// Get clinic settings
$clinicSettings = $settings->getSettings($_SESSION['user_id']);

// Set timezone if available
if (!empty($clinicSettings['time_zone'])) {
    date_default_timezone_set($clinicSettings['time_zone']);
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get appointments with pagination
$filters = [];
if (!empty($search)) {
    $filters['search'] = $search;
}

$total_appointments = $appointment->getCount($_SESSION['user_id'], $filters);
$total_pages = ceil($total_appointments / $per_page);
$offset = ($page - 1) * $per_page;

$appointments = $appointment->getAllPaginated($_SESSION['user_id'], $filters, $offset, $per_page);

// Initialize Patient model and get all patients
$patient = new Patient();
$patients = $patient->getAll($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        .table td {
            vertical-align: middle;
        }
        .btn-group {
            display: flex;
            gap: 5px;
        }
        .appointment-date {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-calendar-alt me-2"></i><?php echo $pageTitle; ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal">
                    <i class="fas fa-plus me-1"></i> New Appointment
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
                                   placeholder="Search appointments by patient name, title..." 
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

        <!-- Appointments List -->
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No appointments found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Title</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $apt): ?>
                                    <tr>
                                        <td class="appointment-date">
                                            <div><?php echo date('M d, Y', strtotime($apt['date'])); ?></div>
                                            <div class="text-muted"><?php echo date('h:i A', strtotime($apt['time'])); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['title']); ?></td>
                                        <td><?php echo $apt['duration']; ?> mins</td>
                                        <td>
                                            <span class="badge bg-<?php echo match($apt['status']) {
                                                'completed' => 'success',
                                                'scheduled' => 'primary',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            }; ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        onclick="editAppointment(<?php echo $apt['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="sendAppointmentEmail(<?php echo $apt['id']; ?>)">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteAppointment(<?php echo $apt['id']; ?>)">
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
                    <nav aria-label="Appointment pagination" class="mt-4">
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

    </div>

    <!-- Appointment Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="appointmentForm" method="POST">
                        <div class="mb-3">
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
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time</label>
                            <input type="time" class="form-control" name="time" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" name="duration" value="30" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="appointmentForm" class="btn btn-primary">Save Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'scheduled': return 'primary';
            case 'completed': return 'success';
            case 'cancelled': return 'danger';
            case 'no_show': return 'warning';
            default: return 'secondary';
        }
    }

    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const isEdit = formData.get('id') ? true : false;
        
        fetch(isEdit ? 'edit.php' : 'create.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to save appointment');
            }
        })
        .catch(error => {
            alert('An error occurred. Please try again.');
            console.error('Error:', error);
        });
    });

    function editAppointment(id) {
        // Fetch appointment details
        fetch(`get_appointment.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const appointment = data.data;
                    const form = document.getElementById('appointmentForm');
                    
                    // Update modal title
                    document.querySelector('#appointmentModal .modal-title').textContent = 'Edit Appointment';
                    
                    // Populate form fields
                    form.querySelector('select[name="patient_id"]').value = appointment.patient_id;
                    form.querySelector('input[name="title"]').value = appointment.title;
                    form.querySelector('input[name="date"]').value = appointment.date;
                    form.querySelector('input[name="time"]').value = appointment.time;
                    form.querySelector('input[name="duration"]').value = appointment.duration;
                    form.querySelector('textarea[name="notes"]').value = appointment.notes || '';
                    
                    // Add appointment ID to form
                    let idInput = form.querySelector('input[name="id"]');
                    if (!idInput) {
                        idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id';
                        form.appendChild(idInput);
                    }
                    idInput.value = id;
                    
                    // Update form action
                    form.action = 'edit.php';
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
                    modal.show();
                } else {
                    alert(data.error || 'Failed to fetch appointment details');
                }
            })
            .catch(error => {
                alert('An error occurred while fetching appointment details');
                console.error('Error:', error);
            });
    }

    function deleteAppointment(id) {
        if (confirm('Are you sure you want to delete this appointment?')) {
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('delete.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        }
    }

    // Get clinic settings from PHP
    const clinicSettings = <?php echo json_encode($clinicSettings ?? []); ?>;

    // Initialize date picker with working days and holidays
    flatpickr('input[type="date"]', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disable: [
            function(date) {
                try {
                    // Parse working days from clinic settings
                    let workingDays = [1, 2, 3, 4, 5]; // Default to Mon-Fri
                    if (clinicSettings.working_days) {
                        workingDays = JSON.parse(clinicSettings.working_days);
                    }

                    // Get the day of week (1-7, Monday-Sunday)
                    let dayOfWeek = date.getDay();
                    dayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek; // Convert Sunday from 0 to 7

                    // Check if it's not a working day
                    if (!workingDays.includes(dayOfWeek)) {
                        return true; // Disable this date
                    }

                    // Check if it's a holiday
                    if (clinicSettings.holidays) {
                        const holidays = JSON.parse(clinicSettings.holidays);
                        const dateString = date.toISOString().split('T')[0];
                        if (dateString in holidays) {
                            return true; // Disable this date
                        }
                    }

                    return false; // Enable this date
                } catch (error) {
                    console.error('Error in date validation:', error);
                    return false; // Enable date if there's an error
                }
            }
        ],
        locale: {
            firstDayOfWeek: 1 // Start week on Monday
        },
        onChange: function(selectedDates, dateStr) {
            console.log('Selected date:', dateStr);
        }
    });

    // Initialize time picker with clinic hours
    flatpickr('input[type="time"]', {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        minTime: clinicSettings.working_hours_start || "09:00",
        maxTime: clinicSettings.working_hours_end || "17:00",
        time_24hr: true
    });

    // Add debug output
    console.log('Clinic Settings:', clinicSettings);
    console.log('Working Days:', clinicSettings.working_days ? JSON.parse(clinicSettings.working_days) : 'Default Mon-Fri');
    console.log('Holidays:', clinicSettings.holidays ? JSON.parse(clinicSettings.holidays) : 'No holidays');

    function updateStatus(id, status) {
        if (confirm(`Are you sure you want to mark this appointment as ${status}?`)) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            
            fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        }
    }

    // Add this to reset form when modal is closed
    document.getElementById('appointmentModal').addEventListener('hidden.bs.modal', function () {
        const form = document.getElementById('appointmentForm');
        form.reset();
        form.action = 'create.php';
        document.querySelector('#appointmentModal .modal-title').textContent = 'New Appointment';
        
        // Remove ID input if it exists
        const idInput = form.querySelector('input[name="id"]');
        if (idInput) {
            idInput.remove();
        }
    });

    function sendAppointmentEmail(id) {
        if (confirm('Send appointment details to the patient?')) {
            // Get elements
            const emailButton = document.querySelector(`button[onclick="sendAppointmentEmail(${id})"]`);
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

    // Add these helper functions
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert alert at the top of the container
        const container = document.querySelector('.container');
        container.insertBefore(alertDiv, container.firstChild);
        
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