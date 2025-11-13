<?php
/**
 * cart_controller.php
 * Cart Controller - Wraps cart_class methods for use by action scripts
 * Provides a thin layer between actions and the cart model
 */

require_once __DIR__ . '/../classes/cart_class.php';

/**
 * Add a product to the cart
 * If product already exists, increments quantity instead of duplicating
 * 
 * @param int $productId Product ID
 * @param int $customerId Customer ID
 * @param int $quantity Quantity to add (default: 1)
 * @return bool|int Returns cart ID on success, false on failure
 */
function add_to_cart_ctr($productId, $customerId, $quantity = 1)
{
    try {
        $cart = new Cart();
        return $cart->addToCart($productId, $customerId, $quantity);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Update the quantity of a product in the cart
 * If quantity is 0 or negative, the item will be removed
 * 
 * @param int $productId Product ID
 * @param int $customerId Customer ID
 * @param int $newQuantity New quantity
 * @return bool True on success, false on failure
 */
function update_cart_quantity_ctr($productId, $customerId, $newQuantity)
{
    try {
        $cart = new Cart();
        return $cart->updateCartQuantity($productId, $customerId, $newQuantity);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Remove a specific product from the cart
 * 
 * @param int $productId Product ID
 * @param int $customerId Customer ID
 * @return bool True on success, false on failure
 */
function remove_from_cart_ctr($productId, $customerId)
{
    try {
        $cart = new Cart();
        return $cart->removeFromCart($productId, $customerId);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Retrieve all cart items for a given user with product details
 * 
 * @param int $customerId Customer ID
 * @return array|false Array of cart items with product details, false on failure
 */
function get_cart_items_ctr($customerId)
{
    try {
        $cart = new Cart();
        return $cart->getCartItems($customerId);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get cart summary for a user (total items, quantity, and price)
 * 
 * @param int $customerId Customer ID
 * @return array|false Array with 'total_items', 'total_quantity', 'total_price', false on failure
 */
function get_cart_summary_ctr($customerId)
{
    try {
        $cart = new Cart();
        return $cart->getCartSummary($customerId);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Empty the entire cart for a user
 * 
 * @param int $customerId Customer ID
 * @return bool True on success, false on failure
 */
function empty_cart_ctr($customerId)
{
    try {
        $cart = new Cart();
        return $cart->emptyCart($customerId);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if a product already exists in the user's cart
 * 
 * @param int $customerId Customer ID
 * @param int $productId Product ID
 * @return array|false Returns cart item data if exists, false otherwise
 */
function product_exists_in_cart_ctr($customerId, $productId)
{
    try {
        $cart = new Cart();
        return $cart->productExistsInCart($customerId, $productId);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get the total number of items in cart for a user
 * 
 * @param int $customerId Customer ID
 * @return int Total number of unique products in cart
 */
function get_cart_count_ctr($customerId)
{
    try {
        $cart = new Cart();
        return $cart->getCartCount($customerId);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Check if cart is empty
 * 
 * @param int $customerId Customer ID
 * @return bool True if cart is empty, false otherwise
 */
function is_cart_empty_ctr($customerId)
{
    try {
        $cart = new Cart();
        return $cart->isCartEmpty($customerId);
    } catch (Exception $e) {
        return true; // Return true on error for safety
    }
}

/**
 * Get a single cart item with product details
 * 
 * @param int $productId Product ID
 * @param int $customerId Customer ID
 * @return array|false Cart item data with product details, false if not found
 */
function get_cart_item_ctr($productId, $customerId)
{
    try {
        $cart = new Cart();
        return $cart->getCartItem($productId, $customerId);
    } catch (Exception $e) {
        return false;
    }
}
?>
