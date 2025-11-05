<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>All Products — Storefront (Frontend Only)</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root{
  --ink:#0f172a; --sub:#475569; --line:#e5e7eb; --bg:#f8fafc; --accent:#111827;
  --r-xl:22px; --r:14px; --shadow:0 20px 40px rgba(0,0,0,.08);
}
body{background:var(--bg);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial}
.container-narrow{max-width:1200px}
.navbar{background:#fff;border-bottom:1px solid rgba(0,0,0,.06)}
.hero{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:var(--r-xl);box-shadow:var(--shadow);padding:24px}
.filters{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:var(--r);padding:12px 12px}
.card-prod{border:1px solid var(--line);border-radius:16px;overflow:hidden;transition:box-shadow .15s,border-color .15s}
.card-prod:hover{box-shadow:0 12px 24px rgba(0,0,0,.06);border-color:rgba(0,0,0,.12)}
.card-prod .thumb{aspect-ratio:4/3;object-fit:cover;width:100%}
.price{font-weight:700}
.badge-soft{background:#f1f5f9;border:1px solid #e2e8f0}
.pagination .page-link{border-radius:10px}
</style>
</head>
<body>

<!-- Top Nav -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container container-narrow">
    <a class="navbar-brand fw-bold" href="#">Z;s</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="#">All Products</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Register</a></li>
      </ul>
      <form class="d-flex gap-2" role="search">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input id="q" class="form-control" type="search" placeholder="Search products...">
        </div>
        <button class="btn btn-dark" type="button" id="btnSearch">Search</button>
      </form>
    </div>
  </div>
</nav>

<!-- Header/Hero -->
<header class="container container-narrow my-4">
  <div class="hero">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h1 class="h3 fw-bold mb-1">All Products</h1>
        <div class="text-muted">Browse, filter, and discover the latest in our catalog.</div>
      </div>
      <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cart-plus"></i> View Cart</a>
    </div>
  </div>
</header>

<!-- Filters Bar -->
<section class="container container-narrow">
  <div class="filters d-flex flex-wrap align-items-center gap-2">
    <select id="fCat" class="form-select" style="min-width:200px">
      <option value="all">All Categories</option>
    </select>
    <select id="fBrand" class="form-select" style="min-width:200px">
      <option value="all">All Brands</option>
    </select>
    <select id="fSort" class="form-select" style="min-width:200px">
      <option value="new">Newest</option>
      <option value="plh">Price: Low → High</option>
      <option value="phl">Price: High → Low</option>
      <option value="az">A → Z</option>
    </select>
    <span class="ms-auto small text-muted" id="count"></span>
  </div>
</section>

<!-- Products Grid -->
<main class="container container-narrow my-3">
  <div id="grid" class="row g-3">
    <!-- JS renders product cards here -->
  </div>

  <!-- Pagination -->
  <nav class="mt-4">
    <ul id="pager" class="pagination justify-content-center gap-1">
      <!-- JS renders pagination -->
    </ul>
  </nav>
</main>

<footer class="py-4 text-center text-muted small">© 2025 Z's Storefront</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ================= SAMPLE DATA (front-end only) ================= */
const PRODUCTS = [
  // id, title, cat, brand, price, img, desc, keywords
  {id:101,title:"Japan Green Outer",cat:"Apparel",brand:"Wink",price:250,img:"https://images.unsplash.com/photo-1520975922284-9d9b6b06d8a6?q=80&w=800&auto=format&fit=crop",desc:"Lightweight outer with clean lines.",keywords:["outer","green","japan"]},
  {id:102,title:"Black to basic tee",cat:"Apparel",brand:"BasicLab",price:90,img:"https://images.unsplash.com/photo-1512436991641-6745cdb1723f?q=80&w=800&auto=format&fit=crop",desc:"Super-soft everyday tee.",keywords:["tee","black","cotton"]},
  {id:103,title:"Soft Hoodie",cat:"Apparel",brand:"Wink",price:180,img:"https://images.unsplash.com/photo-1520975657287-7c1d5f7b5a77?q=80&w=800&auto=format&fit=crop",desc:"Cozy fleece hoodie.",keywords:["hoodie","soft"]},
  {id:104,title:"White off jacket 2024",cat:"Apparel",brand:"Aero",price:420,img:"https://images.unsplash.com/photo-1551024601-bec78aea704b?q=80&w=800&auto=format&fit=crop",desc:"Premium minimalist jacket.",keywords:["jacket","white"]},
  {id:105,title:"Dreamy Brown Shirt",cat:"Apparel",brand:"BasicLab",price:130,img:"https://images.unsplash.com/photo-1541099649105-f69ad21f3246?q=80&w=800&auto=format&fit=crop",desc:"Relaxed brown shirt.",keywords:["shirt","brown"]},
  {id:201,title:"Noise Buds Z",cat:"Electronics",brand:"Noise",price:299,img:"https://images.unsplash.com/photo-1585386959984-a4155223168f?q=80&w=800&auto=format&fit=crop",desc:"True wireless earbuds.",keywords:["earbuds","wireless"]},
  {id:202,title:"Wink Smartwatch S",cat:"Electronics",brand:"Wink",price:990,img:"https://images.unsplash.com/photo-1518441902113-c1d3f20f68b3?q=80&w=800&auto=format&fit=crop",desc:"Fitness + notifications.",keywords:["watch","smart"]},
  {id:301,title:"Fenty Gloss Bomb",cat:"Beauty",brand:"Fenty",price:180,img:"https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?q=80&w=800&auto=format&fit=crop",desc:"High-shine lip gloss.",keywords:["beauty","lip"]},
  {id:302,title:"Glow Serum C",cat:"Beauty",brand:"Glow",price:220,img:"https://images.unsplash.com/photo-1512203492609-8f5b38cd0f02?q=80&w=800&auto=format&fit=crop",desc:"Vitamin C brightening.",keywords:["serum","vitamin c"]},
];
const CATS = [...new Set(PRODUCTS.map(p=>p.cat))].sort();
const BRANDS = [...new Set(PRODUCTS.map(p=>p.brand))].sort();

const grid = document.getElementById('grid');
const fCat = document.getElementById('fCat');
const fBrand = document.getElementById('fBrand');
const fSort = document.getElementById('fSort');
const pager = document.getElementById('pager');
const q = document.getElementById('q');
const btnSearch = document.getElementById('btnSearch');
const count = document.getElementById('count');

CATS.forEach(c=>fCat.insertAdjacentHTML('beforeend', `<option>${c}</option>`));
function refreshBrands(){
  const sel = fCat.value;
  const pool = sel==='all' ? BRANDS : [...new Set(PRODUCTS.filter(p=>p.cat===sel).map(p=>p.brand))];
  fBrand.innerHTML = `<option value="all">All Brands</option>`;
  pool.forEach(b=>fBrand.insertAdjacentHTML('beforeend', `<option>${b}</option>`));
}
refreshBrands();
fCat.addEventListener('change', ()=>{ refreshBrands(); render(1); });
[fBrand,fSort].forEach(el=>el.addEventListener('change', ()=>render(1)));
btnSearch.addEventListener('click', ()=>render(1));
q.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){e.preventDefault(); render(1);} });

