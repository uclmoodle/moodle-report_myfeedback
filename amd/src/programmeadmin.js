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

define(['jquery', 'core/str'], function($, str) {

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
        {
            key: 'email_dept_subject',
            component: 'report_myfeedback',
        },
        {
            key: 'show',
            component: 'core',
        },
        {
            key: 'hide',
            component: 'core',
        },
    ];

    const stringResults = {};

    const setupStudentTable = () => {
        $('.overallgrade').show();
        $('span.studentsassessed').text(stringResults.dashboard_students);
        $('.studentimgdue').attr('title', stringResults.student_due_info);
        $('.studentimgnon').attr('title', stringResults.student_nonsub_info);
        $('.studentimglate').attr('title', stringResults.student_late_info);
        $('.studentimggraded').attr('title', stringResults.student_graded_info);
        $('.studentimglow').attr('title', stringResults.student_low_info);
    };

    return {
        init: function() {
            $(document).ready(function() {

                // Populate strings.
                str.get_strings(strings).then(results => results.forEach((val, key) => {
                    const string = strings[key];
                    stringResults[string.key] = val;
                })).catch();

                $('#deptSelect').change(function() {
                    $('#prog_form_dept').submit();
                });
                $('#progSelect').change(function() {
                    $('#prog_form_prog').submit();
                });
                $('#progmodSelect').change(function() {
                    $('#prog_form_mod').submit();
                });
                $('.progmodClick').click(function() {
                    $(this).closest('.permod').find('.prog_form_mod_click').submit();
                });

                $('.reportPrint').on('click', function() {
                    print();
                });

                $('.x_port').on('click', function() {
                    window.location.href = 'export.php';
                });

                $('.sToggle').click(function() {
                    $(this).closest('#selected-prog-container').find('.progtable').show();
                    $(this).closest('#selected-prog-container').find('.permod').hide();
                    $(this).closest('#selected-prog-container').find('.ptutor').hide();
                    $(this).closest('#selected-prog-container').find('tr.recordRow').show();
                    $('.overallgrade').hide();
                    $('span.studentsassessed').text(stringResults.dashboard_assesments);
                    $('.studentimgdue').attr('title', stringResults.tutortblheader_assessment_info);
                    $('.studentimgnon').attr('title', stringResults.tutortblheader_nonsubmissions_info);
                    $('.studentimglate').attr('title', stringResults.tutortblheader_latesubmissions_info);
                    $('.studentimggraded').attr('title', stringResults.tutortblheader_graded_info);
                    $('.studentimglow').attr('title', stringResults.tutortblheader_lowgrades_info);
                    $('.assessdue').show();
                    $(this).closest('#selected-prog-container').find('table.tutor-inner.stuRec').show();
                    $(this).closest('#selected-prog-container').find('span.sToggle').css({
                        'background-color': '#619eb6',
                        'color': '#fff'
                    });
                    $(this).closest('#selected-prog-container').find('span.aToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                    $(this).closest('#selected-prog-container').find('span.pToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                });
                $('.aToggle').click(function() {
                    $(this).closest('#selected-prog-container').find('.progtable').show();
                    $(this).closest('#selected-prog-container').find('tr.recordRow').hide();
                    $(this).closest('#selected-prog-container').find('.ptutor').hide();
                    $(this).closest('#selected-prog-container').find('.permod').show();
                    $(this).closest('#selected-prog-container').find('table.tutor-inner.stuRec').hide();
                    $('.progtable .modangle').text('\u25bc');
                    $(this).closest('#selected-prog-container').find('span.aToggle').css({
                        'background-color': '#619eb6',
                        'color': '#fff'
                    });
                    $(this).closest('#selected-prog-container').find('span.sToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                    $(this).closest('#selected-prog-container').find('span.pToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                });

                setupStudentTable();
                $('.modass').click(setupStudentTable);

                $('.pToggle').click(function() {
                    $(this).closest('#selected-prog-container').find('.progtable').hide();
                    $(this).closest('#selected-prog-container').find('.ptutor').show();
                    $(this).closest('#selected-prog-container').find('span.pToggle').css({
                        'background-color': '#619eb6',
                        'color': '#fff'
                    });
                    $(this).closest('#selected-prog-container').find('span.sToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                    $(this).closest('#selected-prog-container').find('span.aToggle').css({
                        'background-color': '#f5f5f5',
                        'color': '#444'
                    });
                });

                $('.assess-br').click(function() {
                    var thisAs = $(this).closest('.assRec');
                    var rem = '.stuRec.' + $(thisAs).attr('data-aid');
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

                $('u.hidetable').click(function() {
                    var thisEl = $(this).closest('td.maintable').find('table.innertable');
                    if ($(thisEl).is(':visible')) {
                        $(thisEl).hide();
                        $(this).text(stringResults.show);
                    } else if ($(thisEl).is(':hidden')) {
                        $(thisEl).show();
                        $(this).text(stringResults.hide);
                    }
                });

                $('#selectall1').change(function() {
                    $('.chk2').prop('checked', $(this).prop('checked'));
                });
                $("#emailform1").click(function() {
                    var mylink1 = [];
                    $("input:checked").each(function() {
                        if ($(this).val() != 'on') {
                            mylink1.push($(this).val());
                        }
                    });
                    if (mylink1.length > 0) {
                        $("a#mail1").attr("href", "mailto:?bcc=" + mylink1.join(";") + "&Subject="
                            + stringResults.email_dept_subject);
                    }
                });

                $('td#assess-name').css({
                    'max-width': '300px',
                    'white-space': 'nowrap',
                    'overflow': 'hidden',
                    'text-overflow': 'ellipsis'
                });
            });
        }
    };
});
