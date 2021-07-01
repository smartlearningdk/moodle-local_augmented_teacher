<?php
/**
 * @package     local_augmented_teacher
 * @author      Praxis A/S
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright   (C) 2017 SmartLearning Inc https://www.smartlearning.dk
 */

namespace local_augmented_teacher;

use coding_exception;
use dml_exception;
use Exception;
use InvalidArgumentException;
use lang_string;
use moodle_database;
use moodle_exception;
use moodle_url;
use UnexpectedValueException;

defined('MOODLE_INTERNAL') || die();


/**
 * Class message_send_from_course_builder
 * @package local_augmented_teacher
 */
class message_send_from_course_builder
{
    private $lang_string;

    /**
     * message_send_from_course_builder constructor.
     * @param lang_string|null $lang_string
     */
    public function __construct($lang_string = null) {
        $this->lang_string = $lang_string;
    }

    /**
     * @param string $message
     * @param object $course
     * @param object|null $user
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function append($message, $course, $user = null) {

        if ($user !== null) {
            try {
                return $message . $this->get_send_from_course_text($course, $this->get_user_language($user));
            }
            catch (Exception $exception) {
                // Skip and use moodle default language
            }
        }

        return $message . $this->get_send_from_course_text($course);
    }

    /**
     * @param object $course
     * @param string|null $lang
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_send_from_course_text($course, $lang = null) {
        if ($this->lang_string === null) {
            $this->lang_string = new lang_string(
                'sendfromcourse',
                'local_augmented_teacher',
                $this->get_course_instance($course)
            );
        }
        return $this->lang_string->out($lang);
    }

    /**
     * @param object $user
     * @return string
     * @throws dml_exception
     */
    private function get_user_language($user) {

        if (!empty($user->lang)) {
            return $user->lang;
        }

        if (!isset($user->id)) {
            throw new InvalidArgumentException('User instance is missing the id property');
        }

        $lang = $this->db()->get_field(
            'user',
            'lang',
            ['id' => $user->id]
        );

        if (!$lang) {
            throw new UnexpectedValueException("User had not yet set the language in the profile");
        }

        return $lang;
    }

    /**
     * @param object $course
     * @return object
     * @throws moodle_exception
     */
    private function get_course_instance($course) {

        $course_id = $course->id ?? 0;
        $url = $this->get_course_url($course_id);

        return (object)[
            'link' => '<a href="'. $url .'">'. $course->fullname .'</a>',
            'url' => $url,
            'id' => (int)$course_id,
            'fullname' => $course->fullname ?? '',
            'shortname' => $course->shortname ?? '',
        ];
    }

    /**
     * @param int $course_id
     * @return string
     * @throws moodle_exception
     */
    private function get_course_url($course_id) {
        $url = new moodle_url('/course/view.php', ['id' => $course_id]);
        return $url->out(false);
    }

    /**
     * @return moodle_database
     */
    private function db() {
        global $DB;
        return $DB;
    }
}
