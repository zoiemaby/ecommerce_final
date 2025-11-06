<?php
/**
 * product_actions.php
 * Comprehensive product action handler for all product operations
 * Handles: view all, search, filter by category, filter by brand, view single
 * Uses controller for all business logic
 */

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/product_controller.php';
require_once __DIR__ . '/../controllers/category_controller.php';
require_once __DIR__ . '/../controllers/brand_controller.php';

// Helper function for JSON responses
function respond(bool $success, $data = null, string $message = '', int $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit;
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Pagination parameters
$page = max(1, (int)($_GET['page'] ?? $_POST['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? $_POST['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

try {
    switch ($action) {
        
        // VIEW ALL PRODUCTS
        case 'view_all':
        case 'list':
        case 'all':
            $result = view_all_products_ctr($limit, $offset);
            if ($result['success']) {
                $total = $result['total'] ?? 0;
                $totalPages = ceil($total / $limit);
                respond(true, [
                    'products' => $result['data'],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ]
                ]);
            } else {
                respond(false, null, $result['message'] ?? 'Failed to fetch products', 500);
            }
            break;

        // SEARCH PRODUCTS
        case 'search':
            $query = trim($_GET['q'] ?? $_POST['q'] ?? $_GET['query'] ?? $_POST['query'] ?? '');
            if ($query === '') {
                respond(false, null, 'Search query is required', 400);
            }
            
            $result = search_products_ctr($query, $limit, $offset);
            if ($result['success']) {
                $total = $result['total'] ?? 0;
                $totalPages = ceil($total / $limit);
                respond(true, [
                    'products' => $result['data'],
                    'query' => $result['query'] ?? $query,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ]
                ]);
            } else {
                respond(false, null, $result['message'] ?? 'Search failed', 500);
            }
            break;

        // FILTER BY CATEGORY
        case 'filter_category':
        case 'category':
            $catId = (int)($_GET['cat_id'] ?? $_POST['cat_id'] ?? $_GET['category_id'] ?? $_POST['category_id'] ?? 0);
            if ($catId <= 0) {
                respond(false, null, 'Valid category ID is required', 400);
            }
            
            $result = filter_products_by_category_ctr($catId, $limit, $offset);
            if ($result['success']) {
                $total = $result['total'] ?? 0;
                $totalPages = ceil($total / $limit);
                respond(true, [
                    'products' => $result['data'],
                    'category_id' => $result['category_id'] ?? $catId,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ]
                ]);
            } else {
                respond(false, null, $result['message'] ?? 'Filter failed', 500);
            }
            break;

        // FILTER BY BRAND
        case 'filter_brand':
        case 'brand':
            $brandId = (int)($_GET['brand_id'] ?? $_POST['brand_id'] ?? 0);
            if ($brandId <= 0) {
                respond(false, null, 'Valid brand ID is required', 400);
            }
            
            $result = filter_products_by_brand_ctr($brandId, $limit, $offset);
            if ($result['success']) {
                $total = $result['total'] ?? 0;
                $totalPages = ceil($total / $limit);
                respond(true, [
                    'products' => $result['data'],
                    'brand_id' => $result['brand_id'] ?? $brandId,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ]
                ]);
            } else {
                respond(false, null, $result['message'] ?? 'Filter failed', 500);
            }
            break;

        // VIEW SINGLE PRODUCT
        case 'view_single':
        case 'single':
        case 'detail':
            $productId = (int)($_GET['id'] ?? $_POST['id'] ?? $_GET['product_id'] ?? $_POST['product_id'] ?? 0);
            if ($productId <= 0) {
                respond(false, null, 'Valid product ID is required', 400);
            }
            
            $result = view_single_product_ctr($productId);
            if ($result['success']) {
                respond(true, $result['data']);
            } else {
                respond(false, null, $result['message'] ?? 'Product not found', 404);
            }
            break;

        // GET CATEGORIES (helper for dropdowns)
        case 'get_categories':
        case 'categories':
            $categories = list_categories_ctr();
            respond(true, $categories);
            break;

        // GET BRANDS (helper for dropdowns)
        case 'get_brands':
        case 'brands':
            $catFilter = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : null;
            $brands = $catFilter 
                ? list_brands_ctr($catFilter)
                : list_brands_ctr();
            respond(true, $brands);
            break;

        // COMBINED FILTER (category + brand + search)
        case 'filter':
            $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
            $catId = isset($_GET['cat_id']) && $_GET['cat_id'] !== '' ? (int)$_GET['cat_id'] : null;
            $brandId = isset($_GET['brand_id']) && $_GET['brand_id'] !== '' ? (int)$_GET['brand_id'] : null;
            
            // Start with all or search
            if ($query !== '') {
                $result = search_products_ctr($query, 1000, 0); // Get all matching
            } else {
                $result = view_all_products_ctr(1000, 0); // Get all
            }
            
            if ($result['success']) {
                $products = $result['data'];
                
                // Apply category filter
                if ($catId !== null && $catId > 0) {
                    $products = array_filter($products, function($p) use ($catId) {
                        return (int)$p['product_cat'] === $catId;
                    });
                }
                
                // Apply brand filter
                if ($brandId !== null && $brandId > 0) {
                    $products = array_filter($products, function($p) use ($brandId) {
                        return (int)$p['product_brand'] === $brandId;
                    });
                }
                
                // Re-index array
                $products = array_values($products);
                $total = count($products);
                
                // Apply pagination
                $products = array_slice($products, $offset, $limit);
                $totalPages = ceil($total / $limit);
                
                respond(true, [
                    'products' => $products,
                    'filters' => [
                        'query' => $query,
                        'category_id' => $catId,
                        'brand_id' => $brandId
                    ],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ]
                ]);
            } else {
                respond(false, null, $result['message'] ?? 'Filter failed', 500);
            }
            break;

        default:
            respond(false, null, 'Invalid action. Available actions: view_all, search, filter_category, filter_brand, view_single, categories, brands, filter', 400);
    }
    
} catch (Exception $e) {
    error_log('product_actions.php error: ' . $e->getMessage());
    respond(false, null, 'Server error: ' . $e->getMessage(), 500);
}
