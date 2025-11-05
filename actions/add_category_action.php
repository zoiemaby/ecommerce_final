<?php
// add_category_action.php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../controllers/category_controller.php'; // adjust path if needed

function send_json($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['status' => 'error', 'message' => 'Invalid request method. POST required.'], 405);
}

// Collect and sanitize
$category_name = isset($_POST['category_name']) ? trim((string)$_POST['category_name']) : '';

// Basic validation
if ($category_name === '') {
    send_json(['status' => 'error', 'message' => 'Category name is required.'], 400);
}
if (mb_strlen($category_name) < 2 || mb_strlen($category_name) > 80) {
    send_json(['status' => 'error', 'message' => 'Category name must be between 2 and 80 characters.'], 400);
}

// Optional: further character validation (match your client-side rules)
if (!preg_match('/^[\p{L}0-9 _\-\&\(\)\.]+$/u', $category_name)) {
    send_json(['status' => 'error', 'message' => 'Category name contains invalid characters.'], 400);
}

// Check uniqueness
try {
    if (category_name_exists_ctr($category_name)) {
        send_json(['status' => 'error', 'message' => 'Category name already exists. Choose another name.'], 409);
    }
} catch (Throwable $e) {
    error_log('add_category_action: uniqueness check failed: ' . $e->getMessage());
    send_json(['status' => 'error', 'message' => 'Server error during validation.'], 500);
}

// Attempt to add
try {
    $insertId = add_category_ctr($category_name);
    if ($insertId === false || $insertId === 0) {
        send_json(['status' => 'error', 'message' => 'Failed to add category.'], 500);
    }

    send_json([
        'status' => 'success',
        'message' => 'Category added successfully.',
        'data' => [
            'category_id' => (int)$insertId,
            'category_name' => $category_name
        ]
    ], 201);

} catch (Throwable $e) {
    error_log('add_category_action error: ' . $e->getMessage());
    send_json(['status' => 'error', 'message' => 'Internal server error.'], 500);
}
