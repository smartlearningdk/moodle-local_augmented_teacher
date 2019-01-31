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

class coursenotificationsettings_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('header', '', get_string('coursenotificationsettings', 'local_augmented_teacher'), '');

        $mform->addElement('date_time_selector', 'timestop', get_string('timestop', 'local_augmented_teacher'), array(
            'optional'  => true
        ));

        $mform->addElement('date_time_selector', 'timeresume', get_string('timeresume', 'local_augmented_teacher'), array(
            'optional'  => true
        ));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'formaction');
        $mform->setType('formaction', PARAM_URL);

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
        
        $errors = parent::validation($data, $files);

        if (!empty($data['timeresume']) || !empty($data['timestop'])) {
            if ($data['timeresume'] <= $data['timestop']) {
                $errors['timeresume'] = get_string('resumelessorequaltostop', 'local_augmented_teacher');
            }
        }

        return $errors;
    }
}