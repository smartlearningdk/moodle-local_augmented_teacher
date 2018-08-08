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

class excluded_users_form extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $customdate = $this->_customdata;
        $mform->addElement('header', '', get_string('excludeusersfromreminders', 'local_augmented_teacher'), '');

        $context = context_course::instance($customdate['courseid'], MUST_EXIST);

        if (!$customdate['userid']) {
            $enrolledusers = get_enrolled_users($context, '', 0, 'u.*', 'u.lastname ASC');

            $sql = "SELECT exc.userid
                      FROM {local_augmented_teacher_exc} exc
                     WHERE exc.courseid = ?";

            $excludedusers =  $DB->get_records_sql($sql, array($customdate['courseid']));

            $enrolleduseroptions = array('0' => 'Select');
            foreach ($enrolledusers as $enrolleduser) {
                if ($excludedusers && in_array($enrolleduser->id, array_keys($excludedusers))) {
                    continue;
                }
                $enrolleduseroptions[$enrolleduser->id] = fullname($enrolleduser);
            }
            $mform->addElement('select', 'userid', get_string('user'), $enrolleduseroptions);
            $mform->addRule('userid', null, 'required', null, 'client');
        } else {
            $user = $DB->get_record('user', array('id' => $customdate['userid']), '*', MUST_EXIST);
            $mform->addElement('static', '_userid', get_string('user'), fullname($user));

            $mform->addElement('hidden', 'userid');
            $mform->setType('userid', PARAM_INT);
        }

        $mform->addElement('date_time_selector', 'timeend', get_string('timeend', 'local_augmented_teacher'), array(
            'optional'  => true
        ));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, get_string('submit', 'local_augmented_teacher'));
    }
}