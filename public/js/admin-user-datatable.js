    (function () {
        'use strict';
        const TABLE_SELECTOR = '#usersTable';
        const SEARCH_INPUT_ID = 'users-search-input';

        function initUsersDataTable() {
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
                    { orderable: false, targets: 4 },
                    { className: 'align-middle', targets: '_all' }
                ],
                autoWidth: false,
                language: {
                    emptyTable: 'No users found.',
                    zeroRecords: 'No matching users found.',
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
            document.addEventListener('DOMContentLoaded', initUsersDataTable);
        } else {
            initUsersDataTable();
        }
    })();