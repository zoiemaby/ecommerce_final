<?php
/**
 * order_controller.php
 * Thin controller wrapping Order model (order_class.php)
 * Provides consistent interface for action scripts and other controllers.
 * Ensures pricing/product data consistency when used with cart & product controllers.
 */

require_once __DIR__ . '/../classes/order_class.php';
require_once __DIR__ . '/cart_controller.php'; // for cross-controller cooperation
require_once __DIR__ . '/product_controller.php'; // assumed existing for product validation

/** Generic helper to standardize responses */
function _order_response($ok, $data = null, $message = '') {
    return [
        'success' => (bool)$ok,
        'data' => $data,
        'message' => $message
    ];
}

/** Create new order */
function create_order_ctr($customerId, $invoiceNo = null, $orderDate = null, $status = 'Pending') {
    try {
        $order = new Order();
        $id = $order->createOrder((int)$customerId, $invoiceNo, $orderDate, $status);
        if ($id) { return _order_response(true, ['order_id' => $id, 'invoice_no' => $invoiceNo]); }
        return _order_response(false, null, 'Failed to create order');
    } catch (Throwable $e) {
        error_log('create_order_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception creating order');
    }
}

/** Add single order detail */
function add_order_details_ctr($orderId, $productId, $quantity) {
    try {
        $order = new Order();
        $ok = $order->addOrderDetails((int)$orderId, (int)$productId, (int)$quantity);
        return _order_response($ok, $ok ? true : null, $ok ? '' : 'Failed to add order detail');
    } catch (Throwable $e) {
        error_log('add_order_details_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception adding order detail');
    }
}

/** Add multiple order details */
function add_multiple_order_details_ctr($orderId, array $items) {
    try {
        $order = new Order();
        $ok = $order->addMultipleOrderDetails((int)$orderId, $items);
        return _order_response($ok, $ok ? true : null, $ok ? '' : 'Failed one or more order details');
    } catch (Throwable $e) {
        error_log('add_multiple_order_details_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception adding multiple order details');
    }
}

/** Record payment */
function record_payment_ctr($amount, $customerId, $orderId, $currency = 'GHS', $paymentDate = null) {
    try {
        $order = new Order();
        $payId = $order->recordPayment((float)$amount, (int)$customerId, (int)$orderId, $currency, $paymentDate);
        if ($payId) { return _order_response(true, ['payment_id' => $payId]); }
        return _order_response(false, null, 'Failed to record payment');
    } catch (Throwable $e) {
        error_log('record_payment_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception recording payment');
    }
}

/** Get user orders */
function get_user_orders_ctr($customerId, $orderBy = 'order_date DESC', $limit = null) {
    try {
        $order = new Order();
        $data = $order->getUserOrders((int)$customerId, $orderBy, $limit);
        return _order_response($data !== false, $data, $data === false ? 'Failed to fetch user orders' : '');
    } catch (Throwable $e) {
        error_log('get_user_orders_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception fetching user orders');
    }
}

/** Get order details (optionally enforce ownership) */
function get_order_details_ctr($orderId, $customerId = null) {
    try {
        $order = new Order();
        $data = $order->getOrderDetails((int)$orderId, $customerId === null ? null : (int)$customerId);
        return _order_response($data !== false, $data, $data === false ? 'Failed to fetch order details' : '');
    } catch (Throwable $e) {
        error_log('get_order_details_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception fetching order details');
    }
}

/** Get payment info */
function get_order_payment_ctr($orderId) {
    try {
        $order = new Order();
        $data = $order->getOrderPayment((int)$orderId);
        return _order_response($data !== false, $data, $data === false ? 'No payment found' : '');
    } catch (Throwable $e) {
        error_log('get_order_payment_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception fetching payment');
    }
}

/** Update order status */
function update_order_status_ctr($orderId, $status) {
    try {
        $order = new Order();
        $ok = $order->updateOrderStatus((int)$orderId, (string)$status);
        return _order_response($ok, $ok ? true : null, $ok ? '' : 'Failed to update status');
    } catch (Throwable $e) {
        error_log('update_order_status_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception updating status');
    }
}

/** Get order count for customer */
function get_order_count_ctr($customerId) {
    try {
        $order = new Order();
        $count = $order->getOrderCount((int)$customerId);
        return _order_response(true, ['count' => (int)$count]);
    } catch (Throwable $e) {
        error_log('get_order_count_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception getting order count');
    }
}

/** Admin: get all orders */
function get_all_orders_ctr($orderBy = 'order_date DESC', $limit = null, $offset = 0) {
    try {
        $order = new Order();
        $data = $order->getAllOrders($orderBy, $limit, (int)$offset);
        return _order_response($data !== false, $data, $data === false ? 'Failed to fetch orders' : '');
    } catch (Throwable $e) {
        error_log('get_all_orders_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception fetching all orders');
    }
}

