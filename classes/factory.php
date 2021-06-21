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
}
