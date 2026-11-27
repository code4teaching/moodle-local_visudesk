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
use local_visudesk\local\vgstore;

/**
 * VisuGrade sync: stores the encrypted snapshot of the whole grade book up to a number.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vg_set_snapshot extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'seq' => new external_value(PARAM_INT, 'Number the snapshot covers'),
            'data' => new external_value(PARAM_RAW, 'Encrypted snapshot, base64'),
        ]);
    }

    /**
     * Runs the function for the calling user only.
     *
     * @param int $seq
     * @param string $data
     * @return array
     */
    public static function execute(int $seq, string $data): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['seq' => $seq, 'data' => $data]);
        get_plan::check_user();
        vgstore::set_snapshot((int)$USER->id, $params['seq'], $params['data']);
        return ['ok' => true];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Stored'),
        ]);
    }
}
