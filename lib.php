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

defined('MOODLE_INTERNAL') || die();

define('REMINDER_BEFORE_DUE', 1);
define('REMINDER_AFTER_DUE', 2);

define('MESAGE_TYPE_REMINDER', 1);
define('MESAGE_TYPE_NOTLOGGED', 2);
define('MESAGE_TYPE_RECOMMEND', 3);

function local_augmented_teacher_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;

    if ($PAGE->course->id == SITEID) {
        return;
    }

    if (has_capability('moodle/course:bulkmessaging', $PAGE->context)) {
        if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {

            $keys = $settingnode->get_children_key_list();
            $beforekey = $keys[0];

            $node = navigation_node::create(get_string('pluginname', 'local_augmented_teacher'),
                new moodle_url('/local/augmented_teacher/index.php', array('id' => $PAGE->course->id)),
                navigation_node::TYPE_SETTING, null, 'local_augmented_teacher',
                new pix_icon('i/settings', ''));
            $settingnode->add_node($node, $beforekey);
        }
    }
    return;
}

/**
 * Converts seconds to some more user friendly string.
 * @static
 * @param int $seconds
 * @return string
 */
function local_augmented_get_duration_text($seconds) {
    if (empty($seconds)) {
        return get_string('none');
    }
    $data = local_augmented_parse_seconds($seconds);
    switch ($data['u']) {
        case (60 * 60 * 24 * 7):
            return get_string('numweeks', '', $data['v']);
        case (60 * 60 * 24):
            return get_string('numdays', '', $data['v']);
        case (60 * 60):
            return get_string('numhours', '', $data['v']);
        case (60):
            return get_string('numminutes', '', $data['v']);
        default:
            return get_string('numseconds', '', $data['v'] * $data['u']);
    }
}

/**
 * Finds suitable units for given duration.
 * @static
 * @param int $seconds
 * @return array
 */
function local_augmented_parse_seconds($seconds) {
    foreach (local_augmented_get_units() as $unit => $unused) {
        if ($seconds % $unit === 0) {
            return array('v' => (int)($seconds / $unit), 'u' => $unit);
        }
    }
    return array('v' => (int)$seconds, 'u' => 1);
}

function local_augmented_get_units() {
    return array(
        604800 => get_string('weeks'),
        86400 => get_string('days'),
        3600 => get_string('hours'),
        60 => get_string('minutes'),
        1 => get_string('seconds'),
    );
}

