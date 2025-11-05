<?php
/**
 * add_brand_action.php
 *
 * Creates a new Brand using the Brand controller wrapper.
 * Expects: POST { brand_name: string }
 * Returns: JSON { status: "success"|"error", message: string, data?: any }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/brand_controller.php';

function respond($ok, $message, $data = null, $reason = null, $status = 200) {
  http_response_code($status);
  $out = ['ok' => (bool)$ok, 'message' => $message];
  if ($data !== null)   $out['data'] = $data;
  if ($reason !== null) $out['reason'] = $reason;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(false, 'Use POST to add a brand.', null, 'method', 405);
}

$name = trim((string)($_POST['brand_name'] ?? ''));
if ($name === '') {
  respond(false, 'Brand name is required.', null, 'validation', 422);
}

try {
  // No category parameter
  $result = add_brand_ctr($name);
} catch (Throwable $e) {
  error_log('add_brand_action: ' . $e->getMessage());
  respond(false, 'Server error. Please try again later.', null, 'exception', 500);
}

if (is_array($result) && !empty($result['success'])) {
  respond(true, $result['message'] ?? 'Brand created successfully.', ['brand_id' => $result['id'] ?? null]);
}

$message = is_array($result) ? ($result['message'] ?? 'Failed to create brand.') : 'Failed to create brand.';
$reason  = (stripos($message, 'exist') !== false) ? 'exists' : 'error';
$status  = ($reason === 'exists') ? 409 : 400;
respond(false, $message, null, $reason, $status);
