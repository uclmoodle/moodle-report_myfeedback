<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;
$ac_years = $report->get_archived_dbs();
$res = 'current';
if (isset($_SESSION['viewyear'])) {
    $res = $_SESSION['viewyear'];
}

echo "<form method=\"POST\" id=\"yearform\" action=\"\"><select id=\"mySelect\" value=$res name=\"myselect\">";
foreach ($ac_years as $val) {
    echo "<option value=" . $val;
    if ($res == $val) {
        echo ' selected';
    }
    if ($val == 'current') {
        echo ">" . $val;
    } else {
        echo ">" . "20" . implode('/', str_split($val, 2));
    }
}
echo "</select></form>";

//No support before academic year 1213
if ($res != 'current' && $res < 1213) {
    echo get_string('noarchivesupporth1', 'report_myfeedback');
    echo get_string('noarchivesupporth2', 'report_myfeedback');
    $_SESSION['viewyear'] = 'current';
    exit();
}

echo "<script>
$('#mySelect').change(function(){
    $('#yearform').submit();    
});
</script>";
