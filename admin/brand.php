<?php
require_once '../settings/core.php';
ensure_session();
if (!isLoggedIn()) {
  redirect('../view/login.php');
}
if (!isAdmin()) {
  redirect('../view/login.php');
}

if (file_exists(__DIR__ . '/../controllers/brand_controller.php')) {
    require_once __DIR__ . '/../controllers/brand_controller.php';
}

$flash = $flash ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['brand_name'])) {
    $brandName = trim($_POST['brand_name']);

    if ($brandName === '') {
        $flash = 'Please provide a valid brand name.';
    } else {
        $handled = false;

        if (function_exists('add_brand_ctr')) {
            try {
                $res = add_brand_ctr($brandName);
                $handled = true;
            } catch (ArgumentCountError $e) {
                try {
                    $res = add_brand_ctr($brandName, null);
                    $handled = true;
                } catch (ArgumentCountError $e2) {
                    try {
                        $res = add_brand_ctr($brandName, null, null);
                        $handled = true;
                    } catch (Throwable $e3) {
                        $flash = 'Failed to create brand.';
                    }
                }
            }

            if ($handled) {
                $ok = is_array($res) ? !empty($res['success']) : (bool)$res;
                if ($ok) {
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                }
                $flash = is_array($res) ? ($res['message'] ?? 'Failed to create brand.') : 'Failed to create brand.';
            }
        }

        if (!$handled) {
            global $pdo;
            if ($pdo instanceof PDO) {
                try {
                    $sqlVariants = [
                        'INSERT INTO brands (name, created_at) VALUES (?, NOW())',
                        'INSERT INTO brands (brand_name, created_at) VALUES (?, NOW())',
                    ];
                    $inserted = false;
                    foreach ($sqlVariants as $sql) {
                        try {
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$brandName]);
                            $inserted = true;
                            break;
                        } catch (Throwable $inner) {
                            continue;
                        }
                    }

                    if ($inserted) {
                        header('Location: ' . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                    $flash = 'Failed to create brand.';
                } catch (Throwable $e) {
                    $flash = 'Failed to create brand.';
                }
            } else {
                $flash = 'Brand handler not available.';
            }
        }
    }
}

$brands = [];
$brandIdKey = 'brand_id';
$brandNameKey = 'brand_name';

// Try controller
if (function_exists('list_brands_ctr')) {
    $tmp = list_brands_ctr(null, null, 'name_asc');
    if (is_array($tmp)) $brands = $tmp;
}

// Fallback: Brand class
if (empty($brands) && class_exists('Brand')) {
    $b = new Brand();
    $tmp = $b->listBrands(null, null, 'name_asc');
    if (is_array($tmp) && !empty($tmp)) $brands = $tmp;
}

// Fallback: direct DB using PDO (covers both schemas: brand_id/brand_name OR id/name)
if (empty($brands)) {
    global $pdo;
    if ($pdo instanceof PDO) {
        $candidates = [
            'SELECT brand_id AS brand_id, brand_name AS brand_name FROM brands ORDER BY brand_name ASC',
            'SELECT id        AS brand_id, name       AS brand_name FROM brands ORDER BY name ASC',
            'SELECT brand_id AS brand_id, name       AS brand_name FROM brands ORDER BY name ASC',
            'SELECT id        AS brand_id, brand_name AS brand_name FROM brands ORDER BY brand_name ASC',
        ];
        foreach ($candidates as $sql) {
            try {
                $stmt = $pdo->query($sql);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) { $brands = $rows; break; }
            } catch (Throwable $e) {
                // try next variant
            }
        }
    }
}

