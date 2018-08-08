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
require_once($CFG->dirroot . '/local/augmented_teacher/excluded_users_form.php');

$id   = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

$thispageurl = new moodle_url('/local/augmented_teacher/excluded_users_edit.php', array('id' => $id, 'courseid' => $course->id));

$PAGE->set_url($thispageurl);
$userid = 0;
if ($id) {
    $toform = $DB->get_record('local_augmented_teacher_exc', array('id' => $id, 'courseid' => $course->id), '*', MUST_EXIST);
    $userid = $toform->userid;
}

$returnurl = new moodle_url('/local/augmented_teacher/excluded_users.php', array('id' => $course->id));

require_login($course);

$systemcontext = context_system::instance();

$PAGE->set_pagelayout('incourse');
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->navbar->add(get_string('excludeusersfromreminders', 'local_augmented_teacher'),
    new moodle_url('/local/augmented_teacher/excluded_users.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('addedit', 'local_augmented_teacher'));

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

$mform = new excluded_users_form(null, array('userid' => $userid, 'courseid' => $course->id));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {
    $rec = new stdClass();
    $rec->timeend = $fromform->timeend;
    $rec->modifierid = $USER->id;
    if (!$id) {
        $rec->timecreated = time();
        $rec->userid = $fromform->userid;
        $rec->courseid = $fromform->courseid;
        $rec->id = $DB->insert_record('local_augmented_teacher_exc', $rec);
        redirect($returnurl, '', 0);
    } else {
        $rec->id = $id;
        $rec->timemodified = time();
        $DB->update_record('local_augmented_teacher_exc', $rec);
        redirect($returnurl, '', 0);
    }
    exit;
}

echo $OUTPUT->header();

if (!$id) {
    $toform = new stdClass();
    $toform->id = 0;
    $toform->courseid = $course->id;
}
$mform->set_data($toform);
$mform->display();

echo $OUTPUT->footer();