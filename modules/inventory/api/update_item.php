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
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Item ID is required']);
    exit;
}

$required_fields = ['name', 'quantity', 'unit_cost', 'selling_price'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

$inventory = new Inventory();

// Verify item ownership
$item = $inventory->getById($_POST['id'], $_SESSION['user_id']);
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
    exit;
}

// Update item
$result = $inventory->update($_POST);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update item']);
} 