<?php
defined('MOODLE_INTERNAL') || die;
if ($ADMIN->fulltree) {
    //Database 1 Settings
    $settings->add(new admin_setting_heading('report_myfeedback_addheading','Current Year Database Settings',''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    
    //Database 2 Settings
    $settings->add(new admin_setting_heading('report_myfeedback_addheading2','Previous Year Database Settings',''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost2', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname2', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser2', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass2', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    
    //Database 3 Settings
    $settings->add(new admin_setting_heading('report_myfeedback_addheading3','2 Years Previous Database Settings',''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost3', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname3', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser3', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass3', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    
    //Database 4 Settings
    $settings->add(new admin_setting_heading('report_myfeedback_addheading4','3 Years Previous Database Settings',''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost4', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname4', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser4', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass4', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
}