/** Transaction-safe complete order */
function process_complete_order_ctr($customerId, array $items, $totalAmount, $currency = 'GHS', $invoiceNo = null) {
    try {
        $order = new Order();
        $data = $order->processCompleteOrder((int)$customerId, $items, (float)$totalAmount, $currency, $invoiceNo);
        return _order_response($data !== false, $data, $data === false ? 'Failed to process complete order' : '');
    } catch (Throwable $e) {
        error_log('process_complete_order_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception processing order');
    }
}

/**
 * Convenience: Create order from current cart contents.
 * Steps:
 *  1. Fetch cart items for user.
 *  2. Re-validate product prices (using product_controller if available).
 *  3. Compute totalAmount from authoritative prices * qty.
 *  4. Create order + add details + record payment in a transaction via process_complete_order_ctr.
 *  5. Empty cart if successful.
 */
function create_order_from_cart_ctr($customerId, $invoiceNo = null, $currency = 'GHS') {
    try {
        $customerId = (int)$customerId;
        if ($customerId <= 0) { return _order_response(false, null, 'Invalid customer ID'); }

        // Fetch cart items
        $cartItems = get_cart_items_ctr($customerId);
        
        // DEBUG
        error_log('create_order_from_cart_ctr - Customer ID: ' . $customerId);
        error_log('create_order_from_cart_ctr - Cart items type: ' . gettype($cartItems));
        error_log('create_order_from_cart_ctr - Cart items count: ' . (is_array($cartItems) ? count($cartItems) : 0));
        error_log('create_order_from_cart_ctr - Cart items: ' . print_r($cartItems, true));
        
        // cart_controller returns raw array of items (not wrapped in success/data structure)
        if (!$cartItems || !is_array($cartItems) || empty($cartItems)) {
            error_log('create_order_from_cart_ctr - Cart is empty or unavailable');
            return _order_response(false, null, 'Cart is empty or unavailable');
        }
        
        $rawItems = $cartItems;

        // Build authoritative item list (product_id, quantity, price)
        $itemsForOrder = [];
        $total = 0.0;
        foreach ($rawItems as $ci) {
            $pid = (int)($ci['p_id'] ?? $ci['product_id'] ?? 0);
            $qty = (int)($ci['qty'] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;

            // Validate price using product controller if available
            $productData = null;
            if (function_exists('view_single_product_ctr')) {
                $productData = view_single_product_ctr($pid);
                if (is_array($productData) && isset($productData['data'][0])) {
                    $productRow = $productData['data'][0];
                    $price = (float)$productRow['product_price'];
                } elseif (is_array($productData) && isset($productData['product_price'])) {
                    $price = (float)$productData['product_price']; // alternative shape
                } else {
                    // fallback to cart-provided price
                    $price = (float)($ci['product_price'] ?? $ci['price'] ?? 0);
                }
            } else {
                $price = (float)($ci['product_price'] ?? $ci['price'] ?? 0);
            }

            $lineTotal = $price * $qty;
            $total += $lineTotal;
            $itemsForOrder[] = [
                'product_id' => $pid,
                'quantity' => $qty,
                'price' => $price
            ];
        }

        if (empty($itemsForOrder)) {
            return _order_response(false, null, 'No valid items in cart');
        }

        error_log('create_order_from_cart_ctr - Items for order: ' . count($itemsForOrder) . ' | Total: ' . $total);

        // Process order atomically (pass invoice_no to the function)
        $result = process_complete_order_ctr($customerId, $itemsForOrder, $total, $currency, $invoiceNo);
        if (!$result['success']) { 
            error_log('create_order_from_cart_ctr - process_complete_order_ctr failed: ' . ($result['message'] ?? 'Unknown error'));
            return $result; 
        }

        // Empty cart on success
        $cartEmptied = empty_cart_ctr($customerId);
        error_log('create_order_from_cart_ctr - Cart emptied: ' . ($cartEmptied ? 'YES' : 'NO'));

        return _order_response(true, [
            'order_id' => $result['data']['order_id'],
            'invoice_no' => $result['data']['invoice_no'] ?? $invoiceNo,
            'payment_id' => $result['data']['payment_id'],
            'order_amount' => $total,
            'item_count' => count($itemsForOrder),
            'items' => $itemsForOrder
        ], 'Order successfully created from cart');

    } catch (Throwable $e) {
        error_log('create_order_from_cart_ctr: ' . $e->getMessage());
        return _order_response(false, null, 'Exception creating order from cart');
    }
}

?>
