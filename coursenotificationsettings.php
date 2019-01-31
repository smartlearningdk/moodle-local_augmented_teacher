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
require_once($CFG->dirroot . '/local/augmented_teacher/coursenotificationsettings_form.php');

$id   = optional_param('id', 0, PARAM_INT);
$formaction = optional_param('formaction', 0, PARAM_URL);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

$thispageurl = new moodle_url('/local/augmented_teacher/coursenotificationsettings.php', array('id' => $id));

$PAGE->set_url($thispageurl);

if ($id) {
    $toform = $DB->get_record('local_augmented_teacher_stp', array('courseid' => $course->id), '*');
}

$returnurl = new moodle_url('/local/augmented_teacher/index.php', array('id' => $course->id));

require_login($course);

$systemcontext = context_system::instance();

$PAGE->set_pagelayout('incourse');
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_title("$course->shortname: " . get_string('coursenotificationsettings', 'local_augmented_teacher'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->navbar->add(get_string('coursenotificationsettings', 'local_augmented_teacher'),
    new moodle_url('/local/augmented_teacher/coursenotificationsettings.php', array('id' => $course->id)));

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

$mform = new coursenotificationsettings_form(null, array('courseid' => $course->id));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {
    $rec = new stdClass();
    $rec->timestop = $fromform->timestop;
    $rec->timeresume = $fromform->timeresume;
    if (!$toform) {
        $rec->courseid = $fromform->id;
        $rec->id = $DB->insert_record('local_augmented_teacher_stp', $rec);        
    } else {
        $rec->id = $toform->id;
        $DB->update_record('local_augmented_teacher_stp', $rec);
    }
    redirect($returnurl, '', 0);
    exit;
}

echo $OUTPUT->header();

if (!$toform) {
    $toform = new stdClass();    
}
$toform->id = $course->id;
$toform->formaction = $formaction;

$mform->set_data($toform);
$mform->display();

echo $OUTPUT->footer();