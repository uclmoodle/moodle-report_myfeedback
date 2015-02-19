<?php
defined('MOODLE_INTERNAL') || die;
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
}