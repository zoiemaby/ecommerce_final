<?php
/**
 * test_cart_debug.php
 * Debug script to test cart functionality step by step
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cart Debug Test</h1>";
echo "<pre>";

// Test 1: Session
echo "=== Test 1: Session ===\n";
session_start();
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "YES" : "NO") . "\n";
echo "Customer ID in session: " . ($_SESSION['customer_id'] ?? 'NOT SET') . "\n";
echo "User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "\n";

// Test 2: Include core.php
echo "=== Test 2: Core Functions ===\n";
try {
    require_once __DIR__ . '/settings/core.php';
    echo "core.php loaded: YES\n";
    echo "isLoggedIn function exists: " . (function_exists('isLoggedIn') ? "YES" : "NO") . "\n";
    if (function_exists('isLoggedIn')) {
        echo "User logged in: " . (isLoggedIn() ? "YES" : "NO") . "\n";
    }
} catch (Exception $e) {
    echo "ERROR loading core.php: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Database connection
echo "=== Test 3: Database Connection ===\n";
try {
    require_once __DIR__ . '/settings/db_class.php';
    echo "db_class.php loaded: YES\n";
    
    $db = new Database();
    echo "Database object created: YES\n";
    
    $conn = $db->getConnection();
    echo "Connection obtained: " . ($conn ? "YES" : "NO") . "\n";
    echo "Connection type: " . get_class($conn) . "\n";
    echo "Connection active: " . ($conn->ping() ? "YES" : "NO") . "\n";
} catch (Exception $e) {
    echo "ERROR with database: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Cart class
echo "=== Test 4: Cart Class ===\n";
try {
    require_once __DIR__ . '/classes/cart_class.php';
    echo "cart_class.php loaded: YES\n";
    
    $cart = new Cart();
    echo "Cart object created: YES\n";
} catch (Exception $e) {
    echo "ERROR creating cart: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
echo "\n";

// Test 5: Cart controller
echo "=== Test 5: Cart Controller ===\n";
try {
    require_once __DIR__ . '/controllers/cart_controller.php';
    echo "cart_controller.php loaded: YES\n";
    echo "add_to_cart_ctr function exists: " . (function_exists('add_to_cart_ctr') ? "YES" : "NO") . "\n";
} catch (Exception $e) {
    echo "ERROR loading controller: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Actual cart operation (if logged in)
echo "=== Test 6: Cart Operation Test ===\n";
if (isset($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
    try {
        $customerId = $_SESSION['customer_id'];
        $testProductId = 1; // Use a valid product ID from your database
        
        echo "Testing add to cart...\n";
        echo "Customer ID: $customerId\n";
        echo "Product ID: $testProductId\n";
        
        $result = add_to_cart_ctr($testProductId, $customerId, 1);
        echo "Add to cart result: " . ($result ? "SUCCESS (ID: $result)" : "FAILED") . "\n";
        
        if ($result) {
            $count = get_cart_count_ctr($customerId);
            echo "Cart count: $count\n";
        }
    } catch (Exception $e) {
        echo "ERROR during cart operation: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "SKIPPED - No valid customer ID in session\n";
    echo "Please login first, then run this test again\n";
}

echo "</pre>";
?>
