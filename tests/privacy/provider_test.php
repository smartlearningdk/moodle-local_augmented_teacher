<?php

namespace local_augmented_teacher\privacy;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types\database_table;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Tests for the Augmented Teacher privacy provider.
 *
 * @package    local_augmented_teacher
 * @covers     \local_augmented_teacher\privacy\provider
 */
class provider_test extends \core_privacy\tests\provider_testcase {

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

    /** @var \stdClass Course used by the tests. */
    private \stdClass $course;

    /** @var \stdClass User who owns the tested data. */
    private \stdClass $user;

    /** @var \stdClass Other user used to test isolation. */
    private \stdClass $otheruser;

    /** @var int ID of the first reminder. */
    private int $reminderid;

    /** @var int ID of the other user's reminder. */
    private int $otherreminderid;

    /**
     * Set up records covering all privacy-managed tables.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        global $DB;

        $this->course = self::getDataGenerator()->create_course();
        $this->user = self::getDataGenerator()->create_user();
        $this->otheruser = self::getDataGenerator()->create_user();
        \context_course::instance($this->course->id);
        \context_user::instance($this->user->id);
        \context_user::instance($this->otheruser->id);

        $this->reminderid = $DB->insert_record(self::TABLE_REM, (object)[
            'userid' => $this->user->id,
            'courseid' => $this->course->id,
            'title' => 'Owned reminder',
            'message' => 'Reminder message',
            'messagetype' => 1,
            'type' => 1,
            'timeinterval' => 3600,
            'enabled' => 1,
            'deleted' => 0,
            'timecreated' => 100,
            'timemodified' => 100,
        ]);
        $this->otherreminderid = $DB->insert_record(self::TABLE_REM, (object)[
            'userid' => $this->otheruser->id,
            'courseid' => $this->course->id,
            'title' => 'Other reminder',
            'message' => 'Other reminder message',
            'messagetype' => 2,
            'type' => 1,
            'timeinterval' => 7200,
            'enabled' => 1,
            'deleted' => 0,
            'timecreated' => 200,
            'timemodified' => 200,
        ]);

        $DB->insert_record(self::TABLE_SENT, (object)[
            'userid' => $this->user->id,
            'reminderid' => $this->otherreminderid,
            'timesent' => 300,
        ]);
        $DB->insert_record(self::TABLE_SENT, (object)[
            'userid' => $this->otheruser->id,
            'reminderid' => $this->reminderid,
            'timesent' => 400,
        ]);
        $DB->insert_record(self::TABLE_LOG, (object)[
            'userid' => $this->user->id,
            'remid' => $this->otherreminderid,
            'mid' => 123,
            'timecreated' => 300,
        ]);
        $DB->insert_record(self::TABLE_LOG, (object)[
            'userid' => $this->otheruser->id,
            'remid' => $this->reminderid,
            'mid' => 456,
            'timecreated' => 400,
        ]);

        $DB->insert_record(self::TABLE_EXC, (object)[
            'status' => 1,
            'courseid' => $this->course->id,
            'userid' => $this->user->id,
            'timestart' => 0,
            'timeend' => 500,
            'modifierid' => $this->otheruser->id,
            'timecreated' => 100,
            'timemodified' => 100,
        ]);
        $DB->insert_record(self::TABLE_EXC, (object)[
            'status' => 1,
            'courseid' => $this->course->id,
            'userid' => $this->otheruser->id,
            'timestart' => 0,
            'timeend' => 600,
            'modifierid' => $this->user->id,
            'timecreated' => 200,
            'timemodified' => 200,
        ]);

        $DB->insert_record(self::TABLE_OFH, (object)[
            'userid' => $this->user->id,
            'dayofweek' => 1,
            'timestart' => 32400,
            'timeend' => 61200,
        ]);
    }

    /** Verify that every user-linked table and its fields are declared. */
    public function test_get_metadata_declares_all_personal_data_tables_and_fields(): void {
        $collection = provider::get_metadata(new collection('local_augmented_teacher'));
        $tables = [];
        foreach ($collection->get_collection() as $item) {
            if ($item instanceof database_table) {
                $tables[$item->get_name()] = $item;
            }
        }

        $expected = [
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
        $this->assertEqualsCanonicalizing(array_keys($expected), array_keys($tables));

        foreach ($expected as $tablename => $fields) {
            $this->assertEqualsCanonicalizing($fields, array_keys($tables[$tablename]->get_privacy_fields()));
        }
    }

    /** Check that user contexts include their course and personal office-hours contexts. */
    public function test_get_contexts_for_userid_includes_course_and_user_contexts(): void {
        $contexts = provider::get_contexts_for_userid($this->user->id);
        $contextids = array_map('intval', $contexts->get_contextids());
        $this->assertContains(
            \context_course::instance($this->course->id)->id,
            $contextids
        );
        $this->assertContains(\context_user::instance($this->user->id)->id, $contextids);
        $this->assertNotContains(\context_user::instance($this->otheruser->id)->id, $contextids);
    }

    /** A user without plugin data should have no contexts to export. */
    public function test_get_contexts_for_userid_returns_empty_for_user_without_data(): void {
        $newuser = self::getDataGenerator()->create_user();
        $contexts = provider::get_contexts_for_userid($newuser->id);
        $this->assertEmpty($contexts->get_contextids());

        $approved = new approved_contextlist($newuser, 'local_augmented_teacher', []);
        provider::export_user_data($approved);
        $this->assertFalse(writer::with_context(\context_system::instance())->has_any_data());
    }

    /** Empty approved requests should be safe no-ops. */
    public function test_empty_approved_requests_do_nothing(): void {
        global $DB;
        $coursecontext = \context_course::instance($this->course->id);
        $approvedcontextlist = new approved_contextlist($this->user, 'local_augmented_teacher', []);
        provider::export_user_data($approvedcontextlist);
        provider::delete_data_for_user($approvedcontextlist);

        $approveduserlist = new approved_userlist($coursecontext, 'local_augmented_teacher', []);
        provider::delete_data_for_users($approveduserlist);

        $this->assertTrue($DB->record_exists(self::TABLE_REM, ['id' => $this->reminderid]));
        $this->assertTrue($DB->record_exists(self::TABLE_OFH, ['userid' => $this->user->id]));
    }

    /** Check that user data export includes all of the user's records, and not another user's records. */
    public function test_export_user_data_exports_all_tables_without_other_users_data(): void {
        $this->export_all_data_for_user($this->user->id, 'local_augmented_teacher');

        $coursewriter = writer::with_context(\context_course::instance($this->course->id));
        $this->assertTrue($coursewriter->has_any_data());
        $reminders = $coursewriter->get_data(['reminders'])->records;
        $this->assertCount(1, $reminders);
        $this->assertEquals('Owned reminder', $reminders[0]['title']);

        $sent = $coursewriter->get_data(['sent_reminders'])->records;
        $this->assertCount(1, $sent);
        $this->assertEquals($this->user->id, $sent[0]['userid']);

        $logs = $coursewriter->get_data(['message_log'])->records;
        $this->assertCount(1, $logs);
        $this->assertEquals(123, $logs[0]['mid']);

        $exclusions = $coursewriter->get_data(['exclusions'])->records;
        $this->assertCount(1, $exclusions);
        $this->assertArrayNotHasKey('modifierid', $exclusions[0]);

        $changes = $coursewriter->get_data(['exclusion_changes'])->records;
        $this->assertCount(1, $changes);
        $this->assertEquals($this->user->id, $changes[0]['modifierid']);

        $userwriter = writer::with_context(\context_user::instance($this->user->id));
        $officehours = $userwriter->get_data(['office_hours'])->records;
        $this->assertCount(1, $officehours);
        $this->assertEquals(1, $officehours[0]['dayofweek']);
    }

    /** Check that orphaned deliveries are discovered and exported in system context. */
    public function test_orphaned_delivery_uses_system_context(): void {
        global $DB;
        $DB->insert_record(self::TABLE_SENT, (object)[
            'userid' => $this->user->id,
            'reminderid' => 999999,
            'timesent' => 700,
        ]);

        $contexts = provider::get_contexts_for_userid($this->user->id);
        $this->assertContains(\context_system::instance()->id, array_map('intval', $contexts->get_contextids()));

        $this->export_all_data_for_user($this->user->id, 'local_augmented_teacher');
        $records = writer::with_context(\context_system::instance())->get_data(['sent_reminders'])->records;
        $this->assertCount(1, $records);
        $this->assertEquals(999999, $records[0]['reminderid']);

        $approved = new approved_contextlist($this->user, 'local_augmented_teacher', $contexts->get_contextids());
        provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists(self::TABLE_SENT, ['userid' => $this->user->id, 'reminderid' => 999999]));
        $this->assertTrue($DB->record_exists(self::TABLE_REM, ['id' => $this->otherreminderid]));
    }

