<?php
/**
 * empty_cart_action.php
 * Handles Empty/Clear Cart requests
 * Removes all items from the current user's cart
 * Works for both logged-in users and guests (session-based)
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

// Get current cart count before clearing
$cartCountBefore = get_cart_count_ctr($customerId);

// Check if cart is already empty
if ($cartCountBefore === 0) {
    respond(true, 'Cart is already empty', [
        'items_removed' => 0,
        'cart_count' => 0,
        'cart_is_empty' => true,
        'cart_summary' => [
            'total_items' => 0,
            'total_quantity' => 0,
            'total_price' => 0.00
        ]
    ], 200);
}

try {
    // Empty the cart
    $result = empty_cart_ctr($customerId);
    
    if ($result === false) {
        respond(false, 'Failed to empty cart. Please try again.', null, 500);
    }
    
    // Verify cart is empty
    $cartCountAfter = get_cart_count_ctr($customerId);
    $isEmpty = is_cart_empty_ctr($customerId);
    
    // Get updated cart summary (should be all zeros)
    $cartSummary = get_cart_summary_ctr($customerId);
    
    // Build success message
    $itemsRemoved = $cartCountBefore - $cartCountAfter;
    $message = "Cart cleared successfully";
    
    if ($itemsRemoved > 0) {
        $message .= ". {$itemsRemoved} item" . ($itemsRemoved !== 1 ? 's' : '') . " removed.";
    }
    
    // Build response data
    $responseData = [
        'items_removed' => $itemsRemoved,
        'cart_count' => $cartCountAfter,
        'cart_is_empty' => $isEmpty,
        'cart_summary' => $cartSummary
    ];
    
    respond(true, $message, $responseData, 200);
    
} catch (Exception $e) {
    error_log('empty_cart_action.php - Exception: ' . $e->getMessage());
    respond(false, 'An error occurred while emptying cart', null, 500);
}
?>
