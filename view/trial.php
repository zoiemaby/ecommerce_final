<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Products Management — Admin (Frontend Only)</title>

<!-- Bootstrap 5 + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ========= THEME (matches your Category/Brand pages) ========= */
:root{
  --ink:#0f172a; --sub:#475569; --line:#e5e7eb; --bg:#f8fafc;
  --accent:#111827; --r-xl:22px; --r-lg:16px; --shadow:0 20px 40px rgba(0,0,0,.08);
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
.dropzone{border:2px dashed #cbd5e1;border-radius:14px;padding:16px;text-align:center;background:#f8fafc;cursor:pointer}
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
    <div class="sdbar__brand"><div class="sdbar__logo">✦</div><span class="sdbar__title">Admin</span></div>
    <div class="sdbar__group">
      <a class="sdbar__item" href="#"><span class="sdbar__icon"><i class="bi bi-speedometer2"></i></span>Dashboard</a>
      <a class="sdbar__item" href="#"><span class="sdbar__icon"><i class="bi bi-folder2"></i></span>Category Management</a>
      <a class="sdbar__item" href="#"><span class="sdbar__icon"><i class="bi bi-tags"></i></span>Brand Management</a>
      <a class="sdbar__item sdbar__item--active" href="#"><span class="sdbar__icon"><i class="bi bi-box-seam"></i></span>Product Management</a>
    </div>
    <a class="sdbar__item sdbar__settings" href="#"><span class="sdbar__icon"><i class="bi bi-gear"></i></span>Settings</a>
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
        <button class="btn btn-outline-secondary btn-back"><i class="bi bi-arrow-left"></i> Back</button>
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#productModal">
          <i class="bi bi-plus-lg me-1"></i> Add Product
        </button>
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

    <!-- Grid View -->
    <div id="gridView" class="grid mt-3">
      <!-- sample cards (will be filtered client-side) -->
      <div class="product-card" data-title="Japan Green Outer" data-cat="Apparel" data-brand="Wink" data-price="250">
        <img class="product-img" src="https://images.unsplash.com/photo-1520975922284-9d9b6b06d8a6?q=80&w=800&auto=format&fit=crop" alt="">
        <div class="p-3">
          <div class="fw-semibold">Japan Green Outer</div>
          <div class="text-muted small">Wink • Apparel</div>
          <div class="mt-2 fw-bold">GHS 250.00</div>
          <div class="mt-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productModal" data-edit="1">Edit</button>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </div>
        </div>
      </div>

      <div class="product-card" data-title="Black to basic tee" data-cat="Apparel" data-brand="BasicLab" data-price="90">
        <img class="product-img" src="https://images.unsplash.com/photo-1512436991641-6745cdb1723f?q=80&w=800&auto=format&fit=crop" alt="">
        <div class="p-3">
          <div class="fw-semibold">Black to basic tee</div>
          <div class="text-muted small">BasicLab • Apparel</div>
          <div class="mt-2 fw-bold">GHS 90.00</div>
          <div class="mt-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productModal" data-edit="1">Edit</button>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </div>
        </div>
      </div>

      <div class="product-card" data-title="Soft Hoodie" data-cat="Apparel" data-brand="Wink" data-price="180">
        <img class="product-img" src="https://images.unsplash.com/photo-1520975657287-7c1d5f7b5a77?q=80&w=800&auto=format&fit=crop" alt="">
        <div class="p-3">
          <div class="fw-semibold">Soft Hoodie</div>
          <div class="text-muted small">Wink • Apparel</div>
          <div class="mt-2 fw-bold">GHS 180.00</div>
          <div class="mt-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productModal" data-edit="1">Edit</button>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </div>
        </div>
      </div>

      <div class="product-card" data-title="White off jacket 2024" data-cat="Apparel" data-brand="Aero" data-price="420">
        <img class="product-img" src="https://images.unsplash.com/photo-1551024601-bec78aea704b?q=80&w=800&auto=format&fit=crop" alt="">
        <div class="p-3">
          <div class="fw-semibold">White off jacket 2024</div>
          <div class="text-muted small">Aero • Apparel</div>
          <div class="mt-2 fw-bold">GHS 420.00</div>
          <div class="mt-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productModal" data-edit="1">Edit</button>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </div>
        </div>
      </div>

      <div class="product-card" data-title="Dreamy Brown Shirt" data-cat="Apparel" data-brand="BasicLab" data-price="130">
        <img class="product-img" src="https://images.unsplash.com/photo-1541099649105-f69ad21f3246?q=80&w=800&auto=format&fit=crop" alt="">
        <div class="p-3">
          <div class="fw-semibold">Dreamy Brown Shirt</div>
          <div class="text-muted small">BasicLab • Apparel</div>
          <div class="mt-2 fw-bold">GHS 130.00</div>
          <div class="mt-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productModal" data-edit="1">Edit</button>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </div>
        </div>
      </div>

      <div class="product-card" data-title="Noise buds Z" data-cat="Electronics" data-brand="Noise" data-price="299">
        <img class="product-img" src="https://images.unsplash.com/photo-1585386959984-a4155223168f?q=80&w=800&auto=format&fit=crop" alt="">
        <div class="p-3">
          <div class="fw-semibold">Noise buds Z</div>
          <div class="text-muted small">Noise • Electronics</div>
          <div class="mt-2 fw-bold">GHS 299.00</div>
          <div class="mt-2 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productModal" data-edit="1">Edit</button>
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table View (hidden by default) -->
    <div id="tableView" class="card card-soft mt-3 d-none">
      <div class="table-responsive">
        <table class="table table-products mb-0">
          <thead class="table-light">
            <tr><th>Title</th><th>Category</th><th>Brand</th><th>Price</th><th class="text-end">Actions</th></tr>
          </thead>
          <tbody id="tableBody">
            <!-- JS will mirror cards here -->
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- ========= ADD/EDIT PRODUCT MODAL ========= -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content needs-validation" novalidate>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i><span id="modalTitle">Add Product</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <!-- Title -->
          <div class="col-md-8">
            <div class="form-floating">
              <input id="pTitle" type="text" class="form-control" placeholder="Product Title" required>
              <label for="pTitle">Product Title</label>
              <div class="invalid-feedback">Product title is required.</div>
            </div>
          </div>

          <!-- Price -->
          <div class="col-md-4 position-relative">
            <div class="form-floating">
              <input id="pPrice" type="number" min="0" step="0.01" class="form-control price-input" placeholder="0.00" required>
              <label for="pPrice">Price</label>
              <span class="price-prefix">GHS</span>
              <div class="invalid-feedback">Enter a positive price.</div>
            </div>
          </div>

          <!-- Category -->
          <div class="col-md-6">
            <div class="form-floating">
              <select id="pCategory" class="form-select" required></select>
              <label for="pCategory">Category</label>
              <div class="invalid-feedback">Choose a category.</div>
            </div>
          </div>

          <!-- Brand (filtered by category) -->
          <div class="col-md-6">
            <div class="form-floating">
              <select id="pBrand" class="form-select" required></select>
              <label for="pBrand">Brand</label>
              <div class="invalid-feedback">Choose a brand.</div>
            </div>
          </div>

          <!-- Description -->
          <div class="col-12">
            <label class="form-label">Description <span class="counter" id="descCount">0 / 300</span></label>
            <textarea id="pDesc" class="form-control" rows="3" maxlength="300" placeholder="Short product description (max 300 chars)"></textarea>
            <div class="helper mt-1">2–4 lines recommended.</div>
          </div>

          <!-- Tags -->
          <div class="col-12">
            <label class="form-label">Keywords / Tags</label>
            <div class="token-input" id="tagBox">
              <!-- tokens here -->
              <input id="tagInput" type="text" placeholder="Type a tag and press Enter">
            </div>
            <div class="helper mt-1">Press Enter to add a tag. Example: “summer, cotton”</div>
          </div>

          <!-- Images -->
          <div class="col-12">
            <label class="form-label">Product Images <span class="helper">(max 5)</span></label>
            <div class="dropzone" id="dropzone">Drag & drop images here or click to select</div>
            <input id="filePicker" type="file" accept="image/*" multiple hidden>
            <div id="imgError" class="invalid-feedback d-block" style="display:none">At least one image is required (max 5).</div>
            <div class="preview-grid" id="previews"></div>
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="btnSave" class="btn btn-dark" type="submit" disabled>Save Product</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS (for modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ======== SAMPLE DATA ======== */
const DATA = {
  categories: ["Apparel","Electronics","Beauty"],
  brands: {
    "Apparel": ["Wink","BasicLab","Aero"],
    "Electronics": ["Noise","Apple","Samsung"],
    "Beauty": ["Fenty","Glow"]
  }
};

/* ======== FILTER BAR POPULATION ======== */
const fCat = document.getElementById('fCat');
const fBrand = document.getElementById('fBrand');
DATA.categories.forEach(c=>fCat.insertAdjacentHTML('beforeend', `<option>${c}</option>`));
function refreshBrandFilter(){
  const cat = fCat.value;
  fBrand.innerHTML = '<option value="all">All Brands</option>';
  const list = cat==='all'
    ? [...new Set([...document.querySelectorAll('.product-card')].map(x=>x.dataset.brand))]
    : DATA.brands[cat]||[];
  list.forEach(b=>fBrand.insertAdjacentHTML('beforeend', `<option>${b}</option>`));
}
refreshBrandFilter();
fCat.addEventListener('change', ()=>{ refreshBrandFilter(); applyFilters(); });
[fBrand, document.getElementById('q'), document.getElementById('fSort')].forEach(el=>el.addEventListener('input', applyFilters));

/* ======== GRID/TABLE TOGGLE ======== */
const grid = document.getElementById('gridView');
const table = document.getElementById('tableView');
const btnGrid = document.getElementById('btnGrid');
const btnTable = document.getElementById('btnTable');
btnGrid.addEventListener('click', ()=>{btnGrid.classList.add('active');btnTable.classList.remove('active');grid.classList.remove('d-none');table.classList.add('d-none');});
btnTable.addEventListener('click', ()=>{btnTable.classList.add('active');btnGrid.classList.remove('active');grid.classList.add('d-none');table.classList.remove('d-none');mirrorToTable();});

/* ======== FILTERING & SORTING ======== */
function applyFilters(){
  const query = document.getElementById('q').value.trim().toLowerCase();
  const cat = fCat.value;
  const brand = fBrand.value;
  const sort = document.getElementById('fSort').value;

  let cards = [...document.querySelectorAll('.product-card')];
  // filter
  cards.forEach(card=>{
    const matchesQ = card.dataset.title.toLowerCase().includes(query);
    const matchesC = (cat==='all') || (card.dataset.cat===cat);
    const matchesB = (brand==='all') || (card.dataset.brand===brand);
    card.style.display = (matchesQ && matchesC && matchesB) ? '' : 'none';
  });

  // sort (grid order)
  const visible = cards.filter(c=>c.style.display!=='none');
  const parent = grid;
  visible.sort((a,b)=>{
    if (sort==='plh') return (+a.dataset.price)-(+b.dataset.price);
    if (sort==='phl') return (+b.dataset.price)-(+a.dataset.price);
    if (sort==='az') return a.dataset.title.localeCompare(b.dataset.title);
    return 0; // newest (as-is)
  }).forEach(c=>parent.appendChild(c));

  if (!table.classList.contains('d-none')) mirrorToTable();
}
applyFilters();

function mirrorToTable(){
  const tbody = document.getElementById('tableBody'); tbody.innerHTML='';
  const rows = [...document.querySelectorAll('.product-card')].filter(c=>c.style.display!=='none');
  rows.forEach(c=>{
    tbody.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${c.dataset.title}</td>
        <td>${c.dataset.cat}</td>
        <td>${c.dataset.brand}</td>
        <td>GHS ${Number(c.dataset.price).toFixed(2)}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#productModal" data-edit="1">Edit</button>
          <button class="btn btn-sm btn-outline-danger">Delete</button>
        </td>
      </tr>
    `);
  });
}

/* ======== ADD/EDIT MODAL: dependent dropdowns, validation, images, tags ======== */
const pCategory = document.getElementById('pCategory');
const pBrand = document.getElementById('pBrand');
const pTitle = document.getElementById('pTitle');
const pPrice = document.getElementById('pPrice');
const pDesc = document.getElementById('pDesc');
const descCount = document.getElementById('descCount');
const btnSave = document.getElementById('btnSave');
const tagBox = document.getElementById('tagBox');
const tagInput = document.getElementById('tagInput');
const previews = document.getElementById('previews');
const dropzone = document.getElementById('dropzone');
const filePicker = document.getElementById('filePicker');
const imgError = document.getElementById('imgError');

DATA.categories.forEach(c=>pCategory.insertAdjacentHTML('beforeend', `<option>${c}</option>`));
function loadBrandsFor(cat){
  pBrand.innerHTML = '<option selected disabled value="">Select brand</option>';
  (DATA.brands[cat]||[]).forEach(b=>pBrand.insertAdjacentHTML('beforeend', `<option>${b}</option>`));
}
pCategory.addEventListener('change', e=>loadBrandsFor(e.target.value));

pDesc.addEventListener('input', ()=>descCount.textContent = `${pDesc.value.length} / 300`);
function val(){ // enable save only when valid
  const basic = pTitle.value.trim() && pCategory.value && pBrand.value && Number(pPrice.value) > 0;
  const pics = previews.querySelectorAll('.preview').length > 0;
  btnSave.disabled = !(basic && pics);
  imgError.style.display = pics? 'none':'block';
}
[pTitle,pPrice,pCategory,pBrand].forEach(el=>el.addEventListener('input', val));

/* Tags */
function addToken(text){
  if(!text) return;
  const t = document.createElement('span'); t.className='token'; t.innerHTML = `${text} <span class="x">&times;</span>`;
  t.querySelector('.x').onclick = ()=>{ t.remove(); val(); };
  tagBox.insertBefore(t, tagInput); tagInput.value='';
}
tagInput.addEventListener('keydown', e=>{
  if(e.key==='Enter'){ e.preventDefault(); addToken(tagInput.value.trim()); val(); }
});

/* Images */
let filesArr = [];
function renderPreviews(){
  previews.innerHTML='';
  filesArr.forEach((file,idx)=>{
    const url = URL.createObjectURL(file);
    const box = document.createElement('div');
    box.className='preview';
    box.innerHTML = `<img src="${url}"><button type="button" data-idx="${idx}">&times;</button>`;
    box.querySelector('button').onclick = (e)=>{ filesArr.splice(idx,1); renderPreviews(); val(); };
    previews.appendChild(box);
  });
}
function addFiles(list){
  const incoming = [...list].slice(0, 5 - filesArr.length);
  filesArr = filesArr.concat(incoming.filter(f=>f.type.startsWith('image/')));
  renderPreviews(); val();
}
dropzone.addEventListener('click', ()=>filePicker.click());
filePicker.addEventListener('change', e=>addFiles(e.target.files));
dropzone.addEventListener('dragover', e=>{e.preventDefault(); dropzone.classList.add('drag')});
dropzone.addEventListener('dragleave', ()=>dropzone.classList.remove('drag'));
dropzone.addEventListener('drop', e=>{e.preventDefault(); dropzone.classList.remove('drag'); addFiles(e.dataTransfer.files)});

/* Bootstrap validation */
(() => {
  const form = document.querySelector('.needs-validation');
  form.addEventListener('submit', e=>{
    e.preventDefault(); e.stopPropagation();
    form.classList.add('was-validated');
    val();
    if(!btnSave.disabled){
      // front-end only: simulate save success
      const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
      modal.hide();
      form.reset(); filesArr=[]; renderPreviews(); descCount.textContent='0 / 300';
      pBrand.innerHTML=''; tagBox.querySelectorAll('.token').forEach(t=>t.remove()); val();
      alert('Product saved (frontend only)');
    }
  }, false);
})();

/* Open modal in add/edit mode */
const productModal = document.getElementById('productModal');
productModal.addEventListener('show.bs.modal', (e)=>{
  document.getElementById('modalTitle').textContent = e.relatedTarget?.dataset.edit ? 'Edit Product' : 'Add Product';
  // reset validation state each time
  document.querySelector('.needs-validation').classList.remove('was-validated');
  btnSave.disabled = true; imgError.style.display='none';
});

/* Ensure table mirrors grid on load */
mirrorToTable();
</script>
</body>
</html>
