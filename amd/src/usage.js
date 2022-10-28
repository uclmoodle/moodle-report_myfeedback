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

/**
 * Helper function to build JS vendor path.
 *
 * @param {string} path
 * @returns {string}
 */
const vendorJSURL = function(path) {
    return M.cfg.wwwroot + '/report/myfeedback/vendor/js/' + path + '.min';
};

require.config({
    paths: {
        'datatables.net': vendorJSURL('jquery.dataTables'),
        'datatables.net-buttons': vendorJSURL('dataTables.buttons'),
        'datatables.net-colreorder': vendorJSURL('dataTables.colReorder'),
        'datatables.net-fixedheader': vendorJSURL('dataTables.fixedHeader'),
        'datatables.net-responsive': vendorJSURL('dataTables.responsive'),
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
            $(document).ready( function () {

                $('#wait').css({'cursor':'default','display':'none'});
                $('body').css('cursor', 'default');

                $('#reportSelect').change(function(){
                    $('#report_form_select').submit();
                });

                $('#categorySelect').change(function(){
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
                    'order': [[0, 'asc' ]]
                });

                $('#usagetable').DataTable({
                    pageLength: -1,
                    filtering: false,
                    ordering: true,
                    searching: false,
                    pagination: false,
                    order: [[1, 'desc']]
                });

                $('.reportPrint').on('click', function () {
                    print();
                });

                $('.x_port').on('click', function() {
                    window.location.href= 'export.php';
                });

            });
        }
    };
});
