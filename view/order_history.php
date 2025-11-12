<?php
/**
 * order_history.php
 * User order history page
 * Displays all past orders for the logged-in customer
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

// Get user orders
$ordersResult = get_user_orders_ctr($customerId);

$orders = [];
if ($ordersResult['success']) {
    $orders = $ordersResult['data'];
}

// Currency
$currency = 'GHS';

// Page title
$pageTitle = 'Order History';
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
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .orders-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .order-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .order-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .order-reference {
            font-size: 1.1rem;
            font-weight: bold;
            color: #667eea;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
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
                    <a class="nav-link" href="cart.php">
                        <i class="bi bi-cart"></i> Cart
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="order_history.php">
                        <i class="bi bi-clock-history"></i> My Orders
                    </a>
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

<!-- Orders Content -->
<div class="orders-container">
    <!-- Orders Header -->
    <div class="orders-header">
        <h1 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>
            Order History
        </h1>
        <p class="mb-0 mt-2 opacity-75">
            View and track all your orders
        </p>
    </div>

    <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="bi bi-inbox" style="font-size: 5rem; color: #ccc;"></i>
            <h2 class="mt-4">No orders yet</h2>
            <p class="text-muted">Start shopping to see your orders here!</p>
            <a href="all_product.php" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-shop me-2"></i> Start Shopping
            </a>
        </div>

    <?php else: ?>
        <!-- Orders List -->
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <?php
                $statusClass = 'status-pending';
                $statusIcon = 'clock';
                
                switch(strtolower($order['order_status'])) {
                    case 'completed':
                    case 'delivered':
                        $statusClass = 'status-completed';
                        $statusIcon = 'check-circle';
                        break;
                    case 'processing':
                    case 'shipped':
                        $statusClass = 'status-processing';
                        $statusIcon = 'truck';
                        break;
                }
                ?>
                
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Order Reference</small>
                                <div class="order-reference">
                                    <i class="bi bi-receipt me-1"></i>
                                    <?php echo htmlspecialchars($order['invoice_no']); ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Order Date</small>
                                <div class="fw-semibold">
                                    <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted d-block">Total</small>
                                <div class="fw-bold text-success">
                                    <?php echo $currency . ' ' . number_format($order['order_amount'], 2); ?>
                                </div>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <i class="bi bi-<?php echo $statusIcon; ?> me-1"></i>
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="order-body">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="d-flex align-items-center gap-3 text-muted">
                                    <small>
                                        <i class="bi bi-hash me-1"></i>
                                        Order ID: <?php echo $order['order_id']; ?>
                                    </small>
                                    <small>
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo date('g:i A', strtotime($order['order_date'])); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                                <a href="order_confirmation.php?order_id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Stats -->
        <div class="mt-4 p-3 bg-white rounded">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="text-muted small">Total Orders</div>
                    <div class="fs-4 fw-bold"><?php echo count($orders); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Total Spent</div>
                    <div class="fs-4 fw-bold text-success">
                        <?php 
                        $totalSpent = array_sum(array_column($orders, 'order_amount'));
                        echo $currency . ' ' . number_format($totalSpent, 2); 
                        ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Member Since</div>
                    <div class="fs-4 fw-bold">
                        <?php echo date('M Y', strtotime($orders[count($orders) - 1]['order_date'])); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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

</body>
</html>
