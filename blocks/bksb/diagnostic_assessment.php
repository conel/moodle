<?php 

require('../../config.php');
require('BksbReporting.class.php');
require($CFG->libdir.'/tablelib.php');
$bksb = new BksbReporting();

$user_id = optional_param('id', 0, PARAM_INT);
$course_id = optional_param('course_id', SITEID, PARAM_INT);
$group = optional_param('group', -1, PARAM_INT);
$updatepref = optional_param('updatepref', -1, PARAM_INT);
$ass_no = optional_param('assessment', 1, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    error("Course ID is incorrect");
}
if (!$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
    error("Context ID is incorrect");
}
require_login($course);

// nkowald - 2012-01-10 - Define $baseurl here, needs to keep all get distinct params
$params = $bksb->getDistinctParams();
$baseurl = $CFG->wwwroot.'/blocks/bksb/diagnostic_assessment.php' . $params;

// TODO - change! - Needs some sort of is teacher or admin capability
$access_isteacher = true;

if ($course_id != 1) {
    $PAGE->set_context($coursecontext);
    //$PAGE->set_pagelayout('course');
} else if ($user_id != 0) {
    $PAGE->set_context(get_context_instance(CONTEXT_USER, $USER->id));
    $PAGE->navigation->extend_for_user($USER);
    //$PAGE->set_pagelayout('user');
}
$title = 'BKSB - Diagnostic Assessment Overviews';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($baseurl);

echo $OUTPUT->header();

