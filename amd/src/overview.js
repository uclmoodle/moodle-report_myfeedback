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

                // Before initializing Datatables get the cells in the second row of the header so you can reference them later on
                var filterCells = $('thead tr:eq(1) td');

                // Initialize the DataTable.
                var table = $('#grades').DataTable({
                    dom: 'RlfBrtip',
                    fixedHeader: true,
                    pageLength: 25,
                    orderCellsTop: true,
                    columnDefs: [
                        {targets: [5, 8],
                            searchable: false,
                            orderable:  false
                        }
                    ],
                    order: [[4, 'desc']],
                    buttons: ['colvis'],
                    stateSave: true,
                    stateSaveCallback: function(settings, data) {
                        localStorage.setItem('Overview', JSON.stringify(data));
                    },
                    stateLoadCallback: function() {
                        return JSON.parse(localStorage.getItem('Overview'));
                    },
                    responsive: true
                });

                // Add filtering.
                table.columns().every(function() {
                    if (this.searchable()) {
                        var that = this;

                        // Create the `select` element.
                        var select = $('<select><option value=""></option></select>')
                            .appendTo(
                                filterCells.eq(table.colReorder.transpose(this.index(), 'toOriginal'))
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

                        // Restore state saved values.
                        var state = this.state.loaded();
                        if (state) {
                            var val = state.columns[this.index()];
                            select.val(val.search.search);
                        }
                    }
                    return true;
                });

                // When button is clicked to reset table.
                $('#tableDestroy').on('click', function() {
                    table.colReorder.reset();
                    table.destroy(false);
                    $('thead select').val('').change();
                    table.state.clear();
                    location.reload();
                });

                $('#exportexcel').on('click', function() {
                    window.location.href = 'export.php';
                });

                $('#reportPrint').on('click', function() {
                    print();
                });

                $('#toggle-grade').on('click', function() {
                    $('.t-rel').toggleClass('off');
                });

            });
        }
    };
});
