<?php

namespace local_augmented_teacher;

defined('MOODLE_INTERNAL') || die();

/**
 * Class reminders_factory
 * @package local_augmented_teacher
 */
class reminders_factory
{
    /**
     * Instantiate a new reminders_repository
     * @return reminders_repository
     */
    public static function get_repository(): reminders_repository {
        return new reminders_repository(self::get_db());
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