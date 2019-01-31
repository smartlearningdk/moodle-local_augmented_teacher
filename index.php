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

$courseid     = optional_param('id', 0, PARAM_INT); // This are required.

$PAGE->set_url('/local/augmented_teacher/index.php', array('id' => $courseid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

// Not needed anymore.
unset($courseid);

require_login($course);

$systemcontext = context_system::instance();
$isfrontpage = ($course->id == SITEID);

$frontpagectx = context_course::instance(SITEID);

if ($isfrontpage) {
    $PAGE->set_pagelayout('admin');
    require_capability('moodle/site:viewparticipants', $systemcontext);
} else {
    $PAGE->set_pagelayout('incourse');
    require_capability('moodle/course:viewparticipants', $context);
}

$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->add_body_class('path-user');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

$module = array('name' => 'local_augmented_teacher', 'fullpath' => '/local/augmented_teacher/module.js');
$PAGE->requires->js_init_call('M.local_augmented_teacher.init_taskselection', null, false, $module);

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_augmented_teacher'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// Should use this variable so that we don't break stuff every time a variable is added or changed.
$baseurl = new moodle_url('/local/augmented_teacher/index.php', array('id' => $course->id));

echo html_writer::start_tag('form',
    array(
        'id' => 'taskselectionform',
        'method' => 'post',
        'autocomplete' => 'off',
        'action' => $CFG->wwwroot.'/local/augmented_teacher/action_redir.php'
    )
);
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnto', 'value' => s($PAGE->url->out(false))));

$tasklist = array();
$tasklist['mergedmessages.php'] = get_string('mergedmessages', 'local_augmented_teacher');
if ($CFG->messaging) {
    $tasklist['reminders.php'] = get_string('reminders', 'local_augmented_teacher');
    $tasklist['notloggedinreminders.php'] = get_string('notloggedinreminder', 'local_augmented_teacher');
    $tasklist['excluded_users.php'] = get_string('excludeusersfromreminders', 'local_augmented_teacher');
    $tasklist['recommendactivity.php'] = get_string('recommendactivity', 'local_augmented_teacher');
    $tasklist['coursenotificationsettings.php'] = get_string('coursenotificationsettings', 'local_augmented_teacher');
}
echo $OUTPUT->help_icon('choosetask', 'local_augmented_teacher');
echo html_writer::tag('label', get_string('choosetask', 'local_augmented_teacher'), array('for' => 'formactionid'))  . ' ';
echo html_writer::select($tasklist, 'formaction', '', array('' => 'choosedots'), array('id' => 'formactionid'));

echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $course->id));
echo html_writer::end_tag('form');

echo html_writer::empty_tag('hr', array('style' => 'margin-top:30px; margin-bottom:40px'));

// Table columns.
$table = new html_table();
$table->attributes = array('style' => 'font-size: 90%; width:310px;', 'class' => '');
                      
$table->head = array(
    get_string('day', 'local_augmented_teacher'),
    get_string('hours', 'local_augmented_teacher'),
);
$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
for ($i = 0; $i <  7; $i++) {
    $row = new html_table_row();
    $row->cells = array();
    $row->cells[] = get_string($days[$i], 'calendar');

    if ($officehour = $DB->get_record('local_augmented_teacher_ofh', array('userid' => $USER->id, 'dayofweek' => $i))) {
        $row->cells[] = gmdate('H:i', $officehour->timestart).'-'.gmdate('H:i', $officehour->timeend);
    } else {
        $row->cells[] = '-';
    }

    $table->data[] = $row;
}

$title = get_string('officehours', 'local_augmented_teacher');


if ($istopped = $DB->get_record('local_augmented_teacher_stp', array('courseid' => $course->id))) {
    $time = time();
    if ($istopped->timestop < $time && $time < $istopped->timeresume) {
        echo html_writer::tag('div', get_string('pauseremiderwarning', 'local_augmented_teacher', userdate($istopped->timeresume)),
            array('class' => 'alert alert-info'));
    }
}
echo html_writer::tag('h3', $title, array('style' => 'margin-bottom: 20px;'));
echo html_writer::table($table);

// Add record form.
$formurl = new moodle_url('/local/augmented_teacher/officehours_edit.php');
$hiddenparams =
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $course->id)).
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
$submitbutton  = html_writer::tag('button', get_string('update', 'local_augmented_teacher'), array(
    'class' => 'btn btn-add-exclusion',
    'type' => 'submit',
    'value' => 'submit',
));
$form = html_writer::tag('form', $hiddenparams . $submitbutton, array(
    'action' => $formurl->out(),
    'method' => 'post',
    'autocomplete' => 'off'
));
echo html_writer::div($form, 'add-exclusion-form-wrapper', array('id' => 'add-record-btn'));

echo $OUTPUT->footer();