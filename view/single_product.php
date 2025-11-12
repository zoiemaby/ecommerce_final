<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Product Details — Z's Storefront</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
  --ink:hsl(158, 82%, 15%); --sub:#64748b; --line:#e5e7eb; --bg:#f8fafc; --accent:#111827;
  --r-xl:22px; --r:14px; --shadow:0 20px 40px rgba(0,0,0,.08);
}
body{background:var(--bg);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial;margin:0;padding:0}
.container-narrow{max-width:1200px}
.navbar{background:#fff;border-bottom:1px solid rgba(0,0,0,.06);box-shadow:0 2px 8px rgba(0,0,0,.04)}
.navbar-brand{font-weight:800;font-size:24px;color:var(--ink)!important}
.product-image{width:100%;aspect-ratio:1;object-fit:cover;border-radius:var(--r);border:1px solid var(--line);background:#f1f5f9}
.price-tag{font-size:32px;font-weight:800;color:var(--ink)}
.badge-soft{background:#f1f5f9;border:1px solid #e2e8f0;padding:6px 12px;font-size:14px}
.keyword-tag{display:inline-block;background:#e0f2fe;border:1px solid #7dd3fc;border-radius:999px;padding:4px 12px;font-size:13px;margin:4px}
.btn-cart{font-size:18px;padding:14px 32px}
#loader{text-align:center;padding:48px;color:var(--sub)}
.product-detail-card{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:var(--r-xl);box-shadow:var(--shadow);padding:32px}
.breadcrumb{background:none;padding:0;margin-bottom:24px}
.breadcrumb-item+.breadcrumb-item::before{color:var(--sub)}
</style>
</head>
<body>

<!-- Top Nav -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container container-narrow">
    <a class="navbar-brand" href="all_product.php">Z's</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="all_product.php">All Products</a></li>
        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
      </ul>
      <div class="d-flex gap-2">
        <a href="cart.php" class="btn btn-outline-dark position-relative">
          <i class="bi bi-cart3"></i> Cart
          <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">
            0
          </span>
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- Main Content -->
<main class="container container-narrow my-4">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="all_product.php">Products</a></li>
      <li class="breadcrumb-item active" aria-current="page" id="breadcrumbTitle">Loading...</li>
    </ol>
  </nav>

  <!-- Loader -->
  <div id="loader">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <div class="mt-2 text-muted">Loading product details...</div>
  </div>

  <!-- Product Detail -->
  <div id="productDetail" class="product-detail-card" style="display:none">
    <div class="row g-4">
      <!-- Product Image -->
      <div class="col-lg-5">
        <img id="productImage" class="product-image" src="" alt="Product">
        <div class="mt-3 text-muted small text-center">
          Product ID: <span id="productId"></span>
        </div>
      </div>

      <!-- Product Info -->
      <div class="col-lg-7">
        <div class="mb-3">
          <span class="badge-soft me-2" id="productCategory"></span>
          <span class="badge-soft" id="productBrand"></span>
        </div>

        <h1 class="h2 fw-bold mb-3" id="productTitle"></h1>
        
        <div class="price-tag mb-4">
          GHS <span id="productPrice"></span>
        </div>

        <div class="mb-4">
          <h6 class="text-muted mb-2">Description</h6>
          <p id="productDescription" class="lead"></p>
        </div>

        <div class="mb-4" id="keywordsSection">
          <h6 class="text-muted mb-2">Keywords</h6>
          <div id="productKeywords"></div>
        </div>

        <!-- Quantity Selector -->
        <div class="mb-4">
          <h6 class="text-muted mb-2">Quantity</h6>
          <div class="d-flex align-items-center gap-3">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-secondary" id="decreaseQty">
                <i class="bi bi-dash"></i>
              </button>
              <input type="number" id="productQuantity" class="form-control text-center" value="1" min="1" max="100" style="max-width: 80px;">
              <button type="button" class="btn btn-outline-secondary" id="increaseQty">
                <i class="bi bi-plus"></i>
              </button>
            </div>
            <small class="text-muted">Max: 100</small>
          </div>
        </div>

        <div class="d-flex gap-3">
          <button class="btn btn-dark btn-cart flex-fill add-to-cart-btn" id="btnAddToCart" data-product-id="" data-quantity="1">
            <i class="bi bi-cart-plus me-2"></i>Add to Cart
          </button>
          <a href="cart.php" class="btn btn-outline-dark">
            <i class="bi bi-cart3"></i> View Cart
          </a>
        </div>

        <div class="alert alert-success mt-4" role="alert">
          <i class="bi bi-shield-check me-2"></i>
          <strong>Secure Shopping:</strong> Safe checkout with SSL encryption.
        </div>
      </div>
    </div>
  </div>

  <!-- Error State -->
  <div id="errorState" class="text-center py-5" style="display:none">
    <i class="bi bi-exclamation-triangle" style="font-size:64px;color:#ef4444"></i>
    <h5 class="mt-3">Product Not Found</h5>
    <p class="text-muted">The product you're looking for doesn't exist or has been removed.</p>
    <a href="all_product.php" class="btn btn-primary">Browse All Products</a>
  </div>
</main>

<footer class="py-4 text-center text-muted small">© 2025 Z's Storefront. All rights reserved.</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php $cartJsV = file_exists(__DIR__.'/../assets/js/cart.js') ? filemtime(__DIR__.'/../assets/js/cart.js') : time(); ?>
<script src="../assets/js/cart.js?v=<?php echo $cartJsV; ?>"></script>
<script src="../assets/js/products.js?v=2.1"></script>
<script>
/* Initialize single product page */
document.addEventListener('DOMContentLoaded', () => {
  if (typeof ProductsApp !== 'undefined') {
    ProductsApp.init('single_product');
  }

  // Quantity controls
  $('#decreaseQty').on('click', function() {
    const input = $('#productQuantity');
    const currentVal = parseInt(input.val()) || 1;
    if (currentVal > 1) {
      const newVal = currentVal - 1;
      input.val(newVal);
      $('#btnAddToCart').data('quantity', newVal);
    }
  });

  $('#increaseQty').on('click', function() {
    const input = $('#productQuantity');
    const currentVal = parseInt(input.val()) || 1;
    if (currentVal < 100) {
      const newVal = currentVal + 1;
      input.val(newVal);
      $('#btnAddToCart').data('quantity', newVal);
    }
  });

  $('#productQuantity').on('change', function() {
    let val = parseInt($(this).val()) || 1;
    if (val < 1) val = 1;
    if (val > 100) val = 100;
    $(this).val(val);
    $('#btnAddToCart').data('quantity', val);
  });
});
</script>
</body>
</html>
