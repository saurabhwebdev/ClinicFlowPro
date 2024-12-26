<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Settings.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Clinic Settings";
$message = '';
$error = '';

// Initialize Settings model
$settings = new Settings();
$settings->user_id = $_SESSION['user_id'];

// Get existing settings
$settingsData = $settings->getSettings($_SESSION['user_id']) ?? [];

// Add this after other initial variables
$maxFileSize = 200 * 1024; // 200KB in bytes

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file uploads first
    $uploadDir = BASE_PATH . '/uploads/clinic/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        if ($_FILES['logo']['size'] > $maxFileSize) {
            $error = 'Logo file size must be less than 200KB';
        } else {
            $logoName = 'logo_' . time() . '_' . $_FILES['logo']['name'];
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName)) {
                $settings->logo_path = 'uploads/clinic/' . $logoName;
            }
        }
    } else {
        $settings->logo_path = $settingsData['logo_path'] ?? null;
    }

    // Handle signature upload
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === 0) {
        if ($_FILES['signature']['size'] > $maxFileSize) {
            $error = 'Signature file size must be less than 200KB';
        } else {
            $sigName = 'signature_' . time() . '_' . $_FILES['signature']['name'];
            if (move_uploaded_file($_FILES['signature']['tmp_name'], $uploadDir . $sigName)) {
                $settings->signature_path = 'uploads/clinic/' . $sigName;
            }
        }
    } else {
        $settings->signature_path = $settingsData['signature_path'] ?? null;
    }

    // Set other properties
    $settings->clinic_name = $_POST['clinic_name'] ?? '';
    $settings->email = $_POST['email'] ?? '';
    $settings->phone = $_POST['phone'] ?? '';
    $settings->website = $_POST['website'] ?? '';
    $settings->address = $_POST['address'] ?? '';
    $settings->country = $_POST['country'] ?? '';
    $settings->currency = $_POST['currency'] ?? '';
    if (isset($_POST['working_days']) && is_array($_POST['working_days'])) {
        $workingDays = array_map('intval', $_POST['working_days']);
        // Ensure we have at least one working day
        if (empty($workingDays)) {
            $workingDays = [1, 2, 3, 4, 5]; // Default to Mon-Fri
        }
        $settings->working_days = json_encode($workingDays);
    } else {
        $settings->working_days = json_encode([1, 2, 3, 4, 5]); // Default to Mon-Fri
    }
    $settings->working_hours_start = $_POST['working_hours_start'] ?? '';
    $settings->working_hours_end = $_POST['working_hours_end'] ?? '';
    $settings->default_appointment_duration = $_POST['default_appointment_duration'] ?? 30;
    $settings->doctor_name = $_POST['doctor_name'] ?? '';
    $settings->license_number = $_POST['license_number'] ?? '';
    $settings->degree = $_POST['degree'] ?? '';
    $settings->specialization = $_POST['specialization'] ?? '';
    $settings->awards_recognition = $_POST['awards_recognition'] ?? '';
    $settings->gst_number = $_POST['gst_number'] ?? '';
    $settings->tax_registration_number = $_POST['tax_registration_number'] ?? '';
    if (isset($_POST['holidays']) && is_array($_POST['holidays'])) {
        // Debug output
        error_log('Raw holidays data: ' . print_r($_POST['holidays'], true));
        
        $formattedHolidays = [];
        foreach ($_POST['holidays'] as $date => $description) {
            if (empty($date)) continue;
            
            // Create date object with the correct timezone
            $timezone = new DateTimeZone($settings->time_zone ?? 'UTC');
            $holidayDate = new DateTime($date, $timezone);
            // Adjust the time to start of day in the clinic's timezone
            $holidayDate->setTime(0, 0, 0);
            
            // Format date without timezone conversion
            $formattedDate = $holidayDate->format('Y-m-d');
            $formattedHolidays[$formattedDate] = $description;
            
            error_log("Processing holiday - Input: $date, Output: $formattedDate");
        }
        
        // Debug output
        error_log('Formatted holidays: ' . print_r($formattedHolidays, true));
        
        $settings->holidays = json_encode($formattedHolidays);
    } else {
        $settings->holidays = json_encode([]);
    }
    $settings->time_zone = $_POST['time_zone'] ?? '';
    $settings->appointment_reminder_before = $_POST['appointment_reminder_before'] ?? 24;
    $settings->cancellation_policy = $_POST['cancellation_policy'] ?? '';
    $settings->social_media_links = json_encode($_POST['social_media'] ?? []);
    $settings->payment_methods = json_encode($_POST['payment_methods'] ?? []);
    $settings->invoice_prefix = $_POST['invoice_prefix'] ?? '';
    $settings->invoice_footer_text = $_POST['invoice_footer_text'] ?? '';
    $settings->sms_notifications = isset($_POST['sms_notifications']);
    $settings->email_notifications = isset($_POST['email_notifications']);

    $result = $settings->saveSettings();
    if ($result['success']) {
        $message = 'Settings saved successfully.';
        $settingsData = $settings->getSettings($_SESSION['user_id']);
    } else {
        $error = $result['error'];
    }
}

