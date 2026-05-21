(function () {
    const modal = document.getElementById('stock-view-modal');
    const closeBtns = [document.getElementById('stock-modal-close'), document.getElementById('stock-modal-close-2')];
    if (!modal) {
        return;
    }

    let carousel = { items: [], index: 0 };

    function openModal(row) {
        try {
            carousel.items = JSON.parse(row.getAttribute('data-stock-products') || '[]');
        } catch (e) {
            carousel.items = [];
        }
        carousel.index = 0;

        function renderSlide() {
            const current = carousel.items[carousel.index];
            const name = current?.name || row.getAttribute('data-stock-product-name') || '';
            const description = current?.description || row.getAttribute('data-stock-product-description') || '';
            const image = current?.image || row.getAttribute('data-stock-image') || '';

            document.getElementById('sv-name').textContent = name;
            document.getElementById('sv-description').textContent = description;

            const imgEl = document.getElementById('sv-image');
            const fallback = document.getElementById('sv-fallback');
            if (image) {
                imgEl.src = image;
                imgEl.classList.remove('hidden');
                fallback.classList.add('hidden');
            } else {
                imgEl.classList.add('hidden');
                fallback.classList.remove('hidden');
            }
            const indicator = document.getElementById('sv-indicator');
            if (carousel.items.length > 1) {
                indicator.textContent = (carousel.index + 1) + ' / ' + carousel.items.length;
                indicator.classList.remove('hidden');
            } else {
                indicator.classList.add('hidden');
            }
            document.getElementById('sv-prev').style.display = carousel.items.length > 1 ? '' : 'none';
            document.getElementById('sv-next').style.display = carousel.items.length > 1 ? '' : 'none';
        }

        document.getElementById('sv-prev').onclick = function () {
            if (!carousel.items.length) return;
            carousel.index = (carousel.index - 1 + carousel.items.length) % carousel.items.length;
            renderSlide();
        };
        document.getElementById('sv-next').onclick = function () {
            if (!carousel.items.length) return;
            carousel.index = (carousel.index + 1) % carousel.items.length;
            renderSlide();
        };

        renderSlide();
        document.getElementById('sv-type').textContent = row.getAttribute('data-stock-type') || '';
        document.getElementById('sv-locationRel').textContent = row.getAttribute('data-stock-locationRel') || '';
        document.getElementById('sv-quantity').textContent = row.getAttribute('data-stock-quantity') || '';
        document.getElementById('sv-minimum').textContent = row.getAttribute('data-stock-minimum') || '—';
        const status = row.getAttribute('data-stock-status') || '';
        const statusEl = document.getElementById('sv-status');
        statusEl.textContent = status;
        const lower = status.toLowerCase();
        statusEl.className = 'text-sm font-medium ' + (lower.includes('out') ? 'text-red-700' : (lower.includes('low') ? 'text-orange-700' : 'text-green-700'));
        document.getElementById('sv-updated').textContent = row.getAttribute('data-stock-updated') || '';

        modal.classList.remove('hidden');
        document.documentElement.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.documentElement.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.open-stock-modal');
        if (!btn) return;
        const row = btn.closest('.stock-row');
        if (row) openModal(row);
    });
    closeBtns.forEach(b => b && b.addEventListener('click', closeModal));
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
})();

let stockIdToDelete = null;
let stockCsrfToken = null;
const deleteStockModal = document.getElementById('delete-stock-modal');
const deleteStockCancelBtn = document.getElementById('delete-stock-cancel-btn');
const deleteStockConfirmBtn = document.getElementById('delete-stock-confirm-btn');

if (deleteStockModal && deleteStockCancelBtn && deleteStockConfirmBtn) {
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.delete-stock-btn');
        if (!deleteBtn) return;

        stockIdToDelete = deleteBtn.getAttribute('data-stock-id');
        stockCsrfToken = deleteBtn.getAttribute('data-csrf-token');
        deleteStockModal.style.display = 'flex';
        deleteStockModal.classList.remove('hidden');
    });

    deleteStockCancelBtn.addEventListener('click', function () {
        deleteStockModal.style.display = 'none';
        deleteStockModal.classList.add('hidden');
        stockIdToDelete = null;
    });

    deleteStockConfirmBtn.addEventListener('click', function () {
        if (!stockIdToDelete) return;

        const baseUrl = deleteStockModal.getAttribute('data-base-delete-url') || '';
        if (!baseUrl) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = baseUrl.replace('/0', '/' + stockIdToDelete);

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = stockCsrfToken || '';

        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    });

    deleteStockModal.addEventListener('click', function (e) {
        if (e.target === deleteStockModal) {
            deleteStockModal.style.display = 'none';
            deleteStockModal.classList.add('hidden');
            stockIdToDelete = null;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && deleteStockModal.style.display !== 'none') {
            deleteStockModal.style.display = 'none';
            deleteStockModal.classList.add('hidden');
            stockIdToDelete = null;
        }
    });
}
