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
 * Saves changes to one lesson of the caller's own planning. A field changed meanwhile on another device is refused
 * (listed in conflicts) instead of overwritten; the other fields are saved.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_plan_entry extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'lessonkey' => new external_value(PARAM_RAW, 'Lesson key, starting with YYYY-MM-DD|'),
            'changes' => new external_value(
                PARAM_RAW,
                'JSON object: field => {v: new value (null removes it), basis: time the app knew for the field, 0 = empty}'
            ),
        ]);
    }

    /**
     * Saves.
     *
     * @param string $lessonkey
     * @param string $changes
     * @return array
     */
    public static function execute(string $lessonkey, string $changes): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['lessonkey' => $lessonkey, 'changes' => $changes]);
        get_plan::check_user();

        $result = planstore::save((int)$USER->id, $params['lessonkey'], planstore::decode($params['changes']));
        return [
            'lessonkey' => $params['lessonkey'],
            'fields' => json_encode((object)$result['fields'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'conflicts' => $result['conflicts'],
            'timemodified' => $result['timemodified'],
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'lessonkey' => new external_value(PARAM_RAW, 'Lesson key'),
            'fields' => new external_value(PARAM_RAW, 'All fields now, JSON object: field => {v, t}'),
            'conflicts' => new external_multiple_structure(new external_value(PARAM_RAW, 'Field changed meanwhile')),
            'timemodified' => new external_value(PARAM_INT, 'Last change of the lesson, milliseconds'),
        ]);
    }
}
