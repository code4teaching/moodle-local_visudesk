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
use core_external\external_single_structure;
use core_external\external_value;
use local_visudesk\local\planstore;

/**
 * First upload of a planning the app kept locally. Fields the server already has stay as they are.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_plan extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'lessons' => new external_value(PARAM_RAW, 'JSON object: lesson key => {field: value}'),
        ]);
    }

    /**
     * Imports.
     *
     * @param string $lessons
     * @return array
     */
    public static function execute(string $lessons): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['lessons' => $lessons]);
        get_plan::check_user();

        return planstore::import((int)$USER->id, planstore::decode($params['lessons']));
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'added' => new external_value(PARAM_INT, 'Fields taken over'),
            'skipped' => new external_value(PARAM_INT, 'Fields the server already had'),
        ]);
    }
}
