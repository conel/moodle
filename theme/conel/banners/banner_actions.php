<?php

include('../../../config.php');
include('Banners.class.php');
require_login(); 
if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    error("Only the administrator can access this page!", $CFG->wwwroot);
}

$action = (isset($_REQUEST['action']) && $_REQUEST['action'] != '') ? $_REQUEST['action'] : '' ;
$banner_pos = (isset($_REQUEST['pos']) && $_REQUEST['pos'] != '') ? $_REQUEST['pos'] : '' ;
$id = (isset($_REQUEST['id']) && $_REQUEST['id'] != '') ? $_REQUEST['id'] : '';
$link = (isset($_REQUEST['banner_link']) && $_REQUEST['banner_link'] != '') ? $_REQUEST['banner_link'] : '';
$audience = optional_param('audience', 1, PARAM_INT);

$banners = new Banners($audience);

switch ($action) {
    case 'upload':
        $result = $banners->upload($_FILES);
        break;

    case 'moveup':
        $result = $banners->moveUp($banner_pos);
        break;

    case 'movedown':
        $result = $banners->moveDown($banner_pos);
        break;

    case 'delete':
        $result = $banners->delete($id);
        break;

    case 'disable':
        $result = $banners->disable($id);
        break;

    case 'enable':
        $result = $banners->enable($id);
        break;

    case 'update':
        $result = $banners->update($id, $link, $_FILES);
        break;
}

if ($result === true) {
    $redirect = "index.php?audience=$audience";
    header("Location: $redirect");
    exit;
}

?>
