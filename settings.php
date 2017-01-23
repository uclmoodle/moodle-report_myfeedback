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
    
    $options = array();
    //If we are in Sept-Dec we subtract 2014 from the current year we are in otherwise we subtract 2015
    //This is because we can only support up to 14/15 academic year
    $max = date('m') > 8 ? date('Y') - 2014 : date('Y') - 2015;
    for ($i=0; $i<=$max; $i++) {
        $options[$i] = $i;
    }    
    
    //Archived Database Settings - 
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_archive',get_string('archivedbsettings', 'report_myfeedback'),''));
    $settings->add(new admin_setting_configselect('report_myfeedback/archivedyears', get_string('archiveyears', 'report_myfeedback'),
            get_string('archiveyearsinfo', 'report_myfeedback'), 0, $options));
    $settings->add(new admin_setting_configtext('report_myfeedback/archivedomain', get_string('archivedomain', 'report_myfeedback'),
            get_string('archivedomaininfo', 'report_myfeedback'), get_string('archivedomaindefault', 'report_myfeedback')));
    $settings->add(new admin_setting_configtext('report_myfeedback/namingconvention', get_string('archivenamingconvention', 'report_myfeedback'), 
            get_string('archivenamingconventioninfo', 'report_myfeedback'), get_string('archivenamingconventiondefault', 'report_myfeedback')));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhostarchive', get_string('archivedbhost', 'report_myfeedback'),
                    get_string('archivedbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuserarchive', get_string('archivedbuser', 'report_myfeedback'),
                    get_string('archivedbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpassarchive', get_string('archivedbpass', 'report_myfeedback'),
                    get_string('archivedbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/livedomain', get_string('livedomain', 'report_myfeedback'),
            get_string('livedomaininfo', 'report_myfeedback'), get_string('livedomaindefault', 'report_myfeedback')));
    $settings->add(new admin_setting_configtext('report_myfeedback/academicyear', get_string('settingsacademicyear', 'report_myfeedback'),
                    get_string('academicyearinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configcheckbox('report_myfeedback/archivedinstance', get_string('archivedinstance', 'report_myfeedback'),
                    get_string('archivedinstanceinfo', 'report_myfeedback'), '0'));
    
    //Dept Admin dashboard Settings - 
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_courselimit',get_string('courselimitheading', 'report_myfeedback'),''));
    $settings->add(new admin_setting_configtext('report_myfeedback/courselimit', get_string('courselimit', 'report_myfeedback'),
            get_string('courselimitsettings', 'report_myfeedback'), 200, PARAM_INT));
    
    //Overview tab Settings - 
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_overviewlimit',get_string('overviewlimitheading', 'report_myfeedback'),''));
    $settings->add(new admin_setting_configtext('report_myfeedback/overviewlimit', get_string('overviewlimit', 'report_myfeedback'),
            get_string('overviewlimitsettings', 'report_myfeedback'), 10, PARAM_INT));
    
        //Late feedback Settings - 
    /*$settings->add(new admin_setting_heading('report_myfeedback_addheading_latefeedback',get_string('latefeedbackheading', 'report_myfeedback'),''));
    $settings->add(new admin_setting_configtext('report_myfeedback/latefeedback', get_string('latefeedback', 'report_myfeedback'),
            get_string('latefeedbacksettings', 'report_myfeedback'), 28, PARAM_INT));*/
}
