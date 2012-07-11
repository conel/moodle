<?php
    class block_bksb extends block_base {

        function init() {
            $this->title = get_string('bksb', 'block_bksb');
        }

        function get_content() {

            global $USER;

            if ($this->content !== NULL) {
                return $this->content;
            }

            $user_id = $USER->id;
            $course_id = (isset($_GET['course'])) ? $_GET['course'] : '';

            $this->content = new stdClass;

            // Check user is student
            $user_is_student = true;

            $block_html = '<ul id="bksb_block_ul">';

            // Student Results
            if ($user_is_student === true) {

                $get_params = sprintf('?id=%d', $user_id);
                if ($course_id != '') $get_params .= sprintf('&amp;course=%d', $course_id);

                $block_html .= '<li class="ia_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php'.$get_params.'">'.get_string('my_initial_assessments', 'block_bksb').'</a></li>
                    <li class="da_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_diagnostic_overview.php'.$get_params.'">'.get_string('my_diagnostic_assessments', 'block_bksb').'</a></li>';

            } else {
                // Course Results
                
                if ($course_id == '') {
                    $block_html = 'Course ID required';
                } else {
                    $block_html = '<li class="ia_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_initial_assessment.php?course='.$course_id.'">'.get_string('initial_assessments').'</a></li>
                    <li class="da_icon"><a href="'.$CFG->wwwroot.'/blocks/bksb/bksb_diagnostic_overview.php?course='.$course_id.'&amp;assessment=1">'.get_string('diagnostic_assessments').'</a></li>';
                }
            }
            $block_html .= '</ul>';

            $this->content->text = $block_html;
            $this->content->footer = '';

            return $this->content;
        }

    }
?>
