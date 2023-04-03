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
    'core/str',
    'datatables.net',
    'datatables.net-buttons',
    'datatables.net-colreorder',
    'datatables.net-fixedheader',
    'datatables.net-responsive',
], function($, str) {

    const strings = [
        {
            key: 'dashboard_students',
            component: 'report_myfeedback',
        },
        {
            key: 'student_due_info',
            component: 'report_myfeedback',
        },
        {
            key: 'student_nonsub_info',
            component: 'report_myfeedback',
        },
        {
            key: 'student_late_info',
            component: 'report_myfeedback',
        },
        {
            key: 'student_graded_info',
            component: 'report_myfeedback',
        },
        {
            key: 'student_low_info',
            component: 'report_myfeedback',
        },
        {
            key: 'dashboard_assessments',
            component: 'report_myfeedback',
        },
        {
            key: 'tutortblheader_assessment_info',
            component: 'report_myfeedback',
        },
        {
            key: 'tutortblheader_nonsubmissions_info',
            component: 'report_myfeedback',
        },
        {
            key: 'tutortblheader_latesubmissions_info',
            component: 'report_myfeedback',
        },
        {
            key: 'tutortblheader_graded_info',
            component: 'report_myfeedback',
        },
        {
            key: 'tutortblheader_lowgrades_info',
            component: 'report_myfeedback',
        },
    ];

    return {
        init: function() {
            $(document).ready(function() {
                // Populate strings.
                const stringResults = {};
                str.get_strings(strings).then(results => results.forEach((val, key) => {
                    const string = strings[key];
                    stringResults[string.key] = val;
                })).catch();

                // Create the DataTable.
                $('.modtable').DataTable({
                    dom: '',
                    fixedHeader: true,
                    columnDefs: [
                        {
                            targets: [0, 1, 2, 3, 4, 5, 6],
                            searchable: false,
                            orderable: false
                        }
                    ],
                    order: []
                });

                $('.overallgrade').show();
                $('span.studentsassessed').text(stringResults.dashboard_students);
                $('.studentimgdue').attr('title', stringResults.student_due_info);
                $('.studentimgnon').attr('title', stringResults.student_nonsub_info);
                $('.studentimglate').attr('title', stringResults.student_late_info);
                $('.studentimggraded').attr('title', stringResults.student_graded_info);
                $('.studentimglow').attr('title', stringResults.student_low_info);

                $('.ex_port').on('click', function() {
                    window.location.href = 'export.php';
                });

                $('.reportPrint').on('click', function() {
                    print();
                });

                $('td#assess-name').css({
                    'max-width': '300px',
                    'white-space': 'nowrap',
                    'overflow': 'hidden',
                    'text-overflow': 'ellipsis'
                });

                $('.sToggle').click(function() {
                    $(this).closest('.fullRec').find('table.tutor-inner.assRec').hide();
                    $(this).closest('.fullRec').find('table.tutor-inner.stuRecP').hide();
                    $('.settableheight').hide();
                    $('.overallgrade').hide();
                    $('.assessdue').show();
                    $('span.studentsassessed').text(stringResults.dashboard_assessments);
                    $('.studentimgdue').attr('title', stringResults.tutortblheader_assessment_info);
                    $('.studentimgnon').attr('title', stringResults.tutortblheader_nonsubmissions_info);
                    $('.studentimglate').attr('title', stringResults.tutortblheader_latesubmissions_info);
                    $('.studentimggraded').attr('title', stringResults.tutortblheader_graded_info);
                    $('.studentimglow').attr('title', stringResults.tutortblheader_lowgrades_info);
                    $(this).closest('.fullRec').find('table.tutor-inner.stuRecM').show();
                    $(this).closest('.fullRec').find('span.aToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                    $(this).closest('.fullRec').find('span.sToggle').css({
                        'background-color': '#619eb6',
                        'color': '#fff'
                    });
                });
                $('.aToggle').click(function() {
                    $(this).closest('.fullRec').find('table.tutor-inner.stuRecM').hide();
                    $(this).closest('.fullRec').find('table.tutor-inner.assRec').show();
                    $('.settableheight').show();
                    $('.overallgrade').show();
                    $('span.studentsassessed').text(stringResults.dashboard_students);
                    $('.studentimgdue').attr('title', stringResults.student_due_info);
                    $('.studentimgnon').attr('title', stringResults.student_nonsub_info);
                    $('.studentimglate').attr('title', stringResults.student_late_info);
                    $('.studentimggraded').attr('title', stringResults.student_graded_info);
                    $('.studentimglow').attr('title', stringResults.student_low_info);
                    $('.modtable .modangle').text('\u25bc');
                    $(this).closest('.fullRec').find('span.aToggle').css({
                        'background-color': '#619eb6',
                        'color': '#fff'
                    });
                    $(this).closest('.fullRec').find('span.sToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                });

                $('.assess-br').click(function() {
                    var thisAs = $(this).closest('.assRec');
                    var rem = '.stuRecP.' + $(thisAs).attr('data-aid');
                    if ($(rem).is(':visible')) {
                        $(rem).nextUntil('.settableheight').hide();
                        $(rem).hide();
                        $('.assessdue').hide();
                        $(thisAs).find('.modangle').text('\u25bc');
                    } else if ($(rem).is(':hidden')) {
                        $(rem).nextUntil(':not(rem)').show();
                        $(rem).show();
                        $('.assessdue').hide();
                        $(thisAs).find('.modangle').text('\u25b2');
                    }
                });

            });
        }
    };
});
