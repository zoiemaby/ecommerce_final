<?php
// delete_category_action.php

require_once '../controllers/category_controller.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    if ($category_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid category ID.']);
        exit;
    }

    $deleted = delete_category_ctr($category_id);

    if ($deleted) {
        echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete category. Try again.']);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}
?>
