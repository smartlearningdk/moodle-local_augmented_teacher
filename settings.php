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

$settings = new admin_settingpage('local_augmented_teacher_settings',  get_string('pluginname', 'local_augmented_teacher'));

$options = array();
for ($i = 0; $i < 24; $i++) {
    $options[$i] = $i;
}
$settings->add(new admin_setting_configselect('local_augmented_teacher/notloggedinhour',
    get_string('notloggedinhour', 'local_augmented_teacher'), '', 6, $options)
);
$settings->add(new admin_setting_configselect('local_augmented_teacher/recommendactivityhour',
    get_string('recommendactivityhour', 'local_augmented_teacher'), '', 6, $options)
);

$ADMIN->add('localplugins', $settings);