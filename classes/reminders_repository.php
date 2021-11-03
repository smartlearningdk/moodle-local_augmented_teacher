<?php

namespace local_augmented_teacher;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_enrolment_repository
 * @package local_augmented_teacher
 */
class reminders_repository
{
    private \moodle_database $db;

    /**
     * Constructor
     * @param \moodle_database $DB
     */
    public function __construct(\moodle_database $DB) {
        $this->db = $DB;
    }

    /**
     * Returns activity reminders
     * @return array
     * @throws \dml_exception
     */
    public function get_activity_reminders(): array {
        $sql = "SELECT rem.*, cm.completionexpected, cm.course AS courseid,
                   case rem.type
                       when ".REMINDER_BEFORE_DUE." then cm.completionexpected - rem.timeinterval
                       when ".REMINDER_AFTER_DUE." then cm.completionexpected + rem.timeinterval
                   end as scheduled
              FROM {local_augmented_teacher_rem} rem
              JOIN {course_modules} cm
                ON rem.cmid = cm.id
             WHERE rem.messagetype = :message_type
               AND rem.enabled = 1
               AND rem.deleted = 0";
        $sql_params = ['message_type' => MESAGE_TYPE_REMINDER];

        return $this->db->get_records_sql($sql, $sql_params);
    }

    /**
     * Returns not logged in reminders
     * @return array
     * @throws \dml_exception
     */
    public function get_notloggedin_reminders(): array {
        $sql = "SELECT rem.id,
                   rem.userid, 
                   rem.courseid, 
                   rem.title, 
                   rem.message, 
                   rem.timeinterval
              FROM {local_augmented_teacher_rem} rem
              JOIN {course} c 
                ON rem.courseid = c.id
             WHERE rem.messagetype = :message_type
               AND rem.enabled = 1
               AND rem.deleted = 0";
        $sql_params = ['message_type' => MESAGE_TYPE_NOTLOGGED];

        return $this->db->get_records_sql($sql, $sql_params);
    }

    /**
     * Returns activity recommendation reminders
     * @return array
     * @throws \dml_exception
     */
    public function get_activity_recommendation_reminders(): array {
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
             WHERE rem.messagetype = :message_type
               AND rem.enabled = 1
               AND rem.deleted = 0";
        $sql_params = ['message_type' => MESAGE_TYPE_RECOMMEND];

        return $this->db->get_records_sql($sql, $sql_params);
    }
}