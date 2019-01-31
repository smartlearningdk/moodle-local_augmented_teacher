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

namespace local_augmented_teacher;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/augmented_teacher/lib.php');

/**
 * Class reminder
 * @package local_augmented_teacher
 */
class reminder {

    /**
     * @var
     */
    protected $id;
    /**
     * @var mixed
     */
    protected $instance;
    /**
     * @var mixed
     */
    protected $course;

    /**
     * @var mixed|null
     */
    protected $sender = null;

    /**
     * reminder constructor.
     * @param $id
     * @param null $courseid
     * @throws \dml_exception
     */
    public function __construct($id, $courseid=null) {
        global $DB;

        $rem = $DB->get_record('local_augmented_teacher_rem', array('id' => $id), '*', MUST_EXIST);

        if (!$courseid) {
            $courseid = $rem->courseid;
        }

        $course = $DB->get_record('course', array('id' => $courseid));

        $this->id = $id;
        $this->instance = $rem;
        $this->course = $course;
        if ($sender = $DB->get_record('user', array('id' => $rem->userid, 'deleted' => 0))) {
            $this->sender = $sender;
        }
    }

    /**
     * Magic Method to retrieve something by simply calling using = obj->key
     *
     * @param mixed $key The identifier for the information you want out again
     * @return mixed Either void or what ever was put in
     */
    public function __get($key) {
        if ($this->$key) {
            return $this->$key;
        }
    }

    /**
     * @throws \dml_exception
     */
    public function disable_reminder() {
        global $DB;
        $rec = new \stdClass();
        $rec->id = $this->id;
        $rec->enabled = 0;
        $DB->update_record('local_augmented_teacher_rem', $rec);
    }

    /**
     * @param null $time
     * @return bool
     */
    public function is_course_expired($time=null) {
        if (!$time) {
            $time = time();
        }
        if ($this->course->enddate && $this->course->enddate < $time) {
            return true;
        }
        return false;
    }

    /**
     * @param null $time
     * @return array|bool
     * @throws \dml_exception
     */
    public function get_sender_hours($time=null) {
        global $DB;

        if (!$this->sender) {
            return false;
        }

        if (!$time) {
            $time = time();
        }

        $day = (int) date('w', $time);

        if ($officehour = $DB->get_record('local_augmented_teacher_ofh',
            array('userid' => $this->sender->id, 'dayofweek' => $day))) {
            $midnight = usergetmidnight($time);
            return(array(($midnight + $officehour->timestart),  ($midnight + $officehour->timeend)));
        }
         return (array(0, 0));
    }

    /**
     * @param null $time
     * @return bool
     * @throws \dml_exception
     */
    public function is_in_officehours($time=null) {
        if (!$time) {
            $time = time();
        }

        list($officestart, $officeend) = $this->get_sender_hours($time);

        if ($officestart == 0 && $officeend == 0) {
            return true;
        }

        if ($officestart && $officeend && $officestart < $time && $time < $officeend) {
            return true;
        }
        return false;
    }

    /**
     * @param null $time
     * @return bool
     * @throws \dml_exception
     */
    public function is_allowed_time($time=null) {
        if (!$time) {
            $time = time();
        }
        $prohibited = array(0, 15, 30, 45);
        $minute = (int) date('i', $time);
        if (in_array($minute, $prohibited)) {
            return false;
        }
        return true;
    }

    /**
     * @param null $time
     * @return bool
     * @throws \dml_exception
     */
    public function is_paused($time=null) {
        global $DB;

        if (!$time) {
            $time = time();
        }

        if ($stopper = $DB->get_record('local_augmented_teacher_stp', array('courseid' => $this->course->id))) {
            if ($stopper->timeresume && $stopper->timestop < $time && $time < $stopper->timeresume) {
                return true;
            }
        }
        return false;
    }
}