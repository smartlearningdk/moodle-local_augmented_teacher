<?php
/**
 * @package     local_augmented_teacher\integration
 * @copyright   2021 Praxis
 * @companyinfo https://praxis.dk
 */

namespace local_augmented_teacher\integration;

use local_augmented_teacher\reminders_factory;
use local_augmented_teacher\reminders_repository;
use local_augmented_teacher\test\integration_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Class reminders_repository_test
 * @package local_augmented_teacher\integration
 */
class reminders_repository_test extends integration_testcase
{
    private reminders_repository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = reminders_factory::get_repository();
    }

    /**
     * Test get_activity_reminders
     * @throws \dml_exception
     */
    public function test_get_activity_reminders(): void {
        $datagenerator = self::getDataGenerator();

        $course1 = $datagenerator->create_course();
        $course2 = $datagenerator->create_course();

        $cm1 = $datagenerator->create_module('assign', ['course' => $course1]);
        $cm2 = $datagenerator->create_module('assign', ['course' => $course1]);

        $this->add_reminder('Remember this assignment!', self::MESSAGE_TYPE_REMINDER, $course1->id, $cm1->cmid);
        $this->add_reminder('OOOO', self::MESSAGE_TYPE_REMINDER, $course1->id, $cm1->cmid);
        $this->add_reminder('AAAA', self::MESSAGE_TYPE_REMINDER, $course2->id, $cm2->cmid);

        $reminders = $this->repository->get_activity_reminders();

        // Expect 3 activity reminders
        self::assertCount(3, $reminders);

        $reminders_reindexed = array_values($reminders);

        // Check titles
        self::assertEquals('Remember this assignment!', $reminders_reindexed[0]->title);
        self::assertEquals('OOOO', $reminders_reindexed[1]->title);
        self::assertEquals('AAAA', $reminders_reindexed[2]->title);
    }

    /**
     * Test get_notloggedin_reminders
     * @throws \dml_exception
     */
    public function test_get_notloggedin_reminders(): void {
        $datagenerator = self::getDataGenerator();

        $course1 = $datagenerator->create_course();
        $course2 = $datagenerator->create_course();

        $this->add_reminder('Remember to log in!', self::MESSAGE_TYPE_NOTLOGGED, $course1->id);
        $this->add_reminder('OOOO', self::MESSAGE_TYPE_NOTLOGGED, $course2->id);
        $this->add_reminder('AAAA', self::MESSAGE_TYPE_NOTLOGGED, $course1->id);

        $reminders = $this->repository->get_notloggedin_reminders();

        // Expect 3 not logged in reminders
        self::assertCount(3, $reminders);

        $reminders_reindexed = array_values($reminders);

        // Check titles
        self::assertEquals('Remember to log in!', $reminders_reindexed[0]->title);
        self::assertEquals('OOOO', $reminders_reindexed[1]->title);
        self::assertEquals('AAAA', $reminders_reindexed[2]->title);
    }

    /**
     * Test get_activity_recommendation_reminders
     * @throws \dml_exception
     */
    public function test_get_activity_recommendation_reminders(): void {
        $datagenerator = self::getDataGenerator();

        $course1 = $datagenerator->create_course();
        $course2 = $datagenerator->create_course();

        $this->add_reminder('Look at this cool activity!', self::MESSAGE_TYPE_RECOMMEND, $course1->id);
        $this->add_reminder('OOOO', self::MESSAGE_TYPE_RECOMMEND, $course1->id);
        $this->add_reminder('AAAA', self::MESSAGE_TYPE_RECOMMEND, $course2->id);

        $reminders = $this->repository->get_activity_recommendation_reminders();

        // Expect 3 activity recommendation reminders
        self::assertCount(3, $reminders);

        $reminders_reindexed = array_values($reminders);

        // Check titles
        self::assertEquals('Look at this cool activity!', $reminders_reindexed[0]->title);
        self::assertEquals('OOOO', $reminders_reindexed[1]->title);
        self::assertEquals('AAAA', $reminders_reindexed[2]->title);
    }
}
