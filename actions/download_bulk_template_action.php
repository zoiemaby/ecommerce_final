<?php

/**
 * actions/download_bulk_template_action.php
 * 
 * Generates a ZIP file containing:
 * - products_template.csv with current categories and brands
 * - sample_image.jpg (a placeholder image)
 * 
 * CSV columns: product_title, product_price, product_cat, product_brand, product_desc, product_keywords, product_image
 * Categories and brands are included as comments in the CSV for reference
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../controllers/category_controller.php';
require_once __DIR__ . '/../controllers/brand_controller.php';

// Track created temp paths for cleanup on failure
$tempDir = null;
$zipPath = null;

try {
    // Fetch current categories and brands
    $categories = list_categories_ctr(1000, 0);
    $brands = function_exists('list_brands_ctr')
        ? list_brands_ctr(null, null, 'name_asc')
        : [];

    // If brand controller doesn't work, try Brand class directly
    if (empty($brands) && class_exists('Brand')) {
        require_once __DIR__ . '/../classes/brand_class.php';
        $b = new Brand();
        $brands = $b->listBrands(null, null, 'name_asc');
    }

    // Create temp directory within uploads folder (only place we can create folders)
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadsRoot === false || !is_dir($uploadsRoot)) {
        @mkdir(__DIR__ . '/../uploads', 0755, true);
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
    }

    $tempDir = $uploadsRoot . DIRECTORY_SEPARATOR . 'temp_template_' . uniqid();
    if (!mkdir($tempDir, 0755, true)) {
        throw new Exception('Failed to create temporary directory');
    }

    // ========== CREATE CSV FILE ==========
    $csvPath = $tempDir . '/products_template.csv';
    $csvHandle = fopen($csvPath, 'w');

    if (!$csvHandle) {
        throw new Exception('Failed to create CSV file');
    }

    // Write header comments with categories and brands
    fwrite($csvHandle, "# BULK PRODUCT UPLOAD TEMPLATE\n");
    fwrite($csvHandle, "# Generated on: " . date('Y-m-d H:i:s') . "\n");
    fwrite($csvHandle, "#\n");
    fwrite($csvHandle, "# INSTRUCTIONS:\n");
    fwrite($csvHandle, "# 1. Fill in product details in the rows below\n");
    fwrite($csvHandle, "# 2. Use category and brand IDs from the lists below\n");
    fwrite($csvHandle, "# 3. Place product images in the same folder as this CSV\n");
    fwrite($csvHandle, "# 4. Reference images using their filename (e.g. product1.jpg)\n");
    fwrite($csvHandle, "# 5. Compress this CSV and images into a ZIP file\n");
    fwrite($csvHandle, "# 6. Upload the ZIP file using the bulk upload feature\n");
    fwrite($csvHandle, "#\n");
    fwrite($csvHandle, "# AVAILABLE CATEGORIES:\n");

    if (!empty($categories)) {
        foreach ($categories as $cat) {
            $catId = $cat['cat_id'] ?? $cat['category_id'] ?? '';
            $catName = $cat['cat_name'] ?? $cat['category_name'] ?? '';
            if ($catId && $catName) {
                fwrite($csvHandle, "# - ID: $catId | Name: $catName\n");
            }
        }
    } else {
        fwrite($csvHandle, "# - No categories found. Please create categories first.\n");
    }

    fwrite($csvHandle, "#\n");
    fwrite($csvHandle, "# AVAILABLE BRANDS:\n");

    if (!empty($brands)) {
        foreach ($brands as $brand) {
            $brandId = $brand['brand_id'] ?? $brand['id'] ?? '';
            $brandName = $brand['brand_name'] ?? $brand['name'] ?? '';
            if ($brandId && $brandName) {
                fwrite($csvHandle, "# - ID: $brandId | Name: $brandName\n");
            }
        }
    } else {
        fwrite($csvHandle, "# - No brands found. Please create brands first.\n");
    }

    fwrite($csvHandle, "#\n");
    fwrite($csvHandle, "# CSV FORMAT:\n");
    fwrite($csvHandle, "# - All fields except product_desc and product_keywords are REQUIRED\n");
    fwrite($csvHandle, "# - product_price should be a number (e.g. 99.99)\n");
    fwrite($csvHandle, "# - product_image should be the filename of an image in this ZIP (e.g. sample_image.jpg)\n");
    fwrite($csvHandle, "# - product_keywords can be comma-separated (e.g. electronics /smartphone/ 5G)\n");
    fwrite($csvHandle, "#\n\n");

    // Write CSV header
    fputcsv($csvHandle, [
        'product_title',
        'product_price',
        'product_cat',
        'product_brand',
        'product_desc',
        'product_keywords',
        'product_image'
    ]);

    // Write sample rows
    $sampleCatId = !empty($categories) ? ($categories[0]['cat_id'] ?? $categories[0]['category_id'] ?? '1') : '1';
    $sampleBrandId = !empty($brands) ? ($brands[0]['brand_id'] ?? $brands[0]['id'] ?? '1') : '1';

    fputcsv($csvHandle, [
        'Sample Product 1',
        '99.99',
        $sampleCatId,
        $sampleBrandId,
        'This is a sample product description. Replace with your actual product details.',
        'sample,product,example',
        'product1.jpg'
    ]);

    fputcsv($csvHandle, [
        'Sample Product 2',
        '149.99',
        $sampleCatId,
        $sampleBrandId,
        'Another sample product for demonstration purposes.',
        'demo,test,product',
        'product2.jpg'
    ]);

    fclose($csvHandle);

    // ========== CREATE SAMPLE IMAGE PLACEHOLDER ==========
    // Create a simple text file explaining to replace with actual image
    // (Avoids dependency on GD library)
    $imagePath = $tempDir . '/sample_image.txt';
    $imageNote = "SAMPLE IMAGE PLACEHOLDER\n\n";
    $imageNote .= "Replace this file with your actual product image.\n\n";
    $imageNote .= "Supported formats: JPG, PNG, GIF, WEBP\n";
    $imageNote .= "Maximum size: 5MB per image\n";
    $imageNote .= "Recommended dimensions: 300x225px or higher\n\n";
    $imageNote .= "Example filenames:\n";
    $imageNote .= "- product1.jpg\n";
    $imageNote .= "- laptop_image.png\n";
    $imageNote .= "- phone_photo.webp\n\n";
    $imageNote .= "Reference the actual filename in your CSV's product_image column.";

    file_put_contents($imagePath, $imageNote);

    // ========== CREATE ZIP FILE ==========
    $zipPath = $uploadsRoot . DIRECTORY_SEPARATOR . 'bulk_product_template_' . date('Ymd_His') . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Failed to create ZIP file');
    }

    // Add CSV and placeholder to ZIP
    $zip->addFile($csvPath, 'products_template.csv');
    $zip->addFile($imagePath, 'sample_image.txt');

    // Add README
    $readme = "BULK PRODUCT UPLOAD - README\n\n";
    $readme .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $readme .= "INSTRUCTIONS:\n";
    $readme .= "1. Open products_template.csv\n";
    $readme .= "2. Review the available categories and brands in the comments\n";
    $readme .= "3. Fill in your product details\n";
    $readme .= "4. Add your product images to this folder\n";
    $readme .= "5. Update the product_image column with the correct filenames\n";
    $readme .= "6. Delete the sample rows\n";
    $readme .= "7. Compress this entire folder as a ZIP file\n";
    $readme .= "8. Upload using the bulk upload feature\n\n";
    $readme .= "NOTES:\n";
    $readme .= "- Images must be JPG, PNG, GIF, or WEBP format\n";
    $readme .= "- Maximum file size per image: 5MB\n";
    $readme .= "- All required fields must be filled\n";
    $readme .= "- Invalid rows will be skipped with error messages\n";

    $zip->addFromString('README.txt', $readme);

    $zip->close();

    // Clean up temp files
    @unlink($csvPath);
    @unlink($imagePath);
    @rmdir($tempDir);

    // ========== SEND ZIP TO CLIENT ==========
    if (!file_exists($zipPath)) {
        throw new Exception('ZIP file was not created');
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="bulk_product_template_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    readfile($zipPath);

    // Clean up ZIP file
    @unlink($zipPath);
    exit;
} catch (Exception $e) {
    error_log('Template generation error: ' . $e->getMessage());
    // Cleanup any temp files created before error
    if (!empty($tempDir) && is_dir($tempDir)) {
        // remove files inside then the dir
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) { @rmdir($file->getPathname()); }
            else { @unlink($file->getPathname()); }
        }
        @rmdir($tempDir);
    }
    if (!empty($zipPath) && file_exists($zipPath)) {
        @unlink($zipPath);
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate template: ' . $e->getMessage()
    ]);
    exit;
}
