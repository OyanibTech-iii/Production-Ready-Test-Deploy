if (typeof window.AdminSearch === 'undefined') {
    window.AdminSearch = class AdminSearch {
        constructor(options = {}) {
            this.searchInputId = options.searchInputId;
            this.tableSelector = options.tableSelector || 'table'; // Target the table itself
            this.tableRowClass = options.tableRowClass;
            this.searchDelay = options.searchDelay || 300;
            
            this.init();
        }
        
        init() {
            const searchInput = document.getElementById(this.searchInputId);
            if (!searchInput) return;

            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, this.searchDelay);
            });
            
            this.addClearButton(searchInput);
        }
        
        performSearch(searchTerm) {
            const tableElement = document.querySelector(this.tableSelector);
            
            // If it's a DataTable, use the API
            if ($.fn.DataTable.isDataTable(this.tableSelector)) {
                const table = $(this.tableSelector).DataTable();
                table.search(searchTerm).draw();
                return;
            }

            // FALLBACK: Manual row hiding (your original logic)
            const rows = document.querySelectorAll(`.${this.tableRowClass}`);
            const normalizedSearchTerm = searchTerm.toLowerCase().trim();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(normalizedSearchTerm) ? '' : 'none';
            });
        }

        addClearButton(searchInput) {
            if (searchInput.parentElement.querySelector('.admin-search-clear')) return;

            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.innerHTML = '<ion-icon name="close-circle-outline" class="w-4 h-4"></ion-icon>';
            clearButton.className = 'admin-search-clear absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 z-10';
            clearButton.style.display = 'none';
            
            searchInput.parentElement.style.position = 'relative';
            searchInput.parentElement.appendChild(clearButton);
            searchInput.style.paddingRight = '40px';
            
            searchInput.addEventListener('input', (e) => {
                clearButton.style.display = e.target.value ? 'block' : 'none';
            });
            
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                clearButton.style.display = 'none';
                this.performSearch('');
                searchInput.focus();
            });
        }
    };
}