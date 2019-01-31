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

/**
 * The Augmented Teacher
 *
 * @package    local_augmented_teacher
 * @author     Michael Gardener <mgardener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2017 SmartLearning Inc https://www.smartlearning.dk
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/augmented_teacher/lib.php');
require_once($CFG->dirroot . '/local/augmented_teacher/officehours_form.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('moodle/course:manageactivities', $context);

$thispageurl = new moodle_url('/local/augmented_teacher/officehours_edit.php', array('courseid' => $course->id));
$returnurl = new moodle_url('/local/augmented_teacher/index.php', array('id' => $course->id));

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->navbar->add(get_string('officehours', 'local_augmented_teacher'));
$PAGE->navbar->add(get_string('addedit', 'local_augmented_teacher'));

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

$toform = new stdClass();
$toform->userid = $USER->id;
$toform->courseid = $course->id;

if ($officehours = $DB->get_records('local_augmented_teacher_ofh', array('userid' => $USER->id))) {
    foreach ($officehours as $officehour) {
        $var = 'hour_'.$officehour->dayofweek;
        $toform->{$var} = $officehour->timestart .'-'. $officehour->timeend;
    }
}

$mform = new officehours_form(null, array());

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {

    for ($i = 0; $i <  7; $i++) {
        $var = 'hour_'.$i;

        if (isset($fromform->{$var}) && !empty($fromform->{$var})) {
            $formvalue = $fromform->{$var};

            $rec = new stdClass();
            $rec->userid    = $USER->id;
            $rec->dayofweek = $i;
            $rec->timestart = $formvalue['from'];
            $rec->timeend = $formvalue['to'];

            if ($update = $DB->get_record('local_augmented_teacher_ofh', array('userid' => $USER->id, 'dayofweek' => $i))) {
                $rec->id = $update->id;
                $DB->update_record('local_augmented_teacher_ofh', $rec);
            } else {
                $DB->insert_record('local_augmented_teacher_ofh', $rec);
            }
        } else {
            $DB->delete_records('local_augmented_teacher_ofh', array('userid' => $USER->id, 'dayofweek' => $i));
        }
    }
    redirect($returnurl);
    exit;
}

echo $OUTPUT->header();
$mform->set_data($toform);
$mform->display();
echo $OUTPUT->footer();