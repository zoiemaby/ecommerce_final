/**
 * assets/js/product.js
 * jQuery-based product management: CRUD + image handling
 */

let filesArr = [];
let editingProductId = null;

// ============ LOAD PRODUCTS ============
function loadProducts(limit = 100) {
  $.ajax({
    url: '../actions/fetch_products_action.php',
    type: 'GET',
    data: { limit },
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success' && res.data) {
        renderProductGrid(res.data);
      } else {
        $('#productsLoader').html('<div class="alert alert-warning">No products found</div>');
      }
    },
    error: function() {
      $('#productsLoader').html('<div class="alert alert-danger">Failed to load products</div>');
    }
  });
}

// ============ RENDER PRODUCT GRID ============
function renderProductGrid(products) {
  const grid = $('#gridView');
  grid.empty();
  
  if (!products || products.length === 0) {
    grid.html('<div style="grid-column:1/-1;padding:18px;text-align:center;color:#6b7280;">No products available</div>');
    return;
  }
  
  products.forEach(p => {
    const imgSrc = p.product_image ? `../${p.product_image}` : 'https://via.placeholder.com/300x225?text=No+Image';
    const price = parseFloat(p.product_price || 0).toFixed(2);
    
    const card = $(`
      <div class="product-card" data-product-id="${p.product_id}" data-cat-id="${p.product_cat}" data-brand-id="${p.product_brand}">
        <img src="${imgSrc}" class="product-img" alt="${escapeHtml(p.product_title)}">
        <div class="p-3">
          <h5 class="product-card__title mb-2">${escapeHtml(p.product_title)}</h5>
          <div class="product-card__desc text-muted small mb-2">${escapeHtml(p.product_desc || '')}</div>
          <div class="product-card__price fw-bold mb-3">GHS ${price}</div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary flex-fill btn-edit" data-id="${p.product_id}">
              <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="btn btn-sm btn-outline-danger flex-fill btn-delete" data-id="${p.product_id}">
              <i class="bi bi-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>
    `);
    
    grid.append(card);
  });
  
  // Wire edit/delete handlers
  $('.btn-edit').off('click').on('click', function() {
    const id = $(this).data('id');
    loadProductForEdit(id);
  });
  
  $('.btn-delete').off('click').on('click', function() {
    const id = $(this).data('id');
    deleteProduct(id);
  });
  
  applyFilters();
}

// ============ LOAD PRODUCT FOR EDIT ============
function loadProductForEdit(id) {
  $.ajax({
    url: '../actions/fetch_product_action.php',
    type: 'GET',
    data: { product_id: id },
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success' && res.data) {
        populateEditForm(res.data);
      } else {
        showAlert('error', res.message || 'Failed to load product');
      }
    },
    error: function() {
      showAlert('error', 'Failed to load product details');
    }
  });
}

function populateEditForm(product) {
  editingProductId = product.product_id;
  
  $('#pProductId').val(product.product_id);
  $('#pTitle').val(product.product_title);
  $('#pPrice').val(parseFloat(product.product_price || 0).toFixed(2));
  $('#product_cat').val(product.product_cat);
  $('#product_brand').val(product.product_brand);
  $('#pDesc').val(product.product_desc || '');
  
  // Update description counter
  $('#descCount').text(`${(product.product_desc || '').length} / 300`);
  
  // Tags
  $('#tagBox .token').remove();
  if (product.product_keywords) {
    const tags = product.product_keywords.split(',').map(t => t.trim()).filter(Boolean);
    tags.forEach(tag => addTagToken(tag));
  }
  
  // Clear files for now (edit won't show existing images in preview, but will keep them on server)
  filesArr = [];
  renderPreviews();
  
  // Update modal title and show
  $('#modalTitle').text('Edit Product');
  $('#productModal').modal('show');
  
  // Enable save button if valid
  refreshSaveState();
}

