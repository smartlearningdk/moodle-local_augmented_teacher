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

$string['pluginname'] = 'The augmented teacher';
$string['shortcodes'] = 'Short codes';
$string['shortcodes_help'] = 'You will find specific short-codes or I call them email-merge-tags that can be used with
very little effort to dynamically update a user\'s information in your emails. You will be able to personalize your
emails with the recipient’s first name, last name, etc. So emails will look fully customized by the recipients.';
$string['mergedmessages'] = 'Send merged messages';
$string['choosetask'] = 'Choose task';
$string['choosetask_help'] = '* Send merged messagee - For sending a message to one or more participants';
$string['backtomainmenu'] = 'Back to main menu';
$string['reminder'] = 'Reminder';
$string['reminders'] = 'Reminders';
$string['numberofreminders'] = 'Number of reminders';
$string['add'] = 'Add';
$string['addnewreminder'] = 'Add new reminder';
$string['log'] = 'Log';
$string['rowcount'] = '';
$string['action'] = '';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['title'] = 'Subject';
$string['message'] = 'Message';
$string['type'] = 'Type';
$string['timeinterval'] = 'Interval';
$string['duration'] = 'Duration';
$string['duration_desc'] = 'Duration that user has not logged in for';
$string['duration_help'] = 'It sends reminder to users to come back to the site, when they had not logged in over xyz days into the site.';
$string['enabled'] = 'Enabled';
$string['deleted'] = 'Deleted';
$string['timecreated'] = 'Timecreated';
$string['timemodified'] = 'Timemodified';
$string['before'] = 'Before';
$string['after'] = 'After';
$string['scheduled'] = 'Scheduled';
$string['submit'] = 'Submit';
$string['list'] = 'List';
$string['sendremindermessage'] = 'Send reminder message';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['firstname'] = 'First name';
$string['lastname'] = 'Last name';
$string['subject'] = 'Subject';
$string['smallmessage'] = 'Message';
$string['eventremindercreated'] = 'Reminder created';
$string['eventreminderupdated'] = 'Reminder updated';
$string['eventreminderdeleted'] = 'Reminder deleted';
$string['reminderdelete'] = 'Reminder delete';
$string['deletereminderwarn'] = 'Are you absolutely sure you want to completely delete this reminder and all the data
it contains?';
$string['notloggedinreminder'] = 'Not logged in reminder';
$string['notloggedinreminders'] = 'Not logged in reminders';
$string['notloggedinreminderdelete'] = 'Not logged in reminder delete';
$string['excludeusersfromreminders'] = 'Exclude users from receiving the reminders';
$string['timeend'] = 'Exclusion ends';
$string['addedit'] = 'Add/Edit';
$string['deleteexclusionwarn'] = 'Are you absolutely sure you want to completely delete this record?';
$string['notloggedinhour'] = 'Hour to send not logged in reminders';
$string['resend_notloggedin_notification_infinitely'] = 'Send not logged in reminders infinitely';
$string['resend_notloggedin_notification_infinitely_desc'] = 'Check this if you want users to repeatedly receive not logged in notifications on a course.';
$string['recommendactivityhour'] = 'Hour to send activity recommendation message';
$string['logs'] = 'Logs';
$string['activity'] = 'Activity';
$string['recommendedactivity'] = 'Recommended activity';
$string['recommendactivity'] = 'Recommend activity';
$string['recommendactivitydelete'] = 'Recommend activity delete';
$string['cannotbesame'] = 'Selected activities cannot be the same';
$string['select'] = 'Select';
$string['durationlessthanday'] = 'Duration must not be less than one day';
$string['officehours'] = 'Office hours';
$string['disable'] = 'Disable';
$string['monday'] = 'Monday';
$string['tuesday'] = 'Tuesday';
$string['wednesday'] = 'Wednesday';
$string['thursday'] = 'Thursday';
$string['friday'] = 'Friday';
$string['saturday'] = 'Saturday';
$string['sunday'] = 'Sunday';
$string['1'] = 'Monday';
$string['2'] = 'Tuesday';
$string['3'] = 'Wednesday';
$string['4'] = 'Thursday';
$string['5'] = 'Friday';
$string['6'] = 'Saturday';
$string['7'] = 'Sunday';
$string['day'] = 'Day';
$string['hours'] = 'Hours';
$string['timestart'] = 'Time start';
$string['timeend'] = 'Time end';
$string['timestop'] = 'Stop date';
$string['timeresume'] = 'Resume date';
$string['invalidtimeranges'] = 'Invalid time range';
$string['update'] = 'Update';
$string['coursenotificationsettings'] = 'Course notification settings';
$string['resumelessorequaltostop'] = 'Resume date is less or equal to the Stop date';
$string['pauseremiderwarning'] = 'All automatic course reminders are paused until {$a}';
$string['coursemessage'] = 'Message course users';
$string['addedrecip'] = 'Added {$a} new recipient';
$string['addedrecips'] = 'Added {$a} new recipients';
$string['keepsearching'] = 'Keep searching';
$string['usemessageform'] = 'or use the form below to send a message to the selected students';
$string['formattexttype'] = 'Formatting';
$string['currentlyselectedusers'] = 'Currently selected users';
$string['allfieldsrequired'] = 'All fields are required';
$string['previewhtml'] = 'HTML format preview';
/**
 * $a: {
 *      id: int,
 *      fullname: string,
 *      shortname: string,
 *      url: string,
 *      link: string (HTMLElement <a>...</a>)
 * }
 */
$string['sendfromcourse'] = '<p style="text-align: right;">Send from: {$a->link}</p>';
$string['show_send_from_course_in_message'] = 'Show send from course link in the message';
$string['show_send_from_course_in_message_desc'] = 'The text can be modified in language string with "sendfromcourse" identifier.';

// Capabilities
$string['augmented_teacher:receivereminder'] = 'Receive reminder';
$string['augmented_teacher:mergedmessages'] = 'Allow user to use "Send merged messages"';
$string['augmented_teacher:reminders'] = 'Allow user to use "Reminders"';
$string['augmented_teacher:notloggedinreminders'] = 'Allow user to use "Not logged in reminders"';
$string['augmented_teacher:excludeusersfromreminders'] = 'Allow user to use "Exclude users from receiving the reminders"';
$string['augmented_teacher:recommendactivity'] = 'Allow user to use "Recommend activity"';
$string['augmented_teacher:coursenotificationsettings'] = 'Allow user to use "Course notification settings"';
