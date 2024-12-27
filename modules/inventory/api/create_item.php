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
$required_fields = ['name', 'quantity', 'unit_cost', 'selling_price'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

// Add user_id to the data
$_POST['user_id'] = $_SESSION['user_id'];

// Create new item
$inventory = new Inventory();
$result = $inventory->create($_POST);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Item created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create item']);
} 