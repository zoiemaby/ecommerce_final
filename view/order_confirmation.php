<?php
/**
 * order_confirmation.php
 * Order confirmation/success page
 * Displays order details after successful checkout
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get customer ID from session
$customerId = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 0;

// Double-check customer ID is valid
if ($customerId <= 0) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    header('Location: order_history.php');
    exit();
}

// Get order details
$orderResult = get_order_details_ctr($orderId);

if (!$orderResult['success']) {
    header('Location: order_history.php');
    exit();
}

$orderDetails = $orderResult['data'];

// Verify order belongs to logged-in customer
if ($orderDetails['order'][0]['customer_id'] != $customerId) {
    header('Location: order_history.php');
    exit();
}

// Get payment details
$paymentResult = get_order_payment_ctr($orderId);
$payment = $paymentResult['success'] ? $paymentResult['data'] : null;

// Currency
$currency = $payment['currency'] ?? 'GHS';

// Page title
$pageTitle = 'Order Confirmation';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - E-Commerce</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <style>
        .confirmation-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .success-banner {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .success-icon {
            font-size: 5rem;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .order-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .order-reference {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin: 1.5rem 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .total-row {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .btn-action {
            padding: 0.75rem 2rem;
            font-weight: 500;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #28a745;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #28a745;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: -1.55rem;
            top: 12px;
            width: 2px;
            height: 100%;
            background: #e0e0e0;
        }

        .timeline-item:last-child::after {
            display: none;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="bi bi-shop"></i> E-Commerce
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="all_product.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="order_history.php">My Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../actions/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Confirmation Content -->
<div class="confirmation-container">
    <!-- Success Banner -->
    <div class="success-banner">
        <div class="success-icon mb-3">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h1 class="mb-2">Order Placed Successfully!</h1>
        <p class="mb-0 fs-5">Thank you for your purchase</p>
    </div>

    <!-- Order Reference -->
    <div class="order-card">
        <div class="order-reference">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">Order Reference</small>
                    <h4 class="mb-0 text-success">
                        <i class="bi bi-receipt me-2"></i>
                        <?php echo htmlspecialchars($orderDetails['order'][0]['invoice_no']); ?>
                    </h4>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted d-block mb-1">Order ID</small>
                    <h5 class="mb-0">#<?php echo $orderDetails['order'][0]['order_id']; ?></h5>
                </div>
            </div>
        </div>

        <div class="alert alert-success mt-3">
            <i class="bi bi-info-circle me-2"></i>
            A confirmation email has been sent to your registered email address.
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Order Details -->
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="order-card">
                <h5 class="mb-3">
                    <i class="bi bi-bag-check me-2"></i>
                    Order Items
                </h5>

                <?php foreach ($orderDetails['items'] as $item): ?>
                    <div class="order-item">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h6>
                            <small class="text-muted">
                                <?php echo $currency . ' ' . number_format($item['product_price'], 2); ?> × <?php echo $item['qty']; ?>
                            </small>
                        </div>
                        <div class="fw-bold">
                            <?php 
                            $itemTotal = $item['product_price'] * $item['qty'];
                            echo $currency . ' ' . number_format($itemTotal, 2); 
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="total-row">
                    <div class="d-flex justify-content-between">
                        <strong>Order Total:</strong>
                        <strong class="text-success fs-5">
                            <?php echo $currency . ' ' . number_format($orderDetails['order'][0]['order_amount'], 2); ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="order-card">
                <h5 class="mb-3">
                    <i class="bi bi-clock-history me-2"></i>
                    Order Status
                </h5>

                <div class="timeline">
                    <div class="timeline-item">
                        <strong>Order Placed</strong>
                        <p class="text-muted small mb-0">
                            <?php echo date('F j, Y, g:i a', strtotime($orderDetails['order'][0]['order_date'])); ?>
                        </p>
                    </div>
                    <div class="timeline-item" style="opacity: 0.5;">
                        <strong>Processing</strong>
                        <p class="text-muted small mb-0">Your order is being prepared</p>
                    </div>
                    <div class="timeline-item" style="opacity: 0.5;">
                        <strong>Shipped</strong>
                        <p class="text-muted small mb-0">Your order is on the way</p>
                    </div>
                    <div class="timeline-item" style="opacity: 0.5;">
                        <strong>Delivered</strong>
                        <p class="text-muted small mb-0">Order delivered successfully</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary -->
        <div class="col-lg-4">
            <!-- Order Information -->
            <div class="order-card">
                <h5 class="mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Order Information
                </h5>

                <div class="info-row">
                    <span class="text-muted">Order Date:</span>
                    <span><?php echo date('M j, Y', strtotime($orderDetails['order'][0]['order_date'])); ?></span>
                </div>

                <div class="info-row">
                    <span class="text-muted">Order Status:</span>
                    <span class="badge bg-success">
                        <?php echo htmlspecialchars($orderDetails['order'][0]['order_status']); ?>
                    </span>
                </div>

                <?php if ($payment): ?>
                    <div class="info-row">
                        <span class="text-muted">Payment Method:</span>
                        <span><?php echo htmlspecialchars($payment['pay_type'] ?? 'N/A'); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="text-muted">Payment Status:</span>
                        <span class="badge bg-success">Paid</span>
                    </div>

                    <div class="info-row">
                        <span class="text-muted">Amount Paid:</span>
                        <span class="fw-bold"><?php echo $currency . ' ' . number_format($payment['amt'], 2); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 mt-3">
                <a href="order_history.php" class="btn btn-outline-primary btn-action">
                    <i class="bi bi-clock-history me-2"></i>
                    View All Orders
                </a>
                <a href="all_product.php" class="btn btn-success btn-action">
                    <i class="bi bi-shop me-2"></i>
                    Continue Shopping
                </a>
            </div>

            <!-- Support -->
            <div class="text-center mt-3">
                <small class="text-muted">
                    Need help? <a href="#">Contact Support</a>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container">
        <p class="mb-0">&copy; 2025 E-Commerce. All rights reserved.</p>
    </div>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Print Order Function -->
<script>
function printOrder() {
    window.print();
}
</script>

</body>
</html>
