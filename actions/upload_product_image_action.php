<?php
/**
 * admin/actions/upload_product_image_action.php
 *
 * Upload a single product image to the **uploads/** directory ONLY,
 * and store its resolved relative path in the DB via controller helper.
 *
 * POST fields:
 *   - product_id (int, required)
 *   - sort (int, optional; default 0)
 *   - image (file, required)  // <input type="file" name="image">
 *
 * Responses: JSON
 *   { success: bool, id?: int, path?: string, message: string }
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

require_once '../controllers/product_controller.php';

try {
    // --- Validate inputs ---
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $sort       = isset($_POST['sort']) ? (int)$_POST['sort'] : 0;

    if ($product_id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing or invalid product_id.']); exit; }
    if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'No image file uploaded (field "image").']); exit; }

    $file = $_FILES['image'];
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'File size must be 1..5MB']); exit; }

    $allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Unsupported type']); exit; }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if ($finfo->file($file['tmp_name']) !== $allowed[$ext]) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'MIME mismatch']); exit; }

    // Root uploads/
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadsRoot === false || !is_dir($uploadsRoot) || !is_writable($uploadsRoot)) {
        http_response_code(500); echo json_encode(['success'=>false,'message'=>'uploads/ missing or not writable']); exit;
    }

    // Layout: uploads/p{product}/
    $subDir = 'p' . $product_id;
    $targetDirAbs = $uploadsRoot . DIRECTORY_SEPARATOR . $subDir;
    if (!is_dir($targetDirAbs) && !mkdir($targetDirAbs, 0755, true)) {
        http_response_code(500); echo json_encode(['success'=>false,'message'=>'Failed to create subdirectory']); exit;
    }

    $targetDirReal = realpath($targetDirAbs);
    if ($targetDirReal === false || strpos($targetDirReal, $uploadsRoot) !== 0) {
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid upload destination']); exit;
    }

    // Name like image_{sort}.ext; avoid collision
    $n = $sort >= 0 ? $sort : 0;
    $base = 'image_' . $n . '.' . $ext;
    $targetAbs = $targetDirReal . DIRECTORY_SEPARATOR . $base;
    $c = 1;
    while (file_exists($targetAbs)) {
        $base = 'image_' . $n . '_' . $c . '.' . $ext;
        $targetAbs = $targetDirReal . DIRECTORY_SEPARATOR . $base;
        $c++;
    }

    if (!move_uploaded_file($file['tmp_name'], $targetAbs)) {
        http_response_code(500); echo json_encode(['success'=>false,'message'=>'Move failed']); exit;
    }
    @chmod($targetAbs, 0644);

    $finalReal = realpath($targetAbs);
    if ($finalReal === false || strpos($finalReal, $uploadsRoot) !== 0) {
        @unlink($targetAbs);
        http_response_code(400); echo json_encode(['success'=>false,'message'=>'Escaped uploads/']); exit;
    }

    // Store relative path like uploads/p{product}/image_X.ext
    $relativePath = 'uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $subDir) . '/' . basename($finalReal);

    $result = add_product_image_ctr($product_id, $relativePath, $sort);
    if (!($result['success'] ?? false)) {
        @unlink($finalReal);
        http_response_code(400); echo json_encode($result); exit;
    }

    // Set main image if empty
    set_product_main_image_ctr($product_id, $relativePath, false);

    echo json_encode(['success'=>true,'id'=>$result['id'] ?? null,'path'=>$relativePath,'message'=>'Image uploaded and saved.']);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error: '.$e->getMessage()]);
}
