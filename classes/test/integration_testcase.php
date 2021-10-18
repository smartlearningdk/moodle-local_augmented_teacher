<?php

namespace local_augmented_teacher\test;

class integration_testcase extends \advanced_testcase
{
    public const MESSAGE_TYPE_REMINDER  = 1;
    public const MESSAGE_TYPE_NOTLOGGED = 2;
    public const MESSAGE_TYPE_RECOMMEND = 3;

    public const REMINDER_BEFORE_DUE = 1;
    public const REMINDER_AFTER_DUE  = 2;

    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    /**
     * Creates a reminder and returns the id
     * @param string $title
     * @param int $message_type
     * @param int|null $course_id
     * @param int|null $cmid
     * @return int
     * @throws \dml_exception
     */
    public function add_reminder(string $title, int $message_type, ?int $course_id = null, ?int $cmid = null): int {
        global $DB, $USER;

        $datagenerator = self::getDataGenerator();

        $reminder = [
            'title' => $title,
            'cmid' => $cmid,
            'cmid2' => null,
            'message' => $datagenerator->loremipsum,
            'messagetype' => $message_type,
            'type' => self::REMINDER_AFTER_DUE,
            'enabled' => 1,
            'timeinterval' => 0,
            'userid' => $USER->id,
            'courseid' => $course_id,
            'timecreated' => time(),
        ];

        return $DB->insert_record('local_augmented_teacher_rem', $reminder);
    }
}