    (function () {
        'use strict';
        const TABLE_SELECTOR = '#locationsTable';
        const SEARCH_INPUT_ID = 'locations-search-input';

        function initLocationsDataTable() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }
            const $table = jQuery(TABLE_SELECTOR);
            if (!$table.length || jQuery.fn.DataTable.isDataTable(TABLE_SELECTOR)) {
                return;
            }
            const table = $table.DataTable({
                dom: 'lrtip',
                pageLength: 10,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: 3 },
                    { className: 'align-middle', targets: '_all' }
                ],
                autoWidth: false,
                language: {
                    emptyTable: 'No locations found.',
                    zeroRecords: 'No matching locations found.',
                    paginate: {
                        previous: '<ion-icon name="chevron-back-outline" class="align-middle"></ion-icon>',
                        next: '<ion-icon name="chevron-forward-outline" class="align-middle"></ion-icon>'
                    }
                }
            });

            const searchInput = document.getElementById(SEARCH_INPUT_ID);
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    table.search(this.value).draw();
                });
            }

            if (typeof AdminSearch !== 'undefined') {
                new AdminSearch({
                    searchInputId: SEARCH_INPUT_ID,
                    tableSelector: TABLE_SELECTOR
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLocationsDataTable);
        } else {
            initLocationsDataTable();
        }
        document.addEventListener('turbo:load', initLocationsDataTable);
    })();