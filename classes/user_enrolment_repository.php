<?php

namespace local_augmented_teacher;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_enrolment_repository
 * @package local_augmented_teacher
 */
class user_enrolment_repository
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
     * Get user enrolments by course ids
     * @param array $course_ids
     * @return array
     * @throws \dml_exception
     */
    public function get_by_course_ids(array $course_ids): array {
        if (empty($course_ids)) {
            return [];
        }

        [$in_sql, $params] = $this->db->get_in_or_equal($course_ids);

        $sql = 'SELECT id, status, userid
                FROM {user_enrolments}
                WHERE enrolid IN (SELECT id
                                  FROM {enrol}
                                  WHERE courseid ' . $in_sql . ')';

        return $this->db->get_records_sql($sql, $params);
    }

    /**
     * Get user enrolments by reminders
     * @param array $reminders
     * @return array
     * @throws \dml_exception
     */
    public function get_from_reminders(array $reminders): array {
        $course_ids = [];
        foreach ($reminders as $reminder) {
            $course_ids[] = $reminder->courseid;
        }

        return $this->get_by_course_ids($course_ids);
    }
}