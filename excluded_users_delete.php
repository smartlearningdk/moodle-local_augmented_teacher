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

$id      = optional_param('id', 0, PARAM_INT);
$courseid      = optional_param('courseid', 0, PARAM_INT);
$process = optional_param('process', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);
$excludeduser = $DB->get_record('local_augmented_teacher_exc', array('id' => $id, 'courseid' => $course->id), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $excludeduser->userid));

require_capability('moodle/course:manageactivities', $context);

$thispageurl = new moodle_url('/local/augmented_teacher/excluded_users_delete.phpexcluded_users.php',
    array('id' => $id, 'courseid' => $course->id)
);
$returnurl = new moodle_url('/local/augmented_teacher/excluded_users.php', array('id' => $course->id));

require_login($course);

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->navbar->add(get_string('excludeusersfromreminders', 'local_augmented_teacher'),
    new moodle_url('/local/augmented_teacher/excluded_users.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('delete', 'local_augmented_teacher'));

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

if ($process) {
    require_sesskey();
    $DB->delete_records('local_augmented_teacher_exc', array('id' => $excludeduser->id));
    redirect($returnurl);
    die;
} else {
    echo $OUTPUT->header();
    echo html_writer::tag('h1', get_string('delete', 'local_augmented_teacher'), array('class' => 'page-title'));
    echo $OUTPUT->confirm(
        html_writer::div(
            html_writer::tag('strong', get_string('user').': '). fullname($user).
            html_writer::empty_tag('br').html_writer::empty_tag('br').
            get_string('deleteexclusionwarn', 'local_augmented_teacher').
            html_writer::empty_tag('br').html_writer::empty_tag('br')
        ),
        new moodle_url('/local/augmented_teacher/excluded_users_delete.php', array('id' => $id, 'courseid' => $course->id, 'process' => 1)),
        $returnurl
    );
    echo $OUTPUT->footer();
}