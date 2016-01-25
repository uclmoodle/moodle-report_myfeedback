<?php
defined('MOODLE_INTERNAL') || die;
if ($ADMIN->fulltree) {
    //Database Settings
    $settings->add(new admin_setting_heading('report_myfeedback_addheading',get_string('dbsettings', 'report_myfeedback'),''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    /*
    $options = array();
    for ($i=1; $i<7; $i++) {
        $options[$i] = $i;
    }    
    
    //Archived Database Settings - 
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_archive','Archived Database Settings',''));
    $settings->add(new admin_setting_configselect('report_myfeedback/archivedyears', 'Archived years','How many years of archive do you want to make available?', 6, $options));
    $settings->add(new admin_setting_configtext('report_myfeedback/namingconvention', 'Archived DB naming convention', 'What naming convention do you use before your academic year e.g."moodle_archive_xxxx" where xxxx is the two digit value for the academic years e.g."1415". <br> The current release only uses the default convention.', 'moodle_archive_'));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhostarchive', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbnamearchive', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuserarchive', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpassarchive', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    
    //Database 3 Settings 
    /*$settings->add(new admin_setting_heading('report_myfeedback_addheading2','2 Academic Years Previous Database Settings',''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost2', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname2', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser2', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass2', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    
    //Database 4 Settings
    $settings->add(new admin_setting_heading('report_myfeedback_addheading3','3 Academic Years Previous Database Settings',''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost3', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname3', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser3', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass3', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));*/
}
