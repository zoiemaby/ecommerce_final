<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>All Products — Z's Storefront</title>

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
.hero{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:var(--r-xl);box-shadow:var(--shadow);padding:24px}
.filters{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:var(--r);padding:16px;box-shadow:0 4px 12px rgba(0,0,0,.04)}
.card-prod{border:1px solid var(--line);border-radius:16px;overflow:hidden;transition:box-shadow .15s,border-color .15s,transform .15s;background:#fff;height:100%}
.card-prod:hover{box-shadow:0 12px 24px rgba(0,0,0,.06);border-color:rgba(0,0,0,.12);transform:translateY(-2px)}
.card-prod .thumb{aspect-ratio:4/3;object-fit:cover;width:100%;background:#f1f5f9}
.card-prod .card-body{padding:16px;display:flex;flex-direction:column}
.price{font-weight:700;font-size:20px;color:var(--ink)}
.badge-soft{background:#f1f5f9;border:1px solid #e2e8f0;padding:4px 10px;font-size:12px}
.pagination{gap:6px}
.pagination .page-link{border-radius:10px;border:1px solid var(--line);color:var(--ink);font-weight:500}
.pagination .page-item.active .page-link{background:var(--ink);border-color:var(--ink)}
#loader{text-align:center;padding:48px;color:var(--sub)}
.skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:loading 1.5s ease-in-out infinite}
@keyframes loading{0%{background-position:200% 0}100%{background-position:-200% 0}}
.empty-state{text-align:center;padding:48px 24px;color:var(--sub)}
.empty-state i{font-size:64px;color:#cbd5e1;margin-bottom:16px}
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
        <li class="nav-item"><a class="nav-link active" href="all_product.php">All Products</a></li>
        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
      </ul>
      <form class="d-flex gap-2" role="search" id="searchForm" onsubmit="return false;">
        <div class="input-group" style="min-width:280px">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input id="searchQuery" class="form-control" type="search" placeholder="Search products..." autocomplete="off">
        </div>
        <button class="btn btn-dark" type="button" id="btnSearch">Search</button>
        <a href="cart.php" class="btn btn-outline-dark position-relative">
          <i class="bi bi-cart3"></i> Cart
          <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">
            0
          </span>
        </a>
      </form>
    </div>
  </div>
</nav>

<!-- Header/Hero -->
<header class="container container-narrow my-4">
  <div class="hero">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">All Products</h1>
        <div class="text-muted">Browse, filter, and discover the latest in our catalog</div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-secondary" id="totalCount">0 products</span>
        <button class="btn btn-outline-secondary btn-sm" id="btnResetFilters" style="display:none">
          <i class="bi bi-x-circle"></i> Reset Filters
        </button>
      </div>
    </div>
  </div>
</header>

<!-- Filters Bar -->
<section class="container container-narrow mb-3">
  <div class="filters d-flex flex-wrap align-items-center gap-3">
    <div class="flex-fill" style="min-width:200px">
      <label class="form-label small text-muted mb-1">Category</label>
      <select id="filterCategory" class="form-select">
        <option value="">All Categories</option>
      </select>
    </div>
    <div class="flex-fill" style="min-width:200px">
      <label class="form-label small text-muted mb-1">Brand</label>
      <select id="filterBrand" class="form-select">
        <option value="">All Brands</option>
      </select>
    </div>
    <div class="flex-fill" style="min-width:180px">
      <label class="form-label small text-muted mb-1">Sort By</label>
      <select id="filterSort" class="form-select">
        <option value="newest">Newest First</option>
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="name_asc">Name: A to Z</option>
        <option value="name_desc">Name: Z to A</option>
      </select>
    </div>
  </div>
</section>

<!-- Products Grid -->
<main class="container container-narrow my-3">
  <!-- Loader -->
  <div id="loader" style="display:none">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <div class="mt-2 text-muted">Loading products...</div>
  </div>

  <!-- Products Grid -->
  <div id="productsGrid" class="row g-3">
    <!-- Products will be rendered here by JS -->
  </div>

  <!-- Empty State -->
  <div id="emptyState" class="empty-state" style="display:none">
    <i class="bi bi-inbox"></i>
    <h5>No products found</h5>
    <p class="text-muted">Try adjusting your filters or search query</p>
  </div>

  <!-- Pagination -->
  <nav class="mt-4" id="paginationContainer" style="display:none">
    <ul id="pagination" class="pagination justify-content-center">
      <!-- Pagination will be rendered here by JS -->
    </ul>
  </nav>
</main>

<footer class="py-4 text-center text-muted small">© 2025 Z's Storefront. All rights reserved.</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php $cartJsV = file_exists(__DIR__.'/../assets/js/cart.js') ? filemtime(__DIR__.'/../assets/js/cart.js') : time(); ?>
<script src="../assets/js/cart.js?v=<?php echo $cartJsV; ?>"></script>
<script src="../assets/js/products.js?v=2.1"></script>
<script>
/* Initialize products page */
document.addEventListener('DOMContentLoaded', () => {
  if (typeof ProductsApp !== 'undefined') {
    ProductsApp.init('all_product');
  }
});
</script>
</body>
</html>
