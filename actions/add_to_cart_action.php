<?php

/**
 * add_to_cart_action.php
 * Handles Add to Cart requests
 * Receives product details, validates user session, and adds items to cart
 * Returns JSON response for AJAX calls
 */

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
function respond($success, $message, $data = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    respond(false, 'Please login to add items to cart');
}

// Get customer ID from session
$customerId = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? 0;

if ($customerId <= 0) {
    respond(false, 'Invalid user session. Please login again.');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method');
}

// Get input data
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validate
if ($productId <= 0) {
    respond(false, 'Invalid product ID');
}

if ($quantity <= 0 || $quantity > 100) {
    respond(false, 'Invalid quantity');
}

// Check if product already exists in cart
$existingItem = product_exists_in_cart_ctr($customerId, $productId);

// Add to cart
$result = add_to_cart_ctr($productId, $customerId, $quantity);

if ($result === false) {
    respond(false, 'Failed to add item to cart');
}

// Get updated cart info
$cartCount = get_cart_count_ctr($customerId);
$cartSummary = get_cart_summary_ctr($customerId);

// Build response
$message = $existingItem ? "Cart updated!" : "Product added to cart!";

respond(true, $message, [
    'cart_id' => $result,
    'product_id' => $productId,
    'quantity_added' => $quantity,
    'was_existing' => $existingItem !== false,
    'cart_count' => $cartCount,
    'cart_summary' => $cartSummary
]);
