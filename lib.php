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

    cron_setup_user();
    $time = time();

    $sql = "SELECT rem.*, cm.completionexpected,
                   case rem.type
                       when ".REMINDER_BEFORE_DUE." then cm.completionexpected - rem.timeinterval
                       when ".REMINDER_AFTER_DUE." then cm.completionexpected + rem.timeinterval
                   end as scheduled
              FROM {local_augmented_teacher_rem} rem
              JOIN {course_modules} cm
                ON rem.cmid = cm.id
             WHERE rem.deleted = ?
               AND rem.enabled = ?";

    $rs = $DB->get_recordset_sql($sql, array(0, 1));
    foreach ($rs as $reminder) {
        // Disable unsent reminder after 24 hours.
        if (($time - $reminder->scheduled) >= 24 * 60 * 60) {
            local_augmented_teacher_disable_reminder($reminder->id);
            continue;
        } else if ($reminder->scheduled <= $time) {
            // Send messages.
            if ($cm = get_coursemodule_from_id('', $reminder->cmid)) {
                $course = $DB->get_record('course', array('id' => $cm->course));
                $context = context_course::instance($course->id);
                $instance = $DB->get_record($cm->modname, array('id' => $cm->instance));

                $completion = new completion_info($course);
                if (!$completion->is_enabled($cm) || $cm->completionexpected == 0) {
                    local_augmented_teacher_disable_reminder($reminder->id);
                    continue;
                }
                if (!$userfrom = $DB->get_record('user', array('id' => $reminder->userid, 'deleted' => 0))) {
                    local_augmented_teacher_disable_reminder($reminder->id);
                    continue;
                }

                $users = get_enrolled_users($context, 'local/augmented_teacher:receivereminder');
                $completionrate = local_augmented_teacher_activity_completion_rate($cm, $users);

                foreach ($users as $user) {
                    if (local_augmented_teacher_is_activity_completed($cm, $user)) {
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
                        'firstname' => $user->firstname,
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