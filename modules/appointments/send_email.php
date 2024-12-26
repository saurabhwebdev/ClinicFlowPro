<?php
// Start session first
session_start();

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/email_error.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

// Clear any previous output and set JSON header
ob_clean();
header('Content-Type: application/json');

try {
    require_once '../../config/app_config.php';
    require_once '../auth/middleware/SubscriptionMiddleware.php';
    require_once 'models/Appointment.php';
    require_once '../patients/models/Patient.php';
    require_once BASE_PATH . '/vendor/autoload.php';

    // Check authentication and session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    // Debug session
    error_log("Session data: " . print_r($_SESSION, true));
    
    SubscriptionMiddleware::checkAccess();

    // Get appointment ID from POST data
    $appointmentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    error_log("Processing appointment ID: " . $appointmentId);
    
    if (!$appointmentId) {
        throw new Exception('Invalid appointment ID');
    }

    // Get appointment details
    $appointment = new Appointment();
    $appointmentData = $appointment->getById($appointmentId, $_SESSION['user_id']);
    error_log("Appointment data: " . print_r($appointmentData, true));
    
    if (!$appointmentData) {
        throw new Exception('Appointment not found');
    }

    // Get patient details
    $patient = new Patient();
    $patientId = $appointmentData['patient_id'];
    error_log("Fetching patient ID: " . $patientId);
    
    $patientData = $patient->getOne($patientId);
    error_log("Patient data: " . print_r($patientData, true));
    
    if (!$patientData) {
        throw new Exception('Patient not found');
    }
    
    if (empty($patientData['email'])) {
        throw new Exception('Patient email address is not available');
    }

    // Create PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer [$level] : $str");
        };
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Set sender and recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($patientData['email'], $patientData['first_name'] . ' ' . $patientData['last_name']);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Your Appointment Confirmation";
        $mail->Body = "
            <html>
            <body>
                <h2>Appointment Confirmation</h2>
                <p>Dear {$patientData['first_name']} {$patientData['last_name']},</p>
                <p>This email confirms your appointment details:</p>
                <ul>
                    <li><strong>Date:</strong> " . date('F j, Y', strtotime($appointmentData['date'])) . "</li>
                    <li><strong>Time:</strong> " . date('g:i A', strtotime($appointmentData['time'])) . "</li>
                    <li><strong>Duration:</strong> {$appointmentData['duration']} minutes</li>
                    <li><strong>Title:</strong> {$appointmentData['title']}</li>
                </ul>
                <p>If you need to reschedule or cancel your appointment, please contact us.</p>
                <p>Thank you for choosing our services!</p>
            </body>
            </html>
        ";

        // Send email
        if (!$mail->send()) {
            throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
        }

        error_log("Email sent successfully");
        
        // Clear output buffer and send success response
        if (ob_get_length()) ob_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully'
        ]);
        exit;

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        throw new Exception($e->getMessage());
    }

} catch (Exception $e) {
    error_log("Error in send_email.php: " . $e->getMessage());
    
    // Clear output buffer and send error response
    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
} 