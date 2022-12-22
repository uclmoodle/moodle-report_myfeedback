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
            /* Plugin API method to determine is a column is sortable */
            $.fn.dataTable.Api.register('column().searchable()', function() {
                var ctx = this.context[0];
                return ctx.aoColumns[this[0]].bSearchable;
            });

            $(document).ready(function() {
                const $feedbackcomments = $('#feedbackcomments');

                var filterCells = $('thead tr:eq(1) td');

                // Create the DataTable.
                var feedbacktable = $feedbackcomments.DataTable({
                    dom: 'RlfBrtip',
                    fixedHeader: true,
                    pageLength: 25,
                    orderCellsTop: true,
                    columnDefs: [
                        {
                            targets: [5, 6, 7],
                            searchable: false,
                            orderable:  false
                        }
                    ],
                    order: [[3, 'desc']],
                    buttons: ['colvis'],
                    stateSave: true,
                    stateSaveCallback: function(settings, data) {
                        localStorage.setItem('Feedback', JSON.stringify(data));
                    },
                    stateLoadCallback: function() {
                        return JSON.parse(localStorage.getItem('Feedback'));
                    },
                    responsive: true,
                    colReorder: true,
                });

                // Add filtering.
                feedbacktable.columns().every(async function() {
                    if (this.searchable()) {
                        var that = this;

                        // Create the `select` element.
                        var select = $('<select><option value=""></option></select>')
                            .appendTo(
                                filterCells.eq(feedbacktable.colReorder.transpose(this.index(), 'toOriginal'))
                            )
                            .on('change', function() {
                                that
                                    .search($(this).val())
                                    .draw();
                            });

                        // Add data.
                        this
                            .data()
                            .sort()
                            .unique()
                            .each(function(d) {
                                select.append($('<option>' + d + '</option>'));
                            });

                        // Restore state saved values
                        var state = this.state.loaded();
                        if (state) {
                            var val = state.columns[this.index()];
                            select.val(val.search.search);
                        }
                    }
                });

                // When button is clicked to reset table.
                $('#ftableDestroy').on('click', function() {
                    feedbacktable.colReorder.reset();
                    feedbacktable.destroy(false);
                    $('thead select').val('').change();
                    feedbacktable.state.clear();
                    location.reload();
                });

                $('#exportexcel').on('click', function() {
                    window.location.href = 'export.php';
                });

                $('#reportPrint').on('click', function() {
                    print();
                });

                $feedbackcomments.on('click', '.addnote', function() {
                    var gradeid = $(this).data('gid');
                    var instn = $(this).data('inst');
                    $('#gradeid').val(gradeid);
                    $('#instance1').val(instn);
                    $('#user_id').val($(this).data('uid'));
                    $('#notename').val($('#note-val' + gradeid + instn).text());
                });

                $feedbackcomments.on('click', '.addfeedback', function() {
                    var gradeid2 = $(this).data('gid');
                    var instn = $(this).data('inst');
                    $('#gradeid2').val(gradeid2);
                    $('#instance').val(instn);
                    $('#user_id2').val($(this).data('uid'));
                    $('#feedname').val($('#feed-val' + gradeid2 + instn).text());
                });
            });
        }
    };
});
