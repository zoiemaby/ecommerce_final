/* category.js

Client-side validation + async callers for category actions:
- add_category_action.php  (POST)
- fetch_category_action.php (GET)
- update_category_action.php (POST)
- delete_category_action.php (POST)

Features:
- Validates inputs (type checks, length, allowed characters)
- Uses fetch() with graceful handling if server returns JSON or redirects
- Shows a small toast popup for success / error messages
- Provides init helpers to wire your forms / buttons

Usage examples (at bottom) show how to bind forms/buttons.
*/

(() => {
  // ------------------ Utilities ------------------
  function el(html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();
    return template.content.firstChild;
  }

  // Simple toast system
  const TOAST_LIFETIME = 3500;
  function ensureToastContainer() {
    let c = document.getElementById('cat-toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'cat-toast-container';
      Object.assign(c.style, {
        position: 'fixed',
        right: '18px',
        top: '18px',
        zIndex: 99999,
        display: 'flex',
        flexDirection: 'column',
        gap: '8px',
        alignItems: 'flex-end',
      });
      document.body.appendChild(c);
    }
    return c;
  }

  function showToast(message, type = 'info') {
    const container = ensureToastContainer();
    const node = document.createElement('div');
    node.textContent = message;
    Object.assign(node.style, {
      padding: '10px 14px',
      borderRadius: '10px',
      boxShadow: '0 6px 18px rgba(0,0,0,0.08)',
      maxWidth: '320px',
      fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial',
      fontSize: '14px',
      color: '#fff',
      opacity: '0',
      transform: 'translateY(-6px)',
      transition: 'opacity .18s ease, transform .18s ease',
    });

    if (type === 'success') node.style.background = 'linear-gradient(90deg,#16a34a,#059669)';
    else if (type === 'error') node.style.background = 'linear-gradient(90deg,#dc2626,#b91c1c)';
    else node.style.background = 'linear-gradient(90deg,#2563eb,#1d4ed8)';

    container.appendChild(node);

    // entrance
    requestAnimationFrame(() => {
      node.style.opacity = '1';
      node.style.transform = 'translateY(0)';
    });

    // auto remove
    setTimeout(() => {
      node.style.opacity = '0';
      node.style.transform = 'translateY(-6px)';
      setTimeout(() => node.remove(), 200);
    }, TOAST_LIFETIME);
  }

  // Validate category name
  function validateCategoryName(name) {
    if (typeof name !== 'string') return { ok: false, reason: 'Category name must be text.' };
    const trimmed = name.trim();
    if (trimmed.length === 0) return { ok: false, reason: 'Category name cannot be empty.' };
    if (trimmed.length < 2 || trimmed.length > 80) return { ok: false, reason: 'Category name must be between 2 and 80 characters.' };
    // allow letters, numbers, spaces, hyphen, underscore, ampersand, parentheses
    const re = /^[\p{L}0-9 _\-&()\.]+$/u;
    if (!re.test(trimmed)) return { ok: false, reason: 'Category name contains invalid characters.' };
    return { ok: true, value: trimmed };
  }

  function validateId(id) {
    const n = Number(id);
    if (!Number.isInteger(n) || n <= 0) return { ok: false, reason: 'Invalid category id.' };
    return { ok: true, value: n };
  }

  // fetch helper - tries to parse JSON, otherwise fallbacks to success/error based on response.ok
  async function doFetch(url, opts = {}) {
    opts.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, opts.headers || {});
    const resp = await fetch(url, opts);
    const ctype = resp.headers.get('Content-Type') || '';

    // If server returned JSON, parse and return
    if (ctype.includes('application/json')) {
      const json = await resp.json();
      return { ok: resp.ok, json };
    }

    // Not JSON: attempt to read text but treat 200-299 as ok
    const text = await resp.text();
    return { ok: resp.ok, text };
  }

  // ------------------ API Actions ------------------
  // Helper to update the category list in the DOM
  async function refreshCategoryList() {
    // You may need to adjust this selector and rendering logic to match your HTML
    const container = document.querySelector('.user-list');
    if (!container) return;
    try {
      const res = await doFetch('../actions/fetch_category_action.php', { method: 'GET' });
      if (res.json && Array.isArray(res.json.data)) {
        container.innerHTML = '';
        res.json.data.forEach(cat => {
          const div = document.createElement('div');
          div.className = 'user-item';
          div.innerHTML = `
            <div class="user-details">
              <p class="small"><strong>Name:</strong> ${cat.cat_name}</p>
            </div>
            <div class="user-actions">
              <button class="action-button secondary js-delete" data-id="${cat.cat_id}" title="Delete">Delete</button>
              <form class="edit-category-form" style="display:inline;">
                <input type="hidden" name="category_id" value="${cat.cat_id}">
                <input type="text" name="category_name" value="${cat.cat_name}" required>
                <button class="action-button" type="submit" title="Update">Update</button>
              </form>
            </div>
          `;
          container.appendChild(div);
        });
      }
    } catch (e) {
      showToast('Failed to refresh category list', 'error');
    }
  }

  async function addCategory({ name, formEl }) {
    const v = validateCategoryName(name);
    if (!v.ok) {
      showToast(v.reason, 'error');
      return false;
    }
    const form = new FormData();
    form.append('category_name', v.value);
    try {
      const res = await doFetch('../actions/add_category_action.php', {
        method: 'POST',
        body: form,
      });
      if (res.json && res.json.status === 'success') {
        showToast(res.json.message || 'Category added', 'success');
        if (formEl) formEl.reset();
        await refreshCategoryList();
        return true;
      } else {
        showToast((res.json && res.json.message) || 'Failed to add category.', 'error');
        return false;
      }
    } catch (e) {
      showToast('Network error: ' + e.message, 'error');
      return false;
    }
  }

  async function updateCategory({ id, name }) {
    const vId = validateId(id);
    if (!vId.ok) {
      showToast(vId.reason, 'error');
      return false;
    }
    const v = validateCategoryName(name);
    if (!v.ok) {
      showToast(v.reason, 'error');
      return false;
    }
    const form = new FormData();
    form.append('category_id', String(vId.value));
    form.append('category_name', v.value);
    try {
      const res = await doFetch('../actions/update_category_action.php', { method: 'POST', body: form });
      if (res.json && res.json.status === 'success') {
        showToast(res.json.message || 'Category updated', 'success');
        await refreshCategoryList();
        return true;
      } else {
        showToast((res.json && res.json.message) || 'Failed to update category', 'error');
        return false;
      }
    } catch (e) {
      showToast('Network error: ' + e.message, 'error');
      return false;
    }
  }

  async function deleteCategory({ id }) {
    const vId = validateId(id);
    if (!vId.ok) {
      showToast(vId.reason, 'error');
      return false;
    }
    const form = new FormData();
    form.append('category_id', String(vId.value));
    try {
      const res = await doFetch('../actions/delete_category_action.php', { method: 'POST', body: form });
      if (res.json && res.json.status === 'success') {
        showToast(res.json.message || 'Category deleted', 'success');
        await refreshCategoryList();
        return true;
      } else {
        showToast((res.json && res.json.message) || 'Failed to delete category', 'error');
        return false;
      }
    } catch (e) {
      showToast('Network error: ' + e.message, 'error');
      return false;
    }
  }

  // ------------------ Convenience bindings ------------------
  function wireAddForm(selector) {
    const form = document.querySelector(selector);
    if (!form) return;
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const input = form.querySelector('input[name="category_name"]');
      if (!input) return showToast('Category input not found', 'error');
      await addCategory({ name: input.value, formEl: form });
    });
  }

  function wireEditForm(selector) {
    document.addEventListener('submit', async (ev) => {
      const form = ev.target.closest(selector);
      if (!form) return;
      ev.preventDefault();
      const idEl = form.querySelector('input[name="category_id"]');
      const nameEl = form.querySelector('input[name="category_name"]');
      if (!idEl || !nameEl) return showToast('Required fields missing', 'error');
      await updateCategory({ id: idEl.value, name: nameEl.value });
    });
  }

  function wireDeleteButtons(selector) {
    // Modal elements (assumes modal HTML exists on page)
    const overlay = document.getElementById('confirmOverlay');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const confirmItem = document.getElementById('confirmItem');

    let pendingId = null; // id to delete

    // function to open modal and set text
    function openModal(id, name) {
      pendingId = id;
      confirmItem.textContent = `Category: ${name} (ID: ${id})`;
      // ensure overlay visible
      overlay.style.display = 'flex';
      cancelBtn.focus();
      document.body.style.overflow = 'hidden';
    }
    function closeModal() {
      overlay.style.display = 'none';
      pendingId = null;
      document.body.style.overflow = '';
    }

    // listen for clicks on delete buttons
    document.addEventListener('click', (ev) => {
      const btn = ev.target.closest(selector);
      if (!btn) return;
      ev.preventDefault();
      const id = btn.dataset.id;
      if (!id) { showToast('Missing category id', 'error'); return; }

      // try to get the category name from the row (optional)
      let name = '—';
      const row = btn.closest('.user-item');
      if (row) {
        const nameEl = row.querySelector('.user-details .small') || row.querySelector('.user-details p');
        if (nameEl) name = nameEl.textContent.replace(/^Name:?\s*/i,'').trim();
      }

      // open custom modal instead of native confirm
      openModal(id, name);
    });

    // Confirm button in modal calls the deleteCategory function
    if (confirmBtn) {
      confirmBtn.addEventListener('click', async () => {
        if (!pendingId) { closeModal(); return; }
        await deleteCategory({ id: pendingId });
        closeModal();
      });
    }

    // Cancel behavior
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Close on overlay click or ESC
    if (overlay) {
      overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && overlay.style.display === 'flex') closeModal(); });
    }
  }

  // Expose public API
  window.CategoryAPI = {
    addCategory,
    updateCategory,
    deleteCategory,
    refreshCategoryList,
    wireAddForm,
    wireEditForm,
    wireDeleteButtons,
    showToast,
  };

  // Auto-wire common selectors if present on page
  document.addEventListener('DOMContentLoaded', () => {
    CategoryAPI.wireAddForm('form.collection-form');
    CategoryAPI.wireEditForm('form.edit-category-form');
    CategoryAPI.wireDeleteButtons('.js-delete');
    CategoryAPI.refreshCategoryList();
  });
})();