function local_augmented_teacher_send_reminder_message() {
    global $CFG, $DB;

    if (!isset($CFG->enablecompletion) || $CFG->enablecompletion == COMPLETION_DISABLED) {
        return;
    }

    cron_setup_user(null, null, true);

    $sql = "SELECT rem.*, cm.completionexpected,
                   case rem.type
                       when ".REMINDER_BEFORE_DUE." then cm.completionexpected - rem.timeinterval
                       when ".REMINDER_AFTER_DUE." then cm.completionexpected + rem.timeinterval
                   end as scheduled
              FROM {local_augmented_teacher_rem} rem
              JOIN {course_modules} cm
                ON rem.cmid = cm.id
             WHERE rem.deleted = ?
               AND rem.enabled = ?
               AND rem.messagetype = ?";

    $rs = $DB->get_recordset_sql($sql, array(0, 1, MESAGE_TYPE_REMINDER));
    foreach ($rs as $reminder) {

        $time = time();

        if ($reminder->scheduled <= $time) {
            // Send messages.
            if ($cm = get_coursemodule_from_id('', $reminder->cmid)) {
                $reminderobj = new local_augmented_teacher\reminder($reminder->id, $cm->course);
                $course = $reminderobj->course;
                $context = context_course::instance($course->id);
                $instance = $DB->get_record($cm->modname, array('id' => $cm->instance));

                // Disable unsent reminder after 24 hours.
                if (($time - $reminder->scheduled) >= 24 * 60 * 60) {
                    $reminderobj->disable_reminder();
                    continue;
                }

                // Course is expired.
                if ($reminderobj->is_course_expired($time)) {
                    $reminderobj->disable_reminder();
                    continue;
                }

                // Office hours.
                if (!$reminderobj->is_in_officehours($time)) {
                    continue;
                }

                // Quarters.
                if (!$reminderobj->is_allowed_time($time)) {
                    continue;
                }

                // Paused.
                if ($reminderobj->is_paused()) {
                    continue;
                }

                $completion = new completion_info($course);
                if (!$completion->is_enabled($cm) || $cm->completionexpected == 0) {
                    $reminderobj->disable_reminder();
                    continue;
                }
                if (!$userfrom = $DB->get_record('user', array('id' => $reminder->userid, 'deleted' => 0))) {
                    $reminderobj->disable_reminder();
                    continue;
                }

                $users = get_enrolled_users($context, 'local/augmented_teacher:receivereminder');

                $completionrate = local_augmented_teacher_activity_completion_rate($cm, $users);

                foreach ($users as $user) {
                    if ($user->suspended
                        || local_augmented_is_excluded($user->id, $course->id)
                        || local_augmented_teacher_is_activity_completed($cm, $user)) {
                        continue;
                    }
                    $search = array(
                        'activityname' => '{{activityname}}',
                        'lastname' => '{{lastname}}',
                        'firstname' => '{{firstname}}',
                        'coursename' => '{{coursename}}',
                        'completionrate' => '{{completionrate}}'
                    );

                    $replace = array(
                        'activityname' => $instance->name,
                        'lastname' => $user->lastname,
                        'firstname' => local_augmented_get_first_firstname($user->firstname),
                        'coursename' => $course->fullname,
                        'completionrate' => $completionrate.'%'
                    );

                    $message = str_replace($search, $replace, $reminder->message);
                    $message = text_to_html($message);
                    $message = html_entity_decode($message);
                    if ($mid = message_post_message($userfrom, $user, $message, FORMAT_HTML)) {
                        $log = new stdClass();
                        $log->userid = $user->id;
                        $log->remid = $reminder->id;
                        $log->mid = $mid;
                        $log->timecreated = time();
                        $DB->insert_record('local_augmented_teacher_log', $log);
                    }
                }
            }
            // Disable reminder.
            local_augmented_teacher_disable_reminder($reminder->id);
        }
    }
    $rs->close();
}

function local_augmented_teacher_send_notloggedin_reminder_message() {
    global $CFG, $DB;

    $notloggedinlast = get_config('local_augmented_teacher', 'notloggedinlast');
    $notloggedinhour = get_config('local_augmented_teacher', 'notloggedinhour');
    if (is_null($notloggedinhour)) {
        debugging("local_augmented_teacher_send_notloggedin_reminder_message() needs notloggedinhour setting");
        return;
    }

    $timenow = time();
    $notifytime = usergetmidnight($timenow, $CFG->timezone) + ($notloggedinhour * 3600);

    if ($notloggedinlast > $notifytime) {
        mtrace('Not logged in reminders were already sent today at '.userdate($notloggedinlast, '', $CFG->timezone).'.');
        return;
    } else if ($timenow < $notifytime) {
        mtrace('Not logged in reminders will be sent at '.userdate($notifytime, '', $CFG->timezone).'.');
        return;
    }

    mtrace('Processing not logged in reminders...');

    cron_setup_user(null, null, true);

    $sql = "SELECT rem.id,
                   rem.userid, 
                   rem.courseid, 
                   rem.title, 
                   rem.message, 
                   rem.timeinterval
              FROM {local_augmented_teacher_rem} rem
              JOIN {course} c 
                ON rem.courseid = c.id
             WHERE rem.messagetype = ?
               AND rem.enabled = 1
               AND rem.deleted = 0";

    $rs = $DB->get_recordset_sql($sql, array(MESAGE_TYPE_NOTLOGGED));
    foreach ($rs as $reminder) {

        $time = time();

         if ($reminder->timeinterval) {
             $reminderobj = new local_augmented_teacher\reminder($reminder->id);
            // Send messages.
            if ($course = $reminderobj->course) {

                $context = context_course::instance($course->id);

                if (!$userfrom = $reminderobj->sender) {
                    continue;
                }

                // Course is expired.
                if ($reminderobj->is_course_expired($time)) {
                    $reminderobj->disable_reminder();
                    continue;
                }

                // Office hours.
                if (!$reminderobj->is_in_officehours($time)) {
                    continue;
                }

                // Quarters.
                if (!$reminderobj->is_allowed_time($time)) {
                    continue;
                }

                // Paused.
                if ($reminderobj->is_paused()) {
                    continue;
                }

                $users = get_enrolled_users($context, 'local/augmented_teacher:receivereminder');

                foreach ($users as $user) {
                    if ($user->suspended || local_augmented_is_excluded($user->id, $course->id)) {
                        continue;
                    }
                    if ($timenow < $reminder->timeinterval + $user->lastaccess) {
                        continue;
                    }

                    $search = array(
                        'lastname' => '{{lastname}}',
                        'firstname' => '{{firstname}}',
                        'lastlogindate' => '{{lastlogindate}}',
                    );

                    $replace = array(
                        'lastname' => $user->lastname,
                        'firstname' => local_augmented_get_first_firstname($user->firstname),
                        'lastlogindate' => userdate($user->lastaccess)
                    );

                    $message = str_replace($search, $replace, $reminder->message);
                    $message = text_to_html($message);
                    $message = html_entity_decode($message);
                    if ($mid = message_post_message($userfrom, $user, $message, FORMAT_HTML)) {
                        $log = new stdClass();
                        $log->userid = $user->id;
                        $log->remid = $reminder->id;
                        $log->mid = $mid;
                        $log->timecreated = time();
                        $DB->insert_record('local_augmented_teacher_log', $log);
                    }
                }
            }
        }
    }
    $rs->close();

    set_config('notloggedinlast', $timenow, 'local_augmented_teacher');
}

