<?php
/**
 * admin/actions/bulk_upload_images_action.php
 *
 * Bulk upload images for a product into **uploads/p{product_id}/**.
 * Stores each resolved relative path in the DB (main image field is updated each time).
 *
 * POST fields:
 *   - product_id (int, required)  e.g., 6
 *   - start_sort (int, optional)  starting sort index (default 0)
 *   - images[]   (files, required) multiple file input field named "images[]"
 *
 * Response JSON:
 * {
 *   "success": true|false,
 *   "uploaded": [ { "success":bool, "path"?:string, "id"?:int, "message":string, "index":int } ... ],
 *   "counts": { "ok": N, "fail": M }
 * }
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

require_once __DIR__ . '/../controllers/product_controller.php';

try {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $start_sort = isset($_POST['start_sort']) ? (int)$_POST['start_sort'] : 0;

    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing or invalid product_id.']);
        exit;
    }

    if (!isset($_FILES['images'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No files provided. Field must be "images[]".']);
        exit;
    }

    // Normalize the _FILES structure for multiple uploads
    $files = [];
    if (is_array($_FILES['images']['name'])) {
        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            $files[] = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
        }
    } else {
        $files[] = $_FILES['images'];
    }

    // Verify uploads/ root directory
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadsRoot === false || !is_dir($uploadsRoot) || !is_writable($uploadsRoot)) {
        // try to create uploads directory
        $attempt = __DIR__ . '/../uploads';
        if (!is_dir($attempt)) {
            @mkdir($attempt, 0755, true);
        }
        $uploadsRoot = realpath($attempt);
        if ($uploadsRoot === false || !is_dir($uploadsRoot) || !is_writable($uploadsRoot)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Uploads directory missing or not writable. Expected: /uploads at project root.']);
            exit;
        }
    }

    // Prepare subdirectory inside uploads/ : uploads/p{product_id}
    $subDir = 'p' . $product_id;
    $targetDirAbs = $uploadsRoot . DIRECTORY_SEPARATOR . $subDir;

    // Create subdirectories as needed, BUT ONLY inside uploads/
    if (!is_dir($targetDirAbs)) {
        if (!mkdir($targetDirAbs, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create product subdirectory.']);
            exit;
        }
    }

    // Safety check: ensure targetDirAbs resolves to inside uploadsRoot
    $targetDirReal = realpath($targetDirAbs);
    if ($targetDirReal === false || strpos($targetDirReal, $uploadsRoot) !== 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid upload target.']);
        exit;
    }

    // Validation settings
    $maxBytes = 5 * 1024 * 1024; // 5MB
    $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp'
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $results = [];
    $ok = 0; $fail = 0;
    $sort = $start_sort;

    foreach ($files as $index => $file) {
        $res = ['success' => false, 'index' => $index, 'message' => ''];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $res['message'] = 'Upload error code: ' . (int)($file['error'] ?? -1);
            $results[] = $res; $fail++; $sort++; continue;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            $res['message'] = 'No valid uploaded file.';
            $results[] = $res; $fail++; $sort++; continue;
        }

        if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
            $res['message'] = 'File size must be between 1 byte and 5MB.';
            $results[] = $res; $fail++; $sort++; continue;
        }

        $origName = $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            $res['message'] = 'Unsupported file type: ' . $ext;
            $results[] = $res; $fail++; $sort++; continue;
        }

        $mime = $finfo->file($file['tmp_name']);
        if ($mime !== $allowed[$ext]) {
            $res['message'] = 'MIME mismatch for extension ' . $ext;
            $results[] = $res; $fail++; $sort++; continue;
        }

        // Name like image_{n}.ext; avoid collisions by appending counter
        $n = $sort;
        if ($n < 0) $n = $index;
        $safeBase = 'image_' . $n . '.' . $ext;
        $targetAbs = $targetDirReal . DIRECTORY_SEPARATOR . $safeBase;
        $counter = 1;
        while (file_exists($targetAbs)) {
            $safeBase = 'image_' . $n . '_' . $counter . '.' . $ext;
            $targetAbs = $targetDirReal . DIRECTORY_SEPARATOR . $safeBase;
            $counter++;
        }

        if (!move_uploaded_file($file['tmp_name'], $targetAbs)) {
            $res['message'] = 'Failed to move uploaded file.';
            $results[] = $res; $fail++; $sort++; continue;
        }
        @chmod($targetAbs, 0644);

        $finalReal = realpath($targetAbs);
        if ($finalReal === false || strpos($finalReal, $uploadsRoot) !== 0) {
            @unlink($targetAbs);
            $res['message'] = 'Upload rejected: escaped uploads/ directory.';
            $results[] = $res; $fail++; $sort++; continue;
        }

    $relativePath = 'uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $subDir) . '/' . basename($finalReal);

        $r = add_product_image_ctr($product_id, $relativePath, $sort);
        if (!is_array($r) || !($r['success'] ?? false)) {
            @unlink($finalReal);
            $res['message'] = is_array($r) ? ($r['message'] ?? 'DB insert failed.') : 'DB insert failed.';
            $results[] = $res; $fail++; $sort++; continue;
        }

        $res['success'] = true;
        $res['path'] = $relativePath;
        $res['id'] = $r['id'] ?? null;
        $res['message'] = 'Uploaded';
        // also set as main image if products.product_image is empty
        set_product_main_image_ctr($product_id, $relativePath, false);
        $results[] = $res; $ok++; $sort++;
    }

    $all_ok = ($fail === 0 && $ok > 0);
    $status = $all_ok ? 200 : ($ok > 0 ? 200 : 400);

    http_response_code($status);
    echo json_encode([
        'success'  => $all_ok,
        'uploaded' => $results,
        'counts'   => ['ok' => $ok, 'fail' => $fail]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
