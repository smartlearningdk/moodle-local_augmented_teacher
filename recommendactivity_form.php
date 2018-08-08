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

class reminder_form extends moodleform {
    public function definition() {

        $mform = $this->_form;
        $customdata = $this->_customdata;
        $mform->addElement('header', '', get_string('notloggedinreminder', 'local_augmented_teacher'), '');

        $mform->addElement('text', 'title', get_string('title', 'local_augmented_teacher'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('editor', 'message', get_string('message', 'local_augmented_teacher'));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', null, 'required', null, 'client');

        if ($customdata['editorplugin'] == 'atto_texteditor') {
            $mform->addElement('static', 'shortcodes', get_string('shortcodes', 'local_augmented_teacher'),
                '<button class="shortcode">{{firstname}}</button> ' .
                '<button class="shortcode">{{lastname}}</button> ' .
                '<button class="shortcode">{{coursename}}</button> ' .
                '<button class="shortcode">{{completiondate}}</button> ' .
                '<button class="shortcode">{{activity}}</button> ' .
                '<button class="shortcode">{{recommendedactivity}}</button>');
        } else {
            $mform->addElement('static', 'shortcodes', get_string('shortcodes', 'local_augmented_teacher'),
                '{{firstname}} {{lastname}} {{coursename}} {{completiondate}} {{activity}} {{recommendedactivity}}');
        }
        $mform->addHelpButton('shortcodes', 'shortcodes', 'local_augmented_teacher');

        $mform->addElement('selectyesno', 'enabled', get_string('enabled', 'local_augmented_teacher'));
        $mform->addRule('enabled', null, 'required', null, 'client');
        $mform->setDefault('enabled', '1');

        $activityoptions = local_augmented_get_activity_list_options($customdata['courseid']);

        $mform->addElement('select', 'cmid', get_string('activity', 'local_augmented_teacher'), $activityoptions);
        $mform->addRule('cmid', null, 'required', null, 'client');

        $mform->addElement('select', 'cmid2', get_string('recommendedactivity', 'local_augmented_teacher'), $activityoptions);
        $mform->addRule('cmid2', null, 'required', null, 'client');

        $options = array('optional' => false, 'defaultunit' => 86400);
        $mform->addElement('duration', 'timeinterval', get_string('timeinterval', 'local_augmented_teacher'), $options);
        $mform->addRule('timeinterval', null, 'required', null, 'client');
        $mform->addHelpButton('timeinterval', 'duration', 'local_augmented_teacher');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, get_string('submit', 'local_augmented_teacher'));
    }

    public function validation($data, $files) {
        $errors = array();

        if ($data['cmid'] == $data['cmid2']) {
            $errors['cmid2'] = get_string('cannotbesame', 'local_augmented_teacher');
        }
        if ($data['timeinterval'] < 86400) {
            $errors['timeinterval'] = get_string('durationlessthanday', 'local_augmented_teacher');
        }
        return $errors;
    }
}