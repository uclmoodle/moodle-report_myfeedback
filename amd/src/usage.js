// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require.config({
    paths: {
        'datatables.net': M.cfg.wwwroot + '/report/myfeedback/vendor/js/jquery.dataTables.min',
        'datatables.net-buttons': M.cfg.wwwroot + '/report/myfeedback/vendor/js/dataTables.buttons.min',
        'datatables.net-colreorder': M.cfg.wwwroot + '/report/myfeedback/vendor/js/dataTables.colReorder.min',
        'datatables.net-fixedheader': M.cfg.wwwroot + '/report/myfeedback/vendor/js/dataTables.fixedHeader.min',
        'datatables.net-responsive': M.cfg.wwwroot + '/report/myfeedback/vendor/js/dataTables.responsive.min',
    },
});

define([
    'jquery',
    'datatables.net',
    'datatables.net-buttons',
    'datatables.net-colreorder',
    'datatables.net-fixedheader',
    'datatables.net-responsive',
], function($) {

    return {
        init: function() {
            $(document).ready(function() {

                $('#reportSelect').change(function() {
                    $('#report_form_select').submit();
                });

                $('#categorySelect').change(function() {
                    $('#report_category_select').submit();
                });

                $('td#long-name').css({
                    'max-width': '300px',
                    'white-space': 'nowrap',
                    'overflow': 'hidden',
                    'text-overflow': 'ellipsis'
                });

                $('#userstable').DataTable({
                    'dom': 'rtip',
                    'order': [[0, 'asc']]
                });

                $('#usagetable').DataTable({
                    pageLength: -1,
                    filtering: false,
                    ordering: true,
                    searching: false,
                    pagination: false,
                    order: [[1, 'desc']]
                });

                $('.reportPrint').on('click', function() {
                    print();
                });

                $('.x_port').on('click', function() {
                    window.location.href = 'export.php';
                });

            });
        }
    };
});
