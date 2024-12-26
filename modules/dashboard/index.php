<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once '../patients/models/Patient.php';
require_once '../appointments/models/Appointment.php';
require_once '../invoices/models/Invoice.php';
require_once '../prescriptions/models/Prescription.php';
require_once '../clinic_settings/models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

// Initialize models
$patient = new Patient();
$appointment = new Appointment();
$invoice = new Invoice();
$prescription = new Prescription();
$settings = new Settings();
$clinicSettings = $settings->getSettings($_SESSION['user_id']);

// Get currency symbol, default to '$' if not set
$currencySymbol = !empty($clinicSettings['currency']) ? $clinicSettings['currency'] : '$';

// Get current date and date ranges
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear = date('Y');
$lastMonth = date('Y-m', strtotime('-1 month'));
$nextWeek = date('Y-m-d', strtotime('+7 days'));

// Fetch insights
try {
    // Patient Insights
    $totalPatients = count($patient->getAll($_SESSION['user_id']));
    $recentPatients = array_slice($patient->getAll($_SESSION['user_id']), 0, 5); // Last 5 patients

    // Appointment Insights
    $todayAppointments = $appointment->getAll($_SESSION['user_id'], ['date' => $today]);
    $upcomingAppointments = $appointment->getAll($_SESSION['user_id'], [
        'date_range' => [
            'start' => date('Y-m-d'),
            'end' => $nextWeek
        ]
    ]);
    $appointmentsByStatus = [
        'scheduled' => count($appointment->getAll($_SESSION['user_id'], ['status' => 'scheduled'])),
        'completed' => count($appointment->getAll($_SESSION['user_id'], ['status' => 'completed'])),
        'cancelled' => count($appointment->getAll($_SESSION['user_id'], ['status' => 'cancelled']))
    ];

    // Invoice Insights
    $thisMonthInvoices = $invoice->getAll($_SESSION['user_id'], [
        'date_range' => [
            'start' => date('Y-m-01'),
            'end' => date('Y-m-t')
        ]
    ]);
    
    $invoiceStats = [
        'total_amount' => 0,
        'paid' => 0,
        'pending' => 0,
        'overdue' => 0
    ];

    foreach ($thisMonthInvoices as $inv) {
        $invoiceStats['total_amount'] += $inv['total_amount'];
        if ($inv['status'] === 'paid') {
            $invoiceStats['paid']++;
        } elseif ($inv['status'] === 'sent' && strtotime($inv['due_date']) < time()) {
            $invoiceStats['overdue']++;
        } else {
            $invoiceStats['pending']++;
        }
    }

    // Prescription Insights
    $recentPrescriptions = array_slice(
        $prescription->getAll($_SESSION['user_id']), 
        0, 
        5
    ); // Last 5 prescriptions

    // Calculate percentage changes
    $lastMonthInvoices = $invoice->getAll($_SESSION['user_id'], [
        'date_range' => [
            'start' => date('Y-m-01', strtotime('-1 month')),
            'end' => date('Y-m-t', strtotime('-1 month'))
        ]
    ]);

    $lastMonthTotal = 0;
    foreach ($lastMonthInvoices as $inv) {
        $lastMonthTotal += $inv['total_amount'];
    }

    $revenueChange = $lastMonthTotal > 0 
        ? (($invoiceStats['total_amount'] - $lastMonthTotal) / $lastMonthTotal) * 100 
        : 100;

    // Additional Patient Insights
    $newPatientsThisMonth = count($patient->getAll($_SESSION['user_id'], [
        'date_range' => [
            'start' => date('Y-m-01'),
            'end' => date('Y-m-t')
        ]
    ]));

    // Additional Appointment Analytics
    $appointmentCompletionRate = $appointmentsByStatus['completed'] > 0 
        ? ($appointmentsByStatus['completed'] / array_sum($appointmentsByStatus)) * 100 
        : 0;

    $cancellationRate = $appointmentsByStatus['cancelled'] > 0 
        ? ($appointmentsByStatus['cancelled'] / array_sum($appointmentsByStatus)) * 100 
        : 0;

    // Additional Invoice Analytics
    $totalRevenue = 0;
    $totalPending = 0;
    $averageInvoiceValue = 0;
    $collectionRate = 0;

    foreach ($thisMonthInvoices as $inv) {
        $totalRevenue += ($inv['status'] === 'paid') ? $inv['total_amount'] : 0;
        $totalPending += ($inv['status'] !== 'paid') ? $inv['total_amount'] : 0;
    }

    if (count($thisMonthInvoices) > 0) {
        $averageInvoiceValue = $invoiceStats['total_amount'] / count($thisMonthInvoices);
        $collectionRate = ($totalRevenue / ($totalRevenue + $totalPending)) * 100;
    }

    // Prescription Analytics
    $prescriptionCount = count($prescription->getAll($_SESSION['user_id'], [
        'date_range' => [
            'start' => date('Y-m-01'),
            'end' => date('Y-m-t')
        ]
    ]));

    $prescriptionsPerPatient = $totalPatients > 0 
        ? $prescriptionCount / $totalPatients 
        : 0;

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = "Error loading dashboard data";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Dashboard</h2>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <!-- Appointments Today -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Today's Appointments</h6>
                        <h3><?php echo count($todayAppointments); ?></h3>
                        <p class="mb-0">
                            <span class="text-<?php echo count($todayAppointments) > 0 ? 'success' : 'warning'; ?>">
                                <i class="fas fa-calendar-day"></i>
                                <?php echo count($todayAppointments) > 0 ? 'Appointments scheduled' : 'No appointments'; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Monthly Revenue</h6>
                        <h3><?php echo $currencySymbol . number_format($invoiceStats['total_amount'], 2); ?></h3>
                        <p class="mb-0">
                            <span class="text-<?php echo $revenueChange >= 0 ? 'success' : 'danger'; ?>">
                                <i class="fas fa-<?php echo $revenueChange >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo abs(round($revenueChange, 1)); ?>% from last month
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Total Patients -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Total Patients</h6>
                        <h3><?php echo $totalPatients; ?></h3>
                        <p class="mb-0">
                            <span class="text-info">
                                <i class="fas fa-users"></i>
                                Active patients
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pending Invoices -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Pending Invoices</h6>
                        <h3><?php echo $invoiceStats['pending']; ?></h3>
                        <p class="mb-0">
                            <span class="text-<?php echo $invoiceStats['overdue'] > 0 ? 'danger' : 'warning'; ?>">
                                <i class="fas fa-clock"></i>
                                <?php echo $invoiceStats['overdue']; ?> overdue
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Financial Metrics -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Financial Insights</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Average Invoice</span>
                            <span class="badge bg-success"><?php echo $currencySymbol . number_format($averageInvoiceValue, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Collection Rate</span>
                            <span class="badge bg-<?php echo $collectionRate > 70 ? 'success' : 'warning'; ?>">
                                <?php echo number_format($collectionRate, 1); ?>%
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Pending</span>
                            <span class="badge bg-warning"><?php echo $currencySymbol . number_format($totalPending, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient Metrics -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i>Patient Analytics</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">New This Month</span>
                            <span class="badge bg-info"><?php echo $newPatientsThisMonth; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Prescriptions/Patient</span>
                            <span class="badge bg-primary"><?php echo number_format($prescriptionsPerPatient, 1); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Active Treatments</span>
                            <span class="badge bg-success"><?php echo count($upcomingAppointments); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointment Analytics -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-calendar-check me-2"></i>Appointment Stats</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Completion Rate</span>
                            <span class="badge bg-<?php echo $appointmentCompletionRate > 80 ? 'success' : 'warning'; ?>">
                                <?php echo number_format($appointmentCompletionRate, 1); ?>%
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Cancellation Rate</span>
                            <span class="badge bg-<?php echo $cancellationRate < 20 ? 'success' : 'danger'; ?>">
                                <?php echo number_format($cancellationRate, 1); ?>%
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Upcoming</span>
                            <span class="badge bg-primary"><?php echo count($upcomingAppointments); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">Appointment Distribution</h6>
                    </div>
                    <div class="card-body" style="height: 200px">
                        <canvas id="appointmentChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">Invoice Status</h6>
                    </div>
                    <div class="card-body" style="height: 200px">
                        <canvas id="invoiceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">Monthly Revenue Trend</h6>
                    </div>
                    <div class="card-body" style="height: 200px">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Common chart options for better styling
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 11,
                            family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                        }
                    }
                }
            },
            cutout: '70%', // Makes doughnut charts look more modern
            borderWidth: 0,
            layout: {
                padding: 15
            }
        };

        // Appointment Distribution Chart
        new Chart(document.getElementById('appointmentChart'), {
            type: 'doughnut',
            data: {
                labels: ['Scheduled', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $appointmentsByStatus['scheduled']; ?>,
                        <?php echo $appointmentsByStatus['completed']; ?>,
                        <?php echo $appointmentsByStatus['cancelled']; ?>
                    ],
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.85)',
                        'rgba(25, 135, 84, 0.85)',
                        'rgba(220, 53, 69, 0.85)'
                    ],
                    hoverBackgroundColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(220, 53, 69, 1)'
                    ]
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 13
                        },
                        bodyFont: {
                            size: 12
                        }
                    }
                }
            }
        });

        // Invoice Status Chart
        new Chart(document.getElementById('invoiceChart'), {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending', 'Overdue'],
                datasets: [{
                    data: [
                        <?php echo $invoiceStats['paid']; ?>,
                        <?php echo $invoiceStats['pending']; ?>,
                        <?php echo $invoiceStats['overdue']; ?>
                    ],
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.85)',
                        'rgba(255, 193, 7, 0.85)',
                        'rgba(220, 53, 69, 0.85)'
                    ],
                    hoverBackgroundColor: [
                        'rgba(25, 135, 84, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ]
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                return `${label}: ${value} invoice${value !== 1 ? 's' : ''}`;
                            }
                        }
                    }
                }
            }
        });

        // Revenue Trend Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: ['Last Month', 'This Month'],
                datasets: [{
                    label: 'Revenue',
                    data: [
                        <?php echo $lastMonthTotal; ?>,
                        <?php echo $invoiceStats['total_amount']; ?>
                    ],
                    borderColor: 'rgba(13, 110, 253, 0.8)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return `Revenue: <?php echo $currencySymbol; ?>${context.parsed.y.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: value => '<?php echo $currencySymbol; ?>' + value.toLocaleString(),
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 