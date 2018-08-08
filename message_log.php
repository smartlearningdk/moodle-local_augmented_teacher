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
$cmid    = optional_param('cmid', 0, PARAM_INT);
$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$sort    = optional_param('sort', 'lastname', PARAM_ALPHANUM);
$dir     = optional_param('dir', 'ASC', PARAM_ALPHA);
$action  = optional_param('action', false, PARAM_ALPHA);
$search  = optional_param('search', '', PARAM_TEXT);

$thispageurl = new moodle_url('/local/augmented_teacher/message_log.php', array(
    'page' => $page,
    'perpage' => $perpage,
    'sort' => $sort,
    'dir' => $dir,
    'search' => $search,
    'id' => $id,
    'cmid' => $cmid
));

$PAGE->set_url($thispageurl);

$reminder = $DB->get_record('local_augmented_teacher_rem', array('id' => $id, 'deleted' => 0), '*', MUST_EXIST);

if ($reminder->messagetype == MESAGE_TYPE_REMINDER) {
    $cm = get_coursemodule_from_id('', $reminder->cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $instance = $DB->get_record($cm->modname, array('id' => $cm->instance));
} else if ($reminder->messagetype == MESAGE_TYPE_NOTLOGGED || $reminder->messagetype == MESAGE_TYPE_RECOMMEND) {
    $course = $DB->get_record('course', array('id' => $reminder->courseid), '*', MUST_EXIST);
}

$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$systemcontext = context_system::instance();

$PAGE->set_pagelayout('incourse');
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-view-' . $course->format);

if ($reminder->messagetype == MESAGE_TYPE_REMINDER) {
    $PAGE->navbar->add(get_string('reminders', 'local_augmented_teacher'),
        new moodle_url('/local/augmented_teacher/reminders.php', array('id' => $cm->id)));
    $PAGE->navbar->add($instance->name);
} else if ($reminder->messagetype == MESAGE_TYPE_NOTLOGGED) {
    $PAGE->navbar->add(get_string('notloggedinreminders', 'local_augmented_teacher'),
        new moodle_url('/local/augmented_teacher/notloggedinreminders.php', array('id' => $course->id)));
} else if ($reminder->messagetype == MESAGE_TYPE_RECOMMEND) {
    $PAGE->navbar->add(get_string('recommendactivity', 'local_augmented_teacher'),
        new moodle_url('/local/augmented_teacher/recommendactivity.php', array('id' => $course->id)));
}
$PAGE->navbar->add(get_string('logs', 'local_augmented_teacher'));

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

// Strings.
$stredit = get_string('edit', 'local_augmented_teacher');
$strdelete = get_string('delete', 'local_augmented_teacher');
$strlog = get_string('log', 'local_augmented_teacher');

$datacolumns = array(
    'id' => 'l.id',
    'remid' => 'l.remid',
    'mid' => 'l.mid',
    'timecreated' => 'l.timecreated',
    'subject' => 'm.subject',
    'fullmessage' => 'm.fullmessage',
    'smallmessage' => 'm.smallmessage',
    'firstname' => 'u.firstname',
    'lastname' => 'u.lastname'
);

if ($CFG->branch >= 35) {
    $datacolumns['useridto'] = '(SELECT mcm.userid FROM mdl_message_conversation_members AS mcm WHERE mcm.conversationid = m.conversationid AND mcm.userid != m.useridfrom)';
} else {
    $datacolumns['useridto'] = 'm.useridto';
}

// Filter.
$filters = array();
$params = array();

$filters[] = $datacolumns['remid'] .' = :remid';
$params['remid'] = $id;

$where = implode(' AND ', $filters);

// Sort.
$order = '';
if ($sort) {
    $order = " ORDER BY $datacolumns[$sort] $dir";
}

// Count records for paging.
$countsql = "SELECT COUNT(1)
               FROM {local_augmented_teacher_log} l
               JOIN {message} m
                 ON l.mid = m.id
               JOIN {user} u
                 ON m.useridto = u.id
              WHERE $where";
$totalcount = $DB->count_records_sql($countsql, $params);

// Table columns.
$columns = array(
    'rowcount',
    'firstname',
    'lastname',
    'subject',
    'smallmessage',
    'timecreated'
);
if ($CFG->branch >= 35) {
    $sql = "SELECT l.id,l.remid,l.mid,l.timecreated,
                   (SELECT mcm.userid FROM mdl_message_conversation_members AS mcm WHERE mcm.conversationid = m.conversationid AND mcm.userid != m.useridfrom) useridto,
                   m.subject,m.fullmessage,m.smallmessage,u.firstname,u.lastname
          FROM {local_augmented_teacher_log} l
          JOIN {messages} m ON l.mid = m.id
          JOIN {user} u ON (SELECT mcm.userid FROM mdl_message_conversation_members AS mcm WHERE mcm.conversationid = m.conversationid AND mcm.userid != m.useridfrom) = u.id
         WHERE $where
               $order";
} else {
    $sql = "SELECT l.id,l.remid,l.mid,l.timecreated,m.useridto,m.subject,m.fullmessage,m.smallmessage,u.firstname,u.lastname
          FROM {local_augmented_teacher_log} l
          JOIN {message} m ON l.mid = m.id
          JOIN {user} u ON m.useridto = u.id
         WHERE $where
               $order";
}

foreach ($columns as $column) {
    if ($column == 'completionexpected') {
        $string[$column] = get_string('completionexpected', 'completion');
    } else {
        $string[$column] = get_string($column, 'local_augmented_teacher');
    }
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "timecreated") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        if ($column == "timecreated") {
            $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = "<img class='iconsort' src=\"" . local_augmented_teacher_pix_url('t/' . $columnicon) . "\" alt=\"\" />";
    }
    if (($column == 'rowcount') || ($column == 'action')) {
        $$column = $string[$column];
    } else {
        $sorturl = $thispageurl;
        $sorturl->param('perpage', $perpage);
        $sorturl->param('sort', $column);
        $sorturl->param('dir', $columndir);
        $sorturl->param('search', $search);

        $$column = html_writer::link($sorturl->out(false), $string[$column]).$columnicon;
    }
}

