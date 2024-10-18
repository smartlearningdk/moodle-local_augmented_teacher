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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$formaction = required_param('formaction', PARAM_FILE);
$id = required_param('id', PARAM_INT);

$PAGE->set_url('/user/action_redir.php', array('formaction' => $formaction, 'id' => $id));

// Add every page will be redirected by this script.
$actions = array(
    'mergedmessages.php',
    'messageselect.php',
    'reminders.php',
    'reminders_list.php',
    'excluded_users.php',
    'notloggedinreminders.php',
    'recommendactivity.php',
    'coursenotificationsettings.php'
);

if (array_search($formaction, $actions) === false) {
    throw new \moodle_exception('unknownuseraction', 'error');
}

if (!confirm_sesskey()) {
    throw new \moodle_exception('confirmsesskeybad', 'error');
}

require_once($formaction);
