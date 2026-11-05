<?php

namespace local_augmented_teacher\privacy;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for the Augmented Teacher plugin.
 *
 * @package    local_augmented_teacher
 */
class provider implements metadata_provider, plugin_provider, core_userlist_provider {

    /** @var string Reminder configuration table. */
    private const TABLE_REM = 'local_augmented_teacher_rem';

    /** @var string Reminder delivery table. */
    private const TABLE_SENT = 'local_augmented_teacher_sent';

    /** @var string Reminder message log table. */
    private const TABLE_LOG = 'local_augmented_teacher_log';

    /** @var string Course user exclusion table. */
    private const TABLE_EXC = 'local_augmented_teacher_exc';

    /** @var string User office hours table. */
    private const TABLE_OFH = 'local_augmented_teacher_ofh';

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $tables = [
            self::TABLE_REM => [
                'id', 'userid', 'courseid', 'cmid', 'cmid2', 'title', 'messagetype', 'message', 'type',
                'timeinterval', 'enabled', 'deleted', 'timecreated', 'timemodified',
            ],
            self::TABLE_SENT => ['id', 'userid', 'reminderid', 'timesent'],
            self::TABLE_LOG => ['id', 'userid', 'remid', 'mid', 'timecreated'],
            self::TABLE_EXC => [
                'id', 'status', 'courseid', 'userid', 'timestart', 'timeend', 'modifierid', 'timecreated', 'timemodified',
            ],
            self::TABLE_OFH => ['id', 'userid', 'dayofweek', 'timestart', 'timeend'],
        ];

        foreach ($tables as $tablename => $fields) {
            $fieldmetadata = [];
            foreach ($fields as $field) {
                $fieldmetadata[$field] = 'privacy:metadata:' . $tablename . ':' . $field;
            }
            $collection->add_database_table(
                $tablename,
                $fieldmetadata,
                'privacy:metadata:' . $tablename
            );
        }

