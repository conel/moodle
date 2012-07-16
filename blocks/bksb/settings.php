<?php 

/**
 * Global config file for the BKSB block
 */

// Settings stored in the 'mdl_config_plugins' table.

// BKSB Database Settings
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

$db_password = new admin_setting_configpasswordunmask(
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


// MIS Database Settings
$mis_settings = new admin_setting_heading(
    'block_bksb/mis_settings', 
    get_string('mis_settings', 'block_bksb')
);
$settings->add($mis_settings);

$mis_db_server = new admin_setting_configtext(
    'block_bksb/mis_db_server', 
    get_string('mis_db_server', 'block_bksb'), 
    get_string('mis_set_db_server', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_server);

$mis_db_name = new admin_setting_configtext(
    'block_bksb/mis_db_name', 
    get_string('mis_db_name', 'block_bksb'), 
    get_string('mis_set_db_name', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_name);

$mis_db_user = new admin_setting_configtext(
    'block_bksb/mis_db_user', 
    get_string('mis_db_user', 'block_bksb'), 
    get_string('mis_set_db_user', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_user);

$mis_db_password = new admin_setting_configpasswordunmask(
    'block_bksb/mis_db_password', 
    get_string('mis_db_password', 'block_bksb'), 
    get_string('mis_set_db_password', 'block_bksb'),
    '',
    PARAM_RAW
);
$settings->add($mis_db_password);



// Links to Statistic Pages
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
