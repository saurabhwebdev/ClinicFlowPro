<?php
session_start();
require_once '../../../config/app_config.php';
require_once '../../../vendor/autoload.php';
require_once '../models/Inventory.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $inventory = new Inventory();
    $items = $inventory->getAll($_SESSION['user_id']);
    $lowStockItems = $inventory->getLowStockItems($_SESSION['user_id']);

    // Create email content
    $emailBody = "
        <h2>Inventory Status Report</h2>
        <p>Generated on: " . date('Y-m-d H:i:s') . "</p>

        " . (!empty($lowStockItems) ? "
            <h3 style='color: #dc3545;'>Low Stock Alerts</h3>
            <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                <tr style='background: #f8f9fa;'>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Item</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Current Stock</th>
                    <th style='padding: 8px; border: 1px solid #ddd;'>Minimum Stock</th>
                </tr>" : "");

    foreach ($lowStockItems as $item) {
        $emailBody .= "
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['name']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['quantity']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['min_quantity']}</td>
            </tr>";
    }

    $emailBody .= "</table>
        <h3>Complete Inventory List</h3>
        <table style='width: 100%; border-collapse: collapse;'>
            <tr style='background: #f8f9fa;'>
                <th style='padding: 8px; border: 1px solid #ddd;'>Item</th>
                <th style='padding: 8px; border: 1px solid #ddd;'>SKU</th>
                <th style='padding: 8px; border: 1px solid #ddd;'>Category</th>
                <th style='padding: 8px; border: 1px solid #ddd;'>Quantity</th>
                <th style='padding: 8px; border: 1px solid #ddd;'>Status</th>
            </tr>";

    foreach ($items as $item) {
        $emailBody .= "
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['name']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['sku']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['category']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['quantity']}</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$item['status']}</td>
            </tr>";
    }

    $emailBody .= "</table>";

    // Send email using PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($_SESSION['email']);
    
    $mail->isHTML(true);
    $mail->Subject = 'Inventory Status Report - ' . date('Y-m-d');
    $mail->Body = $emailBody;

    $mail->send();
    
    echo json_encode(['success' => true, 'message' => 'Report sent successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to send report: ' . $e->getMessage()]);
} 