$table = new html_table();

$table->head = array();
$table->wrap = array();
foreach ($columns as $column) {
    $table->head[$column] = $$column;
    $table->wrap[$column] = '';
}

// Override cell wrap.
$table->wrap['action'] = 'nowrap';

$tablerows = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

$counter = ($page * $perpage);

// Action icons.
$iconlog = local_augmented_teacher_pix_url('t/log');
$iconedit = local_augmented_teacher_pix_url('t/edit');
$icondelete = local_augmented_teacher_pix_url('t/delete');

foreach ($tablerows as $tablerow) {
    $row = new html_table_row();
    $actionlinks = '';
    foreach ($columns as $column) {
        $varname = 'cell'.$column;

        switch ($column) {
            case 'rowcount':
                $$varname = ++$counter;
                break;
            case 'timecreated':
            case 'timemodified':
            case 'scheduled':
                $$varname = '-';
                if ($tablerow->$column > 0) {
                    $$varname = new html_table_cell(date("m/d/Y g:i A", $tablerow->$column));
                    $$varname = new html_table_cell(userdate($tablerow->$column, get_string('strftimedaydatetime')));
                }
                break;
            case 'completionexpected':
                $$varname = '-';
                if ($tablerow->$column > 0) {
                    $$varname = new html_table_cell(userdate($tablerow->$column, get_string('strftimedaydate')));
                }
                break;
            case 'timeinterval':
                $$varname = '-';
                if ($tablerow->$column > 0) {
                    $$varname = new html_table_cell(local_augmented_get_duration_text($tablerow->$column));
                }
                break;
            case 'enabled':
                if ($tablerow->$column == 1) {
                    $$varname = new html_table_cell(get_string('yes', 'local_augmented_teacher'));
                } else {
                    $$varname = new html_table_cell(get_string('no', 'local_augmented_teacher'));
                }
                break;
            case 'type':
                if ($tablerow->$column == REMINDER_BEFORE_DUE) {
                    $$varname = new html_table_cell(get_string('before', 'local_augmented_teacher'));
                } else {
                    $$varname = new html_table_cell(get_string('after', 'local_augmented_teacher'));
                }
                break;
            default:
                $$varname = new html_table_cell($tablerow->$column);
        }
    }

    $row->cells = array();
    foreach ($columns as $column) {
        $varname = 'cell' . $column;
        $row->cells[$column] = $$varname;
    }
    $table->data[] = $row;
}

echo $OUTPUT->header();

if ($reminder->messagetype == MESAGE_TYPE_REMINDER) {
    echo $OUTPUT->heading($instance->name.' - '.get_string('reminders', 'local_augmented_teacher'));
} else if ($reminder->messagetype == MESAGE_TYPE_NOTLOGGED) {
    echo $OUTPUT->heading($course->fullname.' - '.get_string('notloggedinreminders', 'local_augmented_teacher'));
} else if ($reminder->messagetype == MESAGE_TYPE_NOTLOGGED) {
    echo $OUTPUT->heading($course->fullname.' - '.get_string('recommendactivity', 'local_augmented_teacher'));
}

echo html_writer::start_div('page-content-wrapper', array('id' => 'page-content'));

$pagingurl = new moodle_url('/local/augmented_teacher/message_log.php',
    array(
        'perpage' => $perpage,
        'sort' => $sort,
        'dir' => $dir,
        'id' => $id
    )
);

$pagingbar = new paging_bar($totalcount, $page, $perpage, $pagingurl, 'page');

echo html_writer::table($table);
echo $OUTPUT->render($pagingbar);

// Add record form.
if ($reminder->messagetype == MESAGE_TYPE_REMINDER) {
    $formurl = new moodle_url('/local/augmented_teacher/reminders_edit.php', array('cmid' => $cm->id));
} else if ($reminder->messagetype == MESAGE_TYPE_NOTLOGGED) {
    $formurl = new moodle_url('/local/augmented_teacher/notloggedinreminders_edit.php', array('courseid' => $course->id));
} else if ($reminder->messagetype == MESAGE_TYPE_RECOMMEND) {
    $formurl = new moodle_url('/local/augmented_teacher/recommendactivity_edit.php', array('courseid' => $course->id));
}
$submitbutton  = html_writer::tag('button', get_string('addnewreminder', 'local_augmented_teacher'),
    array(
        'class' => 'add-record-btn',
        'type' => 'submit',
        'value' => 'submit'
    )
);
$form = html_writer::tag('form', $submitbutton, array(
    'action' => $formurl->out(),
    'method' => 'post',
    'autocomplete' => 'off'
));
echo html_writer::div($form, 'add-record-btn-wrapper', array('id' => 'add-record-btn'));

echo html_writer::end_div(); // Main wrapper.
echo $OUTPUT->footer();
