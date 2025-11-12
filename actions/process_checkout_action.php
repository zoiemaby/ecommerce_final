<?php
/**
 * process_checkout_action.php
 * Handles backend processing of checkout flow after payment confirmation
 * Does NOT handle UI/modal logic - that's handled by checkout.js and view layer
 * 
 * Workflow:
 * 1. Receive request from checkout.js after user confirms payment
 * 2. Validate user session and cart
 * 3. Generate unique order reference
 * 4. Invoke controllers to move cart items to orders/orderdetails/payment tables
 * 5. Empty customer's cart
 * 6. Return structured JSON response
 * 
 * Uses: product_controller, cart_controller, order_controller
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
require_once __DIR__ . '/../controllers/product_controller.php';
require_once __DIR__ . '/../controllers/order_controller.php';

/**
 * Send JSON response and exit
 */
function respond($success, $message, $data = null, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'status' => $success ? 'success' : 'error',
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

/**
 * Generate unique order reference
 * Format: ORD-YYYYMMDD-HHMMSS-RANDOM
 */
function generateOrderReference()
{
    $datePart = date('Ymd-His');
    $randomPart = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    return "ORD-{$datePart}-{$randomPart}";
}

// Check if user is logged in
if (!isLoggedIn()) {
    respond(false, 'Please login to complete checkout', null, 401);
}

// Get customer ID from session (check both for backwards compatibility)
$customerId = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : ($_SESSION['user_id'] ?? null);

// DEBUG: Log session data
error_log('process_checkout_action.php - Session data: ' . print_r($_SESSION, true));
error_log('process_checkout_action.php - Customer ID: ' . ($customerId ?? 'NULL'));

if (!$customerId || $customerId <= 0) {
    respond(false, 'Invalid user session. Please login again.', [
        'session_debug' => [
            'has_customer_id' => isset($_SESSION['customer_id']),
            'has_user_id' => isset($_SESSION['user_id']),
            'customer_id_value' => $_SESSION['customer_id'] ?? null,
            'user_id_value' => $_SESSION['user_id'] ?? null
        ]
    ], 401);
}

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method. Only POST allowed.', null, 405);
}

// Get input data (support both form-data and JSON)
$input = $_POST;
if (empty($input)) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

// Extract payment details from simulated payment modal
$invoiceNo = $input['invoice_no'] ?? null;
$currency = $input['currency'] ?? 'GHS';
$paymentMethod = $input['payment_method'] ?? 'simulated';

// Validate invoice number if provided
if ($invoiceNo && !preg_match('/^[A-Z0-9-]+$/', $invoiceNo)) {
    respond(false, 'Invalid invoice number format', null, 400);
}

