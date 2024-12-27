<?php
session_start();
require_once '../../../config/app_config.php';
require_once '../models/Inventory.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Item ID is required']);
    exit;
}

$inventory = new Inventory();

// Verify item ownership
$item = $inventory->getById($data['id'], $_SESSION['user_id']);
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
    exit;
}

// Delete item
$result = $inventory->delete($data['id']);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
} 