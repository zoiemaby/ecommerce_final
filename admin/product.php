<?php
require_once '../settings/core.php';
ensure_session();
if (!isLoggedIn()) {
  header('Location: ../view/login.php'); exit;
}
// Only admins allowed — remove/change if sellers should be allowed
if (!isAdmin()) {
  header('Location: ../view/login.php'); exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Products Management — Admin (Frontend Only)</title>

<!-- Bootstrap 5 + Icons + jQuery + SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ========= THEME (matches your Category/Brand pages) ========= */
:root{
  --ink:hsl(158, 82%, 15%); --sub:hsl(158, 82%, 15%); --line:#e5e7eb; --bg:#f8fafc;
  --accent:#111827; --r-xl:22px; --r-lg:16px; --shadow:0 20px 40px hsl(158, 82%, 15%)s;
  --glass: rgba(255,255,255,.55);
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial}

/* ========= LAYOUT ========= */
.layout{display:grid;grid-template-columns:280px 1fr;gap:20px;padding:24px;min-height:100vh}

/* Sidebar (exact style used before) */
.sdbar{position:sticky;top:24px;height:calc(100vh - 48px);padding:18px;border-radius:var(--r-xl);
  background:var(--glass);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
  border:1px solid rgba(0,0,0,.07);box-shadow:var(--shadow);display:flex;flex-direction:column;gap:14px}
.sdbar__brand{display:flex;align-items:center;gap:10px;padding:8px 10px;margin-bottom:8px}
.sdbar__logo{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:#111827;color:#fff;font-weight:700}
.sdbar__title{font-weight:700;font-size:18px}
.sdbar__group{display:flex;flex-direction:column;gap:8px}
.sdbar__item{display:flex;align-items:center;gap:12px;padding:12px;border-radius:14px;color:inherit;text-decoration:none;transition:.15s}
.sdbar__item:hover{background:rgba(17,24,39,.06)}
.sdbar__item--active{background:#111827;color:#fff}
.sdbar__icon{width:20px;display:inline-grid;place-items:center}
.sdbar__settings{margin-top:auto}

/* Main */
.main{background:#fff;border:1px solid rgba(0,0,0,.07);border-radius:var(--r-xl);box-shadow:var(--shadow);padding:24px;min-height:calc(100vh - 48px)}
.page-head{display:flex;gap:14px;align-items:center;justify-content:space-between;margin-bottom:16px}
.page-title{font-size:clamp(22px,2.6vw,32px);font-weight:800}
.page-sub{color:var(--sub)}

/* Toolbar */
.toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:18px}
.toolbar .form-select,.toolbar .form-control{min-width:190px}
.view-toggle .btn{border-radius:10px}

/* Cards */
.card-soft{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,.06)}
.card-soft .card-header{border-bottom:1px solid var(--line);background:linear-gradient(180deg,#fff 0,#fafafa 100%)}
.product-card{border:1px solid var(--line);border-radius:14px;overflow:hidden;transition:box-shadow .15s,border-color .15s}
.product-card:hover{box-shadow:0 12px 24px rgba(0,0,0,.06);border-color:rgba(0,0,0,.12)}
.product-img{aspect-ratio:4/3;object-fit:cover;width:100%}

/* Grid / Table */
.grid{display:grid;gap:16px;grid-template-columns:repeat(4,minmax(0,1fr))}
@media (max-width:1200px){.grid{grid-template-columns:repeat(3,1fr)}}
@media (max-width:992px){.grid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:640px){.layout{grid-template-columns:1fr}.sdbar{position:static;height:auto}.grid{grid-template-columns:1fr}}

.table-products th,.table-products td{vertical-align:middle}

/* Modal form bits */
.token-input{display:flex;flex-wrap:wrap;gap:8px;align-items:center;border:1px solid #d1d5db;border-radius:10px;padding:8px}
.token-input input{border:none;outline:none;min-width:140px;flex:1}
.token{background:#eef2ff;border:1px solid #c7d2fe;border-radius:999px;padding:6px 10px;font-size:13px;display:flex;align-items:center;gap:6px}
.token .x{cursor:pointer}
.helper{color:var(--sub);font-size:12px}
.counter{font-size:12px;color:var(--sub)}
.dropzone{border:2px dashed #cbd5e1;border-radius:14px;padding:32px 16px;text-align:center;background:#f8fafc;cursor:pointer;transition:all .2s}
.dropzone:hover{background:#f1f5f9;border-color:#94a3b8}
.dropzone.drag{background:#eef2ff;border-color:#a5b4fc}
.preview-grid{display:grid;gap:10px;grid-template-columns:repeat(5,60px);margin-top:10px}
.preview{position:relative;width:60px;height:60px;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb}
.preview img{width:100%;height:100%;object-fit:cover}
.preview button{position:absolute;top:4px;right:4px;border:none;background:#fff;border-radius:6px;padding:2px 6px;box-shadow:0 2px 10px rgba(0,0,0,.1)}
.price-prefix{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6b7280}
.price-input{padding-left:46px}

/* Back button */
.btn-back{border-radius:50px}
</style>
</head>
<body>

<div class="layout">
  <!-- Sidebar -->
  <aside class="sdbar">
    <div class="sdbar__brand"><span class="sdbar__title">Z's Page</span></div>
    <div class="sdbar__group">
      <a class="sdbar__item" href="category.php"><span class="sdbar__icon"><i class="bi bi-folder2"></i></span>Category Management</a>
      <a class="sdbar__item" href="brand.php"><span class="sdbar__icon"><i class="bi bi-tags"></i></span>Brand Management</a>
      <a class="sdbar__item sdbar__item--active" href="product.php"><span class="sdbar__icon"><i class="bi bi-box-seam"></i></span>Product Management</a>
      <a class="sdbar__item" href="../view/all_product.php"><span class="sdbar__icon"><i class="bi bi-grid"></i></span>Product Display</a>
    </div>
    <a class="sdbar__item sdbar__settings" href="../actions/logout.php"><span class="sdbar__icon"><i class="bi bi-box-arrow-right"></i></span>Logout</a>
  </aside>

  <!-- Main -->
  <main class="main">
    <!-- Header -->
    <div class="page-head">
      <div>
        <div class="page-title">Products</div>
        <div class="page-sub">Create, organize, and manage your product catalog</div>
      </div>
      <div class="d-flex gap-2">
        <div class="btn-group">
          <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="bi bi-plus-lg me-1"></i> Add Product
          </button>
          <button class="btn btn-dark dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Toggle Dropdown</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" id="btnDownloadTemplate"><i class="bi bi-download me-2"></i>Download Bulk Template</a></li>
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUploadModal"><i class="bi bi-upload me-2"></i>Bulk Upload Products</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Filters Toolbar -->
    <div class="toolbar card-soft p-3">
      <div class="input-group" style="min-width:260px">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input id="q" type="text" class="form-control" placeholder="Search products...">
      </div>

      <select id="fCat" class="form-select">
        <option value="all">All Categories</option>
      </select>

      <select id="fBrand" class="form-select">
        <option value="all">All Brands</option>
      </select>

      <select id="fSort" class="form-select">
        <option value="new">Newest</option>
        <option value="plh">Price: Low → High</option>
        <option value="phl">Price: High → Low</option>
        <option value="az">A → Z</option>
      </select>

      <div class="ms-auto view-toggle btn-group" role="group">
        <button class="btn btn-outline-secondary active" id="btnGrid" title="Grid view"><i class="bi bi-grid"></i></button>
        <button class="btn btn-outline-secondary" id="btnTable" title="Table view"><i class="bi bi-list-ul"></i></button>
      </div>
    </div>

    <!-- Grid View (initially empty; backend will render or inject later) -->
    <div id="gridView" class="grid mt-3">
      <div id="productsLoader" style="grid-column:1/-1;padding:18px;text-align:center;color:#6b7280;">
        No products loaded
      </div>
      <!-- product cards will be injected here by backend or via server-side rendering -->
    </div>

  </main>
</div>

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkModalTitle">Bulk Upload Products</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="bulkUploadForm" enctype="multipart/form-data">
        <div class="modal-body">
          <div id="bulkUploadAlert"></div>
          
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Instructions:</strong>
            <ol class="mb-0 mt-2 ps-3">
              <li>Download the template using the dropdown menu</li>
              <li>Fill in the CSV with your product details</li>
              <li>Add product images to the folder</li>
              <li>Compress everything into a ZIP file</li>
              <li>Upload the ZIP file here</li>
            </ol>
          </div>
          
          <div class="mb-3">
            <label for="bulkFile" class="form-label">Select ZIP File <span class="text-danger">*</span></label>
            <input type="file" class="form-control" id="bulkFile" name="bulk_file" accept=".zip" required>
            <div class="form-text">Maximum file size: 50MB</div>
          </div>
          
          <div id="bulkProgress" style="display:none">
            <div class="progress mb-2">
              <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
            </div>
            <div class="text-center small text-muted">Processing... Please wait</div>
          </div>
          
          <div id="bulkResults" style="display:none">
            <h6 class="mb-3">Upload Results</h6>
            <div class="mb-2">
              <span class="badge bg-success me-2">Success</span>
              <span id="bulkSuccessCount">0</span> products created
            </div>
            <div class="mb-2" id="bulkErrorSection" style="display:none">
              <span class="badge bg-danger me-2">Errors</span>
              <span id="bulkErrorCount">0</span> errors
            </div>
            <div class="mb-3" id="bulkWarningSection" style="display:none">
              <span class="badge bg-warning me-2">Warnings</span>
              <span id="bulkWarningCount">0</span> warnings
            </div>
            
            <div id="bulkErrorList" class="alert alert-danger small" style="display:none; max-height:200px; overflow-y:auto">
              <strong>Error Details:</strong>
              <ul id="bulkErrorItems" class="mb-0 mt-2"></ul>
            </div>
            
            <div id="bulkWarningList" class="alert alert-warning small" style="display:none; max-height:200px; overflow-y:auto">
              <strong>Warning Details:</strong>
              <ul id="bulkWarningItems" class="mb-0 mt-2"></ul>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button id="btnBulkUpload" class="btn btn-primary" type="submit">
            <i class="bi bi-upload me-1"></i> Upload & Process
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Product Modal (Add/Edit) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-height:90vh">
    <div class="modal-content" style="max-height:90vh">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="productForm" novalidate>
        <div class="modal-body" style="max-height:70vh;overflow-y:auto">
          <div id="productFormAlert"></div>
          <input type="hidden" id="pProductId" name="product_id" value="">

          <!-- Title -->
          <div class="mb-3">
            <label for="pTitle" class="form-label">Product Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="pTitle" name="product_title" required>
          </div>

          <!-- Price -->
          <div class="mb-3">
            <label for="pPrice" class="form-label">Price (GHS) <span class="text-danger">*</span></label>
            <div style="position:relative">
              <span class="price-prefix">GHS</span>
              <input type="number" step="0.01" min="0" class="form-control price-input" id="pPrice" name="product_price" required>
            </div>
          </div>

          <!-- Category -->
          <div class="mb-3">
            <label for="product_cat" class="form-label">Category <span class="text-danger">*</span></label>
            <select class="form-select" id="product_cat" name="product_cat" required>
              <option value="">Select category</option>
              <!-- Populated by product.js -->
            </select>
          </div>

          <!-- Brand -->
          <div class="mb-3">
            <label for="product_brand" class="form-label">Brand <span class="text-danger">*</span></label>
            <select class="form-select" id="product_brand" name="product_brand" required>
              <option value="">Select brand</option>
              <!-- Populated by product.js -->
            </select>
          </div>

          <!-- Description -->
          <div class="mb-3">
            <label for="pDesc" class="form-label">Description</label>
            <textarea class="form-control" id="pDesc" name="product_desc" rows="3" maxlength="300"></textarea>
            <div class="counter mt-1" id="descCount">0 / 300</div>
          </div>

          <!-- Keywords/Tags -->
          <div class="mb-3">
            <label for="tagInput" class="form-label">Keywords</label>
            <div class="token-input" id="tagBox">
              <input type="text" id="tagInput" placeholder="Type and press Enter">
            </div>
            <input type="hidden" id="product_keywords" name="product_keywords">
            <div class="helper mt-1">Press Enter to add a tag</div>
          </div>

          <!-- Image Upload -->
          <div class="mb-3">
            <label class="form-label d-block">Product Images <span class="text-danger">*</span></label>
            <div id="dropzone" class="dropzone">
              <i class="bi bi-cloud-upload" style="font-size:48px;color:#94a3b8"></i>
              <div class="mt-3" style="font-size:15px"><strong>Click here to select images</strong> or drag & drop</div>
              <div class="text-muted small mt-2">Maximum 5 images • JPG, PNG format</div>
            </div>
            <input type="file" id="filePicker" accept="image/*" multiple hidden>
            <div id="previews" class="preview-grid"></div>
            <div id="imgError" class="text-danger small mt-2" style="display:none">⚠ At least one image is required</div>
          </div>

        </div>
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button id="btnSave" class="btn btn-dark" type="submit" disabled>Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS (for modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Product Management JS -->
<script src="../assets/js/product.js"></script>
</body>
</html>