function local_augmented_teacher_send_activity_recommendation() {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/lib/completionlib.php');

    $recommendactivitylast = get_config('local_augmented_teacher', 'recommendactivitylast');
    $recommendactivityhour = get_config('local_augmented_teacher', 'recommendactivityhour');
    if (is_null($recommendactivityhour)) {
        debugging("local_augmented_teacher_send_activity_recommendation() needs recommendactivityhour setting");
        return;
    }

    $timenow = time();
    $notifytime = usergetmidnight($timenow, $CFG->timezone) + ($recommendactivityhour * 3600);

    if ($recommendactivitylast > $notifytime) {
        mtrace('Recommended activity messages were already sent today at '.userdate($recommendactivitylast, '', $CFG->timezone).'.');
        return;
    } else if ($timenow < $notifytime) {
        mtrace('Recommended activity messages will be sent at '.userdate($notifytime, '', $CFG->timezone).'.');
        return;
    }

    mtrace('Processing recommended activity messages...');

    cron_setup_user(null, null, true);

    $sql = "SELECT rem.id,
                   rem.userid, 
                   rem.courseid, 
                   rem.title, 
                   rem.message, 
                   rem.cmid, 
                   rem.cmid2, 
                   rem.timeinterval
              FROM {local_augmented_teacher_rem} rem
              JOIN {course} c 
                ON rem.courseid = c.id
             WHERE rem.messagetype = ?
               AND rem.enabled = 1
               AND rem.deleted = 0";

    $rs = $DB->get_recordset_sql($sql, array(MESAGE_TYPE_RECOMMEND));

    foreach ($rs as $reminder) {

        $time = time();

         if ($reminder->timeinterval) {
             $cm = get_coursemodule_from_id('', $reminder->cmid);
             $reminderobj = new local_augmented_teacher\reminder($reminder->id, $cm->course);
             $course = $reminderobj->course;
             $cm2 = get_coursemodule_from_id('', $reminder->cmid2);
             $course2 = $DB->get_record('course', array('id' => $cm2->course));

             if ($cm && $cm2 && $course && $course2 && $course->id == $reminder->courseid) {
                 $context = context_course::instance($course->id);

                 if (!$userfrom = $reminderobj->sender) {
                     continue;
                 }

                 // Course is expired.
                 if ($reminderobj->is_course_expired($time)) {
                     $reminderobj->disable_reminder();
                     continue;
                 }

                 // Office hours.
                 if (!$reminderobj->is_in_officehours($time)) {
                     continue;
                 }

                 // Quarters.
                 if (!$reminderobj->is_allowed_time($time)) {
                     continue;
                 }

                 // Paused.
                 if ($reminderobj->is_paused()) {
                     continue;
                 }

                 $users = get_enrolled_users($context, 'local/augmented_teacher:receivereminder');
                 list($insql, $params) = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'u');

                 $params['cmid'] = $cm->id;
                 $params['timestart'] = usergetmidnight(($timenow - $reminder->timeinterval), $CFG->timezone);
                 $params['timeend']  =$params['timestart'] + (86400-1);

                 $sql = "SELECT cmc.id, cmc.coursemoduleid, cmc.userid,
                                cmc.completionstate, cmc.viewed, cmc.overrideby,
                                cmc.timemodified
                           FROM {course_modules_completion} cmc
                          WHERE cmc.coursemoduleid = :cmid
                            AND cmc.userid {$insql}
                            AND cmc.timemodified > :timestart
                            AND cmc.timemodified < :timeend
                            AND cmc.completionstate IN (".COMPLETION_COMPLETE.", ".COMPLETION_COMPLETE_PASS.", ".COMPLETION_COMPLETE_FAIL.")";

                 $rs2 = $DB->get_recordset_sql($sql, $params);

                 foreach ($rs2 as $completion) {
                     if (local_augmented_is_excluded($completion->userid, $course->id)) {
                         continue;
                     }

                     $user = $DB->get_record('user', array('id' => $completion->userid));

                     if ($user->suspended) {
                         continue;
                     }

                     $search = array(
                         'lastname' => '{{lastname}}',
                         'firstname' => '{{firstname}}',
                         'coursename' => '{{coursename}}',
                         'completiondate' => '{{completiondate}}',
                         'activity' => '{{activity}}',
                         'recommendedactivity' => '{{recommendedactivity}}',
                     );

                     $replace = array(
                         'lastname' => $user->lastname,
                         'firstname' => local_augmented_get_first_firstname($user->firstname),
                         'coursename' => $course->fullname,
                         'completiondate' => userdate($completion->timemodified, '', $CFG->timezone),
                         'activity' => html_writer::link(new moodle_url('/mod/' . $cm->modname . '/view.php', array('id' => $cm->id)), $cm->name),
                         'recommendedactivity' => html_writer::link(new moodle_url('/mod/' . $cm2->modname . '/view.php', array('id' => $cm2->id)), $cm2->name),
                     );

                     $message = str_replace($search, $replace, $reminder->message);
                     $message = text_to_html($message);
                     $message = html_entity_decode($message);
                     if ($mid = message_post_message($userfrom, $user, $message, FORMAT_HTML)) {
                         $log = new stdClass();
                         $log->userid = $user->id;
                         $log->remid = $reminder->id;
                         $log->mid = $mid;
                         $log->timecreated = time();
                         $DB->insert_record('local_augmented_teacher_log', $log);
                     }
                 }
                 $rs2->close();
            }
        }
    }
    $rs->close();

    set_config('recommendactivitylast', $timenow, 'local_augmented_teacher');
}

