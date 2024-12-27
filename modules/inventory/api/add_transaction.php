<?php
session_start();
require_once '../../../config/app_config.php';
require_once '../models/Inventory.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate required fields
$required_fields = ['item_id', 'transaction_type', 'quantity'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

$inventory = new Inventory();

// Verify item ownership
$item = $inventory->getById($_POST['item_id'], $_SESSION['user_id']);
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
    exit;
}

// Add user_id to the transaction data
$_POST['user_id'] = $_SESSION['user_id'];

// Process transaction
$result = $inventory->addTransaction($_POST);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Transaction recorded successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record transaction']);
} 