<?php
/**
 * actions/add_product_action.php
 * Creates a product with image upload.
 * Only creates the product if at least one image is successfully uploaded.
 * 
 * POST fields:
 *   - product_title (required)
 *   - product_cat (required)
 *   - product_brand (required)
 *   - product_price (required)
 *   - product_desc (optional)
 *   - product_keywords (optional)
 * FILES:
 *   - images[] (required, at least one)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../controllers/product_controller.php';

function out($a){ echo json_encode($a); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    out(['status'=>'error','message'=>'Use POST']);
}

// Validate required fields
$title = trim($_POST['product_title'] ?? '');
$catId = (int)($_POST['product_cat'] ?? 0);
$brandId = (int)($_POST['product_brand'] ?? 0);
$price = (float)($_POST['product_price'] ?? 0);
$desc  = (string)($_POST['product_desc'] ?? '');
$kw    = $_POST['product_keywords'] ?? '';

if (is_array($kw)) {
    $kw = implode(',', array_filter(array_map('trim', $kw)));
}
$kw = trim((string)$kw);

if ($title === '' || $catId <= 0 || $brandId <= 0 || $price <= 0) {
    out(['status'=>'error','message'=>'Missing required fields: title, category, brand, and price']);
}

// Validate images uploaded
if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
    out(['status'=>'error','message'=>'At least one product image is required']);
}

// Process uploaded images FIRST
$uploadsRoot = realpath(__DIR__ . '/../uploads');
if ($uploadsRoot === false || !is_dir($uploadsRoot)) {
    if (!mkdir(__DIR__ . '/../uploads', 0755, true)) {
        out(['status'=>'error','message'=>'Failed to create uploads directory']);
    }
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
}

if (!is_writable($uploadsRoot)) {
    out(['status'=>'error','message'=>'Uploads directory not writable']);
}

// Normalize files array
$files = [];
if (is_array($_FILES['images']['name'])) {
    $count = count($_FILES['images']['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $files[] = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i]
            ];
        }
    }
} else {
    if ($_FILES['images']['error'] === UPLOAD_ERR_OK) {
        $files[] = $_FILES['images'];
    }
}

if (empty($files)) {
    out(['status'=>'error','message'=>'No valid images uploaded']);
}

// Validate and upload images to temp storage FIRST
$allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
$maxBytes = 5 * 1024 * 1024;
$uploadedPaths = [];
$tempDir = $uploadsRoot . DIRECTORY_SEPARATOR . 'temp_' . uniqid();

if (!mkdir($tempDir, 0755, true)) {
    out(['status'=>'error','message'=>'Failed to create temp directory']);
}

foreach ($files as $idx => $file) {
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        // Cleanup and fail
        array_map('unlink', glob($tempDir . '/*'));
        rmdir($tempDir);
        out(['status'=>'error','message'=>'Image size must be between 1 byte and 5MB']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        array_map('unlink', glob($tempDir . '/*'));
        rmdir($tempDir);
        out(['status'=>'error','message'=>'Unsupported image type. Use JPG, PNG, GIF, or WEBP']);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime !== $allowed[$ext]) {
        array_map('unlink', glob($tempDir . '/*'));
        rmdir($tempDir);
        out(['status'=>'error','message'=>'Image MIME type mismatch']);
    }

    $safeName = 'image_' . $idx . '.' . $ext;
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . $safeName;
    
    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        array_map('unlink', glob($tempDir . '/*'));
        rmdir($tempDir);
        out(['status'=>'error','message'=>'Failed to upload image']);
    }
    
    $uploadedPaths[] = $tempPath;
}

// NOW create the product (images validated successfully)
$res = add_product_ctr([
    'product_title'    => $title,
    'product_cat'      => $catId,
    'product_brand'    => $brandId,
    'product_price'    => $price,
    'product_desc'     => $desc,
    'product_keywords' => $kw,
    'product_image'    => ''
]);

if (empty($res['success'])) {
    // Cleanup temp images
    array_map('unlink', $uploadedPaths);
    rmdir($tempDir);
    out(['status'=>'error','message'=>$res['message'] ?? 'Failed to create product']);
}

$product_id = (int)($res['data']['product_id'] ?? 0);
if ($product_id <= 0) {
    array_map('unlink', $uploadedPaths);
    rmdir($tempDir);
    out(['status'=>'error','message'=>'Invalid product ID returned']);
}

// Move images to final location: uploads/p{product_id}/
$productDir = $uploadsRoot . DIRECTORY_SEPARATOR . 'p' . $product_id;
if (!mkdir($productDir, 0755, true)) {
    array_map('unlink', $uploadedPaths);
    rmdir($tempDir);
    out(['status'=>'error','message'=>'Failed to create product image directory']);
}

$finalPaths = [];
foreach ($uploadedPaths as $idx => $tempPath) {
    $filename = basename($tempPath);
    $finalPath = $productDir . DIRECTORY_SEPARATOR . $filename;
    
    if (!rename($tempPath, $finalPath)) {
        // Cleanup
        array_map('unlink', $finalPaths);
        array_map('unlink', $uploadedPaths);
        rmdir($tempDir);
        rmdir($productDir);
        out(['status'=>'error','message'=>'Failed to move image to final location']);
    }
    
    @chmod($finalPath, 0644);
    $relativePath = 'uploads/p' . $product_id . '/' . $filename;
    $finalPaths[] = $relativePath;
}

rmdir($tempDir);

// Update product with first image path
if (!empty($finalPaths)) {
    $mainImage = $finalPaths[0];
    $updateRes = set_product_main_image_ctr($product_id, $mainImage, true);
    if (empty($updateRes['success'])) {
        // Product created but image link failed - still return success with warning
        out(['status'=>'success','data'=>['product_id'=>$product_id],'message'=>'Product created but image link update failed']);
    }
}

out(['status'=>'success','data'=>['product_id'=>$product_id,'images'=>$finalPaths],'message'=>'Product created successfully']);