try {
    // Step 1: Check if cart is empty
    error_log('process_checkout_action.php - Checking if cart is empty for customer ID: ' . $customerId);
    $isEmpty = is_cart_empty_ctr($customerId);
    error_log('process_checkout_action.php - is_cart_empty_ctr returned: ' . ($isEmpty ? 'TRUE (empty)' : 'FALSE (has items)'));
    
    if ($isEmpty) {
        // Additional debug: Try to get cart count
        $cartCount = get_cart_count_ctr($customerId);
        error_log('process_checkout_action.php - Cart count: ' . $cartCount);
        
        respond(false, 'Your cart is empty. Please add items before checkout.', [
            'cart_is_empty' => true,
            'customer_id' => $customerId,
            'cart_count' => $cartCount,
            'debug_info' => 'Cart appears empty for customer ID ' . $customerId
        ], 400);
    }
    
    // Step 2: Get cart items with full details
    $cartItems = get_cart_items_ctr($customerId);
    
    if (!$cartItems || count($cartItems) === 0) {
        respond(false, 'Unable to retrieve cart items. Please try again.', null, 500);
    }
    
    // Step 3: Get cart summary for total amount
    $cartSummary = get_cart_summary_ctr($customerId);
    
    if (!$cartSummary || !isset($cartSummary['total_price'])) {
        respond(false, 'Unable to calculate cart total. Please try again.', null, 500);
    }
    
    $totalAmount = $cartSummary['total_price'];
    
    // Validate total amount
    if ($totalAmount <= 0) {
        respond(false, 'Invalid cart total. Please refresh and try again.', null, 400);
    }
    
    // Step 4: Validate product prices (security check - ensure prices haven't changed)
    $priceValidationErrors = [];
    
    foreach ($cartItems as $item) {
        $productId = $item['p_id'];
        $cartPrice = $item['product_price'];
        
        // Get current price from product_controller
        $productDetails = view_single_product_ctr($productId);
        
        if (!$productDetails || !$productDetails['success']) {
            $priceValidationErrors[] = "Product '{$item['product_title']}' not found";
            continue;
        }
        
        // Extract price from the nested data structure
        $currentPrice = $productDetails['data']['product_price'] ?? 0;
        
        // Check if price has changed
        if (abs($currentPrice - $cartPrice) > 0.01) {
            $priceValidationErrors[] = "Price for '{$item['product_title']}' has changed from {$currency} {$cartPrice} to {$currency} {$currentPrice}";
        }
    }
    
    // If price validation errors, return them to user
    if (!empty($priceValidationErrors)) {
        respond(false, 'Some product prices have changed. Please review your cart.', [
            'price_errors' => $priceValidationErrors,
            'cart_items' => $cartItems
        ], 400);
    }
    
    // Step 5: Generate unique order reference
    $orderReference = generateOrderReference();
    
    // Use provided invoice number or generated reference
    $finalInvoiceNo = $invoiceNo ?? $orderReference;
    
    // Step 6: Create order from cart using integrated controller function
    // This function handles:
    // - Creating order record
    // - Adding order details from cart items
    // - Recording payment
    // - Emptying cart (on success)
    // All within a transaction for data integrity
    
    $orderResult = create_order_from_cart_ctr($customerId, $finalInvoiceNo, $currency);
    
    if (!$orderResult['success']) {
        respond(false, $orderResult['message'] ?? 'Failed to process order. Please try again.', [
            'order_result' => $orderResult
        ], 500);
    }
    
    // Extract order details from result
    $orderId = $orderResult['data']['order_id'] ?? null;
    $orderInvoice = $orderResult['data']['invoice_no'] ?? $finalInvoiceNo;
    $orderAmount = $orderResult['data']['order_amount'] ?? $totalAmount;
    $itemCount = $orderResult['data']['item_count'] ?? count($cartItems);
    
    // Step 7: Verify cart was emptied
    $cartEmptied = is_cart_empty_ctr($customerId);
    
    if (!$cartEmptied) {
        error_log("process_checkout_action.php - Warning: Cart not emptied after successful order #{$orderId}");
        // Don't fail the checkout, but log warning
    }
    
    // Step 8: Build success response with order details
    $responseData = [
        'order_id' => $orderId,
        'order_reference' => $orderReference,
        'invoice_no' => $orderInvoice,
        'order_amount' => number_format($orderAmount, 2, '.', ''),
        'currency' => $currency,
        'payment_method' => $paymentMethod,
        'item_count' => $itemCount,
        'cart_emptied' => $cartEmptied,
        'order_date' => date('Y-m-d H:i:s'),
        'customer_id' => $customerId
    ];
    
    // Log successful order
    error_log("process_checkout_action.php - Order #{$orderId} created successfully for customer #{$customerId}. Amount: {$currency} {$orderAmount}");
    
    respond(true, 'Order placed successfully!', $responseData, 200);
    
} catch (Exception $e) {
    // Log the exception
    error_log('process_checkout_action.php - Exception: ' . $e->getMessage());
    error_log('process_checkout_action.php - Stack trace: ' . $e->getTraceAsString());
    
    // Return user-friendly error
    respond(false, 'An error occurred while processing your order. Please try again or contact support.', [
        'error_code' => 'CHECKOUT_EXCEPTION',
        'error_details' => $e->getMessage() // Remove in production
    ], 500);
}
?>
