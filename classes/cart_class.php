<?php
/**
 * cart_class.php
 * Cart Model Class - Handles all cart operations
 * Extends database connection class for cart management
 */

require_once __DIR__ . '/../settings/db_class.php';

class Cart extends Database
{
    protected $conn;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct()
    {
        parent::__construct();

        // Get database connection
        if (isset($this->conn) && $this->conn instanceof mysqli) {
            $this->conn = $this->conn;
        } else {
            if (method_exists($this, 'getConnection')) {
                $this->conn = $this->getConnection();
            } else {
                global $conn;
                if (isset($conn) && $conn instanceof mysqli) {
                    $this->conn = $conn;
                } else {
                    throw new Exception('Database connection not available in cart_class.php');
                }
            }
        }
    }

    /**
     * Check if a product already exists in the user's cart
     * 
     * @param int $customerId Customer ID
     * @param int $productId Product ID
     * @return array|false Returns cart item data if exists, false otherwise
     */
    public function productExistsInCart($customerId, $productId)
    {
        $sql = "SELECT c_id, qty FROM cart WHERE p_id = ? AND c_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $productId, $customerId);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        }

        $stmt->close();
        return false;
    }

    /**
     * Add a product to the cart
     * If product already exists, increment quantity instead
     * 
     * @param int $productId Product ID
     * @param int $customerId Customer ID
     * @param int $quantity Quantity to add (default: 1)
     * @return bool|int Returns cart ID on success, false on failure
     */
    public function addToCart($productId, $customerId, $quantity = 1)
    {
        // Validate inputs
        if ($productId <= 0 || $customerId <= 0 || $quantity <= 0) {
            return false;
        }

        // Check if product already exists in cart
        $existingItem = $this->productExistsInCart($customerId, $productId);
        
        if ($existingItem) {
            // Product exists - increment quantity
            $newQuantity = (int)$existingItem['qty'] + (int)$quantity;
            return $this->updateCartQuantity($productId, $customerId, $newQuantity);
        }

        // Product doesn't exist - insert new cart item
        $sql = "INSERT INTO cart (p_id, c_id, qty) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('iii', $productId, $customerId, $quantity);
        
        if ($stmt->execute()) {
            $cartId = $stmt->insert_id;
            $stmt->close();
            return $cartId;
        } else {
            $stmt->close();
            return false;
        }
    }

    /**
     * Update the quantity of a product in the cart
     * 
     * @param int $productId Product ID
     * @param int $customerId Customer ID
     * @param int $newQuantity New quantity (if 0 or negative, item will be removed)
     * @return bool True on success, false on failure
     */
    public function updateCartQuantity($productId, $customerId, $newQuantity)
    {
        // If quantity is 0 or negative, remove the item
        if ($newQuantity <= 0) {
            return $this->removeFromCart($productId, $customerId);
        }

        $sql = "UPDATE cart SET qty = ? WHERE p_id = ? AND c_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('iii', $newQuantity, $productId, $customerId);
        
        if ($stmt->execute()) {
            $success = $stmt->affected_rows >= 0; // >= 0 because it might be the same value
            $stmt->close();
            return $success;
        } else {
            $stmt->close();
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
    public function removeFromCart($productId, $customerId)
    {
        $sql = "DELETE FROM cart WHERE p_id = ? AND c_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $productId, $customerId);
        
        if ($stmt->execute()) {
            $success = $stmt->affected_rows > 0;
            $stmt->close();
            return $success;
        } else {
            $stmt->close();
            return false;
        }
    }

    /**
     * Retrieve all cart items for a given user with product details
     * Joins with products, categories, and brands tables
     * 
     * @param int $customerId Customer ID
     * @return array|false Array of cart items with product details, false on failure
     */
    public function getCartItems($customerId)
    {
        $sql = "SELECT 
                    cart.p_id,
                    cart.c_id,
                    cart.qty,
                    products.product_title,
                    products.product_price,
                    products.product_desc,
                    products.product_image,
                    products.product_keywords,
                    categories.cat_name,
                    brands.brand_name,
                    (cart.qty * products.product_price) AS subtotal
                FROM cart
                INNER JOIN products ON cart.p_id = products.product_id
                LEFT JOIN categories ON products.product_cat = categories.cat_id
                LEFT JOIN brands ON products.product_brand = brands.brand_id
                WHERE cart.c_id = ?
                ORDER BY cart.p_id DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $customerId);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $cartItems = [];
        
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
        }

        $stmt->close();
        return $cartItems;
    }

    /**
     * Get cart summary for a user (total items and total price)
     * 
     * @param int $customerId Customer ID
     * @return array|false Array with 'total_items' and 'total_price', false on failure
     */
    public function getCartSummary($customerId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(cart.qty) as total_quantity,
                    SUM(cart.qty * products.product_price) as total_price
                FROM cart
                INNER JOIN products ON cart.p_id = products.product_id
                WHERE cart.c_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $customerId);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();
        $stmt->close();

        return [
            'total_items' => (int)($summary['total_items'] ?? 0),
            'total_quantity' => (int)($summary['total_quantity'] ?? 0),
            'total_price' => (float)($summary['total_price'] ?? 0.00)
        ];
    }

    /**
     * Empty the entire cart for a user
     * 
     * @param int $customerId Customer ID
     * @return bool True on success, false on failure
     */
    public function emptyCart($customerId)
    {
        $sql = "DELETE FROM cart WHERE c_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $customerId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true; // Return true even if no items were deleted
        } else {
            $stmt->close();
            return false;
        }
    }

    /**
     * Get the total number of items in cart for a user
     * 
     * @param int $customerId Customer ID
     * @return int Total number of unique products in cart
     */
    public function getCartCount($customerId)
    {
        $sql = "SELECT COUNT(*) as count FROM cart WHERE c_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $customerId);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int)$row['count'];
        $stmt->close();
        
        return $count;
    }

    /**
     * Check if cart is empty
     * 
     * @param int $customerId Customer ID
     * @return bool True if cart is empty, false otherwise
     */
    public function isCartEmpty($customerId)
    {
        return $this->getCartCount($customerId) === 0;
    }

    /**
     * Get a single cart item
     * 
     * @param int $productId Product ID
     * @param int $customerId Customer ID
     * @return array|false Cart item data with product details, false if not found
     */
    public function getCartItem($productId, $customerId)
    {
        $sql = "SELECT 
                    cart.p_id,
                    cart.c_id,
                    cart.qty,
                    products.product_title,
                    products.product_price,
                    products.product_desc,
                    products.product_image,
                    categories.cat_name,
                    brands.brand_name,
                    (cart.qty * products.product_price) AS subtotal
                FROM cart
                INNER JOIN products ON cart.p_id = products.product_id
                LEFT JOIN categories ON products.product_cat = categories.cat_id
                LEFT JOIN brands ON products.product_brand = brands.brand_id
                WHERE cart.p_id = ? AND cart.c_id = ?
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $productId, $customerId);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            $stmt->close();
            return $item;
        }

        $stmt->close();
        return false;
    }
}
?>
