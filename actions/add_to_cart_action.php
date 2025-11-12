<?php
/**
 * add_to_cart_action.php
 * Handles Add to Cart requests
 * Receives product details, validates user session, and adds items to cart
 * Returns JSON response for AJAX calls
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to client, log them instead

// Set JSON header
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

/**
 * Send JSON response and exit
 */
function respond($success, $message, $data = null, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    respond(false, 'Please login to add items to cart', null, 401);
}

// Get customer ID from session (check both for backwards compatibility)
$customerId = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : ($_SESSION['user_id'] ?? null);

if (!$customerId || $customerId <= 0) {
    respond(false, 'Invalid user session. Please login again.', null, 401);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method. Only POST allowed.', null, 405);
}

// Get input data (support both form-data and JSON)
$input = $_POST;
if (empty($input)) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

// Extract and validate product details
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

// Debug log incoming request parameters and session state
error_log('add_to_cart_action.php - Incoming add request: productId=' . $productId . ' quantity=' . $quantity . ' customerId=' . ($customerId ?? 'NULL') . ' session_id=' . session_id());

// Validate product ID
if ($productId <= 0) {
    respond(false, 'Invalid product ID', null, 400);
}

// Validate quantity
if ($quantity <= 0) {
    respond(false, 'Quantity must be greater than 0', null, 400);
}

// Optional: Set maximum quantity per add action
$maxQuantity = 100;
if ($quantity > $maxQuantity) {
    respond(false, "Cannot add more than {$maxQuantity} items at once", null, 400);
}

// Optional: Verify product exists and is available (if product_controller available)
if (function_exists('view_single_product_ctr')) {
    try {
        $productData = view_single_product_ctr($productId);
        
        if (!$productData || (is_array($productData) && !$productData['success'])) {
            respond(false, 'Product not found or unavailable', null, 404);
        }
        
        // Extract product details for response
        $productInfo = null;
        if (is_array($productData) && isset($productData['data'][0])) {
            $productInfo = $productData['data'][0];
        } elseif (is_array($productData) && isset($productData['product_title'])) {
            $productInfo = $productData;
        }
    }
}

// Check if product already exists in cart
$existingItem = product_exists_in_cart_ctr($customerId, $productId);

try {
    // Add to cart (will increment if exists, or create new entry)
    $result = add_to_cart_ctr($productId, $customerId, $quantity);
    
    if ($result === false) {
        // Enhanced diagnostics to help identify live server failure
        $dbError = null;
        // We cannot access protected connection property directly; rely on mysqli_ping via global connection if available
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            if (!@mysqli_ping($GLOBALS['conn'])) {
                $dbError = 'Ping failed: ' . $GLOBALS['conn']->error;
            } else {
                $dbError = $GLOBALS['conn']->error ?: null;
            }
        }

        // Check if product already exists (could indicate updateCartQuantity failure)
        $preExisting = product_exists_in_cart_ctr($customerId, $productId);
        $existsFlag = $preExisting ? 'YES qty=' . ($preExisting['qty'] ?? '?') : 'NO';

        error_log('add_to_cart_action.php - ADD FAILURE productId=' . $productId . ' customerId=' . $customerId . ' quantity=' . $quantity . ' exists=' . $existsFlag . ' dbError=' . ($dbError ?: 'NONE'));

        respond(false, 'Failed to add item to cart. Please try again.', [
            'diagnostic' => [
                'product_id' => $productId,
                'customer_id' => $customerId,
                'quantity' => $quantity,
                'already_existed' => (bool)$preExisting,
                'db_error' => $dbError,
                'session_id' => session_id()
            ]
        ], 500);
    }
    
    // Get updated cart count
    $cartCount = get_cart_count_ctr($customerId);
    
    // Get cart summary for total
    $cartSummary = get_cart_summary_ctr($customerId);
    
    // Build success message
    if ($existingItem) {
        $newQty = (int)$existingItem['qty'] + $quantity;
        $message = "Cart updated! Quantity increased to {$newQty}";
    } else {
        $message = "Product added to cart successfully!";
    }
    
    // Build response data
    $responseData = [
        'cart_id' => $result,
        'product_id' => $productId,
        'quantity_added' => $quantity,
        'was_existing' => $existingItem !== false,
        'cart_count' => $cartCount,
        'cart_summary' => $cartSummary
    ];
    
    // Add product info if available
    if (isset($productInfo)) {
        $responseData['product'] = [
            'title' => $productInfo['product_title'] ?? 'Product',
            'price' => $productInfo['product_price'] ?? 0,
            'image' => $productInfo['product_image'] ?? null
        ];
    }
    
    respond(true, $message, $responseData, 200);
    
}
?>