// Decode JSON data for display
$workingDays = json_decode($settingsData['working_days'] ?? '[]', true) ?: [];
$holidays = json_decode($settingsData['holidays'] ?? '[]', true) ?: [];
$socialMedia = json_decode($settingsData['social_media_links'] ?? '[]', true) ?: [];
$paymentMethods = json_decode($settingsData['payment_methods'] ?? '[]', true) ?: [];

// Add debug output
error_log("Saving holidays: " . $settings->holidays);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Add Flatpickr for better date/time inputs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <style>
    .nav-tabs {
        border-bottom: 1px solid #dee2e6;
    }

    .nav-tabs .nav-link {
        color: #495057;
        border: 1px solid transparent;
        border-radius: 0.25rem 0.25rem 0 0;
        padding: 1rem 1.5rem;
        font-weight: 500;
        margin-bottom: -1px;
    }

    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
        background-color: #f8f9fa;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }

    .tab-content {
        padding: 2rem 0;
        background-color: #fff;
    }

    .preview-image {
        max-width: 200px;
        max-height: 200px;
        object-fit: contain;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 0.25rem;
    }

    /* Ensure text visibility */
    .form-label {
        color: #212529;
        font-weight: 500;
    }

    .form-control, .form-select {
        color: #212529;
    }

    .form-control:focus, .form-select:focus {
        color: #212529;
    }

    .text-muted {
        color: #6c757d !important;
    }

    /* Schedule tab specific styles */
    .working-days-container {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
    }

    .working-days-container .form-check {
        margin-bottom: 0.5rem;
    }

    .time-picker-container {
        border-left: 3px solid #0d6efd;
        padding-left: 1rem;
        margin: 1rem 0;
    }
    </style>
