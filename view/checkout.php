<?php
/**
 * checkout.php
 * Checkout page with order summary and payment simulation
 * Features: Cart summary, payment method selection, simulated payment modal
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';
require_once __DIR__ . '/../controllers/product_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get customer ID from session (check both customer_id and user_id for backwards compatibility)
$customerId = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : ($_SESSION['user_id'] ?? 0);

// DEBUG - log what we found
error_log('checkout.php - Session customer_id: ' . ($_SESSION['customer_id'] ?? 'not set'));
error_log('checkout.php - Session user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
error_log('checkout.php - Using customer ID: ' . $customerId);

// Double-check customer ID is valid
if ($customerId <= 0) {
    header('Location: login.php');
    exit();
}

// Get cart items
$cartItems = get_cart_items_ctr($customerId);

// Get cart summary
$cartSummary = get_cart_summary_ctr($customerId);

// Check if cart is empty
$isCartEmpty = is_cart_empty_ctr($customerId);

// If cart is empty, redirect to cart page
if ($isCartEmpty) {
    header('Location: cart.php');
    exit();
}

// Currency
$currency = 'GHS';

// Calculate shipping (free for this demo)
$shipping = 0.00;
$subtotal = $cartSummary['total_price'] ?? 0;
$total = $subtotal + $shipping;

// Page title
$pageTitle = 'Checkout';
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
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .checkout-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .checkout-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1.5rem;
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

        .order-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-summary-row.total {
            border-bottom: none;
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #667eea;
        }

        .payment-method-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .payment-method-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .payment-method-card input[type="radio"]:checked ~ .payment-content {
            border-left: 4px solid #667eea;
            padding-left: 1rem;
        }

        .btn-payment {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 1rem 3rem;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 1rem;
        }

        .progress-step::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: -1;
        }

        .progress-step:first-child::before {
            left: 50%;
        }

        .progress-step:last-child::before {
            right: 50%;
        }

        .progress-step.active .step-icon {
            background: #667eea;
            color: white;
        }

        .progress-step.completed .step-icon {
            background: #28a745;
            color: white;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0e0e0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .invoice-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
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
                    <a class="nav-link" href="../actions/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Checkout Content -->
<div class="checkout-container">
    <!-- Checkout Header -->
    <div class="checkout-header">
        <h1 class="mb-0">
            <i class="bi bi-credit-card-fill me-2"></i>
            Checkout
        </h1>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="progress-step completed">
            <div class="step-icon">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="step-label">Cart</div>
        </div>
        <div class="progress-step active">
            <div class="step-icon">
                <i class="bi bi-credit-card"></i>
            </div>
            <div class="step-label">Checkout</div>
        </div>
        <div class="progress-step">
            <div class="step-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="step-label">Confirmation</div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Order Details -->
        <div class="col-lg-7">
            <!-- Order Items Section -->
            <div class="checkout-section">
                <h4 class="mb-3">
                    <i class="bi bi-bag-check me-2"></i>
                    Order Items (<?php echo $cartSummary['total_items']; ?>)
                </h4>

                <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="d-flex align-items-center flex-grow-1">
                            <?php 
                            $imagePath = !empty($item['product_image']) 
                                ? '../images/' . htmlspecialchars($item['product_image'])
                                : '../assets/images/placeholder.png';
                            ?>
                            <img src="<?php echo $imagePath; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_title']); ?>" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;"
                                 class="me-3">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h6>
                                <small class="text-muted">
                                    <?php echo $currency . ' ' . number_format($item['product_price'], 2); ?> × <?php echo $item['qty']; ?>
                                </small>
                            </div>
                        </div>
                        <div class="fw-bold">
                            <?php 
                            $itemTotal = $item['product_price'] * $item['qty'];
                            echo $currency . ' ' . number_format($itemTotal, 2); 
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Payment Method Section -->
            <div class="checkout-section">
                <h4 class="mb-3">
                    <i class="bi bi-wallet2 me-2"></i>
                    Payment Method
                </h4>

                <div class="payment-methods">
                    <label class="payment-method-card">
                        <input type="radio" name="payment_method" value="Mobile Money" class="d-none">
                        <div class="payment-content d-flex align-items-center">
                            <i class="bi bi-phone text-primary fs-2 me-3"></i>
                            <div>
                                <h6 class="mb-0">Mobile Money</h6>
                                <small class="text-muted">Pay via MTN, Vodafone, or AirtelTigo</small>
                            </div>
                        </div>
                    </label>

                    <label class="payment-method-card">
                        <input type="radio" name="payment_method" value="Cash on Delivery" class="d-none">
                        <div class="payment-content d-flex align-items-center">
                            <i class="bi bi-cash text-success fs-2 me-3"></i>
                            <div>
                                <h6 class="mb-0">Cash on Delivery</h6>
                                <small class="text-muted">Pay when you receive your order</small>
                            </div>
                        </div>
                    </label>

                    <label class="payment-method-card">
                        <input type="radio" name="payment_method" value="Simulated Payment" class="d-none" checked>
                        <div class="payment-content d-flex align-items-center">
                            <i class="bi bi-credit-card text-info fs-2 me-3"></i>
                            <div>
                                <h6 class="mb-0">Simulated Payment</h6>
                                <small class="text-muted">Demo payment for testing (No actual charge)</small>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="col-lg-5">
            <div class="checkout-section position-sticky" style="top: 20px;">
                <h4 class="mb-3">
                    <i class="bi bi-receipt me-2"></i>
                    Order Summary
                </h4>

                <!-- Invoice Number -->
                <div class="invoice-box mb-3">
                    <small class="text-muted d-block mb-1">Invoice Number</small>
                    <strong class="checkout-invoice-no invoice-number">Generating...</strong>
                </div>

                <!-- Summary Details -->
                <div class="order-summary-row">
                    <span>Subtotal (<?php echo $cartSummary['total_items']; ?> items):</span>
                    <span class="checkout-subtotal"><?php echo $currency . ' ' . number_format($subtotal, 2); ?></span>
                </div>

                <div class="order-summary-row">
                    <span>Shipping:</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>

                <div class="order-summary-row">
                    <span>Tax:</span>
                    <span><?php echo $currency . ' 0.00'; ?></span>
                </div>

                <div class="order-summary-row total">
                    <span>Total:</span>
                    <span class="checkout-total" data-total="<?php echo $total; ?>">
                        <?php echo $currency . ' ' . number_format($total, 2); ?>
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4">
                    <button class="btn btn-payment btn-primary w-100 proceed-to-payment-btn">
                        <i class="bi bi-lock-fill me-2"></i>
                        Proceed to Payment
                    </button>

                    <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2 back-to-cart-btn">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Cart
                    </a>
                </div>

                <!-- Security Badge -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-shield-check me-1"></i> 
                        Secure Checkout - SSL Encrypted
                    </small>
                </div>
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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Checkout JS -->
<script src="../assets/js/checkout.js"></script>

<script>
// Initialize checkout data from PHP
$(document).ready(function() {
    // Set checkout state from PHP data
    if (typeof CheckoutApp !== 'undefined') {
        CheckoutApp.setState({
            orderTotal: <?php echo $total; ?>,
            currency: '<?php echo $currency; ?>'
        });
    }

    // Handle payment method selection visual feedback
    $('input[name="payment_method"]').on('change', function() {
        $('.payment-method-card').removeClass('border-primary');
        $(this).closest('.payment-method-card').addClass('border-primary');
    });

    // Trigger click on checked radio to show initial selection
    $('input[name="payment_method"]:checked').trigger('change');
});
</script>

</body>
</html>
