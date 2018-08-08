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

$page      = optional_param('page', 0, PARAM_INT);
$perpage   = optional_param('perpage', 20, PARAM_INT);
$sort      = optional_param('sort', 'lastname', PARAM_ALPHANUM);
$dir       = optional_param('dir', 'ASC', PARAM_ALPHA);
$courseid  = optional_param('id', 0, PARAM_INT);

$thispageurl = new moodle_url('/local/augmented_teacher/reminders.php', array(
    'page' => $page,
    'perpage' => $perpage,
    'sort' => $sort,
    'dir' => $dir,
    'id' => $courseid)
);

$PAGE->set_url($thispageurl);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$systemcontext = context_system::instance();

$PAGE->set_pagelayout('incourse');
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->add_body_class('path-user');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

$PAGE->navbar->add(get_string('reminders', 'local_augmented_teacher'));

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reminders', 'local_augmented_teacher'));


$modinfo = get_fast_modinfo($courseid, $USER->id);
$activities = $modinfo->get_cms();

$table = '';
$rows = '';

$actioniconurl = local_augmented_teacher_pix_url('a/view_list_active');
$actionicontext = get_string('list', 'local_augmented_teacher');
$actionicon = html_writer::img($actioniconurl, $actionicontext, array('width' => '16', 'height' => '16'));

foreach ($activities as $key => $mod) {
    $modulecontext = context_module::instance($mod->id);
    if (!$mod->visible && !has_capability('moodle/course:viewhiddenactivities', $modulecontext)) {
        unset($activities[$key]);
        continue;
    }
    if (($mod->completion == COMPLETION_TRACKING_NONE) || !$mod->completionexpected) {
        unset($activities[$key]);
        continue;
    }

    $modurl = new moodle_url('/mod/' . $mod->modname . '/view.php', array('id' => $mod->id));
    $icon = html_writer::img(local_augmented_teacher_pix_url('icon', $mod->modname), ucfirst($mod->modname),
        array('class' => 'iconlarge activityicon', 'role' => 'presentation')
    );

    $actionlinks = '';
    $actionurl = new moodle_url('/local/augmented_teacher/reminders_list.php', array('id' => $mod->id));
    $actionlinks .= html_writer::link($actionurl->out(false), $actionicon,
        array('class' => 'actionlink', 'title' => get_string('list', 'local_augmented_teacher')));

    $numberofreminders = $DB->count_records('local_augmented_teacher_rem', array('cmid' => $mod->id, 'deleted' => 0));

    $rows .= html_writer::tag('tr',
        html_writer::tag('td',
            html_writer::link($modurl, $icon) . ' ' .
            html_writer::link($modurl, $mod->name),
            array('class' => 'as-assignment-name')
        ).
        html_writer::tag('td', userdate($mod->completionexpected, get_string('strftimedaydate')),
            array('class' => 'upcoming-date')) .
        html_writer::tag('td', $numberofreminders, array('class' => 'upcoming-date')) .
        html_writer::tag('td', $actionlinks, array('class' => 'upcoming-date'))
    );
}

$table .= html_writer::start_tag('table', array('class' => 'activity-list generaltable'));
$table .= html_writer::tag('tr',
    html_writer::tag('th', get_string('activity'), array('class' => '')) .
    html_writer::tag('th', get_string('completionexpected', 'completion'), array('class' => '')) .
    html_writer::tag('th', get_string('numberofreminders', 'local_augmented_teacher'), array('class' => '')) .
    html_writer::tag('th', '', array('class' => ''))
);
$table .= $rows;
$table .= html_writer::end_tag('table');

echo $table;

echo $OUTPUT->footer();