function local_augmented_teacher_disable_reminder($remid) {
    global $DB;
    $rec = new stdClass();
    $rec->id = $remid;
    $rec->enabled = 0;
    $DB->update_record('local_augmented_teacher_rem', $rec);
}

function local_augmented_teacher_activity_completion_rate($cm, $users) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/lib/completionlib.php');

    $numoftotalusers = count($users);

    if ($numoftotalusers == 0) {
        return 0;
    }

    list($userids, $params) = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'usr');
    list($completions, $params2) = $DB->get_in_or_equal(
        array(
            COMPLETION_COMPLETE,
            COMPLETION_COMPLETE_PASS,
            COMPLETION_COMPLETE_FAIL), SQL_PARAMS_NAMED, 'cmp');

    $params = $params + $params2;
    $params['cmid'] = $cm->id;

    $sql = "SELECT COUNT(1)
              FROM {course_modules_completion} comp
             WHERE comp.coursemoduleid = :cmid
               AND comp.completionstate {$completions}
               AND comp.userid {$userids}";
    $numofcompletedusers = $DB->count_records_sql($sql, $params);

    return round((($numofcompletedusers / $numoftotalusers) * 100), 0);
}

function local_augmented_teacher_is_activity_completed($cm, $user) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/lib/completionlib.php');

    list($completions, $params) = $DB->get_in_or_equal(
        array(
            COMPLETION_COMPLETE,
            COMPLETION_COMPLETE_PASS,
            COMPLETION_COMPLETE_FAIL
        ), SQL_PARAMS_NAMED, 'cmp');

    $params['cmid'] = $cm->id;
    $params['userid'] = $user->id;

    $sql = "SELECT 1
              FROM {course_modules_completion} comp
             WHERE comp.coursemoduleid = :cmid
               AND comp.completionstate {$completions}
               AND comp.userid = :userid";

    return $DB->record_exists_sql($sql, $params);
}

