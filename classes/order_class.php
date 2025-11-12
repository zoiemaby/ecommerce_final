<?php
/**
 * order_class.php
 * Order Model Class - Handles all order operations
 * Extends database connection class for order management
 * Manages orders, order details, and payment entries
 */

require_once __DIR__ . '/../settings/db_class.php';

class Order extends Database
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
                    throw new Exception('Database connection not available in order_class.php');
                }
            }
        }
    }

    /**
     * Create a new order in the orders table
     * 
     * @param int $customerId Customer ID
     * @param int $invoiceNo Invoice number (will be auto-generated if not provided)
     * @param string $orderDate Order date (format: Y-m-d)
     * @param string $orderStatus Order status (default: 'Pending')
     * @return int|false Returns order_id on success, false on failure
     */
    public function createOrder($customerId, $invoiceNo = null, $orderDate = null, $orderStatus = 'Pending')
    {
        // Validate inputs
        if ($customerId <= 0) {
            error_log('createOrder: Invalid customer ID');
            return false;
        }

        // Generate invoice number if not provided (timestamp + random)
        if ($invoiceNo === null) {
            $invoiceNo = (int)(date('YmdHis') . rand(100, 999));
        }

        // Use current date if no date provided (DATE format, not DATETIME)
        if ($orderDate === null) {
            $orderDate = date('Y-m-d');
        }

        $sql = "INSERT INTO orders (customer_id, invoice_no, order_date, order_status) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('createOrder prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('iiss', $customerId, $invoiceNo, $orderDate, $orderStatus);
        
        if ($stmt->execute()) {
            $orderId = $stmt->insert_id;
            $stmt->close();
            error_log("createOrder success: Order ID {$orderId}, Invoice No {$invoiceNo}, Customer {$customerId}");
            return $orderId;
        } else {
            error_log('createOrder execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Add order details to the orderdetails table
     * Note: orderdetails table only has order_id, product_id, qty (no price column)
     * 
     * @param int $orderId Order ID
     * @param int $productId Product ID
     * @param int $quantity Quantity ordered
     * @return bool True on success, false on failure
     */
    public function addOrderDetails($orderId, $productId, $quantity)
    {
        // Validate inputs
        if ($orderId <= 0 || $productId <= 0 || $quantity <= 0) {
            error_log('addOrderDetails: Invalid parameters');
            return false;
        }

        $sql = "INSERT INTO orderdetails (order_id, product_id, qty) 
                VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('addOrderDetails prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('iii', $orderId, $productId, $quantity);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log('addOrderDetails execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Add multiple order details at once (batch insert)
     * 
     * @param int $orderId Order ID
     * @param array $items Array of items, each containing: product_id, quantity
     * @return bool True if all items added successfully, false otherwise
     */
    public function addMultipleOrderDetails($orderId, $items)
    {
        if (!is_array($items) || empty($items)) {
            error_log('addMultipleOrderDetails: Invalid items array');
            return false;
        }

        $success = true;
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? 0;
            $quantity = $item['quantity'] ?? 0;

            $result = $this->addOrderDetails($orderId, $productId, $quantity);
            if (!$result) {
                $success = false;
                error_log("addMultipleOrderDetails: Failed to add product_id $productId for order_id $orderId");
            }
        }

        return $success;
    }

    /**
     * Record payment entry in the payments table
     * Simulates payment processing
     * 
     * @param float $amount Payment amount
     * @param int $customerId Customer ID
     * @param int $orderId Order ID
     * @param string $currency Currency code (default: 'GHS')
     * @param string $paymentDate Payment date (format: Y-m-d H:i:s)
     * @return int|false Returns payment_id on success, false on failure
     */
    public function recordPayment($amount, $customerId, $orderId, $currency = 'GHS', $paymentDate = null)
    {
        // Validate inputs
        if ($amount <= 0 || $customerId <= 0 || $orderId <= 0) {
            error_log('recordPayment: Invalid parameters');
            return false;
        }

        // Use current timestamp if no date provided
        if ($paymentDate === null) {
            $paymentDate = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO payment (amt, customer_id, order_id, currency, payment_date) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('recordPayment prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('diiss', $amount, $customerId, $orderId, $currency, $paymentDate);
        
        if ($stmt->execute()) {
            $paymentId = $stmt->insert_id;
            $stmt->close();
            return $paymentId;
        } else {
            error_log('recordPayment execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Retrieve all past orders for a specific user
     * Returns orders with summary information
     * 
     * @param int $customerId Customer ID
     * @param string $orderBy Order by clause (default: 'order_date DESC')
     * @param int $limit Limit number of results (optional)
     * @return array|false Array of orders, false on failure
     */
    public function getUserOrders($customerId, $orderBy = 'order_date DESC', $limit = null)
    {
        if ($customerId <= 0) {
            error_log('getUserOrders: Invalid customer ID');
            return false;
        }

        $sql = "SELECT 
                    o.order_id,
                    o.customer_id,
                    o.invoice_no,
                    o.order_date,
                    o.order_status,
                    COALESCE(p.amt, 0) as order_amount
                FROM orders o
                LEFT JOIN payment p ON o.order_id = p.order_id
                WHERE o.customer_id = ?
                ORDER BY {$orderBy}";
        
        // Add limit if specified
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('getUserOrders prepare failed: ' . $this->conn->error);
            return false;
        }

        if ($limit !== null && $limit > 0) {
            $stmt->bind_param('ii', $customerId, $limit);
        } else {
            $stmt->bind_param('i', $customerId);
        }
        
        if (!$stmt->execute()) {
            error_log('getUserOrders execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $orders = [];
        
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();
        return $orders;
    }

    /**
     * Get detailed information about a specific order
     * Includes order details with product information
     * 
     * @param int $orderId Order ID
     * @param int $customerId Customer ID (for security - ensure user owns the order)
     * @return array|false Order details with products, false on failure
     */
    public function getOrderDetails($orderId, $customerId = null)
    {
        if ($orderId <= 0) {
            error_log('getOrderDetails: Invalid order ID');
            return false;
        }

        // Build query with optional customer verification
        $sql = "SELECT 
                    o.order_id,
                    o.customer_id,
                    o.invoice_no,
                    o.order_date,
                    o.order_status,
                    od.product_id,
                    od.qty,
                    p.product_title,
                    p.product_price,
                    p.product_image,
                    c.cat_name,
                    b.brand_name,
                    (od.qty * p.product_price) AS subtotal
                FROM orders o
                LEFT JOIN orderdetails od ON o.order_id = od.order_id
                LEFT JOIN products p ON od.product_id = p.product_id
                LEFT JOIN categories c ON p.product_cat = c.cat_id
                LEFT JOIN brands b ON p.product_brand = b.brand_id
                WHERE o.order_id = ?";
        
        if ($customerId !== null) {
            $sql .= " AND o.customer_id = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('getOrderDetails prepare failed: ' . $this->conn->error);
            return false;
        }

        if ($customerId !== null) {
            $stmt->bind_param('ii', $orderId, $customerId);
        } else {
            $stmt->bind_param('i', $orderId);
        }
        
        if (!$stmt->execute()) {
            error_log('getOrderDetails execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $orderData = [
            'order_info' => null,
            'items' => []
        ];
        
        while ($row = $result->fetch_assoc()) {
            // Store order info (same for all rows)
            if ($orderData['order_info'] === null) {
                $orderData['order_info'] = [
                    'order_id' => $row['order_id'],
                    'customer_id' => $row['customer_id'],
                    'invoice_no' => $row['invoice_no'],
                    'order_date' => $row['order_date'],
                    'order_status' => $row['order_status']
                ];
            }
            
            // Add product details if they exist
            if ($row['product_id'] !== null) {
                $orderData['items'][] = [
                    'product_id' => $row['product_id'],
                    'product_title' => $row['product_title'],
                    'product_image' => $row['product_image'],
                    'cat_name' => $row['cat_name'],
                    'brand_name' => $row['brand_name'],
                    'qty' => $row['qty'],
                    'price' => $row['product_price'],
                    'subtotal' => $row['subtotal']
                ];
            }
        }

        $stmt->close();
        return $orderData;
    }

    /**
     * Get payment information for a specific order
     * 
     * @param int $orderId Order ID
     * @return array|false Payment details, false on failure
     */
    public function getOrderPayment($orderId)
    {
        if ($orderId <= 0) {
            error_log('getOrderPayment: Invalid order ID');
            return false;
        }

        $sql = "SELECT 
                    pay_id,
                    amt,
                    customer_id,
                    order_id,
                    currency,
                    payment_date
                FROM payment
                WHERE order_id = ?
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('getOrderPayment prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('i', $orderId);
        
        if (!$stmt->execute()) {
            error_log('getOrderPayment execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $payment = $result->fetch_assoc();
            $stmt->close();
            return $payment;
        }

        $stmt->close();
        return false;
    }

    /**
     * Update order status
     * 
     * @param int $orderId Order ID
     * @param string $status New status (e.g., 'Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled')
     * @return bool True on success, false on failure
     */
    public function updateOrderStatus($orderId, $status)
    {
        if ($orderId <= 0 || empty($status)) {
            error_log('updateOrderStatus: Invalid parameters');
            return false;
        }

        $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('updateOrderStatus prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('si', $status, $orderId);
        
        if ($stmt->execute()) {
            $success = $stmt->affected_rows > 0;
            $stmt->close();
            return $success;
        } else {
            error_log('updateOrderStatus execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Get order count for a customer
     * 
     * @param int $customerId Customer ID
     * @return int Number of orders
     */
    public function getOrderCount($customerId)
    {
        if ($customerId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('getOrderCount prepare failed: ' . $this->conn->error);
            return 0;
        }

        $stmt->bind_param('i', $customerId);
        
        if (!$stmt->execute()) {
            error_log('getOrderCount execute failed: ' . $stmt->error);
            $stmt->close();
            return 0;
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['count'] ?? 0);
    }

    /**
     * Get all orders (admin function)
     * 
     * @param string $orderBy Order by clause (default: 'order_date DESC')
     * @param int $limit Limit number of results (optional)
     * @param int $offset Offset for pagination (optional)
     * @return array|false Array of all orders with customer info, false on failure
     */
    public function getAllOrders($orderBy = 'order_date DESC', $limit = null, $offset = 0)
    {
        $sql = "SELECT 
                    o.order_id,
                    o.customer_id,
                    o.invoice_no,
                    o.order_date,
                    o.order_status,
                    c.customer_name,
                    c.customer_email,
                    COALESCE(p.amt, 0) as order_amount
                FROM orders o
                LEFT JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN payment p ON o.order_id = p.order_id
                ORDER BY {$orderBy}";
        
        // Add limit and offset if specified
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log('getAllOrders prepare failed: ' . $this->conn->error);
            return false;
        }

        if ($limit !== null && $limit > 0) {
            $stmt->bind_param('ii', $limit, $offset);
        }
        
        if (!$stmt->execute()) {
            error_log('getAllOrders execute failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $orders = [];
        
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();
        return $orders;
    }

    /**
     * Complete order processing - creates order, adds details, and records payment
     * This is a transaction-safe method that rolls back on failure
     * 
     * @param int $customerId Customer ID
     * @param array $items Array of cart items with product_id, quantity
     * @param float $totalAmount Total order amount (for payment table)
     * @param string $currency Currency code (default: 'GHS')
     * @param int $invoiceNo Invoice number (optional, will be auto-generated)
     * @return array|false Returns array with order_id, invoice_no, and payment_id on success, false on failure
     */
    public function processCompleteOrder($customerId, $items, $totalAmount, $currency = 'GHS', $invoiceNo = null)
    {
        // Start transaction
        $this->conn->begin_transaction();

        try {
            // 1. Create the order (with invoice_no, not invoice_amt)
            $orderId = $this->createOrder($customerId, $invoiceNo);
            if (!$orderId) {
                throw new Exception('Failed to create order');
            }
            
            // Get the invoice number that was used/generated
            $usedInvoiceNo = $invoiceNo ?? (int)(date('YmdHis') . rand(100, 999));

            // 2. Add all order details (orderdetails table has: order_id, product_id, qty only)
            foreach ($items as $item) {
                $result = $this->addOrderDetails(
                    $orderId,
                    $item['product_id'],
                    $item['quantity']
                );
                
                if (!$result) {
                    throw new Exception('Failed to add order details for product ' . $item['product_id']);
                }
            }

            // 3. Record payment (payment table has: amt, customer_id, order_id, currency, payment_date)
            $paymentId = $this->recordPayment($totalAmount, $customerId, $orderId, $currency);
            if (!$paymentId) {
                throw new Exception('Failed to record payment');
            }

            // Commit transaction
            $this->conn->commit();

            error_log("processCompleteOrder success: Order ID {$orderId}, Invoice {$usedInvoiceNo}, Payment ID {$paymentId}");

            return [
                'order_id' => $orderId,
                'invoice_no' => $usedInvoiceNo,
                'payment_id' => $paymentId,
                'success' => true
            ];

        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            error_log('processCompleteOrder failed: ' . $e->getMessage());
            return false;
        }
    }
}
?>
