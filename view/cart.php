<?php
/**
 * cart.php
 * Shopping cart page
 * Displays all items in user's cart with quantity management
 * Features: Continue Shopping, Proceed to Checkout, Empty Cart buttons
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
error_log('cart.php - Session customer_id: ' . ($_SESSION['customer_id'] ?? 'not set'));
error_log('cart.php - Session user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
error_log('cart.php - Using customer ID: ' . $customerId);

// Double-check customer ID is valid
if ($customerId <= 0) {
    // Session exists but customer_id is missing - redirect to login
    header('Location: login.php');
    exit();
}

// Get cart items
$cartItems = get_cart_items_ctr($customerId);

// Get cart summary
$cartSummary = get_cart_summary_ctr($customerId);

// Check if cart is empty
$isCartEmpty = is_cart_empty_ctr($customerId);

// Currency
$currency = 'GHS';

// Page title
$pageTitle = 'Shopping Cart';
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
        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .cart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .cart-item-row {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .cart-item-row:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.375rem;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: #667eea;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: #764ba2;
            transform: scale(1.1);
        }

        .cart-summary {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 2rem;
            position: sticky;
            top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row.total {
            border-bottom: none;
            font-size: 1.25rem;
            font-weight: bold;
            color: #667eea;
            margin-top: 1rem;
        }

        .empty-cart-message {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
        }

        .btn-checkout {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 0.75rem;
            font-weight: bold;
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
                    <a class="nav-link active position-relative" href="cart.php">
                        <i class="bi bi-cart"></i> Cart
                        <span class="cart-badge"><?php echo $cartSummary['total_items'] ?? 0; ?></span>
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

<!-- Cart Content -->
<div class="cart-container">
    <!-- Cart Header -->
    <div class="cart-header">
        <h1 class="mb-0">
            <i class="bi bi-cart-fill me-2"></i>
            Shopping Cart
        </h1>
        <p class="mb-0 mt-2 opacity-75">
            <?php echo $cartSummary['total_items'] ?? 0; ?> item(s) in your cart
        </p>
    </div>

    <?php if ($isCartEmpty): ?>
        <!-- Empty Cart Message -->
        <div class="empty-cart-message">
            <i class="bi bi-cart-x" style="font-size: 5rem; color: #ccc;"></i>
            <h2 class="mt-4">Your cart is empty</h2>
            <p class="text-muted">Add some products to get started!</p>
            <a href="all_product.php" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-shop me-2"></i> Continue Shopping
            </a>
        </div>

    <?php else: ?>
        <!-- Cart Items and Summary -->
        <div class="row">
            <!-- Cart Items Column -->
            <div class="col-lg-8">
                <div class="cart-items-container">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item-row row align-items-center" data-product-id="<?php echo $item['p_id']; ?>">
                            <!-- Product Image -->
                            <div class="col-md-2 text-center">
                                <?php 
                                $imagePath = !empty($item['product_image']) 
                                    ? '../images/' . htmlspecialchars($item['product_image'])
                                    : '../assets/images/placeholder.png';
                                ?>
                                <img src="<?php echo $imagePath; ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_title']); ?>" 
                                     class="product-image">
                            </div>

                            <!-- Product Details -->
                            <div class="col-md-4">
                                <h5 class="mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h5>
                                <p class="text-muted small mb-1">
                                    <?php echo htmlspecialchars($item['cat_name'] ?? 'Category'); ?>
                                </p>
                                <p class="text-muted small mb-0">
                                    Brand: <?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?>
                                </p>
                            </div>

                            <!-- Price -->
                            <div class="col-md-2 text-center">
                                <p class="mb-0 text-muted small">Price</p>
                                <p class="mb-0 fw-bold"><?php echo $currency . ' ' . number_format($item['product_price'], 2); ?></p>
                            </div>

                            <!-- Quantity Controls -->
                            <div class="col-md-2">
                                <p class="mb-2 text-muted small text-center">Quantity</p>
                                <div class="quantity-controls justify-content-center">
                                    <button class="quantity-btn quantity-decrease" type="button">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" 
                                           class="quantity-input form-control" 
                                           data-product-id="<?php echo $item['p_id']; ?>"
                                           value="<?php echo $item['qty']; ?>" 
                                           min="1" 
                                           max="100">
                                    <button class="quantity-btn quantity-increase" type="button">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Subtotal & Remove -->
                            <div class="col-md-2 text-center">
                                <p class="mb-2 text-muted small">Subtotal</p>
                                <p class="mb-2 fw-bold item-subtotal">
                                    <?php 
                                    $subtotal = $item['product_price'] * $item['qty'];
                                    echo $currency . ' ' . number_format($subtotal, 2); 
                                    ?>
                                </p>
                                <button class="btn btn-sm btn-outline-danger remove-from-cart-btn" 
                                        data-product-id="<?php echo $item['p_id']; ?>"
                                        data-product-title="<?php echo htmlspecialchars($item['product_title']); ?>">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Actions -->
                <div class="cart-actions-container mt-3 d-flex justify-content-between">
                    <a href="all_product.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i> Continue Shopping
                    </a>
                    <button class="btn btn-outline-danger empty-cart-btn">
                        <i class="bi bi-trash me-2"></i> Empty Cart
                    </button>
                </div>
            </div>

            <!-- Cart Summary Column -->
            <div class="col-lg-4">
                <div class="cart-summary cart-summary-container">
                    <h4 class="mb-3">
                        <i class="bi bi-receipt me-2"></i> Order Summary
                    </h4>

                    <div class="summary-row">
                        <span>Total Items:</span>
                        <span class="cart-total-items"><?php echo $cartSummary['total_items'] ?? 0; ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Total Quantity:</span>
                        <span class="cart-total-quantity"><?php echo $cartSummary['total_quantity'] ?? 0; ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span class="cart-subtotal"><?php echo $currency . ' ' . number_format($cartSummary['total_price'] ?? 0, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span class="text-success">FREE</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total:</span>
                        <span class="cart-grand-total"><?php echo $currency . ' ' . number_format($cartSummary['total_price'] ?? 0, 2); ?></span>
                    </div>

                    <button class="btn btn-checkout btn-primary w-100 mt-3 proceed-to-checkout-btn">
                        <i class="bi bi-credit-card me-2"></i> Proceed to Checkout
                    </button>

                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-shield-check me-1"></i> Secure Checkout
                        </small>
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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Cart JS -->
<?php $cartJsV = file_exists(__DIR__.'/../assets/js/cart.js') ? filemtime(__DIR__.'/../assets/js/cart.js') : time(); ?>
<script src="../assets/js/cart.js?v=<?php echo $cartJsV; ?>"></script>

<!-- Initialize cart state with server data -->
<script>
    // Initialize cart state from PHP data
    $(document).ready(function() {
        if (typeof CartApp !== 'undefined') {
            CartApp.setState({
                items: <?php echo json_encode($cartItems ?: []); ?>,
                count: <?php echo ($cartSummary['total_items'] ?? 0); ?>,
                summary: {
                    total_items: <?php echo ($cartSummary['total_items'] ?? 0); ?>,
                    total_quantity: <?php echo ($cartSummary['total_quantity'] ?? 0); ?>,
                    total_price: <?php echo ($cartSummary['total_price'] ?? 0); ?>
                }
            });
            console.log('Cart state initialized from server:', {
                count: <?php echo ($cartSummary['total_items'] ?? 0); ?>,
                total_price: <?php echo ($cartSummary['total_price'] ?? 0); ?>
            });
        }
    });
</script>

</body>
</html>
