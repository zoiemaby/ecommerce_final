/* brand.js
 * Front-end only. Validates inputs, checks types, calls your brand action PHP endpoints
 * asynchronously, and shows success/failure via pop-ups (SweetAlert2).
 *
 * Requires:
 *  - jQuery
 *  - SweetAlert2 (Swal)
 *
 * Expected DOM (adjust selectors if different):
 *  - Add form:    #brandForm  with input [name="brand_name"]
 *  - Edit button: .btn-edit   with data attributes: data-brand-id, data-brand-name
 *  - Delete btn:  .btn-delete with data-brand-id
 */

$(function () {
  // -----------------------------
  // Utilities
  // -----------------------------
  const toast = (icon, title, text = '') => Swal.fire({ icon, title, text });

  const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[m]));

  const isNonEmptyString = v => typeof v === 'string' && v.trim().length > 0;
  const isPosInt = v => Number.isInteger(v) && v > 0;
  const toInt = v => {
    const n = Number(v);
    return Number.isFinite(n) ? Math.trunc(n) : NaN;
  };

  // -----------------------------
  // AJAX wrappers for Actions
  // -----------------------------
  const endpoints = {
    fetch:  '../actions/fetch_brand_action.php',
    add:    '../actions/add_brand_action.php',
    update: '../actions/update_brand_action.php',
    delete: '../actions/delete_brand_action.php',
  };

  // Generic GET (for fetch_* actions)
  function getJSON(url, params = {}) {
    return $.ajax({ url, method: 'GET', data: params, dataType: 'json' });
  }

  // Generic POST (for add/update/delete)
  function postJSON(url, body = {}) {
    return $.ajax({
      url, method: 'POST', data: body, dataType: 'json'
      // If you prefer JSON body: set contentType + JSON.stringify and update PHP reader accordingly
      // contentType: 'application/json; charset=utf-8',
      // data: JSON.stringify(body),
    });
  }

  // -----------------------------
  // VALIDATION (Front-end only)
  // -----------------------------
  function validateAddOrUpdatePayload({ brand_name, brand_id = null }) {
    const errors = [];

    if (!isNonEmptyString(brand_name)) {
      errors.push('Brand name is required.');
    } else if (brand_name.trim().length > 255) {
      errors.push('Brand name is too long (max 255 characters).');
    }

    if (brand_id !== null) {
      const brandIdInt = toInt(brand_id);
      if (!isPosInt(brandIdInt)) {
        errors.push('Invalid brand ID.');
      }
    }

    return { ok: errors.length === 0, errors };
  }

  function validateDeletePayload({ brand_id }) {
    const errors = [];
    const brandIdInt = toInt(brand_id);
    if (!isPosInt(brandIdInt)) {
      errors.push('Invalid brand ID.');
    }
    return { ok: errors.length === 0, errors };
  }

  console.info('brand.js build = 2025-11-01-6');
