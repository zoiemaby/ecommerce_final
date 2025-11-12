/**
 * cart.js
 * Handles all UI interactions for shopping cart
 * Features:
 * - Add/Remove/Update/Empty cart items
 * - Async communication with action scripts
 * - Dynamic cart updates without page refresh
 * - User feedback via SweetAlert2
 * - Cart badge updates
 * - Loading states
 */

const CartApp = (function() {
    'use strict';

    // Configuration
    const CONFIG = {
        addToCartUrl: '../actions/add_to_cart_action.php',
        removeFromCartUrl: '../actions/remove_from_cart_action.php',
        updateQuantityUrl: '../actions/update_quantity_action.php',
        emptyCartUrl: '../actions/empty_cart_action.php',
        currency: 'GHS',
        maxQuantity: 100,
        minQuantity: 0
    };

    // State management
    let cartState = {
        items: [],
        count: 0,
        summary: {
            total_items: 0,
            total_quantity: 0,
            total_price: 0.00
        },
        isLoading: false
    };

    /**
     * Initialize cart functionality
     */
    function init() {
        bindEvents();
        updateCartBadge();
        console.log('Cart.js initialized');
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Add to cart buttons (on product pages)
        $(document).on('click', '.add-to-cart-btn', handleAddToCart);

        // Remove from cart buttons
        $(document).on('click', '.remove-from-cart-btn', handleRemoveFromCart);

        // Update quantity buttons
        $(document).on('click', '.quantity-increase', handleQuantityIncrease);
        $(document).on('click', '.quantity-decrease', handleQuantityDecrease);

        // Quantity input change
        $(document).on('change', '.quantity-input', handleQuantityInputChange);

        // Empty cart button
        $(document).on('click', '.empty-cart-btn', handleEmptyCart);

        // Proceed to checkout button
        $(document).on('click', '.proceed-to-checkout-btn', handleProceedToCheckout);
    }

    /**
     * Add item to cart
     */
    async function handleAddToCart(e) {
        e.preventDefault();

        const btn = $(this);
        const productId = btn.data('product-id');
        const quantity = parseInt(btn.data('quantity') || 1);

        if (!productId || productId <= 0) {
            showError('Invalid product');
            return;
        }

        if (quantity <= 0 || quantity > CONFIG.maxQuantity) {
            showError(`Quantity must be between 1 and ${CONFIG.maxQuantity}`);
            return;
        }

        // Show loading state
        setButtonLoading(btn, true);

        try {
            const response = await fetch(CONFIG.addToCartUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${productId}&quantity=${quantity}`
            });

            const result = await response.json();

            if (result.success) {
                // Update cart state
                cartState.count = result.data.cart_count || 0;
                cartState.summary = result.data.cart_summary || cartState.summary;

                // Update UI
                updateCartBadge(cartState.count);

                // Show success message
                const wasExisting = result.data.was_existing;
                const productTitle = result.data.product_title || 'Product';
                const totalQty = result.data.quantity_added || quantity;

                const message = wasExisting 
                    ? `${productTitle} quantity updated to ${totalQty}`
                    : `${productTitle} added to cart`;

                showSuccess(message);

                // Trigger custom event for other components
                $(document).trigger('cart:updated', [result.data]);

            } else {
                showError(result.message || 'Failed to add item to cart');
            }

        } catch (error) {
            console.error('Add to cart error:', error);
            showError('Network error. Please try again.');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    /**
     * Remove item from cart
     */
    async function handleRemoveFromCart(e) {
        e.preventDefault();

        const btn = $(this);
        const productId = btn.data('product-id');
        const productTitle = btn.data('product-title') || 'this item';

        if (!productId || productId <= 0) {
            showError('Invalid product');
            return;
        }

        // Confirm removal
        const confirm = await Swal.fire({
            title: 'Remove Item?',
            text: `Remove ${productTitle} from your cart?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
        });

        if (!confirm.isConfirmed) return;

        // Show loading state
        setButtonLoading(btn, true);

        try {
            const response = await fetch(CONFIG.removeFromCartUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${productId}`
            });

            const result = await response.json();

            if (result.success) {
                // Update cart state
                cartState.count = result.data.cart_count || 0;
                cartState.summary = result.data.cart_summary || cartState.summary;

                // Update UI
                updateCartBadge(cartState.count);

                // Remove item row from DOM
                const cartRow = btn.closest('.cart-item-row, tr');
                cartRow.fadeOut(300, function() {
                    $(this).remove();
                    updateCartSummaryDisplay();
                    
                    // Show empty cart message if needed
                    if (result.data.cart_is_empty) {
                        showEmptyCartMessage();
                    }
                });

                // Show success message
                showSuccess(result.message || 'Item removed from cart');

                // Trigger custom event
                $(document).trigger('cart:item-removed', [result.data]);

            } else {
                showError(result.message || 'Failed to remove item');
            }

        } catch (error) {
            console.error('Remove from cart error:', error);
            showError('Network error. Please try again.');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    /**
     * Increase quantity
     */
    function handleQuantityIncrease(e) {
        e.preventDefault();

        const btn = $(this);
        const input = btn.siblings('.quantity-input');
        const currentQty = parseInt(input.val()) || 1;
        const newQty = Math.min(currentQty + 1, CONFIG.maxQuantity);

        if (newQty !== currentQty) {
            input.val(newQty);
            updateQuantity(input);
        }
    }

    /**
     * Decrease quantity
     */
    function handleQuantityDecrease(e) {
        e.preventDefault();

        const btn = $(this);
        const input = btn.siblings('.quantity-input');
        const currentQty = parseInt(input.val()) || 1;
        const newQty = Math.max(currentQty - 1, 1); // Minimum 1, use remove button for 0

        if (newQty !== currentQty) {
            input.val(newQty);
            updateQuantity(input);
        }
    }

    /**
     * Handle quantity input change
     */
    function handleQuantityInputChange(e) {
        const input = $(this);
        updateQuantity(input);
    }

    /**
     * Update quantity via API
     */
    async function updateQuantity(input) {
        const productId = input.data('product-id');
        let newQty = parseInt(input.val()) || 1;

        // Validate quantity
        if (newQty < 1) {
            newQty = 1;
            input.val(newQty);
        } else if (newQty > CONFIG.maxQuantity) {
            newQty = CONFIG.maxQuantity;
            input.val(newQty);
        }

        if (!productId || productId <= 0) {
            showError('Invalid product');
            return;
        }

        // Show loading state
        const row = input.closest('.cart-item-row, tr');
        setRowLoading(row, true);

        try {
            const response = await fetch(CONFIG.updateQuantityUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${productId}&quantity=${newQty}`
            });

            const result = await response.json();

            if (result.success) {
                // Update cart state
                cartState.count = result.data.cart_count || 0;
                cartState.summary = result.data.cart_summary || cartState.summary;

                // Update UI
                updateCartBadge(cartState.count);

                // If item was removed (qty set to 0)
                if (result.data.new_quantity === 0 || result.data.action === 'removed') {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        updateCartSummaryDisplay();
                        
                        if (result.data.cart_is_empty) {
                            showEmptyCartMessage();
                        }
                    });
                    showSuccess('Item removed from cart');
                } else {
                    // Update item subtotal
                    updateItemSubtotal(row, result.data.updated_item);
                    updateCartSummaryDisplay();
                    
                    // Show subtle feedback
                    showToast(result.message, 'success');
                }

                // Trigger custom event
                $(document).trigger('cart:quantity-updated', [result.data]);

            } else {
                showError(result.message || 'Failed to update quantity');
                // Revert input to old value if available
                if (result.data && result.data.old_quantity) {
                    input.val(result.data.old_quantity);
                }
            }

        } catch (error) {
            console.error('Update quantity error:', error);
            showError('Network error. Please try again.');
        } finally {
            setRowLoading(row, false);
        }
    }

    /**
     * Empty entire cart
     */
    async function handleEmptyCart(e) {
        e.preventDefault();

        // Confirm action
        const confirm = await Swal.fire({
            title: 'Clear Cart?',
            text: 'This will remove all items from your cart!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, clear it!',
            cancelButtonText: 'Cancel'
        });

        if (!confirm.isConfirmed) return;

        // Show loading overlay
        showLoadingOverlay('Clearing cart...');

        try {
            const response = await fetch(CONFIG.emptyCartUrl, {
                method: 'POST'
            });

            const result = await response.json();

            if (result.success) {
                // Update cart state
                cartState.count = 0;
                cartState.summary = {
                    total_items: 0,
                    total_quantity: 0,
                    total_price: 0.00
                };

                // Update UI
                updateCartBadge(0);
                showEmptyCartMessage();

                // Show success message
                await Swal.fire({
                    icon: 'success',
                    title: 'Cart Cleared!',
                    text: result.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Trigger custom event
                $(document).trigger('cart:emptied', [result.data]);

            } else {
                showError(result.message || 'Failed to clear cart');
            }

        } catch (error) {
            console.error('Empty cart error:', error);
            showError('Network error. Please try again.');
        } finally {
            hideLoadingOverlay();
        }
    }

    /**
     * Handle proceed to checkout
     */
    function handleProceedToCheckout(e) {
        e.preventDefault();

        // Check if cart is empty
        if (cartState.count === 0) {
            showError('Your cart is empty. Please add items before checkout.');
            return;
        }

        // Redirect to checkout page
        window.location.href = 'checkout.php';
    }

    /**
     * Update cart badge
     */
    function updateCartBadge(count) {
        if (count === undefined) {
            count = cartState.count;
        }

        const badge = $('.cart-badge, .cart-count');
        
        if (count > 0) {
            badge.text(count).show();
            badge.addClass('animate__animated animate__pulse');
            setTimeout(() => badge.removeClass('animate__animated animate__pulse'), 1000);
        } else {
            badge.text('0').hide();
        }
    }

    /**
     * Update item subtotal in row
     */
    function updateItemSubtotal(row, itemData) {
        if (!itemData) return;

        const quantity = itemData.qty || 1;
        const price = parseFloat(itemData.product_price) || 0;
        const subtotal = quantity * price;

        // Update quantity display
        row.find('.quantity-input').val(quantity);
        row.find('.item-quantity-display').text(quantity);

        // Update subtotal display
        row.find('.item-subtotal').text(formatCurrency(subtotal));
    }

    /**
     * Update cart summary display (totals)
     */
    function updateCartSummaryDisplay() {
        const summary = cartState.summary;

        $('.cart-total-items').text(summary.total_items || 0);
        $('.cart-total-quantity').text(summary.total_quantity || 0);
        $('.cart-total-price').text(formatCurrency(summary.total_price || 0));
        $('.cart-subtotal').text(formatCurrency(summary.total_price || 0));
        $('.cart-grand-total').text(formatCurrency(summary.total_price || 0));
    }

    /**
     * Show empty cart message
     */
    function showEmptyCartMessage() {
        const emptyMessage = `
            <div class="empty-cart-message text-center py-5">
                <i class="bi bi-cart-x" style="font-size: 4rem; color: #ccc;"></i>
                <h3 class="mt-3">Your cart is empty</h3>
                <p class="text-muted">Add some products to get started!</p>
                <a href="all_product.php" class="btn btn-primary mt-3">
                    <i class="bi bi-shop"></i> Continue Shopping
                </a>
            </div>
        `;

        $('.cart-items-container').html(emptyMessage);
        $('.cart-summary-container').hide();
        $('.cart-actions-container').hide();
    }

    /**
     * Set button loading state
     */
    function setButtonLoading(btn, isLoading) {
        if (isLoading) {
            btn.prop('disabled', true);
            btn.data('original-html', btn.html());
            btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
        } else {
            btn.prop('disabled', false);
            const originalHtml = btn.data('original-html');
            if (originalHtml) {
                btn.html(originalHtml);
            }
        }
    }

    /**
     * Set row loading state
     */
    function setRowLoading(row, isLoading) {
        if (isLoading) {
            row.css('opacity', '0.6');
            row.find('button, input').prop('disabled', true);
        } else {
            row.css('opacity', '1');
            row.find('button, input').prop('disabled', false);
        }
    }

    /**
     * Show loading overlay
     */
    function showLoadingOverlay(message = 'Loading...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    /**
     * Hide loading overlay
     */
    function hideLoadingOverlay() {
        Swal.close();
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        const num = parseFloat(amount) || 0;
        return `${CONFIG.currency} ${num.toFixed(2)}`;
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }

    /**
     * Show error message
     */
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonText: 'OK'
        });
    }

    /**
     * Show toast notification
     */
    function showToast(message, icon = 'info') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });

        Toast.fire({
            icon: icon,
            title: message
        });
    }

    /**
     * Public API
     */
    return {
        init: init,
        addToCart: handleAddToCart,
        updateBadge: updateCartBadge,
        getState: () => cartState,
        setState: (newState) => {
            cartState = { ...cartState, ...newState };
        }
    };

})();

// Initialize on document ready
$(document).ready(function() {
    CartApp.init();
});