// ============ DELETE PRODUCT ============
function deleteProduct(id) {
  Swal.fire({
    title: 'Delete Product?',
    text: "This action cannot be undone!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      // Show loading
      Swal.fire({
        title: 'Deleting...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      $.ajax({
        url: '../actions/delete_product_action.php',
        type: 'POST',
        data: { product_id: id },
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            Swal.fire({
              title: 'Deleted!',
              text: 'Product has been deleted successfully',
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            });
            loadProducts();
          } else {
            Swal.fire({
              title: 'Error!',
              text: res.message || 'Failed to delete product',
              icon: 'error',
              confirmButtonColor: '#dc3545'
            });
          }
        },
        error: function() {
          Swal.fire({
            title: 'Error!',
            text: 'Failed to delete product',
            icon: 'error',
            confirmButtonColor: '#dc3545'
          });
        }
      });
    }
  });
}

// ============ SAVE PRODUCT (CREATE/UPDATE) ============
function saveProduct(formData) {
  const isEdit = editingProductId !== null;
  const url = isEdit ? '../actions/update_product_action.php' : '../actions/add_product_action.php';
  
  // Show loading state
  $('#btnSave').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
  
  $.ajax({
    url: url,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success') {
        showToast('success', isEdit ? 'Product updated successfully' : 'Product added successfully');
        $('#productModal').modal('hide');
        loadProducts();
      } else {
        showModalAlert('danger', res.message || 'Failed to save product');
      }
    },
    error: function(xhr) {
      let msg = 'Failed to save product';
      try {
        const res = JSON.parse(xhr.responseText);
        msg = res.message || msg;
      } catch(e) {}
      showModalAlert('danger', msg);
    },
    complete: function() {
      $('#btnSave').prop('disabled', false).text('Save Product');
    }
  });
}

// ============ LOAD CATEGORIES ============
function loadCategories() {
  $.ajax({
    url: '../actions/fetch_category_action.php',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if ((res.success || res.ok) && res.data && Array.isArray(res.data)) {
        const currentVal = $('#product_cat').val();
        
        // Update modal select
        $('#product_cat').find('option:not(:first)').remove();
        res.data.forEach(cat => {
          const catId = cat.cat_id || cat.category_id;
          const catName = cat.cat_name || cat.category_name;
          if (catId && catName) {
            $('#product_cat').append(`<option value="${catId}">${escapeHtml(catName)}</option>`);
          }
        });
        
        // Update filter select
        $('#fCat').find('option:not(:first)').remove();
        res.data.forEach(cat => {
          const catId = cat.cat_id || cat.category_id;
          const catName = cat.cat_name || cat.category_name;
          if (catId && catName) {
            $('#fCat').append(`<option value="${catId}">${escapeHtml(catName)}</option>`);
          }
        });
        
        // Restore value if editing
        if (currentVal) $('#product_cat').val(currentVal);
      }
    },
    error: function(xhr, status, error) {
      console.error('Failed to load categories:', error);
    }
  });
}

// ============ LOAD BRANDS ============
function loadBrands() {
  $.ajax({
    url: '../actions/fetch_brand_action.php',
    type: 'GET',
    data: { type: 'all' },
    dataType: 'json',
    success: function(res) {
      if ((res.ok || res.success) && res.data && Array.isArray(res.data)) {
        const currentVal = $('#product_brand').val();
        
        // Update modal select
        $('#product_brand').find('option:not(:first)').remove();
        res.data.forEach(brand => {
          const brandId = brand.brand_id || brand.id;
          const brandName = brand.brand_name || brand.name;
          if (brandId && brandName) {
            $('#product_brand').append(`<option value="${brandId}">${escapeHtml(brandName)}</option>`);
          }
        });
        
        // Update filter select
        $('#fBrand').find('option:not(:first)').remove();
        res.data.forEach(brand => {
          const brandId = brand.brand_id || brand.id;
          const brandName = brand.brand_name || brand.name;
          if (brandId && brandName) {
            $('#fBrand').append(`<option value="${brandId}">${escapeHtml(brandName)}</option>`);
          }
        });
        
        // Restore value if editing
        if (currentVal) $('#product_brand').val(currentVal);
      }
    },
    error: function(xhr, status, error) {
      console.error('Failed to load brands:', error);
    }
  });
}

