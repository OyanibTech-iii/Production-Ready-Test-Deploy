// Product modal logic
(function() {
    const modal = document.getElementById('product-view-modal');
    const closeButtons = [
        document.getElementById('product-modal-close'),
        document.getElementById('product-modal-close-2')
    ];

    function openModal(row) {
        const name = row.getAttribute('data-product-name');
        const description = row.getAttribute('data-product-description');
        const price = row.getAttribute('data-product-price');
        const status = row.getAttribute('data-product-status');
        const image = row.getAttribute('data-product-image');

        document.getElementById('pm-name').textContent = name || '';
        document.getElementById('pm-description').textContent = description || '';
        document.getElementById('pm-price').textContent = `₱${price || '0.00'}`;
        
        const statusEl = document.getElementById('pm-status');
        statusEl.textContent = status || '';
        statusEl.className = 'text-sm font-medium ' + ((status || '').toLowerCase().includes('available') ? 'text-green-700' : 'text-red-700');

        const imgEl = document.getElementById('pm-image');
        const fallbackEl = document.getElementById('pm-fallback');
        if (image) {
            imgEl.src = image;
            imgEl.classList.remove('hidden');
            fallbackEl.classList.add('hidden');
        } else {
            imgEl.classList.add('hidden');
            fallbackEl.classList.remove('hidden');
        }

        modal.classList.remove('hidden');
        document.documentElement.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.documentElement.style.overflow = '';
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.open-product-modal');
        if (btn) {
            const row = btn.closest('.product-row');
            if (row) openModal(row);
        }
    });

    closeButtons.forEach(btn => btn && btn.addEventListener('click', closeModal));
})();

// Product delete functionality (FIXED)
(function() {
    let productCsrfToken = null;
    let productDeleteUrl = null; // Store the URL here
    
    const deleteModal = document.getElementById('delete-product-modal');
    const deleteCancelBtn = document.getElementById('delete-cancel-btn');
    const deleteConfirmBtn = document.getElementById('delete-confirm-btn');

    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-product-btn');
        if (!deleteBtn) return;
        
        // Read data from the button clicked 
        productCsrfToken = deleteBtn.getAttribute('data-csrf-token');
        productDeleteUrl = deleteBtn.getAttribute('data-delete-url');
        
        if (deleteModal) deleteModal.style.display = 'flex';
    });

    if (deleteCancelBtn) {
        deleteCancelBtn.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
    }

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function() {
            if (productDeleteUrl) {
                const form = document.createElement('form');
                form.method = 'POST';
                // Use the pre-generated URL from the data attribute 
                form.action = productDeleteUrl; 
                
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = productCsrfToken;
                
                form.appendChild(tokenInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) deleteModal.style.display = 'none';
        });
    }
})();