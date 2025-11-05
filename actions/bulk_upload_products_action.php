<?php
/**
 * actions/bulk_upload_products_action.php
 * 
 * Processes a ZIP file containing:
 * - products_template.csv (or any CSV with the expected columns)
 * - Product images referenced in the CSV
 * 
 * Expected CSV columns: product_title, product_price, product_cat, product_brand, product_desc, product_keywords, product_image
 * 
 * Process:
 * 1. Extract ZIP to temp directory
 * 2. Find and parse CSV file
 * 3. Validate each row
 * 4. Create products and move images to uploads/p{product_id}/
 * 5. Return detailed results (success count, errors, warnings)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/product_controller.php';

// Track temp directories for guaranteed cleanup on any exit
$GLOBALS['__TEMP_DIRS'] = [];

function rrmdir_safe($dir) {
    if (!$dir || !is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
    @rmdir($dir);
}

function respond($status, $message, $data = []) {
    // Cleanup any temp directories we created
    if (!empty($GLOBALS['__TEMP_DIRS'])) {
        foreach ($GLOBALS['__TEMP_DIRS'] as $d) {
            rrmdir_safe($d);
        }
        $GLOBALS['__TEMP_DIRS'] = [];
    }
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit;
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['bulk_file']) || $_FILES['bulk_file']['error'] !== UPLOAD_ERR_OK) {
        respond('error', 'No file uploaded or upload error occurred');
    }
    
    $uploadedFile = $_FILES['bulk_file'];
    
    // Validate file type (ZIP)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($uploadedFile['tmp_name']);
    
    $allowedMimes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'];
    if (!in_array($mimeType, $allowedMimes) && pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'zip') {
        respond('error', 'Invalid file type. Please upload a ZIP file.');
    }
    
    // Create temp directory within uploads folder (only place we can create folders)
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadsRoot === false || !is_dir($uploadsRoot)) {
        @mkdir(__DIR__ . '/../uploads', 0755, true);
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
    }
    
    $extractDir = $uploadsRoot . DIRECTORY_SEPARATOR . 'temp_extract_' . uniqid();
    if (!mkdir($extractDir, 0755, true)) {
        respond('error', 'Failed to create extraction directory');
    }
    // track for cleanup
    $GLOBALS['__TEMP_DIRS'][] = $extractDir;
    
    // Extract ZIP
    $zip = new ZipArchive();
    if ($zip->open($uploadedFile['tmp_name']) !== true) {
        respond('error', 'Failed to open ZIP file');
    }
    
    $zip->extractTo($extractDir);
    $zip->close();
    
    // Find CSV file in extracted contents
    $csvFile = null;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'csv') {
            $csvFile = $file->getPathname();
            break;
        }
    }
    
    if (!$csvFile) {
        respond('error', 'No CSV file found in ZIP archive');
    }
    
    // Parse CSV
    $csvHandle = fopen($csvFile, 'r');
    if (!$csvHandle) {
        respond('error', 'Failed to open CSV file');
    }
    
    $results = [
        'success_count' => 0,
        'error_count' => 0,
        'warnings' => [],
        'errors' => [],
        'created_products' => []
    ];
    
    $header = null;
    $rowNumber = 0;
    $expectedColumns = ['product_title', 'product_price', 'product_cat', 'product_brand', 'product_desc', 'product_keywords', 'product_image'];
    
    while (($row = fgetcsv($csvHandle)) !== false) {
        $rowNumber++;
        
        // Skip comment lines and empty rows
        if (empty($row) || (isset($row[0]) && (strpos(trim($row[0]), '#') === 0 || trim($row[0]) === ''))) {
            continue;
        }
        
        // Look for header row (contains all expected columns)
        if ($header === null) {
            $trimmedRow = array_map('trim', $row);
            
            // Check if this row contains the expected column headers
            $hasAllColumns = true;
            foreach ($expectedColumns as $col) {
                if (!in_array($col, $trimmedRow)) {
                    $hasAllColumns = false;
                    break;
                }
            }
            
            // If this row has all expected columns, it's our header
            if ($hasAllColumns) {
                $header = $trimmedRow;
                continue;
            } else {
                // Not a header row, skip it (probably instruction text)
                continue;
            }
        }
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Create associative array
        $data = array_combine($header, $row);
        
        // Validate required fields
        $title = trim($data['product_title'] ?? '');
        $price = trim($data['product_price'] ?? '');
        $catId = trim($data['product_cat'] ?? '');
        $brandId = trim($data['product_brand'] ?? '');
        $imageName = trim($data['product_image'] ?? '');
        
        if (empty($title) || empty($price) || empty($catId) || empty($brandId) || empty($imageName)) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber: Missing required fields (title, price, category, brand, or image)";
            continue;
        }
        
        // Validate numeric fields
        if (!is_numeric($price) || floatval($price) <= 0) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Invalid price '$price'";
            continue;
        }
        
        if (!is_numeric($catId) || intval($catId) <= 0) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Invalid category ID '$catId'";
            continue;
        }
        
        if (!is_numeric($brandId) || intval($brandId) <= 0) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Invalid brand ID '$brandId'";
            continue;
        }
        
        // Find image file - look in the same directory as the CSV file first
        $imageSourcePath = null;
        $csvDir = dirname($csvFile);
        
        // First, try to find in the same directory as CSV
        $possiblePath = $csvDir . DIRECTORY_SEPARATOR . $imageName;
        if (file_exists($possiblePath) && is_file($possiblePath)) {
            $imageSourcePath = $possiblePath;
        } else {
            // If not found, try basename only (in case path was provided)
            $imageBasename = basename($imageName);
            $possiblePath = $csvDir . DIRECTORY_SEPARATOR . $imageBasename;
            if (file_exists($possiblePath) && is_file($possiblePath)) {
                $imageSourcePath = $possiblePath;
            } else {
                // Last resort: search recursively in extracted directory
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getFilename() === $imageBasename) {
                        $imageSourcePath = $file->getPathname();
                        break;
                    }
                }
            }
        }
        
        if (!$imageSourcePath || !file_exists($imageSourcePath)) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Image file '$imageName' not found in ZIP (looked in CSV directory: $csvDir)";
            continue;
        }
        
        // Validate image file
        $imageInfo = @getimagesize($imageSourcePath);
        if ($imageInfo === false) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Invalid image file '$imageName'";
            continue;
        }
        
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedImageTypes)) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Unsupported image type for '$imageName'";
            continue;
        }
        
        // Check image size (max 5MB)
        if (filesize($imageSourcePath) > 5 * 1024 * 1024) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Image '$imageName' exceeds 5MB limit";
            continue;
        }
        
        // Create product (without image first)
        $productData = [
            'product_title' => $title,
            'product_price' => floatval($price),
            'product_cat' => intval($catId),
            'product_brand' => intval($brandId),
            'product_desc' => trim($data['product_desc'] ?? ''),
            'product_keywords' => trim($data['product_keywords'] ?? ''),
            'product_image' => ''
        ];
        
        $createResult = add_product_ctr($productData);
        
        if (empty($createResult['success'])) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Failed to create product - " . ($createResult['message'] ?? 'Unknown error');
            continue;
        }
        
        $productId = $createResult['data']['product_id'] ?? 0;
        if ($productId <= 0) {
            $results['error_count']++;
            $results['errors'][] = "Row $rowNumber ($title): Invalid product ID returned";
            continue;
        }
        
        // Create product image directory (uploads root already set above)
        $productImageDir = $uploadsRoot . DIRECTORY_SEPARATOR . 'p' . $productId;
        if (!is_dir($productImageDir)) {
            if (!mkdir($productImageDir, 0755, true)) {
                $results['warnings'][] = "Row $rowNumber ($title): Product created but failed to create image directory";
                $results['success_count']++;
                continue;
            }
        }
        
        // Copy image to product directory
        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $newImageName = 'image_0.' . $ext;
        $targetImagePath = $productImageDir . DIRECTORY_SEPARATOR . $newImageName;
        
        if (!copy($imageSourcePath, $targetImagePath)) {
            $results['warnings'][] = "Row $rowNumber ($title): Product created but failed to copy image";
            $results['success_count']++;
            continue;
        }
        
        @chmod($targetImagePath, 0644);
        
        // Update product with image path
        $relativePath = 'uploads/p' . $productId . '/' . $newImageName;
        set_product_main_image_ctr($productId, $relativePath, true);
        
        $results['success_count']++;
        $results['created_products'][] = [
            'id' => $productId,
            'title' => $title,
            'row' => $rowNumber
        ];
    }
    
    fclose($csvHandle);
    
    // Clean up extracted files (safe, will also run in respond())
    rrmdir_safe($extractDir);
    // remove from tracked list to avoid double work
    $GLOBALS['__TEMP_DIRS'] = array_filter($GLOBALS['__TEMP_DIRS'], function($d) use ($extractDir){ return $d !== $extractDir; });
    
    // Prepare response message
    $message = "Bulk upload completed: {$results['success_count']} products created";
    if ($results['error_count'] > 0) {
        $message .= ", {$results['error_count']} errors";
    }
    if (!empty($results['warnings'])) {
        $message .= ", " . count($results['warnings']) . " warnings";
    }
    
    respond('success', $message, $results);
    
} catch (Exception $e) {
    error_log('Bulk upload error: ' . $e->getMessage());
    respond('error', 'Server error: ' . $e->getMessage());
}