// Normalize keys
if (!empty($brands)) {
    $first = $brands[0];
    if (isset($first['id']))        $brandIdKey = 'id';
    if (isset($first['name']))      $brandNameKey = 'name';
    if (isset($first['brand_id']))  $brandIdKey = 'brand_id';
    if (isset($first['brand_name']))$brandNameKey = 'brand_name';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Brand Management</title>

<!-- Bootstrap 5 + Icons (CSS only) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"/>

<style>
/* ====== Global + Layout ====== */
:root{
  --ink: hsl(158, 82%, 15%);            /* slate-900 */
  --ink-sub:#475569;         /* slate-600 */
  --line:#e5e7eb;
  --brand:hsl(158, 82%, 15%);           /* dark accent */
  --muted:#f8fafc;           /* page bg */
  --radius-xl:22px;
  --radius-lg:16px;
  --shadow-xl:0 20px 40px rgba(0,0,0,.08);
  --blur:12px;
}
*{box-sizing:border-box}
body{
  margin:0;
  background:var(--muted);
  color:var(--ink);
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}
.layout{
  display:grid;
  grid-template-columns: 280px 1fr;
  gap:20px;
  padding:24px;
  min-height:100vh;
}

/* ====== Sidebar (exact styling as category page) ====== */
.sdbar{
  position: sticky; top:24px;
  height: calc(100vh - 48px);
  padding:18px;
  border-radius: var(--radius-xl);
  background: rgba(255,255,255,.55);
  backdrop-filter: blur(var(--blur));
  -webkit-backdrop-filter: blur(var(--blur));
  border:1px solid rgba(0,0,0,.07);
  box-shadow: var(--shadow-xl);
  display:flex; flex-direction:column; gap:14px;
}
.sdbar__brand{
  display:flex; align-items:center; gap:10px;
  padding:8px 10px; margin-bottom:8px;
}
.sdbar__logo{
  width:34px;height:34px;border-radius:10px;
  display:grid; place-items:center;
  background:#111827;color:#fff; font-weight:700; font-size:16px;
}
.sdbar__title{font-weight:700; font-size:18px; letter-spacing:.2px}
.sdbar__label{
  margin:8px 10px 2px; font-size:12px; color:#hsl(158, 82%, 15%); letter-spacing:.08em;
}
.sdbar__group{display:flex; flex-direction:column; gap:8px}
.sdbar__item{
  display:flex; align-items:center; gap:12px;
  padding:12px 12px; border-radius:14px;
  color:inherit; text-decoration:none;
  transition: background .15s ease, transform .05s ease;
}
.sdbar__item:hover{background: rgba(17,24,39,.06)}
.sdbar__item:active{transform: translateY(1px)}
.sdbar__item--active{ background:#111827; color:#fff }
.sdbar__icon{width:20px; display:inline-grid; place-items:center}
.sdbar__settings{margin-top:auto}

/* ====== Main Carded Area ====== */
.main{
  background:#fff;
  border-radius: var(--radius-xl);
  border:1px solid rgba(0,0,0,.07);
  box-shadow: var(--shadow-xl);
  padding:24px;
  min-height: calc(100vh - 48px);
}
.page-head{
  display:flex; align-items:flex-start; justify-content:space-between;
  gap:16px; margin-bottom:18px;
}
.page-title{
  font-size: clamp(22px, 2.6vw, 34px);
  font-weight:800; letter-spacing:.2px;
}
.page-sub{ color:var(--ink-sub); margin-top:4px }

/* Add Brand Card */
.card-soft{
  background:#fff; border:1px solid rgba(0,0,0,.06);
  border-radius:16px; box-shadow:0 12px 30px rgba(0,0,0,.06);
}
.card-soft .card-header{
  border-bottom:1px solid var(--line);
  background:linear-gradient(180deg,#fff 0%,#fafafa 100%);
  border-top-left-radius:16px; border-top-right-radius:16px;
}
.form-floating>label{ color:#hsl(158, 82%, 15%) }
.btn-dark{ background:var(--brand); border-color:var(--brand) }
.btn-dark:hover{ filter:brightness(1.08) }

/* Category Group Section */
.cat-group{
  border:1px solid rgba(0,0,0,.06);
  border-radius:16px; background:#fff;
  box-shadow:0 12px 30px rgba(0,0,0,.06); margin-bottom:20px;
}
.cat-group__head{
  padding:16px; display:flex; align-items:center; justify-content:space-between;
  border-bottom:1px solid var(--line);
  background:linear-gradient(180deg,#fff 0%,#fafafa 100%);
  border-top-left-radius:16px; border-top-right-radius:16px;
}
.cat-group__meta{ color:var(--ink-sub); font-size:14px }

/* Brand Cards Grid */
.brand-grid{
  display:grid; gap:16px; padding:16px;
  grid-template-columns: repeat(2, minmax(0,1fr));
}
.brand-card{
  border:1px solid var(--line);
  border-radius:14px; padding:16px; background:#hsl(158, 82%, 15%);
  transition: box-shadow .15s ease, transform .05s ease, border-color .15s ease;
}
.brand-card:hover{
  box-shadow: 0 12px 24px rgba(0,0,0,.06);
  border-color: rgba(0,0,0,.12);
}
.brand-card__name{ font-weight:700; font-size:18px }
.brand-card__cat{ color:hsl(158, 82%, 15%);font-size:14px }
.brand-card__actions{
  margin-left:auto; display:flex; gap:8px;
}
.icon-btn{
  display:inline-flex; align-items:center; justify-content:center;
  height:36px; width:36px; border-radius:10px; border:1px solid var(--line);
  background:#fff; color:var(--ink);
  transition: background .15s ease, border-color .15s ease;
}
.icon-btn:hover{ background:#hsl(158, 82%, 15%); border-color:#e2e8f0 }

/* Responsive */
@media (max-width: 960px){
  .layout{ grid-template-columns: 1fr }
  .sdbar{ position: static; height:auto; }
  .main{ margin-top: 8px }
  .brand-grid{ grid-template-columns: 1fr }
}
</style>
</head>

<body>
<div class="layout">

  <!-- ===== Sidebar (same style as Category Page) ===== -->
  <aside class="sdbar">
    <div class="sdbar__brand">
      <span class="sdbar__title">Z's Page</span>
    </div>

    <div class="sdbar__group">
      <a href="category.php" class="sdbar__item">
        <span class="sdbar__icon"><i class="bi bi-speedometer2"></i></span>
        <span>Category Management</span>
      </a>
      <a href="brand.php" class="sdbar__item sdbar__item--active">
        <span class="sdbar__icon"><i class="bi bi-folder2"></i></span>
        <span>Brand Management</span>
      </a>
      <a href="product.php" class="sdbar__item">
        <span class="sdbar__icon"><i class="bi bi-tags"></i></span>
        <span>Product Management</span>
      </a>
      <a href="../view/all_product.php" class="sdbar__item">
        <span class="sdbar__icon"><i class="bi bi-box-seam"></i></span>
        <span>Product Display</span>
      </a>
    </div>

    <a href="#" class="sdbar__item sdbar__settings">
      <span class="sdbar__icon"><i class="bi bi-gear"></i></span>
      <span>Settings</span>
    </a>
  </aside>

  <!-- ===== Main Content ===== -->
  <main class="main">
    <!-- Header -->
    <div class="page-head">
      <div>
        <div class="page-title">Manage Brands</div>
        <div class="page-sub">Add, edit, and organize your brand portfolio</div>
      </div>
      <a href="category.php" class="btn btn-outline-secondary rounded-circle" style="width:44px;height:44px" title="Back to Categories">
        <i class="bi bi-arrow-left"></i>
      </a>
    </div>

    <!-- Flash -->
    <?php if (!empty($flash)): ?>
      <?php if ($flash === 'Brand already exists in this category.'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert" id="brandFlashAlert">
          <?= htmlspecialchars($flash) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
          (function(){
            // auto-hide after 3.5s
            setTimeout(function(){
              var el = document.getElementById('brandFlashAlert');
              if (el) el.classList.add('d-none');
            }, 3500);
          })();
        </script>
      <?php else: ?>
        <div class="alert alert-warning"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>
      <?php unset($flash); ?>
    <?php endif; ?>

    <!-- Add Brand Card -->
    <div class="card card-soft mb-4">
      <div class="card-header">
        <span class="fw-semibold">Add New Brand</span>
      </div>
      <div class="card-body">
        <!-- AJAX add form: JS (assets/js/brand.js) intercepts #brandForm submit -->
        <form id="brandForm" method="post" class="row g-3 align-items-end">
          <div class="col-lg-8">
            <div class="form-floating">
              <input type="text" class="form-control" id="brand_name" name="brand_name" placeholder="Brand Name" required>
              <label for="brand_name">Brand Name</label>
            </div>
          </div>
          <div class="col-lg-4 d-grid">
            <button class="btn btn-dark" type="submit"><i class="bi bi-plus-lg me-1"></i>Add Brand</button>
          </div>
        </form>
      </div>
    </div>
 
    <!-- Inline Edit Panel (hidden) - used by AJAX edit flow -->
    <div id="editPanel" class="card card-soft mb-4" style="display:none;">
      <div class="card-header">
        <span class="fw-semibold">Edit Brand</span>
      </div>
      <div class="card-body">
        <form id="editBrandForm" class="row g-3 align-items-end">
          <input type="hidden" name="brand_id" value="">
          <div class="col-lg-8">
            <div class="form-floating">
              <input type="text" class="form-control" id="edit_brand_name" name="brand_name" placeholder="Brand Name" required>
              <label for="edit_brand_name">Brand Name</label>
            </div>
          </div>
          <div class="col-lg-4 d-grid">
            <button class="btn btn-dark" type="submit">Save</button>
          </div>
          <div class="col-12">
            <button id="editCancel" type="button" class="btn btn-outline-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Brand list -->
    <section class="brand-list card-soft">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold">All Brands</span>
        <span class="text-muted small" id="brandTotalCount"><?= count($brands) ?> total</span>
      </div>
      <div class="brand-grid" id="brandGrid">
        <?php if (empty($brands)): ?>
          <div class="text-muted">No brands found. Add one using the form above.</div>
        <?php else: ?>
          <?php foreach ($brands as $b): ?>
            <?php
              $brandId = isset($b[$brandIdKey]) ? (int)$b[$brandIdKey] : 0;
              $brandName = isset($b[$brandNameKey]) ? $b[$brandNameKey] : '';
            ?>
            <div class="brand-card d-flex align-items-center gap-2">
              <div class="brand-card__name"><?= htmlspecialchars($brandName) ?></div>
              <div class="brand-card__actions ms-auto">
                <button class="icon-btn btn-edit" title="Edit"
                  data-brand-id="<?= $brandId ?>"
                  data-brand-name="<?= htmlspecialchars($brandName, ENT_QUOTES) ?>">
                  <i class="bi bi-pencil-square"></i>
                </button>
                <button class="icon-btn btn-delete" title="Delete" data-brand-id="<?= $brandId ?>">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

  </main>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>console.info('brand.php: loading brand.js v=2025-11-01-7');</script>
<script src="../assets/js/brand.js?v=2025-11-01-7"></script>
</body>
</html>
<?php

