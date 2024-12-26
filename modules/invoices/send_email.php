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
    require_once 'models/Invoice.php';
    require_once '../clinic_settings/models/Settings.php';
    require_once BASE_PATH . '/vendor/autoload.php';

    // Check authentication and session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    // Debug session
    error_log("Session data: " . print_r($_SESSION, true));
    
    SubscriptionMiddleware::checkAccess();

    // Get invoice ID
    $invoiceId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    error_log("Processing invoice ID: " . $invoiceId);
    
    if (!$invoiceId) {
        throw new Exception('Invalid invoice ID');
    }

    // Get invoice details
    $invoice = new Invoice();
    $invoiceData = $invoice->getById($invoiceId, $_SESSION['user_id']);
    error_log("Invoice data: " . print_r($invoiceData, true));
    
    if (!$invoiceData) {
        throw new Exception('Invoice not found');
    }

    // Get clinic settings
    $settings = new Settings();
    $clinicSettings = $settings->getSettings($_SESSION['user_id']);

    // Check if patient email exists
    if (empty($invoiceData['patient_email'])) {
        throw new Exception('Patient email not found');
    }

    // Create email content
    $items = json_decode($invoiceData['items'], true);
    
    // Recalculate totals for email
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['quantity'] * $item['unit_price'];
    }
    
    // Calculate tax
    $tax_amount = 0;
    if ($invoiceData['tax_rate'] > 0) {
        $tax_amount = $subtotal * ($invoiceData['tax_rate'] / 100);
    }
    
    // Calculate discount
    $discount_amount = 0;
    if ($invoiceData['discount_value'] > 0) {
        if ($invoiceData['discount_type'] === 'percentage') {
            $discount_amount = $subtotal * ($invoiceData['discount_value'] / 100);
        } else {
            $discount_amount = $invoiceData['discount_value'];
        }
    }
    
    // Calculate total
    $total_amount = $subtotal + $tax_amount - $discount_amount;
    
    $itemsList = '';
    foreach ($items as $item) {
        $itemsList .= "
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['description']}</td>
                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>{$item['quantity']}</td>
                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>" . 
                    htmlspecialchars($clinicSettings['currency'] ?? 'USD') . " " . 
                    number_format($item['unit_price'], 2) . "</td>
                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>" . 
                    htmlspecialchars($clinicSettings['currency'] ?? 'USD') . " " . 
                    number_format($item['quantity'] * $item['unit_price'], 2) . "</td>
            </tr>";
    }

    // Create PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings with debug output
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
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, $clinicSettings['clinic_name'] ?? SMTP_FROM_NAME);
        $mail->addAddress($invoiceData['patient_email'], $invoiceData['patient_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Invoice #{$invoiceData['invoice_number']} from " . ($clinicSettings['clinic_name'] ?? SMTP_FROM_NAME);
        
        // Email template
        $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>
                <div style='background: #f8f9fa; padding: 20px; margin-bottom: 20px;'>
                    <h2 style='color: #0d6efd; margin: 0;'>" . ($clinicSettings['clinic_name'] ?? 'Clinic Name') . "</h2>
                    <p style='margin: 5px 0;'>" . ($clinicSettings['doctor_name'] ?? '') . "</p>
                    <p style='margin: 5px 0;'>" . ($clinicSettings['specialization'] ?? '') . "</p>
                </div>

                <p>Dear {$invoiceData['patient_name']},</p>
                
                <p>Please find your invoice details below:</p>

                <div style='margin-bottom: 20px;'>
                    <p><strong>Invoice Number:</strong> {$invoiceData['invoice_number']}</p>
                    <p><strong>Date:</strong> " . date('F d, Y', strtotime($invoiceData['created_at'])) . "</p>
                    " . (!empty($invoiceData['due_date']) ? "<p><strong>Due Date:</strong> " . date('F d, Y', strtotime($invoiceData['due_date'])) . "</p>" : "") . "
                </div>
                
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <tr style='background: #f8f9fa;'>
                        <th style='padding: 8px; border: 1px solid #ddd;'>Description</th>
                        <th style='padding: 8px; border: 1px solid #ddd; text-align: right;'>Quantity</th>
                        <th style='padding: 8px; border: 1px solid #ddd; text-align: right;'>Price</th>
                        <th style='padding: 8px; border: 1px solid #ddd; text-align: right;'>Total</th>
                    </tr>
                    {$itemsList}
                    <tr>
                        <td colspan='3' style='padding: 8px; border: 1px solid #ddd; text-align: right;'><strong>Subtotal:</strong></td>
                        <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'><strong>" . 
                            htmlspecialchars($clinicSettings['currency'] ?? 'USD') . " " . 
                            number_format($subtotal, 2) . "</strong></td>
                    </tr>";

                    if ($invoiceData['tax_rate'] > 0) {
                        $emailBody .= "
                            <tr>
                                <td colspan='3' style='padding: 8px; border: 1px solid #ddd; text-align: right;'>
                                    <strong>Tax (" . number_format($invoiceData['tax_rate'], 2) . "%):</strong>
                                </td>
                                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>" . 
                                    htmlspecialchars($clinicSettings['currency'] ?? 'USD') . " " . 
                                    number_format($tax_amount, 2) . "</td>
                            </tr>";
                    }

                    if ($invoiceData['discount_value'] > 0) {
                        $emailBody .= "
                            <tr>
                                <td colspan='3' style='padding: 8px; border: 1px solid #ddd; text-align: right;'>
                                    <strong>Discount " . 
                                    ($invoiceData['discount_type'] === 'percentage' ? 
                                    "(" . number_format($invoiceData['discount_value'], 2) . "%)" : "") . 
                                    ":</strong>
                                </td>
                                <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>-" . 
                                    htmlspecialchars($clinicSettings['currency'] ?? 'USD') . " " . 
                                    number_format($discount_amount, 2) . "</td>
                            </tr>";
                    }

                    $emailBody .= "
                    <tr>
                        <td colspan='3' style='padding: 8px; border: 1px solid #ddd; text-align: right;'>
                            <strong>Total Amount:</strong>
                        </td>
                        <td style='padding: 8px; border: 1px solid #ddd; text-align: right;'>
                            <strong>" . 
                            htmlspecialchars($clinicSettings['currency'] ?? 'USD') . " " . 
                            number_format($total_amount, 2) . "</strong>
                        </td>
                    </tr>
                </table>

                " . (!empty($invoiceData['notes']) ? "
                    <div style='margin-bottom: 20px;'>
                        <h3 style='color: #0d6efd;'>Notes</h3>
                        <p>{$invoiceData['notes']}</p>
                    </div>
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