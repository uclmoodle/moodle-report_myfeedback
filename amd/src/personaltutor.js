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
    'core/str',
    'datatables.net',
    'datatables.net-buttons',
    'datatables.net-colreorder',
    'datatables.net-fixedheader',
    'datatables.net-responsive',
], function($, str) {

    return {
        init: function() {
            $(document).ready(function() {

                $('#wait').css({'cursor':'default','display':'none'});
                $('body').css('cursor', 'default');

                $('#tutortable').DataTable({
                    'dom': 'lfBrtip',
                    fixedHeader: true,
                    'order': [1, 'asc' ],
                    'columnDefs': [
                        {'orderable': false, 'targets': 0 }],
                    buttons: [ 'colvis' ],
                    responsive: true
                });

                $('#selectall').change(function(){
                    $('.chk1').prop('checked', $(this).prop('checked'));
                });
                $("#emailform").click(function() {
                    var mylink = [];
                    $("input:checked").each(function(){
                        if ($(this).val() != 'on') {
                            mylink.push($(this).val());
                        }
                    });
                    if (mylink.length > 0) {
                        str.get_string('email_tutee_subject', 'report_myfeedback').then((email_tutee_subject) => {
                            $("a#mail").attr("href", "mailto:?bcc=" + mylink.join(";") + "&Subject=" + email_tutee_subject + "");
                        });
                    }
                });

                $('.reportPrint').on( 'click', function () {
                    print();
                });

                $('.ex_port').on( 'click', function() {
                    window.location.href= 'export.php';
                });

                $('.tutorCanvas').click(function(){
                    var thisEl = $(this).closest('tr.recordRow ').find('table.accord');
                    if($(thisEl).is(':visible')) {
                        $(this).closest('.recordRow').find('table.accord').hide();
                        $(this).closest('.recordRow').find('.tangle').text('\u25bc');
                    } else if ($(thisEl).is(':hidden')) {
                        $(this).closest('.recordRow').find('table.accord').show();
                        $(this).closest('.recordRow').find('.tangle').text('\u25b2');
                    }
                });
            });
        }
    };
});
