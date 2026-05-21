    function toggleOrderItems(orderId, btn) {
        const extraItems = document.getElementById('extra-items-' + orderId);
        const icon = btn.querySelector('.toggle-icon');
        const span = btn.querySelector('span');
        const isHidden = extraItems.classList.contains('hidden');
        const totalCount = extraItems.querySelectorAll('div').length;

        if (isHidden) {
            extraItems.classList.remove('hidden');
            span.textContent = 'Show less';
            icon.style.transform = 'rotate(180deg)';
        } else {
            extraItems.classList.add('hidden');
            span.textContent = '+ ' + totalCount + ' more';
            icon.style.transform = 'rotate(0deg)';
        }

        if (window.ordersSalesAuditDataTable) {
            window.ordersSalesAuditDataTable.columns.adjust();
        }
    }
    (function () {
        'use strict';
        var TOP_SEL = '#ordersTopSellersTable';
        var TOP_SEARCH = 'top-sellers-search-input';
        var AUDIT_SEL = '#ordersSalesAuditTable';
        var AUDIT_SEARCH = 'sales-audit-search-input';
        var salesAuditInited = false;

        function initTopSellersDataTable() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }
            var $table = jQuery(TOP_SEL);
            if (!$table.length || jQuery.fn.DataTable.isDataTable(TOP_SEL)) {
                return;
            }
            var table = $table.DataTable({
                dom: 'lrtip',
                pageLength: 10,
                order: [[4, 'desc']],
                columnDefs: [
                    { className: 'align-middle', targets: '_all' }
                ],
                autoWidth: false,
                language: {
                    emptyTable: 'No sales data yet.',
                    zeroRecords: 'No matching products found.',
                    paginate: {
                        previous: '<ion-icon name="chevron-back-outline" class="align-middle"></ion-icon>',
                        next: '<ion-icon name="chevron-forward-outline" class="align-middle"></ion-icon>'
                    }
                }
            });

            var searchInput = document.getElementById(TOP_SEARCH);
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    table.search(this.value).draw();
                });
            }

            if (typeof AdminSearch !== 'undefined') {
                new AdminSearch({
                    searchInputId: TOP_SEARCH,
                    tableSelector: TOP_SEL
                });
            }
        }

        function initSalesAuditDataTable() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
                return;
            }
            var $table = jQuery(AUDIT_SEL);
            if (!$table.length || jQuery.fn.DataTable.isDataTable(AUDIT_SEL)) {
                return;
            }
            var table = $table.DataTable({
                dom: 'lrtip',
                pageLength: 10,
                order: [[1, 'desc']],
                columnDefs: [
                    { className: 'align-middle', targets: '_all' }
                ],
                autoWidth: false,
                language: {
                    emptyTable: 'No orders yet.',
                    zeroRecords: 'No matching orders found.',
                    paginate: {
                        previous: '<ion-icon name="chevron-back-outline" class="align-middle"></ion-icon>',
                        next: '<ion-icon name="chevron-forward-outline" class="align-middle"></ion-icon>'
                    }
                }
            });

            window.ordersSalesAuditDataTable = table;
            salesAuditInited = true;

            var searchInput = document.getElementById(AUDIT_SEARCH);
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    table.search(this.value).draw();
                });
            }

            if (typeof AdminSearch !== 'undefined') {
                new AdminSearch({
                    searchInputId: AUDIT_SEARCH,
                    tableSelector: AUDIT_SEL
                });
            }

            table.columns.adjust();
        }

        function onSalesAuditTabShown() {
            if (!salesAuditInited) {
                initSalesAuditDataTable();
            } else if (window.ordersSalesAuditDataTable) {
                setTimeout(function () {
                    window.ordersSalesAuditDataTable.columns.adjust();
                }, 10);
            }
        }

        document.querySelectorAll('.orders-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                if (this.getAttribute('data-tab') === 'sales-audit') {
                    onSalesAuditTabShown();
                }
            });
        });

        function boot() {
            initTopSellersDataTable();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    })();