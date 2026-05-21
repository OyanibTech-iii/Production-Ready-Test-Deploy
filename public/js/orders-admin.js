document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.orders-tab');
    const contents = document.querySelectorAll('.orders-tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = 'tab-' + this.getAttribute('data-tab');
            tabs.forEach(t => {
                t.classList.remove('text-bright-green', 'border-bright-green', 'bg-primary-50');
                t.classList.add('text-light-gray');
            });
            contents.forEach(c => c.classList.add('hidden'));
            this.classList.remove('text-light-gray');
            this.classList.add('text-bright-green', 'border-bright-green', 'bg-primary-50');
            const target = document.getElementById(targetId);
            if (target) target.classList.remove('hidden');
        });
    });

    // Add Order modal
    const addOrderBtn = document.getElementById('add-order-btn');
    const addOrderModal = document.getElementById('add-order-modal');
    const addOrderModalClose = document.getElementById('add-order-modal-close');
    const addOrderCancel = document.getElementById('add-order-cancel');
    const addOrderTotalEl = document.getElementById('add-order-total');
    const addOrderSubmit = document.getElementById('add-order-submit');
    const statusModal = document.getElementById('status-modal');
    const statusIconContainer = document.getElementById('status-icon-container');
    const statusIcon = document.getElementById('status-icon');
    const statusTitle = document.getElementById('status-title');
    const statusMessage = document.getElementById('status-message');
    const statusCloseBtn = document.getElementById('status-modal-close');

    function showStatus(isSuccess, message) {
        // Reset classes
        statusIconContainer.className = 'w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center ';
        statusCloseBtn.className = 'w-full px-4 py-2.5 rounded-lg font-medium transition-colors duration-200 ';

        if (isSuccess) {
            statusTitle.textContent = 'Success!';
            statusMessage.textContent = message || 'Order has been created successfully.';
            statusIcon.name = 'checkmark-circle-outline';
            statusIconContainer.classList.add('bg-green-100', 'text-green-600');
            statusCloseBtn.classList.add('bg-bright-green', 'hover:bg-green-600', 'text-white');
        } else {
            statusTitle.textContent = 'Order Failed';
            statusMessage.textContent = message || 'There was an error processing your order.';
            statusIcon.name = 'alert-circle-outline';
            statusIconContainer.classList.add('bg-red-100', 'text-red-600');
            statusCloseBtn.classList.add('bg-gray-100', 'hover:bg-gray-200', 'text-dark-forest-green','dark:bg-gray-700', 'dark:hover:bg-gray-600', 'dark:text-white');
        }

        statusModal.classList.remove('hidden');
        statusModal.setAttribute('aria-hidden', 'false');
    }

    // Close status modal logic
    if (statusCloseBtn) {
        statusCloseBtn.addEventListener('click', () => {
            statusModal.classList.add('hidden');
            statusModal.setAttribute('aria-hidden', 'true');
            // If it was a success, reload to show new data in the audit table
            if (statusTitle.textContent === 'Success!') {
                window.location.reload();
            }
        });
    }

    // Integrated Submit Logic
    let isSubmitting = false;
    if (addOrderSubmit) {
        addOrderSubmit.addEventListener('click', async function() {
            if (isSubmitting) return; // Prevent double submission
            
            const items = [];
            document.querySelectorAll('.order-qty').forEach(input => {
                const qty = parseInt(input.value, 10) || 0;
                if (qty > 0) {
                    items.push({
                        productId: parseInt(input.dataset.productId, 10),
                        quantity: qty
                    });
                }
            });

            if (items.length === 0) {
                showStatus(false, 'Please add at least one product with quantity.');
                return;
            }

            isSubmitting = true;
            this.disabled = true;
            this.textContent = 'Creating Order...';
            
            try {
                const res = await fetch(window.OrderConfig.createUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.OrderConfig.csrfToken
                    },
                    body: JSON.stringify({ items })
                });

                const data = await res.json();
                
                if (data.success) {
                    closeAddOrderModal();
                    showStatus(true, data.message); // Show success
                } else {
                    showStatus(false, data.message); // Show error modal
                }
            } catch (err) {
                showStatus(false, 'Network error: ' + err.message);
            } finally {
                isSubmitting = false;
                this.disabled = false;
                this.textContent = 'Create Order';
            }
        });
    }

    function updateAddOrderTotal() {
        if (!addOrderTotalEl) return;
        let total = 0;
        document.querySelectorAll('.order-qty').forEach(input => {
            const qty = parseInt(input.value, 10) || 0;
            const row = input.closest('.order-product-row');
            const price = parseFloat(row?.dataset.price || 0);
            total += qty * price;
        });
        addOrderTotalEl.textContent = '₱' + total.toFixed(2);
    }

    function resetAddOrderForm() {
        document.querySelectorAll('.order-qty').forEach(input => {
            input.value = '0';
        });
        updateAddOrderTotal();
    }

    function closeAddOrderModal() {
        if (addOrderModal) {
            addOrderModal.classList.add('hidden');
            addOrderModal.setAttribute('aria-hidden', 'true');
            resetAddOrderForm();
            // Move focus back to the trigger button
            if (addOrderBtn) addOrderBtn.focus();
        }
    }

    if (addOrderBtn) {
        addOrderBtn.addEventListener('click', function() {
            if (addOrderModal) {
                addOrderModal.classList.remove('hidden');
                addOrderModal.setAttribute('aria-hidden', 'false');
                updateAddOrderTotal();
            }
        });
    }
    [addOrderModalClose, addOrderCancel].forEach(btn => {
        if (btn) btn.addEventListener('click', closeAddOrderModal);
    });
    if (addOrderModal) {
        addOrderModal.addEventListener('click', function(e) {
            if (!e.target.closest('.bg-white.rounded-2xl')) {
                closeAddOrderModal();
            }
        });
    }

    document.querySelectorAll('.order-qty').forEach(input => {
        input.addEventListener('input', function() {
            const max = parseInt(this.max, 10);
            let val = parseInt(this.value, 10) || 0;
            if (val > max) this.value = max;
            if (val < 0) this.value = 0;
            updateAddOrderTotal();
        });
    });

    // Payment modal (reserved for future)
    const paymentModal = document.getElementById('payment-modal');
    const paymentModalClose = document.getElementById('payment-modal-close');
    if (paymentModalClose) paymentModalClose.addEventListener('click', () => paymentModal?.classList.add('hidden'));
    if (paymentModal) {
        paymentModal.addEventListener('click', function(e) {
            if (!e.target.closest('.bg-white.rounded-2xl')) paymentModal.classList.add('hidden');
        });
    }
});