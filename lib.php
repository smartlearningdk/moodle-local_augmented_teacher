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

function local_augmented_teacher_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;

    if ($PAGE->course->id == SITEID) {
        return;
    }

    if (has_capability('moodle/course:bulkmessaging', $PAGE->context)) {
        if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {

            $keys = $settingnode->get_children_key_list();
            $beforekey = $keys[0];

            $node = navigation_node::create(get_string('pluginname', 'local_augmented_teacher'),
                new moodle_url('/local/augmented_teacher/index.php', array('id' => $PAGE->course->id)),
                navigation_node::TYPE_SETTING, null, 'local_augmented_teacher',
                new pix_icon('i/settings', ''));
            $settingnode->add_node($node, $beforekey);
        }
    }
    return;
}