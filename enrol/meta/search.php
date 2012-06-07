<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(__FILE__) . '/../../config.php');

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/enrol/meta/search.php');

header('Content-type: application/json; charset=utf-8');

// Check access.
if (!isloggedin()) {
    print_error('mustbeloggedin');
}
if (!confirm_sesskey()) {
    print_error('invalidsesskey');
}

$id = required_param('id', PARAM_INT);// course id
$searchtext = required_param('searchtext', PARAM_RAW);// Get the search parameter.

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

// row limit unlimited if not set in config
$enrol = enrol_get_plugin('meta');
$rowlimit = $enrol->get_config('addmultiple_rowlimit', 0);

$existing = $DB->get_records('enrol', array('enrol'=>'meta', 'courseid'=>$id), '', 'customint1, id');

function get_valid_courses($courses) {

    $valid_courses = array();
    foreach ($courses as $c) {
        if (isset($existing[$c->id]) || $c->id == $course->id || $c->id == SITEID) {
            continue;
        }
        $coursecontext = get_context_instance(CONTEXT_COURSE, $c->id);
        if (!has_capability('enrol/meta:selectaslinked', $coursecontext)) {
            continue;
        }
        $valid = new stdClass();
        $valid->id = $c->id;
        $valid->shortname = $c->shortname;
        $valid->fullname = $c->fullname;
        // omitting $c->id from the key preserves query order (shortname ASC)
        $valid_courses[] = $valid;
    }
    return $valid_courses;
}

if (!empty($searchtext)) {
    $courses = get_courses_search(explode(" ", $searchtext), 'shortname ASC', 0, 99999, $rowlimit);
    $results = get_valid_courses($courses);
} else {
    $rs = $DB->get_recordset('course', array('visible' => 1), 'shortname ASC', 'id, shortname, fullname', 0, $rowlimit);
    $results = get_valid_courses($rs);
    $rs->close();
}

echo json_encode(array('results'=>$results));
