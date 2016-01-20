<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;
$ac_years = $report->get_archived_dbs();
if ($ac_years) {
    $res = 'current';
    $r = '';
    if (isset($_POST['myselect'])) {
        $res = $_POST['myselect'];
    }
    echo "<p>Academic year:</p><form method=\"POST\" id=\"yearform\" action=\"\"><select id=\"mySelect\" value=$res name=\"myselect\" onchange=\"myFunction()\">
    <option value=\"Current\"";
    if ($res == 'Current') {
        echo ' selected';
    }
    echo ">Current";
    foreach ($ac_years as $k => $val) {
        echo "<option value=" . $val;
        if ($res == $val) {
            echo ' selected';
        }
        echo ">" . $val;
    }
    echo "</select></form>

<p>When you select a new car, a function is triggered which outputs the value of the selected car.</p>

<p id=\"demo\">You selected " . $res . "</p>";

    echo "<script>
$('#mySelect').change(function(){
    $('#yearform').submit();    
});
$('#mySelect').change(function(){
    $('#yearform').submit();    
});
</script>";
//$report->setup_ExternalDB(2);
    if ($_GET['currenttab'] == "year") {
        
    }
}
