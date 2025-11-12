<?php
/**
 * update_quantity_action.php
 * Handles Update Cart Quantity requests
 * Receives product ID and new quantity, validates user session, and updates cart
 * If quantity is 0 or negative, removes item from cart
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

// Accept both POST and PUT requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    respond(false, 'Invalid request method. Only POST or PUT allowed.', null, 405);
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

// Extract and validate new quantity
$newQuantity = isset($input['quantity']) ? (int)$input['quantity'] : -1;

// Support 'qty' as alternative parameter name
if ($newQuantity < 0 && isset($input['qty'])) {
    $newQuantity = (int)$input['qty'];
}

if ($newQuantity < 0) {
    respond(false, 'Quantity is required', null, 400);
}

// Optional: Set maximum quantity
$maxQuantity = 100;
if ($newQuantity > $maxQuantity) {
    respond(false, "Quantity cannot exceed {$maxQuantity}", null, 400);
}

// Check if item exists in cart
$existingItem = get_cart_item_ctr($productId, $customerId);

if (!$existingItem) {
    respond(false, 'Item not found in cart', null, 404);
}

// Store product details for response
$productTitle = $existingItem['product_title'] ?? 'Product';
$oldQuantity = $existingItem['qty'] ?? 0;

try {
    // Update quantity (will remove if quantity is 0)
    $result = update_cart_quantity_ctr($productId, $customerId, $newQuantity);
    
    if ($result === false) {
        respond(false, 'Failed to update cart quantity. Please try again.', null, 500);
    }
    
    // Get updated cart count
    $cartCount = get_cart_count_ctr($customerId);
    
    // Get updated cart summary
    $cartSummary = get_cart_summary_ctr($customerId);
    
    // Check if cart is empty (in case quantity was 0)
    $isEmpty = is_cart_empty_ctr($customerId);
    
    // Build success message
    if ($newQuantity === 0) {
        $message = "{$productTitle} removed from cart";
        $action = 'removed';
    } elseif ($newQuantity > $oldQuantity) {
        $message = "Quantity increased to {$newQuantity}";
        $action = 'increased';
    } elseif ($newQuantity < $oldQuantity) {
        $message = "Quantity decreased to {$newQuantity}";
        $action = 'decreased';
    } else {
        $message = "Quantity unchanged ({$newQuantity})";
        $action = 'unchanged';
    }
    
    // Get updated item details if not removed
    $updatedItem = null;
    if ($newQuantity > 0) {
        $updatedItem = get_cart_item_ctr($productId, $customerId);
    }
    
    // Build response data
    $responseData = [
        'product_id' => $productId,
        'product_title' => $productTitle,
        'old_quantity' => $oldQuantity,
        'new_quantity' => $newQuantity,
        'action' => $action,
        'cart_count' => $cartCount,
        'cart_is_empty' => $isEmpty,
        'cart_summary' => $cartSummary,
        'updated_item' => $updatedItem
    ];
    
    respond(true, $message, $responseData, 200);
    
} catch (Exception $e) {
    error_log('update_quantity_action.php - Exception: ' . $e->getMessage());
    respond(false, 'An error occurred while updating quantity', null, 500);
}
?>
