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

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/local/augmented_teacher/classes/timeselector.php');

class officehours_form extends moodleform {

    /**
     * @var array days
     */
    protected $days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('header', '', get_string('officehours', 'local_augmented_teacher'), '');

        for ($i = 0; $i < 7; $i++) {
            $elementname = 'hour_'.$i;

            $mform->addElement('time_selector', $elementname, get_string($this->days[$i], 'calendar'), array('optional' => true));
        }

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        
        $this->add_action_buttons(true, get_string('submit', 'local_augmented_teacher'));
    }

    /**
     * A bit of custom validation for this form
     *
     * @param array $data An assoc array of field=>value
     * @param array $files An array of files
     * @return array
     */
    function validation($data, $files) {
        $errors = array();

        for ($i = 0; $i < 7; $i++) {
           $elementname = 'hour_'.$i;
            if (isset($data[$elementname])
                && !empty($data[$elementname])
                && $data[$elementname]['from'] >= $data[$elementname]['to']) {
                $errors[$elementname] = get_string('invalidtimeranges', 'local_augmented_teacher');
            }
        }

        return $errors;
    }
}