function local_augmented_teacher_pix_url($imagename, $component=null) {
    global $CFG, $OUTPUT;
    if ($CFG->branch >= 33) { // MDL 3.3+.
        return $OUTPUT->image_url($imagename, $component);
    } else {
        return $OUTPUT->pix_url($imagename, $component);
    }
}

function local_augmented_get_first_firstname($firstname) {
    $names = explode(' ', trim($firstname));
    return reset($names);
}

function local_augmented_get_excluded_users($courseid) {
    global $DB;

    $sql = "SELECT exc.userid
              FROM {local_augmented_teacher_exc} exc
              JOIN {user} u 
                ON exc.userid = u.id
             WHERE exc.courseid = ? 
               AND (exc.timeend > ? OR exc.timeend = 0)
               AND u.deleted = 0";

    return $DB->get_records_sql($sql, array($courseid, time()));
}

function local_augmented_is_excluded($userid, $courseid) {
    global $DB;

    $sql = "SELECT exc.userid
              FROM {local_augmented_teacher_exc} exc
              JOIN {user} u 
                ON exc.userid = u.id
             WHERE exc.userid = ? 
               AND exc.courseid = ? 
               AND (exc.timeend > ? OR exc.timeend = 0)
               AND u.deleted = 0";

    return $DB->record_exists_sql($sql, array($userid, $courseid, time()));
}

function local_augmented_get_activity_list_options($courseid) {
    global $USER;

    $activitylist = array('' => get_string('select', 'local_augmented_teacher'));

    $modinfo = get_fast_modinfo($courseid, $USER->id);
    $activities = $modinfo->get_cms();

    foreach ($activities as $key => $mod) {
        $modulecontext = context_module::instance($mod->id);
        if (!$mod->visible && !has_capability('moodle/course:viewhiddenactivities', $modulecontext)) {
            unset($activities[$key]);
            continue;
        }
        if ($mod->completion == COMPLETION_TRACKING_NONE) {
            unset($activities[$key]);
            continue;
        }

        $activitylist[$mod->id] = $mod->name;
    }

    return $activitylist;
}

/**
 * Prints a basic textarea field.
 *
 * When using this function, you should
 *
 * @global object
 * @param bool $unused No longer used.
 * @param int $rows Number of rows to display  (minimum of 10 when $height is non-null)
 * @param int $cols Number of columns to display (minimum of 65 when $width is non-null)
 * @param null $width (Deprecated) Width of the element; if a value is passed, the minimum value for $cols will be 65. Value is otherwise ignored.
 * @param null $height (Deprecated) Height of the element; if a value is passe, the minimum value for $rows will be 10. Value is otherwise ignored.
 * @param string $name Name to use for the textarea element.
 * @param string $value Initial content to display in the textarea.
 * @param int $obsolete deprecated
 * @param bool $return If false, will output string. If true, will return string value.
 * @param string $id CSS ID to add to the textarea element.
 * @return string|void depending on the value of $return
 */
function local_augmented_print_textarea($unused, $rows, $cols, $width, $height, $name, $value='', $obsolete=0, $return=false, $id='') {
    /// $width and height are legacy fields and no longer used as pixels like they used to be.
    /// However, you can set them to zero to override the mincols and minrows values below.

    global $OUTPUT;

    $mincols = 65;
    $minrows = 10;

    if ($id === '') {
        $id = 'edit-'.$name;
    }

    if ($height && ($rows < $minrows)) {
        $rows = $minrows;
    }
    if ($width && ($cols < $mincols)) {
        $cols = $mincols;
    }

    $textarea = $OUTPUT->print_textarea($name, $id, $value, $rows, $cols);
    if ($return) {
        return $textarea;
    }

    echo $textarea;
}