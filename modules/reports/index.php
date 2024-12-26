<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Report.php';
require_once '../clinic_settings/models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Reports & Analytics";

// Initialize models
$report = new Report();
$settings = new Settings();

// Get clinic settings with fallback
$clinicSettings = $settings->getSettings($_SESSION['user_id']);
if (!$clinicSettings) {
    $clinicSettings = ['currency' => '$']; // Default currency if settings not found
}

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get metrics
$patientMetrics = $report->getPatientMetrics($_SESSION['user_id'], $start_date, $end_date);
$appointmentMetrics = $report->getAppointmentMetrics($_SESSION['user_id'], $start_date, $end_date);
$financialMetrics = $report->getFinancialMetrics($_SESSION['user_id'], $start_date, $end_date);
$prescriptionMetrics = $report->getPrescriptionMetrics($_SESSION['user_id'], $start_date, $end_date);
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
        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-value {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        .stats-label {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-chart-bar text-primary me-2"></i>Reports & Analytics</h2>
                <p class="text-muted">Analytics for the period <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>
            <form class="d-flex gap-2">
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i>
                </button>
            </form>
        </div>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <!-- Patient Stats -->
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-users text-primary me-2"></i>
                        <span class="stats-label">Total Patients</span>
                    </div>
                    <p class="stats-value">
                        <?php echo $patientMetrics ? $patientMetrics['total_patients'] : '0'; ?>
                    </p>
                    <?php if ($patientMetrics && isset($patientMetrics['new_patients']) && $patientMetrics['new_patients'] > 0): ?>
                        <small class="text-success">
                            <i class="fas fa-arrow-up"></i> <?php echo $patientMetrics['new_patients']; ?> new
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Appointment Stats -->
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-calendar-check text-success me-2"></i>
                        <span class="stats-label">Appointments</span>
                    </div>
                    <p class="stats-value">
                        <?php echo $appointmentMetrics && isset($appointmentMetrics['total_appointments']) ? $appointmentMetrics['total_appointments'] : '0'; ?>
                    </p>
                    <small class="text-muted">
                        <?php echo $appointmentMetrics && isset($appointmentMetrics['upcoming_appointments']) ? $appointmentMetrics['upcoming_appointments'] : '0'; ?> upcoming
                    </small>
                </div>
            </div>

            <!-- Financial Stats -->
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-file-invoice-dollar text-info me-2"></i>
                        <span class="stats-label">Revenue</span>
                    </div>
                    <p class="stats-value">
                        <?php 
                        $currency = $clinicSettings && isset($clinicSettings['currency']) ? $clinicSettings['currency'] : '$';
                        if ($financialMetrics && isset($financialMetrics['total_revenue'])) {
                            echo $currency . number_format($financialMetrics['total_revenue'], 2);
                        } else {
                            echo $currency . '0.00';
                        }
                        ?>
                    </p>
                    <small class="text-muted">
                        <?php 
                        if ($financialMetrics && isset($financialMetrics['paid_invoices'])) {
                            echo $financialMetrics['paid_invoices'] . ' paid invoices';
                        } else {
                            echo '0 paid invoices';
                        }
                        ?>
                    </small>
                </div>
            </div>

            <!-- Prescription Stats -->
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-prescription text-warning me-2"></i>
                        <span class="stats-label">Prescriptions</span>
                    </div>
                    <p class="stats-value">
                        <?php echo $prescriptionMetrics && isset($prescriptionMetrics['total_prescriptions']) ? $prescriptionMetrics['total_prescriptions'] : '0'; ?>
                    </p>
                    <small class="text-muted">
                        <?php echo $prescriptionMetrics && isset($prescriptionMetrics['unique_patients']) ? $prescriptionMetrics['unique_patients'] : '0'; ?> patients
                    </small>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-4">Patient Demographics</h5>
                    <canvas id="patientChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="mb-4">Appointment Status</h5>
                    <canvas id="appointmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Patient Demographics Chart
        new Chart(document.getElementById('patientChart'), {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [
                        <?php echo $patientMetrics && isset($patientMetrics['male_patients']) ? $patientMetrics['male_patients'] : 0; ?>,
                        <?php echo $patientMetrics && isset($patientMetrics['female_patients']) ? $patientMetrics['female_patients'] : 0; ?>
                    ],
                    backgroundColor: ['#36a2eb', '#ff6384']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Appointment Status Chart
        new Chart(document.getElementById('appointmentChart'), {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Upcoming', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $appointmentMetrics && isset($appointmentMetrics['completed_appointments']) ? $appointmentMetrics['completed_appointments'] : 0; ?>,
                        <?php echo $appointmentMetrics && isset($appointmentMetrics['upcoming_appointments']) ? $appointmentMetrics['upcoming_appointments'] : 0; ?>,
                        <?php echo $appointmentMetrics && isset($appointmentMetrics['cancelled_appointments']) ? $appointmentMetrics['cancelled_appointments'] : 0; ?>
                    ],
                    backgroundColor: ['#4bc0c0', '#36a2eb', '#ff6384']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 