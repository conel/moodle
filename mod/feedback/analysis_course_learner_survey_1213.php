<?php

/***
*  Shows an analysed view of a feedback on the mainsite
*
*  @version $Id: analysis_course.php,v 1.5.2.3 2008/07/18 14:54:43 agrabs Exp $
*  @author Andreas Grabs
*  @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*  @package feedback
*  @modified    nkowald     2012-04-02
*
***/

require("../../config.php");
require("lib.php");

/*
@error_reporting(E_ALL ^ E_STRICT); // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
$CFG->debug = (E_ALL ^ E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!
*/

$current_tab = 'analysis';

$id = required_param('id', PARAM_INT);  //the POST dominated the GET

//print "id: $id<br>";

//if ($id) {
   if (!$cm = get_coursemodule_from_id('feedback', $id)) {
	   print_error("Course Module ID was incorrect");
   }
   if (!$course = $DB->get_record("course", array("id"=>$cm->course))) {
	   print_error("Course is misconfigured");
   }
   if (!$feedback = $DB->get_record("feedback", array("id"=>$cm->instance))) {
	   print_error("Course module is incorrect");
   }
//}

//print_object($feedback);

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

if (!($feedback->publish_stats OR has_capability('mod/feedback:viewreports', $context))) {
    print_error('error');
}

/* ---------------- Settings --------------------- 
* If you're adding the same filters on new surveys
* these are the only settings you'll need to change
*/
require('FeedbackFilters.class.php');

// set academic year of this survey
$this_ac_year = '1213';
$ff = new FeedbackFilters($feedback, $this_ac_year);

// each survey requires a unique sess key
$uniq_sess_name = 'tls1213test'; 

// does the survey have the site filter?
$has_site = true;

/* ---------------------------------------------- */

require('analysis_body.php');

?>
