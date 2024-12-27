<?php
session_start();
require_once '../../../config/app_config.php';
require_once '../models/Inventory.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Item ID is required']);
    exit;
}

$inventory = new Inventory();
$item = $inventory->getById($_GET['id'], $_SESSION['user_id']);

if ($item) {
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
} 