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
require_once($CFG->dirroot.'/local/augmented_teacher/lib.php');

$courseid  = optional_param('id', 0, PARAM_INT);

// Paging options.
$page      = optional_param('page', 0, PARAM_INT);
$perpage   = optional_param('perpage', 20, PARAM_INT);
$sort      = optional_param('sort', 'lastname', PARAM_ALPHANUM);
$dir       = optional_param('dir', 'ASC', PARAM_ALPHA);
// Action.
$action    = optional_param('action', false, PARAM_ALPHA);


$thispageurl = new moodle_url('/local/augmented_teacher/excluded_users.php', array(
        'id' => $courseid,
        'page' => $page,
        'perpage' => $perpage,
        'sort' => $sort,
        'dir' => $dir
    )
);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_title("$course->shortname: ".get_string('participants'));
$PAGE->set_heading($course->fullname);

// Breadcrumb.
$PAGE->navbar->add(get_string('excludeusersfromreminders', 'local_augmented_teacher'));

$settingnode = $PAGE->settingsnav->find('local_augmented_teacher', navigation_node::TYPE_SETTING);
$settingnode->make_active();

$enrolledusers = get_enrolled_users($context, '', 0, 'u.id');

list($insql, $params) = $DB->get_in_or_equal(array_keys($enrolledusers), SQL_PARAMS_NAMED, 'usr');

$params['courseid'] = $course->id;
$params['deleted'] = 0;

$datacolumns = array(
    'id' => 'ex.id',
    'firstname' => 'u.firstname',
    'lastname' => 'u.lastname',
    'timeend' => 'ex.timeend'
);
// Filter.
$where = '';

// Sort.
$order = '';
if ($sort) {
    $order = " ORDER BY $datacolumns[$sort] $dir";
}

// Count records for paging.
$countsql = "SELECT COUNT(1) 
               FROM {local_augmented_teacher_exc} ex
               JOIN {user} u 
                 ON ex.userid = u.id
              WHERE ex.courseid = :courseid
                AND u.deleted = :deleted
                AND ex.userid {$insql}
                    $where";

$totalcount = $DB->count_records_sql($countsql, $params);

// Table columns.
$columns = array(
    'rowcount',
    'firstname',
    'lastname',
    'timeend',
    'action'
);

$sql = "SELECT ex.id,
               u.firstname,
               u.lastname,
               ex.timeend
          FROM {local_augmented_teacher_exc} ex
          JOIN {user} u 
            ON ex.userid = u.id
         WHERE ex.courseid = :courseid
           AND u.deleted = :deleted
           AND ex.userid {$insql}
               $where
               $order";

foreach ($columns as $column) {
    $string[$column] = get_string($column, 'local_augmented_teacher');
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "name") {
            $columndir = "ASC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        if ($column == "minpoint") {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->image_url('t/' . $columnicon) . "\" alt=\"\" />";

    }
    if (($column == 'rowcount')
        || ($column == 'action')
    ) {
        $$column = $string[$column];
    } else {
        $sorturl = $thispageurl;
        $sorturl->param('perpage', $perpage);
        $sorturl->param('sort', $column);
        $sorturl->param('dir', $columndir);

        $$column = html_writer::link($sorturl->out(false), $string[$column]).$columnicon;
    }
}

$table = new html_table();

$table->head = array();
$table->wrap = array();
foreach ($columns as $column) {
    $table->head[$column] = $$column;
    $table->wrap[$column] = 'nowrap';
}

// Override cell wrap.
$table->wrap['action'] = 'nowrap';

$tablerows = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

$counter = ($page * $perpage);

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
            case 'timeend':
                $$varname = '-';
                if ($tablerow->$column > 0) {
                    $$varname = new html_table_cell(userdate($tablerow->$column));
                }
                break;
            case 'action':
                $actionurl = new moodle_url('/local/augmented_teacher/excluded_users_edit.php',
                    array('id' => $tablerow->id, 'courseid' => $course->id)
                );
                $actionicontext = get_string('edit', 'local_augmented_teacher');
                $actionicon = html_writer::img($OUTPUT->image_url('i/edit'), $actionicontext,
                    array('class' => 'icon', 'title' => $actionicontext)
                );
                $actionlinks .= html_writer::link(
                    $actionurl->out(false),
                    $actionicon,
                    array(
                        'class' => 'actionlink',
                        'title' => $actionicontext
                    )
                ).' ';

                $actionurl = new moodle_url('/local/augmented_teacher/excluded_users_delete.php',
                    array('id' => $tablerow->id, 'courseid' => $course->id)
                );
                $actionicontext = get_string('delete', 'local_augmented_teacher');
                $actionicon = html_writer::img($OUTPUT->image_url('i/delete'), $actionicontext,
                    array('class' => 'icon', 'title' => $actionicontext)
                );
                $actionlinks .= html_writer::link(
                    $actionurl->out(false),
                    $actionicon,
                    array(
                        'class' => 'actionlink',
                        'title' => $actionicontext
                    )
                );
                $$varname = new html_table_cell($actionlinks);
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
echo html_writer::start_div('fazzi-enrollment-search', array('id' => 'page-content'));
echo html_writer::tag('h1', get_string('excludeusersfromreminders', 'local_augmented_teacher'), array('class' => 'page-title'));


$pagingurl = new moodle_url('/local/augmented_teacher/excluded_users.php',
    array(
        'id' => $course->id,
        'perpage' => $perpage,
        'sort' => $sort,
        'dir' => $dir
    )
);

$pagingbar = new paging_bar($totalcount, $page, $perpage, $pagingurl, 'page');
$formurl = new moodle_url('/local/augmented_teacher/excluded_users.php');

echo $OUTPUT->render($pagingbar);
echo html_writer::table($table);
echo $OUTPUT->render($pagingbar);

// Add record form.
$formurl = new moodle_url('/local/augmented_teacher/excluded_users_edit.php');
$hiddenparams =
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $course->id)).
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'add')).
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
$submitbutton  = html_writer::tag('button', get_string('add', 'local_augmented_teacher'), array(
    'class' => 'btn btn-add-exclusion',
    'type' => 'submit',
    'value' => 'submit',
));
$form = html_writer::tag('form', $hiddenparams.$submitbutton, array(
    'action' => $formurl->out(),
    'method' => 'post',
    'autocomplete' => 'off'
));
echo html_writer::div($form, 'add-exclusion-form-wrapper', array('id' => 'add-record-btn'));

echo html_writer::end_div(); // Main wrapper.
echo $OUTPUT->footer();