    /** Orphaned exclusions export the subject's data without leaking another user's ID. */
    public function test_orphaned_exclusions_are_exported_for_subject_and_modifier(): void {
        global $DB;
        $DB->insert_record(self::TABLE_EXC, (object)[
            'status' => 1,
            'courseid' => 999995,
            'userid' => $this->user->id,
            'timestart' => 0,
            'timeend' => 900,
            'modifierid' => $this->otheruser->id,
            'timecreated' => 740,
            'timemodified' => 740,
        ]);
        $DB->insert_record(self::TABLE_EXC, (object)[
            'status' => 1,
            'courseid' => 999994,
            'userid' => $this->otheruser->id,
            'timestart' => 0,
            'timeend' => 950,
            'modifierid' => $this->user->id,
            'timecreated' => 750,
            'timemodified' => 750,
        ]);

        $this->export_all_data_for_user($this->user->id, 'local_augmented_teacher');

        $writer = writer::with_context(\context_system::instance());
        $exclusions = $writer->get_data(['exclusions'])->records;
        $this->assertCount(1, $exclusions);
        $this->assertEquals($this->user->id, $exclusions[0]['userid']);
        $this->assertArrayNotHasKey('modifierid', $exclusions[0]);

        $changes = $writer->get_data(['exclusion_changes'])->records;
        $this->assertCount(1, $changes);
        $this->assertEquals($this->user->id, $changes[0]['modifierid']);
        $this->assertArrayNotHasKey('userid', $changes[0]);
    }