// ============ FILTERING & SORTING ============
function applyFilters() {
  const query = $('#q').val().trim().toLowerCase();
  const catId = $('#fCat').val();
  const brandId = $('#fBrand').val();
  const sortKey = $('#fSort').val();
  
  const cards = $('.product-card').toArray();
  
  // Filter
  cards.forEach(card => {
    const $card = $(card);
    const title = $card.find('.product-card__title').text().toLowerCase();
    const desc = $card.find('.product-card__desc').text().toLowerCase();
    
    const matchText = !query || title.includes(query) || desc.includes(query);
    const matchCat = catId === 'all' || $card.data('cat-id') == catId;
    const matchBrand = brandId === 'all' || $card.data('brand-id') == brandId;
    
    $card.toggle(matchText && matchCat && matchBrand);
  });
  
  // Sort
  const $grid = $('#gridView');
  const sorted = cards.sort((a, b) => {
    const $a = $(a), $b = $(b);
    
    if (sortKey === 'az') {
      const titleA = $a.find('.product-card__title').text().toLowerCase();
      const titleB = $b.find('.product-card__title').text().toLowerCase();
      return titleA.localeCompare(titleB);
    }
    
    if (sortKey === 'plh' || sortKey === 'phl') {
      const priceA = parseFloat($a.find('.product-card__price').text().replace(/[^0-9.\-]/g, '')) || 0;
      const priceB = parseFloat($b.find('.product-card__price').text().replace(/[^0-9.\-]/g, '')) || 0;
      return sortKey === 'plh' ? (priceA - priceB) : (priceB - priceA);
    }
    
    return 0; // 'new' - keep original order
  });
  
  sorted.forEach(card => $grid.append(card));
}

// ============ IMAGE HANDLING ============
function renderPreviews() {
  const $previews = $('#previews');
  $previews.empty();
  
  filesArr.forEach((file, idx) => {
    const url = URL.createObjectURL(file);
    const $wrapper = $(`
      <div class="preview">
        <img src="${url}" alt="">
        <button type="button" data-idx="${idx}">&times;</button>
      </div>
    `);
    
    $wrapper.find('button').on('click', function() {
      filesArr.splice(idx, 1);
      renderPreviews();
      refreshSaveState();
    });
    
    $previews.append($wrapper);
  });
}

function setFiles(fileList) {
  const max = 5;
  const onlyImages = Array.from(fileList).filter(f => f.type.startsWith('image/'));
  filesArr = [...filesArr, ...onlyImages].slice(0, max);
  renderPreviews();
  refreshSaveState();
}

// ============ TAGS ============
function addTagToken(value) {
  const v = String(value || '').trim();
  if (!v) return;
  
  const $token = $(`<span class="token" data-value="${escapeHtml(v)}">${escapeHtml(v)} <span class="x" aria-label="Remove">×</span></span>`);
  $token.find('.x').on('click', function() {
    $token.remove();
    refreshSaveState();
  });
  
  $('#tagBox').prepend($token);
  refreshSaveState();
}

// ============ FORM VALIDATION ============
function refreshSaveState() {
  const hasFiles = filesArr.length > 0 || editingProductId !== null; // Allow edit without new images
  const title = $('#pTitle').val().trim();
  const price = $('#pPrice').val().trim();
  const cat = $('#product_cat').val();
  const brand = $('#product_brand').val();
  
  const basicValid = title && price && cat && brand;
  const isValid = basicValid && hasFiles;
  
  $('#imgError').toggle(!hasFiles && editingProductId === null);
  $('#btnSave').prop('disabled', !isValid);
}