</head>
<body>
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="fas fa-cog me-2"></i><?php echo $pageTitle; ?></h2>
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

        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Clinic Information Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-hospital me-2"></i>Clinic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Logo Upload -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Clinic Logo</h6>
                                    <img src="<?php echo BASE_URL . '/' . ($settingsData['logo_path'] ?? 'assets/images/default-logo.png'); ?>" 
                                         class="preview-image mb-3" 
                                         id="logoPreview" 
                                         alt="Clinic Logo">
                                    <div class="input-group">
                                        <input type="file" class="form-control" name="logo" accept="image/*"
                                               onchange="validateFileSize(this, 'logoPreview')"
                                               data-max-size="<?php echo $maxFileSize; ?>">
                                        <small class="form-text text-muted d-block">Maximum file size: 200KB</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Clinic Name *</label>
                                    <input type="text" class="form-control" name="clinic_name" required
                                           value="<?php echo htmlspecialchars($settingsData['clinic_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email"
                                           value="<?php echo htmlspecialchars($settingsData['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone"
                                           value="<?php echo htmlspecialchars($settingsData['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" name="website"
                                           value="<?php echo htmlspecialchars($settingsData['website'] ?? ''); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($settingsData['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country</label>
                                    <select class="form-select" name="country">
                                        <option value="">Select Country</option>
                                        <?php
                                        $countries = ['USA', 'Canada', 'UK', 'Australia', 'India', 'Other'];
                                        foreach ($countries as $country) {
                                            $selected = ($settingsData['country'] ?? '') === $country ? 'selected' : '';
                                            echo "<option value=\"$country\" $selected>$country</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Currency</label>
                                    <select class="form-select" name="currency">
                                        <option value="">Select Currency</option>
                                        <?php
                                        $currencies = [
                                            'USD' => 'US Dollar ($)',
                                            'EUR' => 'Euro (€)',
                                            'GBP' => 'British Pound (£)',
                                            'INR' => 'Indian Rupee (₹)',
                                            'AUD' => 'Australian Dollar (A$)'
                                        ];
                                        foreach ($currencies as $code => $name) {
                                            $selected = ($settingsData['currency'] ?? '') === $code ? 'selected' : '';
                                            echo "<option value=\"$code\" $selected>$name</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Time Zone</label>
                                    <select class="form-select" name="time_zone">
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers();
                                        $currentTimezone = $settingsData['time_zone'] ?? date_default_timezone_get();
                                        foreach ($timezones as $timezone) {
                                            $selected = ($currentTimezone === $timezone) ? 'selected' : '';
                                            echo "<option value=\"$timezone\" $selected>$timezone</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctor Details Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-md me-2"></i>Doctor Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Signature Upload -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Doctor's Signature</h6>
                                    <img src="<?php echo BASE_URL . '/' . ($settingsData['signature_path'] ?? 'assets/images/default-signature.png'); ?>" 
                                         class="preview-image mb-3" 
                                         id="signaturePreview" 
                                         alt="Doctor's Signature">
                                    <div class="input-group">
                                        <input type="file" class="form-control" name="signature" accept="image/*"
                                               onchange="validateFileSize(this, 'signaturePreview')"
                                               data-max-size="<?php echo $maxFileSize; ?>">
                                        <small class="form-text text-muted d-block">Maximum file size: 200KB</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Information -->
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Doctor's Name *</label>
                                    <input type="text" class="form-control" name="doctor_name" required
                                           value="<?php echo htmlspecialchars($settingsData['doctor_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">License Number</label>
                                    <input type="text" class="form-control" name="license_number"
                                           value="<?php echo htmlspecialchars($settingsData['license_number'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Degree/Qualification</label>
                                    <input type="text" class="form-control" name="degree"
                                           value="<?php echo htmlspecialchars($settingsData['degree'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Specialization</label>
                                    <input type="text" class="form-control" name="specialization"
                                           value="<?php echo htmlspecialchars($settingsData['specialization'] ?? ''); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Awards & Recognition</label>
                                    <textarea class="form-control" name="awards_recognition" rows="3"
                                              placeholder="List your awards and recognition (one per line)"><?php echo htmlspecialchars($settingsData['awards_recognition'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar me-2"></i>Schedule & Working Hours
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Working Days -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Working Days</h6>
                            <div class="working-days-container">
                                <?php
                                $daysOfWeek = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday'
                                ];
                                foreach ($daysOfWeek as $dayNum => $dayName): 
                                    $checked = in_array($dayNum, $workingDays) ? 'checked' : '';
                                ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="working_days[]" value="<?php echo $dayNum; ?>" 
                                               id="day_<?php echo $dayNum; ?>" <?php echo $checked; ?>>
                                        <label class="form-check-label" for="day_<?php echo $dayNum; ?>">
                                            <?php echo $dayName; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Working Hours -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Working Hours</h6>
                            <div class="time-picker-container">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="working_hours_start"
                                               value="<?php echo htmlspecialchars($settingsData['working_hours_start'] ?? '09:00'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="working_hours_end"
                                               value="<?php echo htmlspecialchars($settingsData['working_hours_end'] ?? '17:00'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Duration -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Default Appointment Duration</h6>
                            <div class="mb-3">
                                <select class="form-select" name="default_appointment_duration">
                                    <?php
                                    $durations = [15, 30, 45, 60, 90, 120];
                                    foreach ($durations as $duration) {
                                        $selected = ($settingsData['default_appointment_duration'] ?? 30) == $duration ? 'selected' : '';
                                        echo "<option value='$duration' $selected>$duration minutes</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Holidays -->
                        <div class="col-md-12 mb-4">
                            <h6 class="mb-3">Holidays</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div id="holidayPicker"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Selected Holidays</h6>
                                            <div id="selectedHolidays">
                                                <!-- Existing holidays will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice me-2"></i>Business Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Tax Information -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Tax Information</h6>
                            <div class="mb-3">
                                <label class="form-label">GST Number</label>
                                <input type="text" class="form-control" name="gst_number"
                                       value="<?php echo htmlspecialchars($settingsData['gst_number'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tax Registration Number</label>
                                <input type="text" class="form-control" name="tax_registration_number"
                                       value="<?php echo htmlspecialchars($settingsData['tax_registration_number'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Invoice Settings -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Invoice Settings</h6>
                            <div class="mb-3">
                                <label class="form-label">Invoice Prefix</label>
                                <input type="text" class="form-control" name="invoice_prefix"
                                       value="<?php echo htmlspecialchars($settingsData['invoice_prefix'] ?? ''); ?>"
                                       placeholder="e.g., INV-">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Invoice Footer Text</label>
                                <textarea class="form-control" name="invoice_footer_text" rows="3"
                                        placeholder="Terms and conditions, thank you message, etc."><?php echo htmlspecialchars($settingsData['invoice_footer_text'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Payment Methods</h6>
                            <div class="mb-3">
                                <?php
                                $availablePaymentMethods = [
                                    'cash' => 'Cash',
                                    'card' => 'Credit/Debit Card',
                                    'upi' => 'UPI',
                                    'bank_transfer' => 'Bank Transfer',
                                    'insurance' => 'Insurance'
                                ];
                                foreach ($availablePaymentMethods as $value => $label) {
                                    $checked = in_array($value, $paymentMethods) ? 'checked' : '';
                                    echo "
                                    <div class='form-check'>
                                        <input class='form-check-input' type='checkbox' name='payment_methods[]' 
                                               value='$value' id='payment_$value' $checked>
                                        <label class='form-check-label' for='payment_$value'>$label</label>
                                    </div>";
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Social Media Links -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Social Media Links</h6>
                            <div class="mb-3">
                                <label class="form-label">Facebook</label>
                                <input type="url" class="form-control" name="social_media[facebook]"
                                       value="<?php echo htmlspecialchars($socialMedia['facebook'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instagram</label>
                                <input type="url" class="form-control" name="social_media[instagram]"
                                       value="<?php echo htmlspecialchars($socialMedia['instagram'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Twitter</label>
                                <input type="url" class="form-control" name="social_media[twitter]"
                                       value="<?php echo htmlspecialchars($socialMedia['twitter'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Notification Settings -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Notification Preferences</h6>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" name="email_notifications" id="email_notifications"
                                       <?php echo ($settingsData['email_notifications'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">Email Notifications</label>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" name="sms_notifications" id="sms_notifications"
                                       <?php echo ($settingsData['sms_notifications'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_notifications">SMS Notifications</label>
                            </div>
                        </div>

                        <!-- Reminder Settings -->
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3">Reminder Settings</h6>
                            <div class="mb-3">
                                <label class="form-label">Send Appointment Reminder Before</label>
                                <select class="form-select" name="appointment_reminder_before">
                                    <?php
                                    $reminderHours = [12, 24, 48, 72];
                                    foreach ($reminderHours as $hours) {
                                        $selected = ($settingsData['appointment_reminder_before'] ?? 24) == $hours ? 'selected' : '';
                                        echo "<option value='$hours' $selected>$hours hours</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Cancellation Policy -->
                        <div class="col-12">
                            <h6 class="mb-3">Cancellation Policy</h6>
                            <div class="mb-3">
                                <textarea class="form-control" name="cancellation_policy" rows="4"
                                        placeholder="Enter your cancellation policy"><?php echo htmlspecialchars($settingsData['cancellation_policy'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save All Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    // Add this function for file size validation
    function validateFileSize(input, previewId) {
        const maxSize = parseInt(input.getAttribute('data-max-size'));
        const file = input.files[0];
        
        if (file) {
            if (file.size > maxSize) {
                alert('File size must be less than 200KB');
                input.value = ''; // Clear the input
                return false;
            }
            
            // If size is okay, preview the image
            previewImage(input, previewId);
        }
        return true;
    }

    // Update the existing previewImage function
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Initialize date/time pickers
    flatpickr("input[type=time]", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    // Initialize holiday picker with calendar view
    flatpickr("#holidayPicker", {
        mode: "multiple",
        dateFormat: "Y-m-d",
        inline: true,
        minDate: "today",
        showMonths: 2,
        theme: "material_blue",
        onChange: function(selectedDates, dateStr) {
            if (selectedDates.length > 0) {
                const lastDate = selectedDates[selectedDates.length - 1];
                // Use local date string to prevent timezone issues
                const localDate = new Date(lastDate.getTime() - (lastDate.getTimezoneOffset() * 60000));
                const formattedDateStr = localDate.toISOString().split('T')[0];
                addHoliday(lastDate, formattedDateStr, false);
                this.clear();
            }
        }
    });

    function addHoliday(date, dateStr, isInitialLoad = true) {
        const container = document.getElementById('selectedHolidays');
        
        // Create date in local timezone
        const localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
        const formattedDateStr = localDate.toISOString().split('T')[0];
        
        const formattedDate = localDate.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            timeZone: 'UTC'  // Use UTC to prevent additional timezone shifts
        });
        
        // Check if date already exists and show alert only for new additions
        if (document.querySelector(`input[name="holidays[${formattedDateStr}]"]`)) {
            if (!isInitialLoad) { // Only show alert for new additions
                alert('This date is already added as a holiday');
            }
            return;
        }
        
        const holidayHtml = `
            <div class="holiday-item mb-2">
                <div class="d-flex align-items-center border rounded p-2">
                    <input type="hidden" name="holidays[${formattedDateStr}]" value="">
                    <span class="me-2 text-primary">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                    <span class="me-2">${formattedDate}</span>
                    <input type="text" class="form-control form-control-sm me-2" 
                           placeholder="Holiday description"
                           onchange="updateHolidayDescription(this, '${formattedDateStr}')">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="removeHoliday(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', holidayHtml);
    }
        
    function updateHolidayDescription(input, dateStr) {
        const hiddenInput = input.closest('.holiday-item').querySelector(`input[name="holidays[${dateStr}]"]`);
        hiddenInput.value = input.value;
    }

    function removeHoliday(button) {
        button.closest('.holiday-item').remove();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Load existing holidays
        <?php if (!empty($settingsData['holidays'])): ?>
        try {
            const existingHolidays = <?php echo $settingsData['holidays']; ?>;
            Object.entries(existingHolidays).forEach(([date, description]) => {
                const holidayDate = new Date(date);
                addHoliday(holidayDate, date, true);
                
                // Set the description
                const descInput = document.querySelector(`input[name="holidays[${date}]"]`);
                if (descInput) {
                    descInput.value = description;
                }
            });
        } catch (e) {
            console.error('Error loading existing holidays:', e);
        }
        <?php endif; ?>
    });
    </script>

    <?php if (isset($settingsData['holidays'])): ?>
        <script>
            // Display saved holidays when page loads
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    const savedHolidays = <?php echo $settingsData['holidays'] ?: '{}'; ?>;
                    console.log('Saved holidays:', savedHolidays);
                    
                    // Recreate holiday items for saved holidays
                    Object.entries(savedHolidays).forEach(([date, description]) => {
                        const holidayDate = new Date(date + 'T00:00:00');
                        addHoliday(holidayDate, date, true);
                        // Set the description if it exists
                        const holidayItem = document.querySelector(`input[name="holidays[${date}]"]`);
                        if (holidayItem) {
                            const descInput = holidayItem.closest('.holiday-item').querySelector('input[type="text"]');
                            if (descInput) {
                                descInput.value = description;
                                holidayItem.value = description;
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error loading saved holidays:', e);
                }
            });
        </script>
    <?php endif; ?>
</body>
</html> 