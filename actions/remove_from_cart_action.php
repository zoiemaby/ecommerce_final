<?php
/**
 * remove_from_cart_action.php
 * Handles Remove from Cart requests
 * Receives product ID, validates user session, and removes item from cart
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
    respond(false, 'Please login to manage your cart', null, 401);
}

// Get customer ID from session (check both for backwards compatibility)
$customerId = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : ($_SESSION['user_id'] ?? null);

if (!$customerId || $customerId <= 0) {
    respond(false, 'Invalid user session. Please login again.', null, 401);
}

// Accept both POST and DELETE requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    respond(false, 'Invalid request method. Only POST or DELETE allowed.', null, 405);
}

// Get input data (support both form-data and JSON)
$input = $_POST;
if (empty($input)) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

// Extract and validate product ID
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;

// Support 'p_id' as alternative parameter name
if ($productId <= 0 && isset($input['p_id'])) {
    $productId = (int)$input['p_id'];
}

// Validate product ID
if ($productId <= 0) {
    respond(false, 'Invalid product ID', null, 400);
}

// Check if item exists in cart before attempting removal
$existingItem = get_cart_item_ctr($productId, $customerId);

if (!$existingItem) {
    respond(false, 'Item not found in cart', null, 404);
}

// Store product details for response message
$productTitle = $existingItem['product_title'] ?? 'Product';
$removedQuantity = $existingItem['qty'] ?? 0;

try {
    // Remove item from cart
    $result = remove_from_cart_ctr($productId, $customerId);
    
    if ($result === false) {
        respond(false, 'Failed to remove item from cart. Please try again.', null, 500);
    }
    
    // Get updated cart count
    $cartCount = get_cart_count_ctr($customerId);
    
    // Get updated cart summary
    $cartSummary = get_cart_summary_ctr($customerId);
    
    // Check if cart is now empty
    $isEmpty = is_cart_empty_ctr($customerId);
    
    // Build success message
    $message = "{$productTitle} removed from cart";
    if ($isEmpty) {
        $message .= ". Your cart is now empty.";
    }
    
    // Build response data
    $responseData = [
        'product_id' => $productId,
        'product_title' => $productTitle,
        'removed_quantity' => $removedQuantity,
        'cart_count' => $cartCount,
        'cart_is_empty' => $isEmpty,
        'cart_summary' => $cartSummary
    ];
    
    respond(true, $message, $responseData, 200);
    
} catch (Exception $e) {
    error_log('remove_from_cart_action.php - Exception: ' . $e->getMessage());
    respond(false, 'An error occurred while removing item from cart', null, 500);
}
?>
