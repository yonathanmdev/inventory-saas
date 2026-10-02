document.addEventListener('DOMContentLoaded', function () {

    const table = document.getElementById('businessesTable');

    if (!table) {
        return;
    }

    new DataTable('#businessesTable', {

        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'All']
        ],

        order: [
            [0, 'desc']
        ],

        columnDefs: [
            {
                orderable: false,
                targets: 7
            }
        ],

        layout: {

            topStart: {
              buttons: [

    {
        extend: 'copy',
        text: '<i class="bi bi-copy me-1"></i> Copy'
    },

    {
        extend: 'csv',
        text: '<i class="bi bi-filetype-csv me-1"></i> CSV'
    },

    {
        extend: 'excel',
        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel'
    },

    {
        extend: 'print',
        text: '<i class="bi bi-printer me-1"></i> Print'
    }

]
            },

            topEnd: {
                search: {
                    placeholder: 'Search businesses...'
                }
            },

            bottomStart: 'pageLength',

            bottomEnd: 'paging'

        },

        language: {

            emptyTable:
                'No businesses found.',

            zeroRecords:
                'No matching businesses found.',

            info:
                'Showing _START_ to _END_ of _TOTAL_ businesses',

            infoEmpty:
                'Showing 0 businesses',

            lengthMenu:
                'Show _MENU_'

        }

    });

});