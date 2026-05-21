  document.addEventListener('DOMContentLoaded', function () {
        const select = document.querySelector('select.products-select');
        if (!select) return;

        const wrapper = select.closest('.products-dropdown-wrapper');
        if (!wrapper) return;

        const display = document.createElement('div');
        display.className = 'products-dropdown-display w-full form-input pl-10 flex items-center cursor-pointer transition-all duration-200 relative ';
        display.setAttribute('tabindex', '0');
        display.setAttribute('role', 'combobox');
        display.setAttribute('aria-expanded', 'false');
        display.setAttribute('aria-haspopup', 'listbox');

        // Icon on the left (matching other form inputs)
        const icon = document.createElement('div');
        icon.className = 'absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none';
        icon.innerHTML = '<ion-icon name="pricetags-outline" class="text-gray-400"></ion-icon>';
        display.appendChild(icon);

        // Content span keeps text separate from caret SVG so updates don't remove the caret
        const contentSpan = document.createElement('span');
        contentSpan.className = 'flex-1 truncate text-gray-500';
        contentSpan.textContent = 'Select a product';
        display.appendChild(contentSpan);

        // Caret SVG on the right (uses Tailwind sizing and color)
        const caret = document.createElement('span');
        caret.className = 'ml-2 flex items-center text-gray-400 transition-transform duration-200';
        caret.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>`;
        display.appendChild(caret);

        // Create dropdown menu
        const menu = document.createElement('div');
        menu.className = 'products-dropdown-menu absolute mt-1 bg-white border border-gray-200 rounded-md max-h-72 overflow-y-auto z-50 hidden dark:bg-gray-800';
        menu.setAttribute('role', 'listbox');
        menu.style.position = 'absolute';
        menu.style.maxHeight = '18rem';
        menu.style.overflowY = 'auto';
        menu.style.overflowX = 'hidden';
        menu.style.display = 'none';

        // Create options in menu
        const options = Array.from(select.options);
        options.forEach((option, index) => {
            // Skip empty/placeholder option
            if (!option.value) return;

            const optionDiv = document.createElement('div');
            optionDiv.className = 'products-dropdown-option px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors duration-150 text-sm text-gray-700 dark:bg-gray-800 dark:!text-gray-200 dark:hover:!text-gray-800';
            optionDiv.setAttribute('role', 'option');
            optionDiv.setAttribute('data-value', option.value);
            optionDiv.textContent = option.textContent;

            // Handle click on option - single selection
            optionDiv.addEventListener('click', function () {
                // Clear previous selection
                select.value = '';
                options.forEach(opt => opt.selected = false);

                // Set new selection
                option.selected = true;
                select.value = option.value;

                updateDisplay();
                toggleMenu(); // Close menu after selection
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });

            if (option.selected) {
                optionDiv.classList.add('bg-primary-50', 'text-bright-green', 'font-medium');
            }

            menu.appendChild(optionDiv);
        });

        // Update display text
        function updateDisplay() {
            const selectedOption = select.options[select.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                contentSpan.textContent = 'Select a product';
                contentSpan.className = 'flex-1 truncate text-gray-500';
            } else {
                contentSpan.textContent = selectedOption.textContent;
                contentSpan.className = 'flex-1 truncate text-gray-700';
            }

            // Update selected styles in menu
            menu.querySelectorAll('.products-dropdown-option').forEach((optionDiv) => {
                const value = optionDiv.getAttribute('data-value');
                if (select.value === value) {
                    optionDiv.classList.add('bg-primary-50', 'text-bright-green', 'font-medium');
                } else {
                    optionDiv.classList.remove('bg-primary-50', 'text-bright-green', 'font-medium');
                }
            });
        }

        // Toggle menu
        function toggleMenu() {
            const isOpen = menu.style.display !== 'none' && menu.style.display !== '';
            if (isOpen) {
                menu.style.display = 'none';
                menu.classList.add('hidden');
                display.setAttribute('aria-expanded', 'false');
                caret.style.transform = 'rotate(0deg)';
                display.classList.remove('ring-2', 'ring-bright-green', 'border-bright-green');
            } else {
                const rect = display.getBoundingClientRect();
                menu.style.top = `${rect.bottom + window.scrollY}px`;
                menu.style.left = `${rect.left + window.scrollX}px`;
                menu.style.width = `${rect.width}px`;

                menu.style.display = 'block';
                menu.classList.remove('hidden');
                display.setAttribute('aria-expanded', 'true');
                caret.style.transform = 'rotate(180deg)';
                display.classList.add('ring-2', 'ring-bright-green', 'border-bright-green');
            }
        }

        // Event listeners
        display.addEventListener('click', toggleMenu);
        display.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleMenu();
            }
        });

        // When product changes, prefill quantity/min/max from latest stock for that product
        async function handlePrefill() {
            const quantityInput = document.getElementById('stock_quantity');
            const minInput = document.getElementById('stock_minimum_quantity');
            const maxInput = document.getElementById('stock_maximum_quantity');
            if (!quantityInput || !minInput || !maxInput) {
                return;
            }

            const productId = select.value;
            if (!productId) {
                quantityInput.value = '';
                minInput.value = '';
                maxInput.value = '';
                return;
            }

            const prefillUrl = wrapper.dataset.prefillUrl;
            if (!prefillUrl) {
                return;
            }

            try {
                const response = await fetch(prefillUrl + '?productId=' + encodeURIComponent(productId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    return;
                }
                const payload = await response.json();
                if (!payload.success || !payload.data) {
                    return;
                }

                const data = payload.data;
                if (data.quantity !== null && data.quantity !== undefined) {
                    quantityInput.value = data.quantity;
                }
                if (data.minimumQuantity !== null && data.minimumQuantity !== undefined) {
                    minInput.value = data.minimumQuantity;
                }
                if (data.maximumQuantity !== null && data.maximumQuantity !== undefined) {
                    maxInput.value = data.maximumQuantity;
                }
            } catch (e) {
                // Fail silently; admin can still enter values manually
            }
        }

        select.addEventListener('change', handlePrefill);

        // Close menu when clicking outside
        document.addEventListener('click', function (e) {
            if (!display.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = 'none';
                menu.classList.add('hidden');
                display.setAttribute('aria-expanded', 'false');
                caret.style.transform = 'rotate(0deg)';
                display.classList.remove('ring-2', 'ring-bright-green', 'border-bright-green');
            }
        });

        // Handle Escape key to close menu
        display.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                menu.style.display = 'none';
                menu.classList.add('hidden');
                display.setAttribute('aria-expanded', 'false');
                caret.style.transform = 'rotate(0deg)';
                display.classList.remove('ring-2', 'ring-bright-green', 'border-bright-green');
            }
        });

        // Initialize display
        updateDisplay();

        // Set wrapper to relative positioning if not already (ensures display stays in-flow)
        wrapper.style.position = 'relative';

        // Insert elements
        wrapper.appendChild(display);
        document.body.appendChild(menu);

        // Initialize menu as hidden
        menu.style.display = 'none';
    });