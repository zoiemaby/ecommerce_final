<?php
require_once '../settings/core.php';
ensure_session();
if (!isLoggedIn()) {
  redirect('../view/login.php');
}
if (!isAdmin()) {
  redirect('../view/login.php');
}
require_once '../controllers/category_controller.php';
// Handle create, update, delete actions
// ...existing code...
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard , Create Category</title>

    <!-- Fonts & icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <!-- <script src="../assets/js/category.js"></script> -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />

    <style>
      :root{
        --primary: hsl(158, 82%, 15%);
        --white: #ffffff;
        --muted: #6b7280;
        --card-shadow: 0 8px 20px rgba(0,0,0,0.08);
        --radius: 12px;
        --max-width: 1100px;
        --gap: 24px;
      }

      /* reset */
      *{box-sizing:border-box;margin:0;padding:0}
      html,body{height:100%}
      body{
        font-family: "Montserrat", "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        background: linear-gradient(180deg, #f6fbfa 0%, #fbfefe 100%);
        color:#111827;
        -webkit-font-smoothing:antialiased;
        -moz-osx-font-smoothing:grayscale;
        padding: 24px;
        display: flex;
        justify-content: center;
      }

      /* app container */
      .app {
        width: 100%;
        max-width: var(--max-width);
      }

      /* header */
      .topbar {
        display:flex;
        align-items:center;
        gap:12px;
        margin-bottom: 22px;
      }
      .back-btn{
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        display: inline-flex;
        align-items:center;
        justify-content:center;
        color: var(--primary);
      }
      .page-title {
        flex:1;
        text-align:center;
      }
      .page-title h1{
        font-size: 20px;
        color: var(--primary);
        margin-bottom: 4px;
      }
      .page-sub {
        font-size:13px;
        color:var(--muted);
      }
      .profile-btn{
        background: none;
        border: 1px solid rgba(17,24,39,0.06);
        padding: 6px 8px;
        border-radius: 8px;
        cursor:pointer;
      }

      /* main layout: side-by-side on desktop, stacked on mobile */
      .cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--gap);
        align-items:start;
      }

      /* make the left (form) slightly wider visually */
      .cards > .left { min-width: 0; }
      .cards > .right { min-width: 0; }

      /* card common */
      .card {
        background: var(--white);
        border-radius: var(--radius);
        box-shadow: var(--card-shadow);
        padding: 28px;
        overflow: visible;
      }

      /* collection form */
      .collection-card {
        display: flex;
        flex-direction: column;
        gap: 18px;
      }

      .collection-card h2{
        color: var(--primary);
        font-size: 18px;
        margin-bottom: 4px;
      }
      .collection-card p.lead {
        font-size: 13px;
        color: var(--muted);
      }

      .collection-form{
        display:flex;
        flex-direction:column;
        gap:14px;
        width:100%;
      }

      .collection-input{
        padding:14px 16px;
        border-radius:8px;
        border:1px solid rgba(17,24,39,0.08);
        font-size:15px;
        background:#fff;
      }

      .create-btn{
        padding:14px 18px;
        border-radius:10px;
        border:none;
        cursor:pointer;
        font-weight:600;
        font-size:15px;
        background: linear-gradient(180deg, var(--primary), color-mix(in srgb, var(--primary) 85%, black 10%));
        color: var(--white);
        box-shadow: 0 6px 16px rgba(21,128,120,0.18);
        transition: transform .12s ease, box-shadow .12s ease;
      }
      .create-btn:hover{ transform: translateY(-3px); box-shadow: 0 10px 26px rgba(21,128,120,0.14) }

      .error-message{
        background: rgba(255,0,0,0.06);
        border: 1px solid rgba(255,0,0,0.12);
        color: #991b1b;
        padding:10px 12px;
        border-radius:8px;
        font-size:13px;
      }

      /* manage users */
      .manage-card h2{
        color: var(--primary);
        font-size:18px;
      }

      .user-list {
        margin-top:12px;
        display:flex;
        flex-direction:column;
        gap:12px;
      }

      .user-item{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        padding:14px;
        border-radius:10px;
        border:1px solid rgba(17,24,39,0.04);
        background: linear-gradient(180deg, #ffffff 0%, #fbfefe 100%);
      }

      .user-details p{ font-size:14px; color:#111827; margin-bottom:6px }
      .user-details p.small{ color:var(--muted); font-size:13px; margin:0 }

      .user-actions{
        display:flex;
        gap:8px;
      }

      .action-button {
        padding:8px 12px;
        font-size:13px;
        border-radius:8px;
        border:none;
        cursor:pointer;
        font-weight:600;
        color:var(--white);
        background: var(--primary);
        transition: transform .12s ease;
      }
      .action-button.secondary {
        background: transparent;
        color: var(--primary);
        border: 1px solid rgba(17,24,39,0.06);
      }
      .action-button:hover{ transform: translateY(-3px) }

      /* responsive */
      @media (max-width: 920px){
        .cards{ grid-template-columns: 1fr; }
        .page-title { text-align:left }
      }

      @media (max-width: 420px){
        body { padding: 12px; }
        .card { padding: 18px; }
        .collection-input, .create-btn { font-size:14px; padding:12px }

    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 9999; }
    .modal { background: #fff; box-shadow: 0 12px 40px rgba(0,0,0,0.25); border-radius: 12px; padding: 20px; }
    .modal-overlay[hidden] { display: none !important; }
      }

    :root{
  --sdbar-bg: rgba(255,255,255,0.55);
  --sdbar-bdr: rgba(0,0,0,0.07);
  --sdbar-txt:  hsl(158, 82%, 15%);
  --sdbar-sub: #6b7280;
  --sdbar-active: #111827;
  --sdbar-active-txt: #fff;
  --sdbar-hover: rgba(17,24,39,0.06);
  --radius-xl: 22px;
  --radius-lg: 14px;
  --shadow-xl: 0 20px 40px rgba(0,0,0,0.08);
  --blur: 12px;
}

* { box-sizing: border-box }

body{
  margin:0;
  font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
  color: var(--sdbar-txt);
  background: #f4f5f7; /* soft app canvas */
}

.layout{
  display:grid;
  grid-template-columns: 280px 1fr;
  gap: 20px;
  padding: 24px;
  min-height: 100vh;
}

/* SIDEBAR PANEL */
.sdbar{
  position: sticky;
  top: 24px;
  height: calc(100vh - 48px);
  padding: 18px;
  border-radius: var(--radius-xl);
  background: var(--sdbar-bg);
  backdrop-filter: blur(var(--blur));
  -webkit-backdrop-filter: blur(var(--blur));
  border: 1px solid var(--sdbar-bdr);
  box-shadow: var(--shadow-xl);
  display:flex;
  flex-direction:column;
  gap: 14px;
}

/* Brand */
.sdbar__brand{
  display:flex; align-items:center; gap:10px;
  padding: 8px 10px;
  margin-bottom: 8px;
}
.sdbar__logo{
  width:34px;height:34px;border-radius:10px;
  display:grid;place-items:center;
  background:#111827;color:#fff;font-weight:700; font-size:16px;
}
.sdbar__title{ font-weight:700; font-size:18px; letter-spacing:.2px }

/* Groups */
.sdbar__label{
  margin: 8px 10px 2px;
  font-size: 12px;
  color: var(--sdbar-sub);
  letter-spacing:.08em;
}

.sdbar__group{ display:flex; flex-direction:column; gap:8px }

/* Items */
.sdbar__item{
  display:flex; align-items:center; gap:12px;
  padding: 12px 12px;
  border-radius: var(--radius-lg);
  text-decoration:none;
  color: inherit;
  transition: background .15s ease, transform .05s ease;
}

.sdbar__item:hover{ background: var(--sdbar-hover) }
.sdbar__item:active{ transform: translateY(1px) }

.sdbar__item--active{
  background: var(--sdbar-active);
  color: var(--sdbar-active-txt);
}
.sdbar__icon{ width:20px; display:inline-grid; place-items:center }

/* Tiny team dots */
.sdbar__dot{
  width:10px;height:10px;border-radius:999px;display:inline-block;
  margin-right:2px; border:2px solid rgba(0,0,0,.06)
}
.sdbar__dot--green{ background:#22c55e }
.sdbar__dot--purple{ background:#8b5cf6 }

/* Push settings to bottom */
.sdbar__settings{ margin-top:auto }

/* MAIN AREA */
.main{
  background:#fff;
  border-radius: var(--radius-xl);
  border:1px solid var(--sdbar-bdr);
  box-shadow: var(--shadow-xl);
  padding: 24px;
  min-height: calc(100vh - 48px);
}

/* Responsive: collapse to overlay on small screens */
@media (max-width: 920px){
  .layout{ grid-template-columns: 1fr }
  .sdbar{
    position: fixed; left: 16px; right: 16px; top: 16px;
    height:auto; z-index: 50;
  }
  .main{ margin-top: 170px }
}

    </style>
  </head>

  <body>
    <div class="layout">
  <!-- SIDEBAR -->
  <aside class="sdbar">
    <div class="sdbar__brand">
      <span class="sdbar__title">Z's Page</span>
    </div>

    <nav class="sdbar__group">
      <a href="category.php" class="sdbar__item sdbar__item--active">
        <span class="sdbar__icon">➕</span> <span>Category Management</span>
      </a>
      <a href="brand.php" class="sdbar__item">
        <span class="sdbar__icon">➕</span> <span>Brand Management</span>
      </a>
      <a href="product.php" class="sdbar__item">
        <span class="sdbar__icon">➕</span> <span>Product Management</span>
      </a>
      <a href="../view/all_product.php" class="sdbar__item">
        <span class="sdbar__icon">➕</span> <span>Products Display</span>
      </a>
      <a href="#" class="sdbar__item">
        <span class="sdbar__icon">➕</span> <span>Customers</span>
      </a>
    </nav>

    <nav class="sdbar__group sdbar__settings">
      <a href="#" class="sdbar__item">
        <span class="sdbar__icon">⚙️</span> <span>Settings</span>
      </a>
    </nav>
  </aside>
    </div>
    <div class="app">
      <!-- Top bar -->
      <div class="topbar">
        <a href="../index.php" class="back-btn" aria-label="Back to Login Page">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
        </a>

        <div class="page-title">
          <h1>Create New Category</h1>
          <div class="page-sub">Quickly add a new Category — it will appear in the category list.</div>
        </div>

        <div>
          <button class="profile-btn" aria-label="Profile">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
              stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary)">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </button>
        </div>
      </div>

      <!-- Main cards container -->
      <div class="cards">
        <!-- Left: Collection form (fills more visually) -->
        <div class="left card collection-card">
          <h2><i class="fa-solid fa-layer-group" style="color:var(--primary); margin-right:8px"></i> Create New Category</h2>
          <p class="lead">Give your Category a name. Keep it concise and descriptive so users can easily find it.</p>

          <form action="" method="post" class="collection-form" novalidate>
            <!-- Server-side error area (PHP rendered) -->

            <?php
            // Handle create
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
                $name = trim($_POST['category_name']);
                $errors = [];
                if ($name === '') {
                    $errors[] = 'Category name required.';
                } elseif (category_name_exists_ctr($name)) {
                    $errors[] = 'Category name must be unique.';
                }
                if (empty($errors)) {
                    $result = add_category_ctr($name);
                    if ($result === false) {
                        $errors[] = 'Failed to add category. Check database connection, table structure, and error logs.';
                        echo '<div class="error-message">'.implode('<br>', array_map('htmlspecialchars', $errors)).'</div>';
                    } else {
                        echo '<script>window.location.reload();</script>';
                    }
                } else {
                    echo '<div class="error-message">'.implode('<br>', array_map('htmlspecialchars', $errors)).'</div>';
                }
            }
            ?>

            <input
              type="text"
              name="category_name"
              placeholder="Enter Category Name"
              required
              class="collection-input"
              aria-label="Collection name"
            />

            <button type="submit" class="create-btn">Create Category</button>
          </form>

          <!-- optional helpful note -->
          <div style="margin-top: 8px; color:var(--muted); font-size:13px">
            Tip: category names are visible to users — avoid using personal data or special characters.
          </div>
        </div>

        <!-- Right: Category List -->
        <div class="right card manage-card">
          <h2><i class="fa-solid fa-users" style="color:var(--primary); margin-right:8px"></i> Category List</h2>
          
          <div class="user-list">
            <?php
      $categories = list_categories_ctr();
      foreach ($categories as $cat) {
        echo '<div class="user-item">';
        echo '<div class="user-details">';
        echo '<p><strong>ID:</strong> '.htmlspecialchars($cat['cat_id']).'</p>';
        echo '<p class="small"><strong>Name:</strong> '.htmlspecialchars($cat['cat_name']).'</p>';
        echo '</div>';
        echo '<div class="user-actions">';
        // JS-interceptable delete button (non-submitting)
        echo '<button class="action-button secondary js-delete" type="button" data-id="'.htmlspecialchars($cat['cat_id']).'" title="Delete">Delete</button>';
        echo '<form method="post" style="display:inline;"><input type="hidden" name="update_id" value="'.htmlspecialchars($cat['cat_id']).'"><input type="text" name="update_name" value="'.htmlspecialchars($cat['cat_name']).'" required><button class="action-button" type="submit" title="Update">Update</button></form>';
        echo '</div>';
        echo '</div>';
      }
      // Handle delete
      // if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
      //   delete_category_ctr((int)$_POST['delete_id']);
      //   echo '<script>window.location.reload();</script>';
      // }
      // Handle update
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'], $_POST['update_name'])) {
        edit_category_ctr((int)$_POST['update_id'], $_POST['update_name']);
        echo '<script>window.location.reload();</script>';
      }
            ?>
          </div>
        </div>
      </div>
    </div>
    <!-- ...rest of your HTML... -->
  <div class="modal-overlay" id="confirmOverlay" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" style="display:none;">
    <div class="modal" role="document" style="max-width:520px;border-radius:12px;padding:20px;">
      <h3 id="confirmTitle" style="margin:0 0 8px;color:#a33;">Confirm delete</h3>
      <div id="confirmItem" style="font-size:13px;color:#666;margin-bottom:8px">Category: —</div>
      <p style="margin:0 0 16px;color:#444">This action cannot be undone. The category will be permanently removed.</p>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button id="cancelBtn" class="action-button secondary" type="button">Cancel</button>
        <button id="confirmBtn" class="action-button" type="button" style="background:linear-gradient(90deg,#ff8f77,#ffb7a7);color:#4b0a00">Delete</button>
      </div>
    </div>
  </div>
  <!-- Place your JS after the modal -->
  <script src="../assets/js/category.js"></script>
  </body>
  </html>
  </body>
</html>
