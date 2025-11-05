<?php
/**
 * update_brand_action.php
 *
 * Updates an existing Brand using the Brand controller wrapper.
 * Expects: POST { brand_id: int, brand_name: string, cat_id: int }
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
    json_error('Use POST to update a brand.', 405);
}

// --- Gather + validate input ---
$brandId = get_input_param('brand_id', null);
$name    = trim((string) get_input_param('brand_name', ''));

if (!is_numeric($brandId) || (int)$brandId <= 0) {
    json_error('A valid brand_id is required.');
}
$brandId = (int)$brandId;

if ($name === '') {
    json_error('brand_name is required.');
}

try {
    // Optional: ensure the brand exists first (clearer error than a silent no-op)
    $existing = get_brand_ctr($brandId);
    if (!$existing) {
        json_error('Brand not found.', 404);
    }

    // Optional pre-check for clearer UX (class also enforces uniqueness)
    if (brand_name_exists_ctr($name, $brandId)) {
        json_error('Another brand with this name already exists.');
    }

    // --- Update brand ---
    $res = edit_brand_ctr($brandId, $name);

    if (!is_array($res)) {
        json_error('Unexpected response from controller.');
    }

    if (($res['success'] ?? false) === true) {
        json_success(null, $res['message'] ?? 'Brand updated successfully.');
    } else {
        json_error($res['message'] ?? 'Failed to update brand.');
    }
} catch (Throwable $e) {
    error_log('update_brand_action exception: ' . $e->getMessage());
    json_error('Server error. Please try again later.', 500);
}
