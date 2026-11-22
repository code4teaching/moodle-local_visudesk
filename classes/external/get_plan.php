<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_visudesk\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_visudesk\local\planstore;

/**
 * Returns the caller's own lesson planning for a date range.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_plan extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'from' => new external_value(PARAM_RAW, 'First day, YYYY-MM-DD'),
            'to' => new external_value(PARAM_RAW, 'Last day, YYYY-MM-DD'),
            'since' => new external_value(
                PARAM_INT,
                'Only lessons changed after this time (milliseconds), 0 = all',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Reads the lessons.
     *
     * @param string $from
     * @param string $to
     * @param int $since
     * @return array
     */
    public static function execute(string $from, string $to, int $since = 0): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['from' => $from, 'to' => $to, 'since' => $since]);
        self::check_user();

        $lessons = [];
        foreach (planstore::get((int)$USER->id, $params['from'], $params['to'], $params['since']) as $lesson) {
            $lesson['fields'] = json_encode((object)$lesson['fields'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $lessons[] = $lesson;
        }
        return ['lessons' => $lessons, 'servertime' => planstore::now()];
    }

    /**
     * Only real, logged-in users, each in their own user context.
     */
    public static function check_user(): void {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            throw new \require_login_exception('Guests have no planning');
        }
        self::validate_context(\context_user::instance($USER->id));
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'lessons' => new external_multiple_structure(new external_single_structure([
                'lessonkey' => new external_value(PARAM_RAW, 'Lesson key'),
                'fields' => new external_value(PARAM_RAW, 'JSON object: field => {v: value or null, t: milliseconds}'),
                'timemodified' => new external_value(PARAM_INT, 'Last change, milliseconds'),
            ])),
            'servertime' => new external_value(PARAM_INT, 'Server time in milliseconds, for the next "since"'),
        ]);
    }
}