    /** The user context user list contains the office-hours owner. */
    public function test_get_users_in_user_context_includes_office_hours_owner(): void {
        $usercontext = \context_user::instance($this->user->id);
        $userlist = new userlist($usercontext, 'local_augmented_teacher');
        provider::get_users_in_context($userlist);

        $this->assertEquals([(int)$this->user->id], array_map('intval', $userlist->get_userids()));
    }

    /** Orphaned data is discoverable in system context and bulk deletion stays user-scoped. */
    public function test_system_context_userlist_and_bulk_deletion_handle_orphans(): void {
        global $DB;
        $orphanreminderid = $DB->insert_record(self::TABLE_REM, (object)[
            'userid' => $this->user->id,
            'title' => 'Unlinked reminder',
            'message' => 'Unlinked message',
            'messagetype' => 1,
            'type' => 1,
            'timeinterval' => 0,
            'enabled' => 1,
            'deleted' => 0,
            'timecreated' => 700,
            'timemodified' => 700,
        ]);
        $DB->insert_record(self::TABLE_SENT, (object)[
            'userid' => $this->user->id,
            'reminderid' => $orphanreminderid,
            'timesent' => 710,
        ]);
        $DB->insert_record(self::TABLE_SENT, (object)[
            'userid' => $this->otheruser->id,
            'reminderid' => 999998,
            'timesent' => 720,
        ]);
        $DB->insert_record(self::TABLE_EXC, (object)[
            'status' => 1,
            'courseid' => 999997,
            'userid' => $this->otheruser->id,
            'timestart' => 0,
            'timeend' => 0,
            'modifierid' => $this->user->id,
            'timecreated' => 730,
            'timemodified' => 730,
        ]);

        $systemcontext = \context_system::instance();
        $userlist = new userlist($systemcontext, 'local_augmented_teacher');
        provider::get_users_in_context($userlist);
        $userids = array_map('intval', $userlist->get_userids());
        $this->assertContains((int)$this->user->id, $userids);
        $this->assertContains((int)$this->otheruser->id, $userids);

        $approved = new approved_userlist($systemcontext, 'local_augmented_teacher', [$this->user->id]);
        provider::delete_data_for_users($approved);

        $this->assertFalse($DB->record_exists(self::TABLE_REM, ['id' => $orphanreminderid]));
        $this->assertFalse($DB->record_exists(self::TABLE_SENT, ['userid' => $this->user->id, 'reminderid' => $orphanreminderid]));
        $this->assertTrue($DB->record_exists(self::TABLE_SENT, ['userid' => $this->otheruser->id, 'reminderid' => 999998]));
        $modifierid = $DB->get_field(
            self::TABLE_EXC,
            'modifierid',
            ['userid' => $this->otheruser->id, 'courseid' => 999997]
        );
        $this->assertEquals(0, $modifierid);
    }

