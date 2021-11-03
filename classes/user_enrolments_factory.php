<?php

namespace local_augmented_teacher;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_enrolments_factory
 * @package local_augmented_teacher
 */
class user_enrolments_factory
{
    /**
     * Instantiate a new user_enrolment_repository
     * @return user_enrolment_repository
     */
    public static function get_repository(): user_enrolment_repository {
        return new user_enrolment_repository(self::get_db());
    }

    /**
     * Get DB
     * @return \moodle_database
     */
    public static function get_db(): \moodle_database {
        global $DB;

        return $DB;
    }
}