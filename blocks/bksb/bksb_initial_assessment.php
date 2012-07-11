<?php 

require('../../config.php');
require('BksbReporting.class.php');
require($CFG->libdir.'/tablelib.php');
$bksb = new BksbReporting();

$user_id = optional_param('id', 0, PARAM_INT);
$course_id = optional_param('course_id', 1, PARAM_INT);
$group = optional_param('group', -1, PARAM_INT);
$updatepref = optional_param('updatepref', -1, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    error("Course ID is incorrect");
}
if (!$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
    error("Context ID is incorrect");
}
require_login($course);

// nkowald - 2012-01-10 - Define $baseurl here, needs to keep all get distinct params
$params = $bksb->getDistinctParams();
$baseurl = $CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php' . $params;

$title = 'BKSB - Initial Assessment Overview';
$PAGE->set_context(get_system_context());
//$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($baseurl);

// TODO - change! - Needs some sort of is teacher or admin capability
$access_isteacher = true;

echo $OUTPUT->header();

// User
if ($user_id != 0) {

    if ($user = $DB->get_record('user', array('id' => $user_id), 'id, idnumber, firstname, lastname')) {
        $fullname = $user->firstname . ' ' . $user->lastname;
        $conel_id = $user->idnumber;
    }

    echo '<div style="text-align:center;">';
        echo "<h2><span>$fullname</span></h2>";
        $profile_link = $CFG->wwwroot . "/user/profile.php?id=$user_id&amp;courseid=$course_id";
        echo '<a href="'.$profile_link.'" title="View Profile">'.$OUTPUT->user_picture($user, array('size'=>100)).'</a>';
        echo '<br /><br />';
    echo '</div>';

    // Get BKSB Result categories
    $cats = $bksb->ass_cats;
    $tablecolumns = $cats;
    $tableheaders = $cats;

    $table = new flexible_table('initial-assessments');
                    
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl);
    $table->collapsible(false);
    $table->initialbars(false);
    $table->column_suppress('picture');	
    $table->column_class('picture', 'picture');
    $table->column_class('fullname', 'fullname');
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'bksb_results_group');
    $table->set_attribute('class', 'bksb_results');
    $table->set_attribute('width', '95%');
    $table->set_attribute('align', 'center');
    foreach($cats as $cat) {
        $table->no_sorting($cat);
    }
    $table->setup();

    $bksb_results = $bksb->getResults($conel_id);
    $row = $bksb_results;
    $table->add_data($row);

    $table->print_html();  // Print the whole table

} else {

    /* Course View */
    if ($course_id && $access_isteacher && $course->id != $SITE->id) {

        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        if ($updatepref > 0) {
            $perpage = optional_param('perpage', 10, PARAM_INT);
            $perpage = ($perpage <= 0) ? 10 : $perpage ;
            set_user_preference('bksb_ia_perpage', $perpage);
        }

        /* next we get perpage and from database */
        $perpage = get_user_preferences('bksb_ia_perpage', 10);
        $page = optional_param('page', 0, PARAM_INT);

        // Are groups being used in this course?. If so set $currentgroup to reflect the current group
        $groupmode = groups_get_course_groupmode($course); // Groups are being used
        $currentgroup = groups_get_course_group($course, true);
        if (!$currentgroup) $currentgroup = NULL;

        $isseparategroups = ($course->groupmode == SEPARATEGROUPS 
            && $course->groupmodeforce 
            && !has_capability('moodle/site:accessallgroups', $context)
        );

        print_heading('Initial Assessment Overview ('.$course->shortname.')');
        groups_print_course_menu($course, $baseurl); 
        echo '<br />';

        // Get BKSB Result categories
        $cols = array('picture', 'fullname');
        $cols_header = array('Picture', 'Name');
        $cats = $bksb->ass_cats;
        $tablecolumns = array_merge($cols, $cats);
        $tableheaders = array_merge($cols_header, $cats);

        $table = new flexible_table('bksb_ia');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($baseurl);
        $table->sortable(false);
        $table->collapsible(false);
        $table->initialbars(true);
        $table->column_suppress('picture');	
        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'bksb_results_group');
        $table->set_attribute('class', 'bksb_results');
        $table->set_attribute('width', '95%');
        $table->set_attribute('align', 'center');
        foreach($cats as $cat) {
            $table->no_sorting($cat);
        }
        $course_students = $bksb->getStudentsForCourse($course->id);
        $table->pagesize($perpage, count($course_students));
        $table->setup();
        $offset = $page * $perpage;
        $students = $bksb->filterStudentsByPage($course_students, $offset, $perpage);

        if (count($students) > 0) {
            foreach ($students as $student) {
                $bksb_results = $bksb->getResults($student->idnumber);

                $picture = $OUTPUT->user_picture($student, array('size'=>40));
                $name_html = '<a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?id='.$student->id.'">'.fullname($student).'</a>';
                $col_row = array($picture, $name_html);
                $row = array_merge($col_row, $bksb_results);
                $table->add_data($row);
                $records_found = true;
            }

            $table->print_html();  /// Print the whole table

            echo '<form name="options" action="bksb_initial_assessment.php?courseid='.$course_id.'" method="post">';
            echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
            echo '<table id="optiontable" align="center">';
            echo '<tr align="right"><td>';
            echo '<label for="perpage">Per page</label>';
            echo ':</td>';
            echo '<td align="left">';
            echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
            helpbutton('pagesize', 'Per page', 'target');
            echo '</td></tr>';
            echo '<tr>';
            echo '<td colspan="2" align="right">';
            echo '<input type="submit" value="'.get_string('savepreferences').'" />';
            echo '</td></tr></table>';
            echo '</form>';

        } else {
            echo '<center><p><strong>No Initial Assessment results for this course or filter.</strong></p></center>';
        }
    }

    //redirect("bksb_initial_assessment.php$get_params", 'You are being directed to your own initial assessment results',0);
}

echo $OUTPUT->footer();

?>