    /** System-context cleanup should remove orphaned data for all users only. */
    public function test_delete_data_for_all_users_in_system_context_removes_orphans(): void {
        global $DB;
        $DB->insert_record(self::TABLE_SENT, (object)[
            'userid' => $this->user->id,
            'reminderid' => 999996,
            'timesent' => 800,
        ]);

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $this->assertFalse($DB->record_exists(self::TABLE_SENT, ['reminderid' => 999996]));
        $this->assertTrue($DB->record_exists(self::TABLE_SENT, ['reminderid' => $this->otherreminderid]));
        $this->assertTrue($DB->record_exists(self::TABLE_REM, ['id' => $this->reminderid]));
    }

    /** Ensure an individual erasure removes only that user's data and anonymises their modifier ID. */
    public function test_delete_data_for_user_is_scoped_to_user(): void {
        global $DB;
        $contexts = provider::get_contexts_for_userid($this->user->id);
        $approved = new approved_contextlist($this->user, 'local_augmented_teacher', $contexts->get_contextids());
        provider::delete_data_for_user($approved);

        $this->assertFalse($DB->record_exists(self::TABLE_REM, ['id' => $this->reminderid]));
        $this->assertTrue($DB->record_exists(self::TABLE_REM, ['id' => $this->otherreminderid]));
        $this->assertFalse($DB->record_exists(self::TABLE_SENT, ['userid' => $this->user->id]));
        $this->assertFalse($DB->record_exists(self::TABLE_LOG, ['userid' => $this->user->id]));
        $this->assertFalse($DB->record_exists(self::TABLE_EXC, ['userid' => $this->user->id]));
        $this->assertFalse($DB->record_exists(self::TABLE_OFH, ['userid' => $this->user->id]));
        $this->assertTrue($DB->record_exists(self::TABLE_SENT, ['userid' => $this->otheruser->id]));
        $this->assertTrue($DB->record_exists(self::TABLE_LOG, ['userid' => $this->otheruser->id]));
        $this->assertEquals(0, $DB->get_field(self::TABLE_EXC, 'modifierid', ['userid' => $this->otheruser->id]));
    }

    /** Ensure course-context deletion removes all course data but leaves user-context data. */
    public function test_delete_data_for_all_users_in_course_context(): void {
        global $DB;
        provider::delete_data_for_all_users_in_context(\context_course::instance($this->course->id));

        foreach ([self::TABLE_REM, self::TABLE_SENT, self::TABLE_LOG, self::TABLE_EXC] as $table) {
            $this->assertEquals(0, $DB->count_records($table));
        }
        $this->assertEquals(1, $DB->count_records(self::TABLE_OFH, ['userid' => $this->user->id]));
    }

    /** User-context deletion removes that user's office hours. */
    public function test_delete_data_for_all_users_in_user_context(): void {
        global $DB;
        provider::delete_data_for_all_users_in_context(\context_user::instance($this->user->id));

        $this->assertFalse($DB->record_exists(self::TABLE_OFH, ['userid' => $this->user->id]));
        $this->assertTrue($DB->record_exists(self::TABLE_REM, ['id' => $this->reminderid]));
    }

    /** Ensure bulk deletion is limited to the approved users in the selected context. */
    public function test_delete_data_for_users_deletes_only_selected_users(): void {
        global $DB;
        $coursecontext = \context_course::instance($this->course->id);
        $userlist = new userlist($coursecontext, 'local_augmented_teacher');
        provider::get_users_in_context($userlist);
        $approved = new approved_userlist($coursecontext, 'local_augmented_teacher', [$this->user->id]);
        provider::delete_data_for_users($approved);

        $this->assertFalse($DB->record_exists(self::TABLE_REM, ['id' => $this->reminderid]));
        $this->assertTrue($DB->record_exists(self::TABLE_REM, ['id' => $this->otherreminderid]));
        $this->assertFalse($DB->record_exists(self::TABLE_EXC, ['userid' => $this->user->id]));
        $this->assertEquals(0, $DB->get_field(self::TABLE_EXC, 'modifierid', ['userid' => $this->otheruser->id]));
    }

    /** Verify that user lists include the users who own, receive, or modify course data. */
    public function test_get_users_in_course_context_finds_all_related_users(): void {
        $userlist = new userlist(\context_course::instance($this->course->id), 'local_augmented_teacher');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing(
            [$this->user->id, $this->otheruser->id],
            $userlist->get_userids()
        );
    }
}
