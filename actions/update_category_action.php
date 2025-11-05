<?php
// update_category_action.php


require_once '../controllers/category_controller.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $newName = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';

    if ($id <= 0 || empty($newName)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid category ID or name.']);
        exit;
    }

    // Check uniqueness (exclude current id)
    if (category_name_exists_ctr($newName, $id)) {
        echo json_encode(['status' => 'error', 'message' => 'Category name already exists. Please choose another name.']);
        exit;
    }

    $result = edit_category_ctr($id, $newName);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Category updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update category. Please try again.']);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}
?>
