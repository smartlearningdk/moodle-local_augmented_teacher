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

namespace local_augmented_teacher\task;

defined('MOODLE_INTERNAL') || die();

class send_reminder_message extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('sendremindermessage', 'local_augmented_teacher');
    }
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/augmented_teacher/lib.php');
        if (!$CFG->messaging) {
            return;
        }
        local_augmented_teacher_send_reminder_message();
    }
}