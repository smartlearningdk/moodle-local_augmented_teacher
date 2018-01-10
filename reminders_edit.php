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
require_once($CFG->dirroot . '/local/augmented_teacher/reminders_form.php');

$id   = optional_param('id', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);

$thispageurl = new moodle_url('/local/augmented_teacher/reminders_edit.php', array('id' => $id));

$PAGE->set_url($thispageurl);

if ($id) {
    $toform = $DB->get_record('local_augmented_teacher_rem', array('id' => $id, 'deleted' => 0), '*', MUST_EXIST);
    $cm = get_coursemodule_from_id('', $toform->cmid, 0, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
}
$instance = $DB->get_record($cm->modname, array('id' => $cm->instance));
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);
$returnurl = new moodle_url('/local/augmented_teacher/reminders_list.php', array('id' => $cm->id));

require_login($course);

$systemcontext = context_system::instance();

$PAGE->set_pagelayout('incourse');
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

$module = array('name' => 'local_augmented_teacher', 'fullpath' => '/local/augmented_teacher/module.js');
$PAGE->requires->js_init_call('M.local_augmented_teacher.init_shortcode', null, false, $module);

$PAGE->navbar->add(get_string('reminders', 'local_augmented_teacher'),
    new moodle_url('/local/augmented_teacher/reminders.php', array('id' => $course->id)));
$PAGE->navbar->add($instance->name);

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

$mform = new reminder_form(null, array());

if ($id) {
    $toform = $DB->get_record('local_augmented_teacher_rem', array('id' => $id, 'deleted' => 0), '*', MUST_EXIST);
    $toform->message = array('text' => clean_text($toform->message, FORMAT_HTML));
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {
    $rec = new stdClass();
    $rec->id = $fromform->id;
    $rec->title = $fromform->title;
    $rec->message = $fromform->message['text'];
    $rec->type = $fromform->type;
    $rec->enabled = $fromform->enabled;
    $rec->timeinterval = $fromform->timeinterval;
    if (!$id) {
        $rec->userid = $USER->id;
        $rec->cmid = $fromform->cmid;
        $rec->timecreated = time();
        $rec->id = $DB->insert_record('local_augmented_teacher_rem', $rec);
        redirect($returnurl, '', 0);
    } else {
        $rec->id = $id;
        $rec->timemodified = time();
        $DB->update_record('local_augmented_teacher_rem', $rec);
        redirect($returnurl, '', 0);
    }
    exit;
}

echo $OUTPUT->header();

if (!$id) {
    $toform = new stdClass();
    $toform->id = 0;
    $toform->cmid = $cmid;
}
$mform->set_data($toform);
$mform->display();

echo $OUTPUT->footer();