// Ensure we always have a container to render into
  function getGridEl() {
    let el = document.getElementById('brandGrid');
    if (!el) {
      const host = document.querySelector('.brand-list') || document.body;
      el = document.createElement('div');
      el.id = 'brandGrid';
      el.className = 'brand-grid';
      host.appendChild(el);
    }
    return el;
  }

  // Increment "X total" safely
  function bumpTotal(delta = 1) {
    const counter = document.getElementById('brandTotalCount');
    if (!counter) return;
    const m = (counter.textContent || '').match(/(\d+)/);
    const cur = m ? parseInt(m[1], 10) : 0;
    counter.textContent = `${Math.max(0, cur + delta)} total`;
  }

  // Append a single brand card to the grid (no fetch needed)
  function appendBrandCard({ id, name }) {
    const grid = getGridEl();

    // Remove empty-state message if present
    const emptyMsg = grid.querySelector('.text-muted');
    if (emptyMsg) emptyMsg.remove();

    const card = document.createElement('div');
    card.className = 'brand-card d-flex align-items-center gap-2';
    card.innerHTML = `
      <div class="brand-card__name">${esc(name)}</div>
      <div class="brand-card__actions ms-auto">
        <button class="icon-btn btn-edit" title="Edit"
          data-brand-id="${id ?? ''}" data-brand-name="${esc(name)}">
          <i class="bi bi-pencil-square"></i>
        </button>
        <button class="icon-btn btn-delete" title="Delete" data-brand-id="${id ?? ''}">
          <i class="bi bi-trash3"></i>
        </button>
      </div>
    `;
    grid.prepend(card); // show newest first
    bumpTotal(+1);
  }

  // -----------------------------
  // FETCH
  // -----------------------------
  function fetchBrands({ type = 'all', brand_id = null, search = null, suppressToast = true } = {}) {
    const params = { type };
    if (type === 'single') {
      const b = toInt(brand_id);
      if (!isPosInt(b)) return Promise.reject(new Error('Invalid brand ID for single fetch.'));
      params.brand_id = b;
    }
    if (isNonEmptyString(search)) params.search = search.trim();

    return getJSON(endpoints.fetch, params).then(res => {
      if (res?.ok === true || res?.status === 'success') return res.data || [];
      throw new Error(res?.message || 'Failed to fetch brands.');
    }).then(list => {
      console.debug('fetchBrands ok: items=', list.length);
      return list;
    }).catch(err => {
      if (!suppressToast) toast('error', 'Request Failed', err?.responseJSON?.message || err.message || 'Network error.');
      console.error('fetchBrands error:', err);
      throw err;
    });
  }

  // Render full list after fetch
  function renderBrands(list = []) {
    const grid = getGridEl();
    const items = Array.isArray(list) ? list : [];

    if (!items.length) {
      grid.innerHTML = '<div class="text-muted">No brands found. Add one using the form above.</div>';
    } else {
      grid.innerHTML = items.map(b => {
        const id = b.brand_id ?? b.id ?? '';
        const name = esc(b.brand_name ?? b.name ?? '');
        return `
          <div class="brand-card d-flex align-items-center gap-2">
            <div class="brand-card__name">${name}</div>
            <div class="brand-card__actions ms-auto">
              <button class="icon-btn btn-edit" title="Edit"
                data-brand-id="${id}" data-brand-name="${name}">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button class="icon-btn btn-delete" title="Delete" data-brand-id="${id}">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </div>`;
      }).join('');
    }

    const counter = document.getElementById('brandTotalCount');
    if (counter) counter.textContent = `${items.length} total`;
  }

  // -----------------------------
  // ADD
  // -----------------------------
  function addBrand({ brand_name }) {
    const v = validateAddOrUpdatePayload({ brand_name });
    if (!v.ok) {
      toast('warning', 'Check Inputs', v.errors.join('\n'));
      return Promise.reject(new Error('Validation failed.'));
    }

    return postJSON(endpoints.add, { brand_name: brand_name.trim() })
      .then(res => {
        if (res && res.ok === true) {
          const newId = res?.data?.brand_id ?? null;

          // Update UI immediately (no fetch dependency)
          appendBrandCard({ id: newId, name: brand_name.trim() });

          return Swal.fire({
            icon: 'success',
            title: 'Brand created',
            text: res.message || 'Brand added successfully.',
            confirmButtonText: 'OK'
          });
        }

        if (res && res.ok === false && res.reason === 'exists') {
          toast('warning', 'Exists', res.message || 'Brand already exists.');
          return Promise.reject(new Error('exists'));
        }

        const msg = (res && res.message) ? res.message : 'Failed to add brand.';
        toast('error', 'Error', msg);
        throw new Error(msg);
      })
      .catch(err => {
        if (err?.message === 'exists') return Promise.reject(err);
        toast('error', 'Request Failed', err?.responseJSON?.message || err.message || 'Network error.');
        throw err;
      });
  }

  // -----------------------------
  // UPDATE (unchanged network call)
  // -----------------------------
  function updateBrand({ brand_id, brand_name }) {
    const v = validateAddOrUpdatePayload({ brand_id, brand_name });
    if (!v.ok) {
      toast('warning', 'Check Inputs', v.errors.join('\n'));
      return Promise.reject(new Error('Validation failed.'));
    }

    return postJSON(endpoints.update, {
      brand_id: toInt(brand_id),
      brand_name: brand_name.trim()
    })
      .then(res => {
        if (res?.status === 'success') {
          toast('success', 'Updated', res.message || 'Brand updated successfully.');
          return true;
        }
        const msg = res?.message || 'Failed to update brand.';
        toast('error', 'Error', msg);
        throw new Error(msg);
      })
      .catch(err => {
        toast('error', 'Request Failed', err?.responseJSON?.message || err.message || 'Network error.');
        throw err;
      });
  }

  // -----------------------------
  // DELETE (unchanged network call)
  // -----------------------------
  function deleteBrand({ brand_id }) {
    const v = validateDeletePayload({ brand_id });
    if (!v.ok) {
      toast('warning', 'Check Inputs', v.errors.join('\n'));
      return Promise.reject(new Error('Validation failed.'));
    }

    return Swal.fire({
      icon: 'warning',
      title: 'Delete Brand?',
      text: 'This action cannot be undone.',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      reverseButtons: true,
    }).then(result => {
      if (!result.isConfirmed) return Promise.reject(new Error('User cancelled.'));
      return postJSON(endpoints.delete, { brand_id: toInt(brand_id) });
    }).then(res => {
      if (res?.status === 'success') {
        toast('success', 'Deleted', res.message || 'Brand deleted successfully.');
        return true;
      }
      const msg = res?.message || 'Failed to delete brand.';
      toast('error', 'Error', msg);
      throw new Error(msg);
    }).catch(err => {
      if (err?.message === 'User cancelled.') return false; // no popup needed
      toast('error', 'Request Failed', err?.responseJSON?.message || err.message || 'Network error.');
      throw err;
    });
  }

  // -----------------------------
  // EVENT WIRING (adjust selectors if your HTML differs)
  // -----------------------------

  // Add Brand form - keep existing behavior
  $('#brandForm').on('submit', function (e) {
    e.preventDefault();
    const $f = $(this);
    const brand_name = String($f.find('[name="brand_name"]').val() || '');

    addBrand({ brand_name })
      .then(() => {
        // clear input after successful add
        $f.trigger('reset');
      })
      .catch(() => { /* errors already toasted */ });
  });

  // Edit button click (open your inline edit panel & preload fields)
  $(document).on('click', '.btn-edit', function () {
    const $btn = $(this);
    const brand_id = $btn.data('brand-id');
    const brand_name = $btn.data('brand-name');

    $('#editPanel').show();
    const $f = $('#editBrandForm');
    $f.find('[name="brand_id"]').val(brand_id);
    $f.find('[name="brand_name"]').val(brand_name);
    // scroll into view for better UX
    $('html, body').animate({ scrollTop: $('#editPanel').offset().top - 20 }, 250);
  });

  // cancel edit
  $(document).on('click', '#editCancel', function () {
    $('#editPanel').hide();
  });

  // Edit submit -> perform AJAX update and update DOM in-place (no reload)
  $('#editBrandForm').on('submit', function (e) {
    e.preventDefault();
    const $f = $(this);
    const payload = {
      brand_id: $f.find('[name="brand_id"]').val(),
      brand_name: String($f.find('[name="brand_name"]').val() || '')
    };

    updateBrand(payload)
      .then(() => {
        const bid = String(payload.brand_id);
        const $editBtn = $(`.btn-edit[data-brand-id="${bid}"]`);
        const $card = $editBtn.closest('.brand-card');
        if ($card && $card.length) {
          $card.find('.brand-card__name').text(payload.brand_name);
          $editBtn.data('brand-name', payload.brand_name);
        } else {
          location.reload();
        }
        $('#editPanel').hide();
      })
      .catch(() => { /* errors already toasted */ });
  });

  // Delete button: call delete action and remove card from DOM on success (no reload)
  $(document).on('click', '.btn-delete', function () {
    const $btn = $(this);
    const brand_id = $btn.data('brand-id');

    deleteBrand({ brand_id })
      .then(deleted => {
        if (deleted) {
          // remove card from DOM
          const $card = $btn.closest('.brand-card');
          // update category count if present
          const $group = $card.closest('.cat-group');
          if ($card && $card.length) $card.fadeOut(200, function () { $(this).remove(); });
          if ($group && $group.length) {
            const $meta = $group.find('.cat-group__meta');
            if ($meta && $meta.length) {
              // decrement numeric count in "X brands"
              const text = $meta.text() || '';
              const m = text.match(/(\d+)/);
              if (m) {
                const n = Math.max(0, parseInt(m[1], 10) - 1);
                const suffix = n === 1 ? 'brand' : 'brands';
                $meta.text(`${n} ${suffix}`);
              }
            }
          }
        }
      })
      .catch(() => { /* errors already toasted */ });
  });

  // Initial load: fetch then render
  fetchBrands().then(renderBrands).catch(() => { /* ignore; UI will append on add */ });
});
