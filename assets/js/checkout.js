/**
 * checkout.js
 * Handles checkout flow and simulated payment modal
 * Features:
 * - Manage simulated payment modal
 * - Send async request to process_checkout_action.php
 * - Handle JSON responses
 * - Update UI with order confirmation
 * - Handle transitions: cart → checkout → confirmation
 * - Error handling and user feedback
 */

const CheckoutApp = (function() {
    'use strict';

    // Configuration
    const CONFIG = {
        processCheckoutUrl: '../actions/process_checkout_action.php',
        orderConfirmationUrl: 'order_confirmation.php',
        orderHistoryUrl: 'order_history.php',
        cartUrl: 'cart.php',
        currency: 'GHS',
        paymentMethods: ['Mobile Money', 'Cash on Delivery', 'Simulated Payment']
    };

    // Checkout state
    let checkoutState = {
        cartItems: [],
        orderTotal: 0,
        currency: 'GHS',
        invoiceNo: null,
        isProcessing: false
    };

    /**
     * Initialize checkout functionality
     */
    function init() {
        bindEvents();
        generateInvoiceNumber();
        loadCheckoutSummary();
        console.log('Checkout.js initialized');
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Proceed to payment button
        $(document).on('click', '.proceed-to-payment-btn', handleProceedToPayment);

        // Confirm payment button (in modal)
        $(document).on('click', '.confirm-payment-btn', handleConfirmPayment);

        // Cancel payment button (in modal)
        $(document).on('click', '.cancel-payment-btn', handleCancelPayment);

        // Payment method selection
        $(document).on('change', 'input[name="payment_method"]', handlePaymentMethodChange);

        // Back to cart button
        $(document).on('click', '.back-to-cart-btn', handleBackToCart);

        // View order button (after confirmation)
        $(document).on('click', '.view-order-btn', handleViewOrder);

        // Continue shopping button
        $(document).on('click', '.continue-shopping-btn', handleContinueShopping);
    }

    /**
     * Generate unique invoice number
     */
    function generateInvoiceNumber() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        checkoutState.invoiceNo = `INV-${timestamp}-${random}`;
        
        // Update invoice display if element exists
        $('.invoice-number').text(checkoutState.invoiceNo);
        
        return checkoutState.invoiceNo;
    }

    /**
     * Load checkout summary (cart items and totals)
     */
    function loadCheckoutSummary() {
        // In a real app, this would fetch from server
        // For now, read from DOM or localStorage
        
        const totalElement = $('.checkout-total');
        if (totalElement.length) {
            const totalText = totalElement.text().replace(/[^0-9.]/g, '');
            checkoutState.orderTotal = parseFloat(totalText) || 0;
        }

        // Update state
        updateCheckoutDisplay();
    }

    /**
     * Handle proceed to payment
     */
    async function handleProceedToPayment(e) {
        e.preventDefault();

        // Validate order total
        if (checkoutState.orderTotal <= 0) {
            showError('Invalid order total. Please refresh and try again.');
            return;
        }

        // Get selected payment method
        const paymentMethod = $('input[name="payment_method"]:checked').val() || 'Simulated Payment';

        // Show simulated payment modal
        showPaymentModal(paymentMethod);
    }

    /**
     * Show simulated payment modal
     */
    function showPaymentModal(paymentMethod) {
        const modalHtml = `
            <div class="payment-modal-content">
                <div class="text-center mb-4">
                    <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Simulated Payment</h4>
                </div>
                
                <div class="payment-details">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Invoice Number:</strong></td>
                            <td class="text-end">${checkoutState.invoiceNo}</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Method:</strong></td>
                            <td class="text-end">${paymentMethod}</td>
                        </tr>
                        <tr>
                            <td><strong>Amount to Pay:</strong></td>
                            <td class="text-end">
                                <span class="text-primary fw-bold fs-5">
                                    ${formatCurrency(checkoutState.orderTotal)}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    This is a simulated payment. No actual transaction will occur.
                </div>

                <div class="payment-instructions mt-3">
                    <p class="text-muted small">
                        In a real payment flow, you would be redirected to a payment gateway.
                        For this demo, click "Confirm Payment" to simulate a successful payment.
                    </p>
                </div>
            </div>
        `;

        Swal.fire({
            title: 'Complete Payment',
            html: modalHtml,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-check-circle me-2"></i> Yes, I\'ve Paid',
            cancelButtonText: '<i class="bi bi-x-circle me-2"></i> Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            width: '600px',
            allowOutsideClick: false,
            allowEscapeKey: true,
            customClass: {
                confirmButton: 'btn btn-success btn-lg confirm-payment-btn',
                cancelButton: 'btn btn-secondary btn-lg cancel-payment-btn'
            },
            didOpen: () => {
                // Store payment method in modal
                $('.confirm-payment-btn').data('payment-method', paymentMethod);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // User clicked "Yes, I've Paid"
                processCheckout(paymentMethod);
            } else if (result.isDismissed) {
                // User cancelled
                handlePaymentCancelled();
            }
        });
    }

    /**
     * Handle confirm payment (from modal)
     */
    function handleConfirmPayment(e) {
        e.preventDefault();
        
        const btn = $(this);
        const paymentMethod = btn.data('payment-method') || 'Simulated Payment';
        
        // Close modal and process checkout
        Swal.close();
        processCheckout(paymentMethod);
    }

    /**
     * Handle cancel payment (from modal)
     */
    function handleCancelPayment(e) {
        e.preventDefault();
        Swal.close();
        handlePaymentCancelled();
    }

    /**
     * Handle payment cancelled
     */
    function handlePaymentCancelled() {
        Swal.fire({
            icon: 'info',
            title: 'Payment Cancelled',
            text: 'You can proceed when ready.',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }

    /**
     * Process checkout (send request to backend)
     */
    async function processCheckout(paymentMethod) {
        // Prevent double submission
        if (checkoutState.isProcessing) {
            return;
        }

        checkoutState.isProcessing = true;

        // Show processing overlay
        Swal.fire({
            title: 'Processing Order...',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Please wait while we process your order...</p>
                    <p class="text-muted small">Do not close or refresh this page.</p>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });

        try {
            const response = await fetch(CONFIG.processCheckoutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    invoice_no: checkoutState.invoiceNo,
                    currency: checkoutState.currency,
                    payment_method: paymentMethod
                })
            });

            const result = await response.json();

            if (result.success) {
                // Order processed successfully
                await handleCheckoutSuccess(result.data);
            } else {
                // Order processing failed
                await handleCheckoutFailure(result);
            }

        } catch (error) {
            console.error('Checkout error:', error);
            await handleCheckoutError(error);
        } finally {
            checkoutState.isProcessing = false;
        }
    }

    /**
     * Handle successful checkout
     */
    async function handleCheckoutSuccess(orderData) {
        // Close processing overlay
        Swal.close();

        // Show success confirmation with order details
        const confirmationHtml = `
            <div class="order-confirmation-content">
                <div class="text-center mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 text-success">Order Placed Successfully!</h3>
                </div>

                <div class="order-details bg-light p-4 rounded">
                    <h5 class="mb-3">Order Details</h5>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td><strong>Order Reference:</strong></td>
                            <td class="text-end">
                                <code class="fs-6">${orderData.order_reference || orderData.invoice_no}</code>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Order ID:</strong></td>
                            <td class="text-end">#${orderData.order_id}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-end">
                                <span class="text-success fw-bold">
                                    ${orderData.currency} ${orderData.order_amount}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Items:</strong></td>
                            <td class="text-end">${orderData.item_count}</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Method:</strong></td>
                            <td class="text-end">${orderData.payment_method || 'Simulated'}</td>
                        </tr>
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td class="text-end">${formatDate(orderData.order_date)}</td>
                        </tr>
                    </table>
                </div>

                <div class="alert alert-success mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    A confirmation email has been sent to your registered email address.
                </div>

                <p class="text-muted mt-3 small text-center">
                    Thank you for your order! You can track your order status in your order history.
                </p>
            </div>
        `;

        await Swal.fire({
            html: confirmationHtml,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-eye me-2"></i> View Order',
            cancelButtonText: '<i class="bi bi-shop me-2"></i> Continue Shopping',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#007bff',
            width: '700px',
            allowOutsideClick: false,
            customClass: {
                confirmButton: 'btn btn-success btn-lg me-2 view-order-btn',
                cancelButton: 'btn btn-primary btn-lg continue-shopping-btn'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to order confirmation page
                window.location.href = `${CONFIG.orderConfirmationUrl}?order_id=${orderData.order_id}`;
            } else {
                // Continue shopping
                window.location.href = 'all_product.php';
            }
        });

        // Update cart badge to 0
        if (typeof CartApp !== 'undefined') {
            CartApp.updateBadge(0);
        }

        // Trigger custom event
        $(document).trigger('checkout:success', [orderData]);
    }

    /**
     * Handle checkout failure
     */
    async function handleCheckoutFailure(result) {
        Swal.close();

        let errorMessage = result.message || 'Unable to process your order. Please try again.';
        let errorDetails = '';

        // Handle specific error cases
        if (result.data && result.data.price_errors) {
            // Prices changed during checkout
            errorDetails = `
                <div class="alert alert-warning mt-3">
                    <h6>Price Changes Detected:</h6>
                    <ul class="mb-0 text-start">
                        ${result.data.price_errors.map(err => `<li>${err}</li>`).join('')}
                    </ul>
                </div>
                <p class="mt-2">Please review your cart and try again.</p>
            `;
        } else if (result.data && result.data.cart_is_empty) {
            errorDetails = '<p>Please add items to your cart before checking out.</p>';
        }

        await Swal.fire({
            icon: 'error',
            title: 'Checkout Failed',
            html: `
                <p>${errorMessage}</p>
                ${errorDetails}
            `,
            confirmButtonText: 'Back to Cart',
            confirmButtonColor: '#007bff'
        }).then(() => {
            // Redirect back to cart if price errors
            if (result.data && result.data.price_errors) {
                window.location.href = CONFIG.cartUrl;
            }
        });

        // Trigger custom event
        $(document).trigger('checkout:failed', [result]);
    }

    /**
     * Handle checkout error (network/exception)
     */
    async function handleCheckoutError(error) {
        Swal.close();

        await Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            html: `
                <p>Unable to process your order due to a network error.</p>
                <p class="text-muted small">Please check your internet connection and try again.</p>
            `,
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#007bff'
        });

        // Trigger custom event
        $(document).trigger('checkout:error', [error]);
    }

    /**
     * Handle payment method change
     */
    function handlePaymentMethodChange(e) {
        const selectedMethod = $(this).val();
        
        // Update UI based on selected method
        $('.payment-method-details').hide();
        $(`.payment-method-details[data-method="${selectedMethod}"]`).show();

        // Show info for different methods
        if (selectedMethod === 'Mobile Money') {
            showToast('Mobile Money selected. You will receive payment instructions.', 'info');
        }
    }

    /**
     * Handle back to cart
     */
    function handleBackToCart(e) {
        e.preventDefault();
        window.location.href = CONFIG.cartUrl;
    }

    /**
     * Handle view order
     */
    function handleViewOrder(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');
        window.location.href = `${CONFIG.orderConfirmationUrl}?order_id=${orderId}`;
    }

    /**
     * Handle continue shopping
     */
    function handleContinueShopping(e) {
        e.preventDefault();
        window.location.href = 'all_product.php';
    }

    /**
     * Update checkout display
     */
    function updateCheckoutDisplay() {
        $('.checkout-invoice-no').text(checkoutState.invoiceNo);
        $('.checkout-total').text(formatCurrency(checkoutState.orderTotal));
        $('.checkout-currency').text(checkoutState.currency);
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        const num = parseFloat(amount) || 0;
        return `${CONFIG.currency} ${num.toFixed(2)}`;
    }

    /**
     * Format date
     */
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        return date.toLocaleDateString('en-US', options);
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
            timer: 3000,
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
        processCheckout: processCheckout,
        getState: () => checkoutState,
        setState: (newState) => {
            checkoutState = { ...checkoutState, ...newState };
        }
    };

})();

// Initialize on document ready
$(document).ready(function() {
    CheckoutApp.init();
});
