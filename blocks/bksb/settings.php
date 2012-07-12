<?php 

/**
 * Global config file for the BKSB block
 */

$bksb_settings = new admin_setting_heading(
    'block_bksb/bksb_settings', 
    get_string('bksb_settings', 'block_bksb')
);
$settings->add($bksb_settings);

$db_server = new admin_setting_configtext(
    'block_bksb/db_server', 
    get_string('db_server', 'block_bksb'), 
    get_string('set_db_server', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_server);

$db_name = new admin_setting_configtext(
    'block_bksb/db_name', 
    get_string('db_name', 'block_bksb'), 
    get_string('set_db_name', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_name);

$db_user = new admin_setting_configtext(
    'block_bksb/db_user', 
    get_string('db_user', 'block_bksb'), 
    get_string('set_db_user', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_user);

$db_password = new admin_setting_configtext(
    'block_bksb/db_password', 
    get_string('db_password', 'block_bksb'), 
    get_string('set_db_password', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($db_password);

$bksb_stats = new admin_setting_heading(
    'block_bksb/bksb_stats', 
    get_string('bksb_stats', 'block_bksb')
);

$ia_link = new moodle_url('/blocks/bksb/stats/initial_assessments.php');
$ia_link_html = '<a href="'.$ia_link.'">'.get_string('bksb_stats_ia', 'block_bksb').'</a>';
$settings->add(new admin_setting_heading(
    'block_bksb/stats_ia', 
    get_string('bksb_stats_ia', 'block_bksb'), 
    $ia_link_html
));

$da_link = new moodle_url('/blocks/bksb/stats/diagnostic_assessments.php');
$da_link_html = '<a href="'.$da_link.'">'.get_string('bksb_stats_da', 'block_bksb').'</a>';
$settings->add(new admin_setting_heading(
    'block_bksb/stats_da', 
    get_string('bksb_stats_da', 'block_bksb'), 
    $da_link_html
));
?>
