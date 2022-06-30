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
 * Upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_local_augmented_teacher_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018011700) {

        // Define table local_augmented_teacher_rem to be created.
        $table = new xmldb_table('local_augmented_teacher_rem');

        // Adding fields to table local_augmented_teacher_rem.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeinterval', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_augmented_teacher_rem.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_augmented_teacher_rem.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table local_augmented_teacher_log to be created.
        $table = new xmldb_table('local_augmented_teacher_log');

        // Adding fields to table local_augmented_teacher_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('remid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('mid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_augmented_teacher_log.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_augmented_teacher_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Augmented_teacher savepoint reached.
        upgrade_plugin_savepoint(true, 2018011700, 'local', 'augmented_teacher');
    }

    if ($oldversion < 2018070100) {

        $table = new xmldb_table('local_augmented_teacher_rem');

        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('messagetype', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0', 'title');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('cmid2', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'cmid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->execute("UPDATE {local_augmented_teacher_rem} SET messagetype=1");

        // Augmented_teacher savepoint reached.
        upgrade_plugin_savepoint(true, 2018070100, 'local', 'augmented_teacher');
    }

    if ($oldversion < 2018080101) {

        // Define table local_augmented_teacher_exc to be created.
        $table = new xmldb_table('local_augmented_teacher_exc');

        // Adding fields to table local_augmented_teacher_exc.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '2147483647');
        $table->add_field('modifierid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_augmented_teacher_exc.
        $table->add_key('aug_userenro_enruse_uix', XMLDB_KEY_UNIQUE, array('courseid', 'userid'));
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table local_augmented_teacher_exc.
        $table->add_index('aug_userenro_enr_ix', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        $table->add_index('aug_userenro_use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('aug_userenro_mod_ix', XMLDB_INDEX_NOTUNIQUE, array('modifierid'));

        // Conditionally launch create table for local_augmented_teacher_exc.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Augmented_teacher savepoint reached.
        upgrade_plugin_savepoint(true, 2018080101, 'local', 'augmented_teacher');
    }

    if ($oldversion < 2019010800) {

        // Define table local_augmented_teacher_ofh to be created.
        $table = new xmldb_table('local_augmented_teacher_ofh');

        // Adding fields to table local_augmented_teacher_ofh.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dayofweek', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '18', null, null, null, '0');
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '18', null, null, null, '0');

        // Adding keys to table local_augmented_teacher_ofh.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('mdl_locaaugmteacofh_day_uix', XMLDB_KEY_UNIQUE, array('dayofweek', 'userid'));

        // Adding indexes to table local_augmented_teacher_ofh.
        $table->add_index('mdl_locaaugmteacofh_use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch create table for local_augmented_teacher_ofh.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_augmented_teacher_stp to be created.
        $table = new xmldb_table('local_augmented_teacher_stp');

        // Adding fields to table local_augmented_teacher_stp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestop', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeresume', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_augmented_teacher_stp.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_UNIQUE, array('courseid'));

        // Conditionally launch create table for local_augmented_teacher_stp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Augmented_teacher savepoint reached.
        upgrade_plugin_savepoint(true, 2019010800, 'local', 'augmented_teacher');
    }

    if ($oldversion < 2022073000) {
        $table = new xmldb_table('local_augmented_teacher_sent');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reminderid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesent', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('id', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('userid_reminderid_ix', XMLDB_INDEX_NOTUNIQUE, ['userid', 'reminderid']);
        $table->add_index('reminderid_ix', XMLDB_INDEX_NOTUNIQUE, ['reminderid']);
        $table->add_index('userid_ix', XMLDB_INDEX_NOTUNIQUE, ['userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022073000, 'local', 'augmented_teacher');
    }
    return true;
}
