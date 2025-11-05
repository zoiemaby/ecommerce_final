<?php
/**
 * admin/actions/update_product_action.php
 * Updates a product using Product controller wrappers.
 * Expects POST: product_id (required), plus any fields to update:
 *   title?, cat_id?, brand_id?, price?, description?, keywords?
 * Returns JSON: { success: bool, message: string }
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../controllers/product_controller.php';
function json_out($a){ echo json_encode($a); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['status'=>'error','message'=>'Use POST']);

    $id = (int)($_POST['product_id'] ?? 0);
    if ($id <= 0) json_out(['status'=>'error','message'=>'Invalid product id.']);

    // Build update data from POST
    $data = [];
    if (isset($_POST['product_title'])) $data['product_title'] = trim($_POST['product_title']);
    if (isset($_POST['product_cat'])) $data['product_cat'] = (int)$_POST['product_cat'];
    if (isset($_POST['product_brand'])) $data['product_brand'] = (int)$_POST['product_brand'];
    if (isset($_POST['product_price'])) {
        $raw = (string)$_POST['product_price'];
        $clean = preg_replace('/[^\d\.\-]/', '', $raw);
        $data['product_price'] = (float)($clean === '' ? 0 : $clean);
    }
    if (isset($_POST['product_desc'])) $data['product_desc'] = (string)$_POST['product_desc'];
    if (isset($_POST['product_keywords'])) {
        $kw = $_POST['product_keywords'];
        if (is_array($kw)) $kw = implode(',', array_filter(array_map('trim', $kw)));
        $data['product_keywords'] = trim((string)$kw);
    }

    if (empty($data)) {
        json_out(['status'=>'error','message'=>'No fields to update']);
    }

    // Handle image uploads if present
    if (!empty($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        if ($uploadsRoot === false || !is_dir($uploadsRoot)) {
            json_out(['status'=>'error','message'=>'Uploads directory not found']);
        }

        $productDir = $uploadsRoot . DIRECTORY_SEPARATOR . 'p' . $id;
        if (!is_dir($productDir)) {
            if (!mkdir($productDir, 0755, true)) {
                json_out(['status'=>'error','message'=>'Failed to create product directory']);
            }
        }

        $allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
        $maxBytes = 5 * 1024 * 1024;

        // Normalize files array
        $files = [];
        if (is_array($_FILES['images']['name'])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name'     => $_FILES['images']['name'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'size'     => $_FILES['images']['size'][$i]
                    ];
                }
            }
        }

        $uploadedPath = null;
        foreach ($files as $idx => $file) {
            if ($file['size'] <= 0 || $file['size'] > $maxBytes) continue;

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!isset($allowed[$ext])) continue;

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if ($mime !== $allowed[$ext]) continue;

            $safeName = 'image_' . $idx . '.' . $ext;
            $targetPath = $productDir . DIRECTORY_SEPARATOR . $safeName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                @chmod($targetPath, 0644);
                $relativePath = 'uploads/p' . $id . '/' . $safeName;
                if ($uploadedPath === null) $uploadedPath = $relativePath; // First image
            }
        }

        // Update product_image if we uploaded a new one
        if ($uploadedPath !== null) {
            $data['product_image'] = $uploadedPath;
        }
    }

    // Update product
    $up = update_product_ctr($id, $data);
    if (!is_array($up) || empty($up['success'])) {
        json_out(['status'=>'error','message'=>$up['message'] ?? 'Update failed']);
    }

    json_out(['status'=>'success','message'=>$up['message'] ?? 'Product updated']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
