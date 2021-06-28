<?php
/**
 * @package     local_augmented_teacher
 * @author      Praxis A/S
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright   (C) 2017 SmartLearning Inc https://www.smartlearning.dk
 */

namespace local_augmented_teacher;

use lang_string;

defined('MOODLE_INTERNAL') || die();


/**
 * Class factory
 * @package local_augmented_teacher
 */
class factory
{
    /**
     * @param lang_string|null $lang_string
     * @return message_send_from_course_builder
     */
    public static function get_message_send_from_course_builder($lang_string = null) {
        return new message_send_from_course_builder($lang_string);
    }

    /**
     * @param string $message
     * @param object $course
     * @param null|object $user
     * @return string
     */
    public static function append_message_send_from_course($message, $course, $user = null) {
        try {
            $can_append_message = (bool)get_config(
                'local_augmented_teacher',
                'show_send_from_course_in_message'
            );

            if (!$can_append_message) {
                return $message;
            }

            return self::get_message_send_from_course_builder()->append(
                $message,
                $course,
                $user
            );
        }
        catch (\Exception $exception) {
        }

        return $message;
    }
}