        return $collection;
    }

    /**
     * Find all contexts containing data associated with a user.
     *
     * Course-linked data is reported in its course context. Office hours use the user context. Data
     * whose course/reminder link is missing is reported in the system context so it remains exportable
     * and deletable instead of becoming orphaned from privacy requests.
     *
     * @param int $userid The user ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $contextlevel = CONTEXT_COURSE;

        $contextlist->add_from_sql(
            "SELECT DISTINCT ctx.id
               FROM {local_augmented_teacher_rem} rem
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
               JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                    AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
              WHERE rem.userid = :userid",
            ['contextlevel' => $contextlevel, 'userid' => $userid]
        );

        foreach ([self::TABLE_SENT => 'reminderid', self::TABLE_LOG => 'remid'] as $table => $reminderfield) {
            $contextlist->add_from_sql(
                "SELECT DISTINCT ctx.id
                   FROM {" . $table . "} item
                   JOIN {local_augmented_teacher_rem} rem ON rem.id = item." . $reminderfield . "
                   LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                   JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                        AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
                  WHERE item.userid = :userid",
                ['contextlevel' => $contextlevel, 'userid' => $userid]
            );
        }

        $contextlist->add_from_sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
               JOIN {local_augmented_teacher_exc} exc ON exc.courseid = ctx.instanceid
              WHERE ctx.contextlevel = :contextlevel
                AND (exc.userid = :userid OR exc.modifierid = :modifierid)",
            ['contextlevel' => $contextlevel, 'userid' => $userid, 'modifierid' => $userid]
        );

        if ($DB->record_exists(self::TABLE_OFH, ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }

        if (self::user_has_orphaned_data($userid)) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Export the user's data in each approved context.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        if (count($contextlist) === 0) {
            return;
        }

        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context instanceof \context_course) {
                self::export_course_data($userid, $context);
            } else if ($context instanceof \context_user && (int)$context->instanceid === $userid) {
                self::export_office_hours($userid, $context);
            } else if ($context instanceof \context_system) {
                self::export_orphaned_data($userid, $context);
            }
        }
    }

    /**
     * Delete all plugin data in a context.
     *
     * @param \context $context The context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \context_course) {
            $courseid = $context->instanceid;
            $reminderids = self::get_reminder_ids_for_course($courseid);
            self::delete_records_by_ids(self::TABLE_SENT, 'reminderid', $reminderids);
            self::delete_records_by_ids(self::TABLE_LOG, 'remid', $reminderids);
            self::delete_records_by_ids(self::TABLE_REM, 'id', $reminderids);
            $DB->delete_records(self::TABLE_EXC, ['courseid' => $courseid]);
        } else if ($context instanceof \context_user) {
            $DB->delete_records(self::TABLE_OFH, ['userid' => $context->instanceid]);
        } else if ($context instanceof \context_system) {
            self::delete_orphaned_data(null);
        }
    }

    /**
     * Delete a user's data in approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (count($contextlist) === 0) {
            return;
        }

        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            self::delete_users_in_context([$userid], $context);
        }
    }

    /**
     * Add users with plugin data in the requested context.
     *
     * @param userlist $userlist The user list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context instanceof \context_course) {
            $params = [
                'courseid1' => $context->instanceid,
                'courseid2' => $context->instanceid,
                'courseid3' => $context->instanceid,
                'courseid4' => $context->instanceid,
                'courseid5' => $context->instanceid,
            ];
            $sql = "SELECT rem.userid AS userid
                      FROM {local_augmented_teacher_rem} rem
                      LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                     WHERE COALESCE(rem.courseid, cm.course) = :courseid1
                    UNION
                    SELECT item.userid AS userid
                      FROM {local_augmented_teacher_sent} item
                      JOIN {local_augmented_teacher_rem} rem ON rem.id = item.reminderid
                      LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                     WHERE COALESCE(rem.courseid, cm.course) = :courseid2
                    UNION
                    SELECT item.userid AS userid
                      FROM {local_augmented_teacher_log} item
                      JOIN {local_augmented_teacher_rem} rem ON rem.id = item.remid
                      LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                     WHERE COALESCE(rem.courseid, cm.course) = :courseid3
                    UNION
                    SELECT exc.userid AS userid
                      FROM {local_augmented_teacher_exc} exc
                     WHERE exc.courseid = :courseid4
                    UNION
                    SELECT exc.modifierid AS userid
                      FROM {local_augmented_teacher_exc} exc
                     WHERE exc.courseid = :courseid5";
            $userlist->add_from_sql('userid', $sql, $params);
        } else if ($context instanceof \context_user) {
            if ($DB->record_exists(self::TABLE_OFH, ['userid' => $context->instanceid])) {
                $userlist->add_user($context->instanceid);
            }
        } else if ($context instanceof \context_system) {
            $sql = "SELECT rem.userid AS userid
                      FROM {local_augmented_teacher_rem} rem
                      LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                      LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel1
                           AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
                     WHERE ctx.id IS NULL
                    UNION
                    SELECT item.userid AS userid
                      FROM {local_augmented_teacher_sent} item
                      LEFT JOIN {local_augmented_teacher_rem} rem ON rem.id = item.reminderid
                      LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                      LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel2
                           AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
                     WHERE ctx.id IS NULL
                    UNION
                    SELECT item.userid AS userid
                      FROM {local_augmented_teacher_log} item
                      LEFT JOIN {local_augmented_teacher_rem} rem ON rem.id = item.remid
                      LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                      LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel3
                           AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
                     WHERE ctx.id IS NULL
                    UNION
                    SELECT exc.userid AS userid
                      FROM {local_augmented_teacher_exc} exc
                      LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel4 AND ctx.instanceid = exc.courseid
                     WHERE ctx.id IS NULL
                    UNION
                    SELECT exc.modifierid AS userid
                      FROM {local_augmented_teacher_exc} exc
                      LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel5 AND ctx.instanceid = exc.courseid
                     WHERE ctx.id IS NULL";
            $userlist->add_from_sql('userid', $sql, [
                'contextlevel1' => CONTEXT_COURSE,
                'contextlevel2' => CONTEXT_COURSE,
                'contextlevel3' => CONTEXT_COURSE,
                'contextlevel4' => CONTEXT_COURSE,
                'contextlevel5' => CONTEXT_COURSE,
            ]);
        }
    }

    /**
     * Delete multiple users' data in a context.
     *
     * @param approved_userlist $userlist The approved users and context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        self::delete_users_in_context($userids, $userlist->get_context());
    }

    /** Export course-scoped data for a user. */
    private static function export_course_data(int $userid, \context_course $context): void {
        global $DB;

        $courseid = $context->instanceid;
        $writer = writer::with_context($context);

        $records = $DB->get_records_sql(
            "SELECT rem.*
               FROM {local_augmented_teacher_rem} rem
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
              WHERE rem.userid = :userid AND COALESCE(rem.courseid, cm.course) = :courseid",
            ['userid' => $userid, 'courseid' => $courseid]
        );
        self::export_records($writer, 'reminders', $records);

        foreach ([self::TABLE_SENT => 'reminderid', self::TABLE_LOG => 'remid'] as $table => $reminderfield) {
            $records = $DB->get_records_sql(
                "SELECT item.*
                   FROM {" . $table . "} item
                   JOIN {local_augmented_teacher_rem} rem ON rem.id = item." . $reminderfield . "
                   LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                  WHERE item.userid = :userid AND COALESCE(rem.courseid, cm.course) = :courseid",
                ['userid' => $userid, 'courseid' => $courseid]
            );
            self::export_records($writer, $table === self::TABLE_SENT ? 'sent_reminders' : 'message_log', $records);
        }

        $records = $DB->get_records(self::TABLE_EXC, ['userid' => $userid, 'courseid' => $courseid]);
        foreach ($records as $record) {
            // modifierid identifies another user; do not disclose that user's ID in this export.
            unset($record->modifierid);
        }
        self::export_records($writer, 'exclusions', $records);

        $records = $DB->get_records(self::TABLE_EXC, ['modifierid' => $userid, 'courseid' => $courseid]);
        foreach ($records as $recordid => $record) {
            // Export the user's authorship without disclosing the excluded user's ID.
            $records[$recordid] = (object)[
                'modifierid' => $userid,
                'courseid' => $record->courseid,
                'timestart' => $record->timestart,
                'timeend' => $record->timeend,
                'timecreated' => $record->timecreated,
                'timemodified' => $record->timemodified,
            ];
        }
        self::export_records($writer, 'exclusion_changes', $records);
    }

    /** Export office hours from the user's context. */
    private static function export_office_hours(int $userid, \context_user $context): void {
        global $DB;

        self::export_records(
            writer::with_context($context),
            'office_hours',
            $DB->get_records(self::TABLE_OFH, ['userid' => $userid])
        );
    }

    /** Export data that cannot be associated with an existing course context. */
    private static function export_orphaned_data(int $userid, \context_system $context): void {
        global $DB;

        $writer = writer::with_context($context);
        $records = $DB->get_records_sql(
            "SELECT rem.*
               FROM {local_augmented_teacher_rem} rem
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                    AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
              WHERE rem.userid = :userid AND ctx.id IS NULL",
            ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]
        );
        self::export_records($writer, 'reminders', $records);

        foreach ([self::TABLE_SENT => 'reminderid', self::TABLE_LOG => 'remid'] as $table => $reminderfield) {
            $records = $DB->get_records_sql(
                "SELECT item.*
                   FROM {" . $table . "} item
                   LEFT JOIN {local_augmented_teacher_rem} rem ON rem.id = item." . $reminderfield . "
                   LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                   LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                        AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
                  WHERE item.userid = :userid AND ctx.id IS NULL",
                ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]
            );
            self::export_records($writer, $table === self::TABLE_SENT ? 'sent_reminders' : 'message_log', $records);
        }

        $records = $DB->get_records_sql(
            "SELECT exc.*
               FROM {local_augmented_teacher_exc} exc
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = exc.courseid
              WHERE exc.userid = :userid AND ctx.id IS NULL",
            ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]
        );
        foreach ($records as $record) {
            unset($record->modifierid);
        }
        self::export_records($writer, 'exclusions', $records);

        $records = $DB->get_records_sql(
            "SELECT exc.*
               FROM {local_augmented_teacher_exc} exc
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = exc.courseid
              WHERE exc.modifierid = :userid AND exc.userid <> :userid2 AND ctx.id IS NULL",
            ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid, 'userid2' => $userid]
        );
        foreach ($records as $recordid => $record) {
            $records[$recordid] = (object)[
                'modifierid' => $userid,
                'timestart' => $record->timestart,
                'timeend' => $record->timeend,
                'timecreated' => $record->timecreated,
                'timemodified' => $record->timemodified,
            ];
        }
        self::export_records($writer, 'exclusion_changes', $records);
    }

    /** Export a set of records as individual entries. */
    private static function export_records(
        \core_privacy\local\request\content_writer $writer,
        string $subcontext,
        array $records
    ): void {
        if (empty($records)) {
            return;
        }

        $export = [];
        foreach ($records as $record) {
            $export[] = (array)$record;
        }
        $writer->export_data([$subcontext], (object)['records' => $export]);
    }

    /** Delete all data for a set of users in one context. */
    private static function delete_users_in_context(array $userids, \context $context): void {
        global $DB;

        if ($context instanceof \context_course) {
            self::delete_course_data_for_users($userids, $context->instanceid);
        } else if ($context instanceof \context_user) {
            $userids = array_intersect($userids, [$context->instanceid]);
            self::delete_records_by_ids(self::TABLE_OFH, 'userid', $userids);
        } else if ($context instanceof \context_system) {
            self::delete_orphaned_data($userids);
        }
    }

    /** Delete course-scoped data for users, preserving other recipients' records. */
    private static function delete_course_data_for_users(array $userids, int $courseid): void {
        global $DB;

        [$userinsql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'privacyuser');
        $params = $userparams + ['courseid' => $courseid];

        foreach ([self::TABLE_SENT => 'reminderid', self::TABLE_LOG => 'remid'] as $table => $reminderfield) {
            $records = $DB->get_records_sql(
                "SELECT item.id
                   FROM {" . $table . "} item
                   JOIN {local_augmented_teacher_rem} rem ON rem.id = item." . $reminderfield . "
                   LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                  WHERE item.userid {$userinsql} AND COALESCE(rem.courseid, cm.course) = :courseid",
                $params
            );
            self::delete_records_by_ids($table, 'id', array_keys($records));
        }

        $reminders = $DB->get_records_sql(
            "SELECT rem.id
               FROM {local_augmented_teacher_rem} rem
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
              WHERE rem.userid {$userinsql} AND COALESCE(rem.courseid, cm.course) = :courseid",
            $params
        );
        self::delete_records_by_ids(self::TABLE_REM, 'id', array_keys($reminders));

        $DB->delete_records_select(self::TABLE_EXC, "courseid = :courseid AND userid {$userinsql}", $params);
        $DB->set_field_select(
            self::TABLE_EXC,
            'modifierid',
            0,
            "courseid = :courseid AND modifierid {$userinsql}",
            $params
        );
    }

    /** Delete data in the system context, limited to records without a valid course context. */
    private static function delete_orphaned_data(?array $userids): void {
        global $DB;

        $userfilter = '';
        $userparams = [];
        if ($userids !== null) {
            [$userinsql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'privacyuser');
            $userfilter = $userinsql;
        }

        $reminderparams = ['contextlevel' => CONTEXT_COURSE] + $userparams;
        $reminderuserfilter = $userids === null ? '' : " AND rem.userid {$userfilter}";
        $reminders = $DB->get_records_sql(
            "SELECT rem.id
               FROM {local_augmented_teacher_rem} rem
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                    AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
              WHERE ctx.id IS NULL" . $reminderuserfilter,
            $reminderparams
        );
        $reminderids = array_keys($reminders);

        foreach ([self::TABLE_SENT => 'reminderid', self::TABLE_LOG => 'remid'] as $table => $reminderfield) {
            $itemuserfilter = $userids === null ? '' : " AND item.userid {$userfilter}";
            $records = $DB->get_records_sql(
                "SELECT item.id
                   FROM {" . $table . "} item
                   LEFT JOIN {local_augmented_teacher_rem} rem ON rem.id = item." . $reminderfield . "
                   LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
                   LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                        AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
                  WHERE ctx.id IS NULL" . $itemuserfilter,
                ['contextlevel' => CONTEXT_COURSE] + $userparams
            );
            self::delete_records_by_ids($table, 'id', array_keys($records));
        }

        $exclusionuserfilter = $userids === null ? '' : " AND exc.userid {$userfilter}";
        $exclusions = $DB->get_records_sql(
            "SELECT exc.id
               FROM {local_augmented_teacher_exc} exc
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = exc.courseid
              WHERE ctx.id IS NULL" . $exclusionuserfilter,
            ['contextlevel' => CONTEXT_COURSE] + $userparams
        );
        self::delete_records_by_ids(self::TABLE_EXC, 'id', array_keys($exclusions));

        if ($userids !== null) {
            $modifierrecords = $DB->get_records_sql(
                "SELECT exc.id
                   FROM {local_augmented_teacher_exc} exc
                   LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                        AND ctx.instanceid = exc.courseid
                  WHERE exc.modifierid {$userfilter} AND ctx.id IS NULL",
                ['contextlevel' => CONTEXT_COURSE] + $userparams
            );
            self::update_field_by_ids(self::TABLE_EXC, 'modifierid', 0, array_keys($modifierrecords));
        }

        // Remove reminders after their own rows have been handled; recipient histories remain intact.
        if ($userids !== null && !empty($reminderids)) {
            [$reminderinsql, $reminderidparams] = $DB->get_in_or_equal($reminderids, SQL_PARAMS_NAMED);
            [$ownerinsql, $ownerparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'owner');
            $DB->delete_records_select(
                self::TABLE_REM,
                "id {$reminderinsql} AND userid {$ownerinsql}",
                $reminderidparams + $ownerparams
            );
        } else if ($userids === null) {
            self::delete_records_by_ids(self::TABLE_REM, 'id', $reminderids);
        }
    }

    /** Return reminder IDs associated with a course. */
    private static function get_reminder_ids_for_course(int $courseid): array {
        global $DB;

        $records = $DB->get_records_sql(
            "SELECT rem.id
               FROM {local_augmented_teacher_rem} rem
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
              WHERE COALESCE(rem.courseid, cm.course) = :courseid",
            ['courseid' => $courseid]
        );
        return array_keys($records);
    }

    /**
     * Delete records by a list of IDs in the selected field.
     *
     * @param string $table The table name.
     * @param string $field The ID field.
     * @param array $ids The IDs.
     */
    private static function delete_records_by_ids(string $table, string $field, array $ids): void {
        global $DB;

        if (empty($ids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $DB->delete_records_select($table, "{$field} {$insql}", $params);
    }

    /** Update a field for a list of record IDs. */
    private static function update_field_by_ids(string $table, string $field, $value, array $ids): void {
        global $DB;

        if (empty($ids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $DB->set_field_select($table, $field, $value, "id {$insql}", $params);
    }

    /** Check whether the user has data that must be handled in the system context. */
    private static function user_has_orphaned_data(int $userid): bool {
        global $DB;

        $contextlevel = CONTEXT_COURSE;
        $checks = [
            "SELECT rem.id
               FROM {local_augmented_teacher_rem} rem
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                    AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
              WHERE rem.userid = :userid AND ctx.id IS NULL",
            "SELECT item.id
               FROM {local_augmented_teacher_sent} item
               LEFT JOIN {local_augmented_teacher_rem} rem ON rem.id = item.reminderid
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                    AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
              WHERE item.userid = :userid AND ctx.id IS NULL",
            "SELECT item.id
               FROM {local_augmented_teacher_log} item
               LEFT JOIN {local_augmented_teacher_rem} rem ON rem.id = item.remid
               LEFT JOIN {course_modules} cm ON cm.id = rem.cmid
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                    AND ctx.instanceid = COALESCE(rem.courseid, cm.course)
              WHERE item.userid = :userid AND ctx.id IS NULL",
            "SELECT exc.id
               FROM {local_augmented_teacher_exc} exc
               LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = exc.courseid
              WHERE (exc.userid = :userid OR exc.modifierid = :modifierid) AND ctx.id IS NULL",
        ];

        foreach ($checks as $sql) {
            if ($DB->record_exists_sql($sql, ['contextlevel' => $contextlevel, 'userid' => $userid, 'modifierid' => $userid])) {
                return true;
            }
        }
        return false;
    }
}
