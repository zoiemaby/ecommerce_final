/**
 * products.js v2.1
 * Comprehensive JavaScript for product pages
 * Handles: all_product.php, single_product.php, product_search_result.php
 * Features: dynamic search, async filtering, pagination, enhanced UX
 * Last updated: 2025-11-05 - Fixed pagination variable shadowing & sorting
 */

const ProductsApp = (function() {
  'use strict';

  const API_BASE = '../actions/product_actions.php';
  const PAGE_SIZE = 10;
  
  let currentPage = 1;
  let currentFilters = {
    query: '',
    category: '',
    brand: '',
    sort: 'newest'
  };
  let pageType = 'all_product'; // 'all_product', 'single_product', 'search_results'

  /**
   * Initialize the app based on page type
   */
  function init(type) {
    pageType = type;
    console.log(`[ProductsApp] Initializing ${type} page`);

    switch(type) {
      case 'all_product':
        initAllProductsPage();
        break;
      case 'single_product':
        initSingleProductPage();
        break;
      case 'search_results':
        initSearchResultsPage();
        break;
    }
  }

  /**
   * Initialize All Products Page
   */
  function initAllProductsPage() {
    loadCategories();
    loadBrands();
    loadProducts();

    // Event listeners
    $('#filterCategory').on('change', () => {
      currentFilters.category = $('#filterCategory').val();
      currentPage = 1;
      loadProducts();
    });

    $('#filterBrand').on('change', () => {
      currentFilters.brand = $('#filterBrand').val();
      currentPage = 1;
      loadProducts();
    });

    $('#filterSort').on('change', () => {
      currentFilters.sort = $('#filterSort').val();
      currentPage = 1;
      loadProducts();
    });

    $('#btnSearch').on('click', () => {
      const query = $('#searchQuery').val().trim();
      if (query) {
        window.location.href = `product_search_result.php?q=${encodeURIComponent(query)}`;
      }
    });

    $('#searchQuery').on('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        $('#btnSearch').click();
      }
    });

    $('#btnResetFilters').on('click', () => {
      currentFilters = { query: '', category: '', brand: '', sort: 'newest' };
      $('#filterCategory').val('');
      $('#filterBrand').val('');
      $('#filterSort').val('newest');
      currentPage = 1;
      loadProducts();
      $('#btnResetFilters').hide();
    });
  }

  /**
   * Initialize Single Product Page
   */
  function initSingleProductPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');

    if (!productId) {
      showError();
      return;
    }

    loadSingleProduct(productId);

    // Add to cart handler
    $('#btnAddToCart').on('click', () => {
      Swal.fire({
        icon: 'info',
        title: 'Coming Soon!',
        text: 'Add to Cart functionality will be implemented in the next phase.',
        confirmButtonColor: 'hsl(158, 82%, 15%)',
        confirmButtonText: 'Got it!'
      });
      // TODO: Implement cart functionality
    });

    $('#btnWishlist').on('click', () => {
      Swal.fire({
        icon: 'info',
        title: 'Coming Soon!',
        text: 'Wishlist functionality will be available soon.',
        confirmButtonColor: 'hsl(158, 82%, 15%)',
        confirmButtonText: 'Got it!'
      });
    });
  }

  /**
   * Initialize Search Results Page
   */
  function initSearchResultsPage() {
    const urlParams = new URLSearchParams(window.location.search);
    currentFilters.query = urlParams.get('q') || '';

    if (!currentFilters.query) {
      window.location.href = 'all_product.php';
      return;
    }

    $('#searchQuery').val(currentFilters.query);
    $('#searchQueryDisplay').text(currentFilters.query);

    loadCategories();
    loadBrands();
    searchProducts();

    // Event listeners
    $('#filterCategory').on('change', () => {
      currentFilters.category = $('#filterCategory').val();
      currentPage = 1;
      searchProducts();
    });

    $('#filterBrand').on('change', () => {
      currentFilters.brand = $('#filterBrand').val();
      currentPage = 1;
      searchProducts();
    });

    $('#filterSort').on('change', () => {
      currentFilters.sort = $('#filterSort').val();
      currentPage = 1;
      searchProducts();
    });

    $('#btnSearch').on('click', () => {
      const query = $('#searchQuery').val().trim();
      if (query) {
        window.location.href = `product_search_result.php?q=${encodeURIComponent(query)}`;
      }
    });

    $('#searchQuery').on('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        $('#btnSearch').click();
      }
    });

    $('#btnClearSearch').on('click', () => {
      window.location.href = 'all_product.php';
    });
  }

  /**
   * Load categories for dropdown
   */
  function loadCategories() {
    $.ajax({
      url: API_BASE,
      type: 'GET',
      data: { action: 'categories' },
      dataType: 'json',
      success: function(response) {
        if (response.success && Array.isArray(response.data)) {
          const $select = $('#filterCategory');
          $select.find('option:not(:first)').remove();
          
          response.data.forEach(cat => {
            $select.append(`<option value="${cat.cat_id}">${cat.cat_name}</option>`);
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('[ProductsApp] Failed to load categories:', error);
        showErrorAlert('Failed to load categories', 'Please refresh the page to try again.');
      }
    });
  }

  /**
   * Load brands for dropdown
   */
  function loadBrands() {
    $.ajax({
      url: API_BASE,
      type: 'GET',
      data: { action: 'brands' },
      dataType: 'json',
      success: function(response) {
        if (response.success && Array.isArray(response.data)) {
          const $select = $('#filterBrand');
          $select.find('option:not(:first)').remove();
          
          response.data.forEach(brand => {
            const brandId = brand.brand_id || brand.id;
            const brandName = brand.brand_name || brand.name;
            $select.append(`<option value="${brandId}">${brandName}</option>`);
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('[ProductsApp] Failed to load brands:', error);
        showErrorAlert('Failed to load brands', 'Please refresh the page to try again.');
      }
    });
  }

  /**
   * Load all products with filters
   */
  function loadProducts() {
    console.log(`[ProductsApp] Loading products - Page: ${currentPage}`);
    showLoader();

    const params = {
      action: 'filter',
      page: currentPage,
      limit: PAGE_SIZE
    };

    if (currentFilters.category) params.cat_id = currentFilters.category;
    if (currentFilters.brand) params.brand_id = currentFilters.brand;
    if (currentFilters.query) params.q = currentFilters.query;

    $.ajax({
      url: API_BASE,
      type: 'GET',
      data: params,
      dataType: 'json',
      success: function(response) {
        hideLoader();
        
        if (response.success) {
          let products = response.data.products || [];
          const pagination = response.data.pagination || {};
          
          // Apply sorting BEFORE rendering
          if (currentFilters.sort) {
            products = sortProducts(products, currentFilters.sort);
          }
          
          renderProducts(products);
          renderPagination(pagination);
          updateCounts(pagination.total || 0);
          
          // Show reset button if filters applied
          if (currentFilters.category || currentFilters.brand) {
            $('#btnResetFilters').show();
          }
        } else {
          showEmptyState();
        }
      },
      error: function(xhr, status, error) {
        hideLoader();
        console.error('[ProductsApp] Failed to load products:', error);
        showEmptyState();
        showErrorAlert('Failed to Load Products', 'Unable to fetch products. Please check your connection and try again.');
      }
    });
  }

  /**
   * Search products
   */
  function searchProducts() {
    if (!currentFilters.query) {
      loadProducts();
      return;
    }

    showLoader();

    const params = {
      action: 'search',
      q: currentFilters.query,
      page: currentPage,
      limit: PAGE_SIZE
    };

    if (currentFilters.category) params.cat_id = currentFilters.category;
    if (currentFilters.brand) params.brand_id = currentFilters.brand;

    $.ajax({
      url: API_BASE,
      type: 'GET',
      data: params,
      dataType: 'json',
      success: function(response) {
        hideLoader();
        
        if (response.success) {
          let products = response.data.products || [];
          const pagination = response.data.pagination || {};

          // Apply filters if needed
          if (currentFilters.category) {
            products = products.filter(p => p.product_cat == currentFilters.category);
          }
          if (currentFilters.brand) {
            products = products.filter(p => p.product_brand == currentFilters.brand);
          }

          // Apply sorting
          if (currentFilters.sort) {
            products = sortProducts(products, currentFilters.sort);
          }
          
          renderProducts(products);
          renderPagination(pagination);
          updateCounts(pagination.total || 0);
        } else {
          showEmptyState();
        }
      },
      error: function(xhr, status, error) {
        hideLoader();
        console.error('[ProductsApp] Failed to search products:', error);
        showEmptyState();
        showErrorAlert('Search Failed', 'Unable to search products. Please try again.');
      }
    });
  }

  /**
   * Load single product
   */
  function loadSingleProduct(productId) {
    showLoader();

    $.ajax({
      url: API_BASE,
      type: 'GET',
      data: { action: 'single', id: productId },
      dataType: 'json',
      success: function(response) {
        hideLoader();
        
        if (response.success && response.data) {
          renderSingleProduct(response.data);
        } else {
          showError();
        }
      },
      error: function(xhr, status, error) {
        hideLoader();
        console.error('[ProductsApp] Failed to load product:', error);
        showError();
        showErrorAlert('Product Not Found', 'Unable to load product details. The product may not exist.');
      }
    });
  }

  /**
   * Render products grid
   */
  function renderProducts(products) {
    const $grid = $('#productsGrid');
    $grid.empty();

    if (!products || products.length === 0) {
      showEmptyState();
      return;
    }

    $('#emptyState').hide();
    
    products.forEach(product => {
      const imageUrl = product.product_image 
        ? `../${product.product_image}` 
        : 'https://via.placeholder.com/400x300?text=No+Image';
      
      const card = `
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
          <div class="card-prod">
            <img class="thumb" src="${imageUrl}" alt="${escapeHtml(product.product_title)}" 
                 onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
            <div class="card-body">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge-soft">${escapeHtml(product.cat_name || 'Category')}</span>
                <span class="badge-soft">${escapeHtml(product.brand_name || 'Brand')}</span>
              </div>
              <a class="text-decoration-none text-dark fw-semibold d-block mb-2" 
                 href="single_product.php?id=${product.product_id}">
                ${escapeHtml(product.product_title)}
              </a>
              <div class="mt-auto">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="price">GHS ${parseFloat(product.product_price).toFixed(2)}</div>
                  <button class="btn btn-sm btn-dark add-to-cart-btn" type="button" 
                          data-product-id="${product.product_id}" data-quantity="1">
                    <i class="bi bi-cart-plus"></i>
                  </button>
                </div>
                <a href="single_product.php?id=${product.product_id}" class="btn btn-sm btn-outline-dark w-100">
                  <i class="bi bi-eye"></i> View Details
                </a>
              </div>
            </div>
          </div>
        </div>
      `;
      $grid.append(card);
    });
  }

  /**
   * Render single product detail
   */
  function renderSingleProduct(product) {
    $('#loader').hide();
    $('#productDetail').show();

    const imageUrl = product.product_image 
      ? `../${product.product_image}` 
      : 'https://via.placeholder.com/600x600?text=No+Image';

    $('#productId').text(product.product_id);
    $('#productImage').attr('src', imageUrl);
    $('#productImage').attr('alt', product.product_title);
    $('#productTitle').text(product.product_title);
    $('#productPrice').text(parseFloat(product.product_price).toFixed(2));
    $('#productCategory').text(product.cat_name || 'Category');
    $('#productBrand').text(product.brand_name || 'Brand');
    $('#productDescription').text(product.product_desc || 'No description available.');
    $('#breadcrumbTitle').text(product.product_title);

    // Render keywords
    if (product.product_keywords && product.product_keywords.trim()) {
      const keywords = product.product_keywords.split(',').map(k => k.trim()).filter(k => k);
      if (keywords.length > 0) {
        const keywordsHtml = keywords.map(k => 
          `<span class="keyword-tag">${escapeHtml(k)}</span>`
        ).join('');
        $('#productKeywords').html(keywordsHtml);
        $('#keywordsSection').show();
      } else {
        $('#keywordsSection').hide();
      }
    } else {
      $('#keywordsSection').hide();
    }

    // Store product ID for cart functionality
    $('#btnAddToCart').data('product-id', product.product_id);
  }

  /**
   * Render pagination
   */
  function renderPagination(pagination) {
    const $pager = $('#pagination');
    const $container = $('#paginationContainer');
    $pager.empty();

    if (!pagination || pagination.total_pages <= 1) {
      $container.hide();
      return;
    }

    $container.show();

    const page = pagination.current_page || 1;
    const totalPages = pagination.total_pages || 1;

    // Previous button
    if (pagination.has_prev) {
      $pager.append(`
        <li class="page-item">
          <a class="page-link" href="#" data-page="${page - 1}">&laquo;</a>
        </li>
      `);
    }

    // Page numbers
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(totalPages, page + 2);

    if (startPage > 1) {
      $pager.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
      if (startPage > 2) {
        $pager.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      const activeClass = i === page ? 'active' : '';
      $pager.append(`
        <li class="page-item ${activeClass}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        $pager.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
      $pager.append(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
    }

    // Next button
    if (pagination.has_next) {
      $pager.append(`
        <li class="page-item">
          <a class="page-link" href="#" data-page="${page + 1}">&raquo;</a>
        </li>
      `);
    }

    // Bind pagination click events
    $pager.off('click').on('click', 'a[data-page]', function(e) {
      e.preventDefault();
      const newPage = parseInt($(this).data('page'));
      
      if (!isNaN(newPage) && newPage > 0) {
        currentPage = newPage;
        console.log(`[ProductsApp] Navigating to page ${currentPage}`);
        
        if (pageType === 'search_results') {
          searchProducts();
        } else {
          loadProducts();
        }

        $('html, body').animate({ scrollTop: 0 }, 400);
      }
    });
  }

  /**
   * Sort products array
   */
  function sortProducts(products, sortType) {
    const sorted = [...products];
    
    switch(sortType) {
      case 'price_asc':
        return sorted.sort((a, b) => parseFloat(a.product_price) - parseFloat(b.product_price));
      case 'price_desc':
        return sorted.sort((a, b) => parseFloat(b.product_price) - parseFloat(a.product_price));
      case 'name_asc':
        return sorted.sort((a, b) => a.product_title.localeCompare(b.product_title));
      case 'name_desc':
        return sorted.sort((a, b) => b.product_title.localeCompare(a.product_title));
      case 'newest':
      default:
        return sorted.sort((a, b) => b.product_id - a.product_id);
    }
  }

  /**
   * Update counts
   */
  function updateCounts(total) {
    $('#totalCount').text(`${total} product${total !== 1 ? 's' : ''}`);
  }

  /**
   * Show loader
   */
  function showLoader() {
    $('#loader').show();
    $('#productsGrid').hide();
    $('#emptyState').hide();
    $('#paginationContainer').hide();
  }

  /**
   * Hide loader
   */
  function hideLoader() {
    $('#loader').hide();
    $('#productsGrid').show();
  }

  /**
   * Show empty state
   */
  function showEmptyState() {
    $('#productsGrid').hide();
    $('#emptyState').show();
    $('#paginationContainer').hide();
    updateCounts(0);
  }

  /**
   * Show error (for single product)
   */
  function showError() {
    $('#loader').hide();
    $('#productDetail').hide();
    $('#errorState').show();
  }

  /**
   * Add to cart - now handled by cart.js
   * This function is deprecated but kept for backwards compatibility
   */
  function addToCart(productId) {
    // Trigger the add-to-cart button click which is handled by cart.js
    const btn = $(`.add-to-cart-btn[data-product-id="${productId}"]`).first();
    if (btn.length) {
      btn.trigger('click');
    } else {
      console.warn('[ProductsApp] Add to cart button not found for product:', productId);
    }
  }

  /**
   * Escape HTML to prevent XSS
   */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Show error alert with SweetAlert2
   */
  function showErrorAlert(title, message) {
    Swal.fire({
      icon: 'error',
      title: title || 'Error',
      text: message || 'Something went wrong. Please try again.',
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Close'
    });
  }

  /**
   * Show success alert with SweetAlert2
   */
  function showSuccessAlert(title, message) {
    Swal.fire({
      icon: 'success',
      title: title || 'Success!',
      text: message,
      confirmButtonColor: 'hsl(158, 82%, 15%)',
      confirmButtonText: 'Great!',
      timer: 2000,
      showConfirmButton: false
    });
  }

  /**
   * Show loading alert with SweetAlert2
   */
  function showLoadingAlert(message) {
    Swal.fire({
      title: 'Loading...',
      text: message || 'Please wait',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
  }

  // Public API
  return {
    init,
    addToCart
  };
})();