// ============ RESET FORM ============
function resetProductForm() {
  editingProductId = null;
  $('#productForm')[0].reset();
  $('#pProductId').val('');
  $('#modalTitle').text('Add Product');
  $('#productFormAlert').empty();
  $('#product_keywords').val('');
  $('#descCount').text('0 / 300');
  $('#tagBox .token').remove();
  filesArr = [];
  renderPreviews();
  refreshSaveState();
}

// ============ ALERTS ============
function showModalAlert(type, message) {
  const alertClass = type === 'success' ? 'alert-success' : (type === 'error' || type === 'danger') ? 'alert-danger' : 'alert-info';
  $('#productFormAlert').html(`<div class="alert ${alertClass} alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>${message}</div>`);
}

function showAlert(type, message) {
  const alertClass = type === 'success' ? 'alert-success' : (type === 'error' || type === 'danger') ? 'alert-danger' : 'alert-warning';
  const $alert = $(`<div class="alert ${alertClass} alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>${message}</div>`);
  $('.page-head').after($alert);
  setTimeout(() => $alert.fadeOut(() => $alert.remove()), 5000);
}

function showToast(type, message) {
  // Simple toast notification
  const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
  const $toast = $(`
    <div class="position-fixed top-0 end-0 p-3" style="z-index:11000">
      <div class="toast show ${bgClass} text-white" role="alert">
        <div class="toast-body">${message}</div>
      </div>
    </div>
  `);
  $('body').append($toast);
  setTimeout(() => $toast.fadeOut(() => $toast.remove()), 3000);
}

