<?php
/**
 * @package     local_augmented_teacher\unit
 * @copyright   2021 Praxis
 * @companyinfo https://praxis.dk
 */

namespace local_augmented_teacher\unit;

use basic_testcase;
use coding_exception;
use lang_string;
use local_augmented_teacher\message_send_from_course_builder;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();


/**
 * Class message_send_from_course_builder_test
 * @package local_augmented_teacher\unit
 */
class message_send_from_course_builder_test extends basic_testcase
{
    /**
     * @dataProvider language_provider
     * @param string $lang
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function test_append_send_from_course_text_to_message(string $lang): void {

        $strings = [
            'en' => 'A English test text',
            'da' => 'A Danish test text',
        ];

        $message = 'This is a test message.';
        // Expect non-exists language to use English as default
        $lang_text = $strings[$lang] ?? $strings['en'];
        $expected_message = $message . $lang_text;

        $course = (object)[
            'id' => 1,
            'fullname' => 'This is a test course 1'
        ];

        $student = (object)[
            'id' => 1,
            'lang' => $lang
        ];

        $lang_string = $this->mock_lang_string($strings);

        $builder = new message_send_from_course_builder($lang_string);
        $actual_message = $builder->append(
            $message,
            $course,
            $student
        );

        self::assertSame($expected_message, $actual_message);
    }

    /**
     * @return string[][]
     */
    public function language_provider(): array {
        return [
            ['da'],
            ['en'],
            ['es'],
        ];
    }

    /**
     * @param string[] $langs ["en" => "Some english text"]
     * @return lang_string
     */
    private function mock_lang_string(array $langs): lang_string {

        $default_lang = 'en';
        $string = $this->createMock(lang_string::class);
        $string->expects(self::once())
            ->method('out')
            ->willReturnCallback(function($lang) use($langs, $default_lang) {
                if (!isset($langs[$lang])) {
                    // Simulate moodle lang_string::out() error
                    if (!isset($langs[$default_lang])) {
                        throw new coding_exception("Cannot find the language $lang or $default_lang");
                    }
                    $lang = $default_lang;
                }
                return $langs[$lang];
            });
        return $string;
    }
}
