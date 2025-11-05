<?php
/**
 * delete_brand_action.php
 *
 * Deletes an existing Brand using the Brand controller wrapper.
 * Expects: POST { brand_id: int }
 * Returns: JSON { status: "success"|"error", message: string, data?: any }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/brand_controller.php';

// --- Helpers ---
function json_success($data = null, $message = 'OK') {
    echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
    exit;
}
function json_error($message = 'Something went wrong', $code = 400, $extra = null) {
    http_response_code($code);
    $out = ['status' => 'error', 'message' => $message];
    if ($extra !== null) $out['data'] = $extra;
    echo json_encode($out);
    exit;
}
function get_input_param($key, $default = null) {
    // Support application/json bodies AND form-encoded posts
    static $jsonBody = null;
    if ($jsonBody === null && $_SERVER['REQUEST_METHOD'] === 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $jsonBody = json_decode($raw, true);
        if (!is_array($jsonBody)) $jsonBody = [];
    }
    if ($jsonBody !== null && array_key_exists($key, $jsonBody)) return $jsonBody[$key];
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_GET[$key]))  return $_GET[$key];
    return $default;
}

// --- Method guard ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Use POST to delete a brand.', 405);
}

// --- Gather + validate input ---
$brandId = get_input_param('brand_id', null);
if (!is_numeric($brandId) || (int)$brandId <= 0) {
    json_error('A valid brand_id is required.');
}
$brandId = (int)$brandId;

try {
    // Optional: ensure the brand exists first for clearer errors
    $existing = get_brand_ctr($brandId);
    if (!$existing) {
        json_error('Brand not found.', 404);
    }

    // --- Delete brand ---
    $res = delete_brand_ctr($brandId);

    if (!is_array($res)) {
        json_error('Unexpected response from controller.');
    }

    if (($res['success'] ?? false) === true) {
        json_success(null, $res['message'] ?? 'Brand deleted successfully.');
    } else {
        // If controller couldn't delete (e.g., FK constraint), surface message
        json_error($res['message'] ?? 'Failed to delete brand.');
    }
} catch (Throwable $e) {
    error_log('delete_brand_action exception: ' . $e->getMessage());
    json_error('Server error. Please try again later.', 500);
}