// ============ UTILITY ============
function escapeHtml(text) {
  const map = {'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#039;'};
  return String(text || '').replace(/[&<>"']/g, m => map[m]);
}

// ============ BULK OPERATIONS ============
function downloadBulkTemplate() {
  window.location.href = '../actions/download_bulk_template_action.php';
}

function handleBulkUpload(formData) {
  // Show progress
  $('#bulkProgress').show();
  $('#bulkResults').hide();
  $('#bulkUploadAlert').empty();
  $('#btnBulkUpload').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');
  
  $.ajax({
    url: '../actions/bulk_upload_products_action.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(res) {
      $('#bulkProgress').hide();
      
      if (res.status === 'success') {
        // Show results
        $('#bulkSuccessCount').text(res.success_count || 0);
        $('#bulkErrorCount').text(res.error_count || 0);
        $('#bulkWarningCount').text(res.warnings ? res.warnings.length : 0);
        
        // Show/hide error section
        if (res.error_count > 0 && res.errors && res.errors.length > 0) {
          $('#bulkErrorSection').show();
          $('#bulkErrorList').show();
          const $errorItems = $('#bulkErrorItems');
          $errorItems.empty();
          res.errors.forEach(err => {
            $errorItems.append(`<li>${escapeHtml(err)}</li>`);
          });
        } else {
          $('#bulkErrorSection').hide();
          $('#bulkErrorList').hide();
        }
        
        // Show/hide warning section
        if (res.warnings && res.warnings.length > 0) {
          $('#bulkWarningSection').show();
          $('#bulkWarningList').show();
          const $warningItems = $('#bulkWarningItems');
          $warningItems.empty();
          res.warnings.forEach(warn => {
            $warningItems.append(`<li>${escapeHtml(warn)}</li>`);
          });
        } else {
          $('#bulkWarningSection').hide();
          $('#bulkWarningList').hide();
        }
        
        $('#bulkResults').show();
        
        // Show success toast
        if (res.success_count > 0) {
          showToast('success', `Successfully created ${res.success_count} product(s)`);
          loadProducts(); // Reload product list
        }
        
        // Reset form
        $('#bulkUploadForm')[0].reset();
      } else {
        $('#bulkUploadAlert').html(`
          <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <strong>Error:</strong> ${escapeHtml(res.message || 'Upload failed')}
          </div>
        `);
      }
    },
    error: function(xhr) {
      $('#bulkProgress').hide();
      let msg = 'Upload failed. Please try again.';
      try {
        const res = JSON.parse(xhr.responseText);
        msg = res.message || msg;
      } catch(e) {}
      
      $('#bulkUploadAlert').html(`
        <div class="alert alert-danger alert-dismissible fade show">
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          <strong>Error:</strong> ${escapeHtml(msg)}
        </div>
      `);
    },
    complete: function() {
      $('#btnBulkUpload').prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Upload & Process');
    }
  });
}

// ============ EVENT HANDLERS ============
$(document).ready(function() {
  // Load initial data
  loadProducts();
  loadCategories();
  loadBrands();
  
  // Filter inputs
  $('#q').on('input', applyFilters);
  $('#fCat, #fBrand, #fSort').on('change', applyFilters);
  
  // View toggle
  $('#btnGrid').on('click', function() {
    $(this).addClass('active');
    $('#btnTable').removeClass('active');
    // Could show/hide grid vs table here
  });
  
  $('#btnTable').on('click', function() {
    $(this).addClass('active');
    $('#btnGrid').removeClass('active');
    // Could show/hide grid vs table here
  });
  
  // Description counter
  $('#pDesc').on('input', function() {
    const len = $(this).val().length;
    $('#descCount').text(`${len} / 300`);
    refreshSaveState();
  });
  
  // Form inputs validation trigger
  $('#pTitle, #pPrice, #product_cat, #product_brand').on('input change', refreshSaveState);
  
  // Tag input
  $('#tagInput').on('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      addTagToken($(this).val());
      $(this).val('');
    }
  });
  
  // Image dropzone
  $('#dropzone').on('click', () => $('#filePicker').click());
  
  $('#filePicker').on('change', function(e) {
    setFiles(e.target.files);
    $(this).val('');
  });
  
  $('#dropzone').on('dragover', function(e) {
    e.preventDefault();
    $(this).addClass('drag');
  }).on('dragleave', function() {
    $(this).removeClass('drag');
  }).on('drop', function(e) {
    e.preventDefault();
    $(this).removeClass('drag');
    setFiles(e.originalEvent.dataTransfer.files);
  });
  
  // Modal reset on close
  $('#productModal').on('hidden.bs.modal', resetProductForm);
  
  // Form submit
  $('#productForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    
    // Add product ID if editing
    if (editingProductId) {
      formData.append('product_id', editingProductId);
    }
    
    // Add basic fields
    formData.append('product_title', $('#pTitle').val().trim());
    formData.append('product_price', $('#pPrice').val().trim());
    formData.append('product_cat', $('#product_cat').val());
    formData.append('product_brand', $('#product_brand').val());
    formData.append('product_desc', $('#pDesc').val().trim());
    
    // Collect tags
    const tags = [];
    $('#tagBox .token').each(function() {
      tags.push($(this).data('value'));
    });
    formData.append('product_keywords', tags.join(','));
    
    // Add images
    filesArr.forEach((file, idx) => {
      formData.append('images[]', file);
    });
    
    saveProduct(formData);
  });
  
  // Download template button
  $('#btnDownloadTemplate').on('click', function(e) {
    e.preventDefault();
    downloadBulkTemplate();
  });
  
  // Bulk upload form
  $('#bulkUploadForm').on('submit', function(e) {
    e.preventDefault();
    
    const fileInput = $('#bulkFile')[0];
    if (!fileInput.files || fileInput.files.length === 0) {
      $('#bulkUploadAlert').html(`
        <div class="alert alert-warning alert-dismissible fade show">
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          Please select a ZIP file to upload
        </div>
      `);
      return;
    }
    
    const formData = new FormData();
    formData.append('bulk_file', fileInput.files[0]);
    
    handleBulkUpload(formData);
  });
  
  // Reset bulk upload modal on close
  $('#bulkUploadModal').on('hidden.bs.modal', function() {
    $('#bulkUploadForm')[0].reset();
    $('#bulkUploadAlert').empty();
    $('#bulkProgress').hide();
    $('#bulkResults').hide();
  });
});
