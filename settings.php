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

/**
 * @var admin_root $ADMIN
 * @var bool $hassiteconfig
 */

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_augmented_teacher_settings',  get_string('pluginname', 'local_augmented_teacher'));

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('local_augmented_teacher/notloggedinhour',
            get_string('notloggedinhour', 'local_augmented_teacher'), '', 6, $options)
    );
    $settings->add(new admin_setting_configcheckbox('local_augmented_teacher/resend_notloggedin_notification_infinitely',
            get_string('resend_notloggedin_notification_infinitely', 'local_augmented_teacher'), get_string('resend_notloggedin_notification_infinitely_desc', 'local_augmented_teacher'), true)
    );
    $settings->add(new admin_setting_configselect('local_augmented_teacher/recommendactivityhour',
        get_string('recommendactivityhour', 'local_augmented_teacher'), '', 6, $options)
    );

    $yesno = [
        0 => get_string('no'),
        1 => get_string('yes'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_augmented_teacher/show_send_from_course_in_message',
        get_string('show_send_from_course_in_message', 'local_augmented_teacher'),
        get_string('show_send_from_course_in_message_desc', 'local_augmented_teacher'),
        0,
        $yesno
    ));

    $ADMIN->add('localplugins', $settings);
}
