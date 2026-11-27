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
use local_visudesk\local\vgstore;

/**
 * VisuGrade sync: changes after a number, with the snapshot if the device has a gap.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vg_fetch extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'since' => new external_value(PARAM_INT, 'Newest number the device has applied, 0 = none'),
        ]);
    }

    /**
     * Runs the function for the calling user only.
     *
     * @param int $since
     * @return array
     */
    public static function execute(int $since): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['since' => $since]);
        get_plan::check_user();
        $r = vgstore::fetch((int)$USER->id, max(0, $params['since']));
        $r['hassnapshot'] = $r['snapshot'] !== null;
        $r['snapshot'] = $r['snapshot'] ?? ['seq' => 0, 'timecreated' => 0, 'data' => ''];
        return $r;
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'hassnapshot' => new external_value(PARAM_BOOL, 'The device has to start over from the snapshot'),
            'snapshot' => new external_single_structure([
                'seq' => new external_value(PARAM_INT, 'Number the snapshot covers'),
                'timecreated' => new external_value(PARAM_INT, 'Milliseconds'),
                'data' => new external_value(PARAM_RAW, 'Encrypted, base64'),
            ]),
            'entries' => new external_multiple_structure(new external_single_structure([
                'seq' => new external_value(PARAM_INT, 'Number'),
                'device' => new external_value(PARAM_ALPHANUM, 'Random device id'),
                'timecreated' => new external_value(PARAM_INT, 'Arrival on the server, milliseconds'),
                'data' => new external_value(PARAM_RAW, 'Encrypted change, base64'),
            ])),
            'last' => new external_value(PARAM_INT, 'Newest number on the server'),
        ]);
    }
}