const PAGE_SIZE = 8;

function render(page=1){
  // filter
  const query = q.value.trim().toLowerCase();
  const cat = fCat.value;
  const brand = fBrand.value;
  let items = PRODUCTS.filter(p=>{
    const matchesQ = !query || p.title.toLowerCase().includes(query) || p.keywords.join(' ').includes(query);
    const matchesC = (cat==='all') || p.cat===cat;
    const matchesB = (brand==='all') || p.brand===brand;
    return matchesQ && matchesC && matchesB;
  });

  // sort
  if (fSort.value==='plh') items.sort((a,b)=>a.price-b.price);
  if (fSort.value==='phl') items.sort((a,b)=>b.price-a.price);
  if (fSort.value==='az')  items.sort((a,b)=>a.title.localeCompare(b.title));
  // newest = as inserted

  // pagination
  const pages = Math.max(1, Math.ceil(items.length / PAGE_SIZE));
  page = Math.min(Math.max(1, page), pages);
  const start = (page-1)*PAGE_SIZE;
  const slice = items.slice(start, start+PAGE_SIZE);

  // grid
  grid.innerHTML = slice.map(p => `
    <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
      <div class="card-prod h-100">
        <img class="thumb" src="${p.img}" alt="${p.title}">
        <div class="p-3 d-flex flex-column">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge badge-soft">${p.cat}</span>
            <span class="badge badge-soft">${p.brand}</span>
          </div>
          <a class="stretched-link text-decoration-none text-dark fw-semibold" href="single_product.html?id=${p.id}">
            ${p.title}
          </a>
          <div class="mt-auto d-flex align-items-center justify-content-between">
            <div class="price">GHS ${p.price.toFixed(2)}</div>
            <button class="btn btn-sm btn-outline-dark" type="button"><i class="bi bi-cart-plus"></i></button>
          </div>
          <div class="text-muted small mt-2">ID: ${p.id}</div>
        </div>
      </div>
    </div>
  `).join('');

  // pager
  pager.innerHTML = '';
  function addPage(i,label=String(i),active=false){
    pager.insertAdjacentHTML('beforeend', `
      <li class="page-item ${active?'active':''}">
        <button class="page-link" type="button" data-page="${i}">${label}</button>
      </li>`); }
  addPage(Math.max(1,page-1),'&laquo;');
  for(let i=1;i<=pages;i++) addPage(i,String(i), i===page);
  addPage(Math.min(pages,page+1),'&raquo;');
  pager.querySelectorAll('[data-page]').forEach(btn=>btn.addEventListener('click',e=>render(+e.currentTarget.dataset.page)));

  count.textContent = `${items.length} product${items.length!==1?'s':''} found`;
}
render(1);
</script>
</body>
</html>