// User
if ($user_id != 0) {

    if ($user = $DB->get_record('user', array('id' => $user_id), 'id, idnumber, firstname, lastname')) {
        $fullname = fullname($user);
        $conel_id = $user->idnumber;
    }

    echo '<div style="text-align:center;">';
        echo "<h2>Diagnostic Assessment Overview for <span>$fullname</span></h2>";
        echo $OUTPUT->user_picture($user, array('size'=>100));
        echo '<br /><br />';
    echo '</div>';

    $best_scores = $bksb->getBestScores($conel_id);
    $user_sessions = $bksb->getBksbDiagSessions($conel_id);
    $existing_diagnostics = $bksb->filterAssessmentsFromSessions($user_sessions);

    $results_found = false;

    foreach ($existing_diagnostics as $ass_no => $ass_type) {

        $bksb_results = $bksb->getDiagnosticResults($conel_id, $ass_no, $best_scores);
        if ($bksb_results === false) continue;

        $results_found = true;

        print_heading($ass_type . ' Assessment');

        // Create array of questions for num returned
        $questions = range(1, count($bksb_results));
        // nkowald - 2010-10-05 - Add question % column
        $questions[] = 'BKSB %';
        
        $tablecolumns = $questions;
        $tableheaders = $questions;

        $table = new flexible_table('bksb_do');
                        
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($baseurl);
        $table->collapsible(false);
        $table->initialbars(false);
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'bksb_results_' . $ass_no);
        $table->set_attribute('class', 'bksb_results');
        $table->set_attribute('width', '95%');
        $table->set_attribute('align', 'center');
        foreach ($questions as $question) {
            $table->no_sorting($question);
        }
            
        $table->setup();
        $diag_results = array();
        foreach ($bksb_results as $res) {
            $diag_results[] = $bksb->getHTMLResult($res[1]);
        }
            
        $bksb_session_id = isset($user_sessions[$ass_type]) ? $user_sessions[$ass_type] : 0;
        $percentage = $bksb->getBksbPercentage($bksb_session_id);
        
        $bksb_results_url = 'http://bksb/bksb_Reporting/Reports/DiagReport.aspx?session='.$bksb_session_id;	
        $diag_results[] = '<span style="white-space:nowrap";>'.$percentage.'%<br /><a href="'.$bksb_results_url.'" class="percentage_link" title="Go to BKSB results page" target="_blank">View on BKSB</a></span>';
        
        $table->add_data($diag_results);

        $table->print_html();  // Print the table

        $overviews = $bksb->getAssDetails($ass_no);
        echo '<table class="bksb_key" width="95%">';
        echo '<tr><td>';

        echo "<h5>Questions</h5>";
        echo "<ol>";
        foreach ($overviews as $overview) {
            echo "<li>".$overview[0]."<span style=\"color:#CCC;\"> &mdash; ".$overview[1]."</span></li>";
        }
        echo "</ol>";
        echo '</td></tr>';
        echo '</table>';

    } // foreach

    if ($results_found == false) {
        echo '<center><p><b>No diagnostic overviews for this student.</b></p></center>';
    }
    echo '<br />';

} else if ($course_id and $access_isteacher and $course->id != $SITE->id) {

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    
    if ($updatepref > 0){
        $perpage = optional_param('perpage', 10, PARAM_INT);
        $perpage = ($perpage <= 0) ? 10 : $perpage ;
        set_user_preference('bksb_do_perpage', $perpage);
    }
    /* next we get perpage and from database */
    $perpage = get_user_preferences('bksb_do_perpage', 10);
    $page = optional_param('page', 0, PARAM_INT);

    // Are groups being used in this course?. If so set $currentgroup to reflect the current group
    $groupmode = groups_get_course_groupmode($course);   // Groups are being used
    $currentgroup = groups_get_course_group($course, true);
    if (!$currentgroup) $currentgroup  = NULL;

    $get_url = $CFG->wwwroot . '/blocks/bksb/diagnostic_assessment.php';
    echo '<form action="'.$get_url.'" method="GET">
        <input type="hidden" name="course_id" value="'.$course_id.'" />
        <table style="margin:0 auto;">
        <tr><td>Assessment Type:</td><td>
        <select name="assessment" onchange="this.form.submit()">
            <option value="">-- Select Assessment Type --</option>';

    foreach ($bksb->ass_types as $key => $value) {
        if ($key == $ass_no) {
            echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
        } else {
            echo '<option value="'.$key.'">'.$value.'</option>';
        }
    }
    echo '</select></td></tr></table></form><br />';

    $isseparategroups = ($course->groupmode == SEPARATEGROUPS 
        && $course->groupmodeforce 
        && !has_capability('moodle/site:accessallgroups', $context)
    );	

    $ass_type = $bksb->getAssTypeFromNo($ass_no);

    print_heading($ass_type . ' - Diagnostic Assessment Overview ('.$course->shortname.')');
    groups_print_course_menu($course, $baseurl); 
    echo '<br />';

    // Get BKSB Result categories
    $cols = array('picture', 'fullname');
    $cols_header = array('Picture', 'Name');
    $no_questions = $bksb->getNoQuestions($ass_no);
    // Create array of questions for num returned
    $questions = array();
    for ($i=1; $i<=$no_questions; $i++) {
       $questions[] = $i; 
    }
    $questions[] = 'BKSB %';
    
    $tablecolumns = array_merge($cols, $questions);
    $tableheaders = array_merge($cols_header, $questions);

    $table = new flexible_table('bkbs_ao');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl);
    //$table->sortable(true, 'lastname');
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
    foreach ($questions as $question) {
        $table->no_sorting($question);
    }
    $course_students = $bksb->getStudentsForCourse($course->id);
    $table->pagesize($perpage, count($course_students));
    $table->setup();
    $offset = $page * $perpage;
    $students = $bksb->filterStudentsByPage($course_students, $offset, $perpage);

    $records_found = false;

    if (count($students > 0)) {
        foreach ($students as $student) {
            $user_sessions = $bksb->getBksbDiagSessions($student->idnumber);
            $best_scores = $bksb->getBestScores($student->idnumber);
            $bksb_results = $bksb->getDiagnosticResults($student->idnumber, $ass_no, $best_scores);
            if ($bksb_results === false) continue;

            $records_found = true;
            
            $picture = $OUTPUT->user_picture($student, array('size'=>40));
            $name_html = '<a href="'.$CFG->wwwroot.'/blocks/bksb/diagnostic_assessment.php?course_id='.$course_id.'&amp;id='.$student->id.'" title="View all assessments for '.fullname($student).'">'.fullname($student).'</a>';
            $col_row = array($picture, $name_html);

            $diag_results = array();
            foreach ($bksb_results as $res) {
                $diag_results[] = $bksb->getHTMLResult($res[1]);
            }
            $bksb_session_id = isset($user_sessions[$ass_type]) ? $user_sessions[$ass_type] : 0;
            $percentage = $bksb->getBksbPercentage($bksb_session_id);
            $bksb_results_url = 'http://bksb/bksb_Reporting/Reports/DiagReport.aspx?session='.$bksb_session_id;
            
            $diag_results[] = '<span style="white-space:nowrap";>'.$percentage.'%<br /><a href="'.$bksb_results_url.'" class="percentage_link" title="Go to BKSB results page" target="_blank">View on BKSB</a></span>';
            
            $row = array_merge($col_row, $diag_results);
            $table->add_data($row);
        }
        $table->print_html();  // Print the table
    }

    if ($records_found == true) {
        $overviews = $bksb->getAssDetails($ass_no);
        echo '<table class="bksb_key" width="95%">';
        echo '<tr><td>';
        echo "<h5>Questions</h5>";
        echo "<ol>";
        foreach ($overviews as $overview) {
            if ($overview[0] != $overview[1]) {
                echo "<li>".$overview[0]."<span style=\"color:#CCC;\"> &mdash; ".$overview[1]."</span></li>";
            } else {
                echo "<li>".$overview[0]."</li>";
            }
        }
        echo "</ol>";
        echo '</td></tr>';
        echo '</table>';
    } else {
        echo '<center><br /><p style="color:#000;"><strong>No students chose to do this level.</strong></p></center>';
    }

    echo '<form name="options" action="'.$baseurl.'" method="post">';
    echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
    echo '<table id="optiontable" align="center">';
    echo '<tr align="right"><td><label for="perpage">Per page:</label></td>';
    echo '<td align="left">';
    echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
    echo '</td></tr>';
    echo '<tr>';
    echo '<td colspan="2" align="right">';
    echo '<input type="submit" value="'.get_string('savepreferences').'" />';
    echo '</td></tr></table>';
    echo '</form>';

    /*
    $courses = enrol_get_my_courses($USER->id); // should be courses i can teach in
    if ($courses) {
        print_heading(get_string('courses'));
        echo '<div class="generalbox" style="width:95%; margin:auto">';
        foreach ($courses as $course) {
            echo '<a href="diagnostic_assessment.php?course_id='.$course->id.'">'.$course->fullname.'</a><br />';
        }
        echo '</div>';
    }
     */
}

echo $OUTPUT->footer();

?>
