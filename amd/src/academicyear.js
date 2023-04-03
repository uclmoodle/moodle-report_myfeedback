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

define(['jquery'], function($) {
    return {
        init: function(archivedomain, userid, currenttab, ptut, prog, siteadmin, livedomain, archiveinst) {
            $(document).ready(function() {
                $('#mySelect').change(function() {

                    let archivedomain = '';
                    let t = this.value.toString();
                    if (t === 'current') {
                        $('#yearform').submit();
                    } else {
                        archivedomain = ($("#mySelect option:selected").attr('url'));
                        window.open(archivedomain + '/report/myfeedback/index.php?userid=' + userid
                            + '&currenttab=' + currenttab + M.cfg.sesskey, '_blank');
                    }

                    let archiveyear = t.substring(0, 2) + '-' + t.substring(2);
                    if (ptut === 'no' && prog === 'no' && siteadmin === 'no') {
                        if (archiveinst) {
                            if (t === 'current') {
                                location.replace(
                                    livedomain + '/report/myfeedback/index.php?userid=' + userid
                                    + '&currenttab=' + currenttab + M.cfg.sesskey
                                );
                            } else {
                                location.replace(
                                    archivedomain + archiveyear + '/report/myfeedback/index.php?userid=' + userid
                                    + '&currenttab=' + currenttab + M.cfg.sesskey
                                );
                            }
                        } else {
                            $('#yearform').submit();
                        }
                    } else {
                        // If personal tutor or dept admin or site admin.
                        if (archiveinst === 'no') {
                            // Only if not archive instance.
                            $('#yearform').submit();
                        } else {
                            // If not archive instance.
                            if (t === 'current') {
                                location.replace(
                                    livedomain + '/report/myfeedback/index.php?userid=' + userid
                                    + '&currenttab=' + currenttab + M.cfg.sesskey
                                );
                            } else {
                                location.replace(
                                    archivedomain + archiveyear + '/report/myfeedback/index.php?userid=' + userid
                                    + '&currenttab=' + currenttab + M.cfg.sesskey
                                );
                            }
                        }
                    }
                });
            });
        }
    };
});
