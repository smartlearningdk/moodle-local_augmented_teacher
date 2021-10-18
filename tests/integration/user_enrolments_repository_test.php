<?php
/**
 * @package     local_augmented_teacher\integration
 * @copyright   2021 Praxis
 * @companyinfo https://praxis.dk
 */

namespace local_augmented_teacher\integration;

use local_augmented_teacher\reminders_factory;
use local_augmented_teacher\test\integration_testcase;
use local_augmented_teacher\user_enrolment_repository;
use local_augmented_teacher\user_enrolments_factory;

defined('MOODLE_INTERNAL') || die();


/**
 * Class user_enrolments_repository_test
 * @package local_augmented_teacher\integration
 */
class user_enrolments_repository_test extends integration_testcase
{
    private array $course_ids;
    private user_enrolment_repository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        global $DB;

        $datagenerator = self::getDataGenerator();

        $user1 = $datagenerator->create_user();
        $user2 = $datagenerator->create_user();
        $user3 = $datagenerator->create_user();

        $course1 = $datagenerator->create_course();
        $datagenerator->enrol_user($user1->id, $course1->id);
        $datagenerator->enrol_user($user2->id, $course1->id);
        $datagenerator->enrol_user($user3->id, $course1->id);

        $course2 = $datagenerator->create_course();
        $datagenerator->enrol_user($user1->id, $course2->id);
        $datagenerator->enrol_user($user3->id, $course2->id);

        $course3 = $datagenerator->create_course();
        $datagenerator->enrol_user($user1->id, $course3->id);

        $course4 = $datagenerator->create_course();

        $this->repository = user_enrolments_factory::get_repository($DB);

        $this->course_ids = [
            $course1->id,
            $course2->id,
            $course3->id,
            $course4->id
        ];
    }

    public function test_get_user_enrolments_by_course_ids(): void {
        $user_enrolments = $this->repository->get_by_course_ids($this->course_ids);

        // Expect 6 user_enrolments
        self::assertCount(6, $user_enrolments);
    }

    public function test_get_user_enrolments_by_reminders(): void {
        $this->add_reminder('IIII', self::MESSAGE_TYPE_NOTLOGGED, $this->course_ids[0]);
        $this->add_reminder('OOOO', self::MESSAGE_TYPE_NOTLOGGED, $this->course_ids[1]);
        $this->add_reminder('AAAA', self::MESSAGE_TYPE_NOTLOGGED, $this->course_ids[0]);

        $reminders_repo = reminders_factory::get_repository();
        $reminders = $reminders_repo->get_notloggedin_reminders();

        $user_enrolments = $this->repository->get_from_reminders($reminders);

        // Expect 5 user_enrolments
        self::assertCount(5, $user_enrolments);
    }
}
