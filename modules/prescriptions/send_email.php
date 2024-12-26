<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Prescription.php';
require_once '../clinic_settings/models/Settings.php';
require_once BASE_PATH . '/vendor/autoload.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

header('Content-Type: application/json');

try {
    // Get prescription ID
    $prescriptionId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$prescriptionId) {
        throw new Exception('Invalid prescription ID');
    }

    // Get prescription details
    $prescription = new Prescription();
    $prescriptionData = $prescription->getById($prescriptionId, $_SESSION['user_id']);
    if (!$prescriptionData) {
        throw new Exception('Prescription not found');
    }

    // Get clinic settings
    $settings = new Settings();
    $clinicSettings = $settings->getSettings($_SESSION['user_id']);

    // Check if patient email exists
    if (empty($prescriptionData['patient_email'])) {
        throw new Exception('Patient email not found');
    }

    // Create email content
    $medications = json_decode($prescriptionData['medications'], true);
    $medicationsList = '';
    foreach ($medications as $med) {
        $medicationsList .= "
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$med['name']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$med['dosage']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$med['frequency']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$med['duration']}</td>
            </tr>";
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    
    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, $clinicSettings['clinic_name'] ?? SMTP_FROM_NAME);
    $mail->addAddress($prescriptionData['patient_email'], $prescriptionData['patient_name']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = "Your Prescription from " . ($clinicSettings['clinic_name'] ?? SMTP_FROM_NAME);
    
    // Email template
    $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>
            <div style='background: #f8f9fa; padding: 20px; margin-bottom: 20px;'>
                <h2 style='color: #0d6efd; margin: 0;'>" . ($clinicSettings['clinic_name'] ?? 'Clinic Name') . "</h2>
                <p style='margin: 5px 0;'>" . ($clinicSettings['doctor_name'] ?? '') . "</p>
                <p style='margin: 5px 0;'>" . ($clinicSettings['specialization'] ?? '') . "</p>
            </div>

            <p>Dear {$prescriptionData['patient_name']},</p>
            
            <p>Please find your prescription details below:</p>

            " . (!empty($prescriptionData['diagnosis']) ? "
                <h3 style='color: #0d6efd;'>Diagnosis</h3>
                <p>{$prescriptionData['diagnosis']}</p>
            " : "") . "
            
            <h3 style='color: #0d6efd;'>Medications</h3>
            <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                <tr style='background: #f8f9fa;'>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Medication</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Dosage</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Frequency</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Duration</th>
                </tr>
                {$medicationsList}
            </table>

            " . (!empty($prescriptionData['instructions']) ? "
                <h3 style='color: #0d6efd;'>Instructions</h3>
                <p>{$prescriptionData['instructions']}</p>
            " : "") . "

            " . (!empty($prescriptionData['notes']) ? "
                <h3 style='color: #0d6efd;'>Additional Notes</h3>
                <p>{$prescriptionData['notes']}</p>
            " : "") . "

            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
                <p><strong>Contact Information:</strong></p>
                " . (!empty($clinicSettings['phone']) ? "<p>Phone: {$clinicSettings['phone']}</p>" : "") . "
                " . (!empty($clinicSettings['email']) ? "<p>Email: {$clinicSettings['email']}</p>" : "") . "
                " . (!empty($clinicSettings['address']) ? "<p>Address: {$clinicSettings['address']}</p>" : "") . "
            </div>
        </div>
    ";

    $mail->Body = $emailBody;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailBody));

    if ($mail->send()) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully'
        ]);
    } else {
        throw new Exception('Failed to send email');
    }

} catch (Exception $e) {
    error_log("Error in send_email.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 