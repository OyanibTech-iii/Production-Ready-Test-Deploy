
document.addEventListener('DOMContentLoaded', () => {
    const headerSearch = document.getElementById('admin-header-search');
    if (!headerSearch) return;

    const pageSearchIds = ['user-search', 'product-search', 'stock-search'];

    function findPageSearchInput() {
        return pageSearchIds
            .map(id => document.getElementById(id))
            .find(el => el instanceof HTMLInputElement);
    }

    function syncToPageSearch(value) {
        const pageInput = findPageSearchInput();
        if (!pageInput) return;

        // Keep the page search input in sync so existing AdminSearch listeners work
        pageInput.value = value;

        // Trigger input event so existing search logic runs
        const event = new Event('input', { bubbles: true, cancelable: true });
        pageInput.dispatchEvent(event);
    }

    headerSearch.addEventListener('input', (e) => {
        syncToPageSearch(e.target.value);
    });

    headerSearch.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            syncToPageSearch(headerSearch.value);
        }
    });
});
