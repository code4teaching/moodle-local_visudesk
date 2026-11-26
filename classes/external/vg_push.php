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
 * VisuGrade sync: appends encrypted changes, only on top of the newest number.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vg_push extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'basis' => new external_value(PARAM_INT, 'Newest number the device knows'),
            'device' => new external_value(PARAM_ALPHANUM, 'Random device id'),
            'items' => new external_multiple_structure(new external_value(PARAM_RAW, 'Encrypted change, base64')),
        ]);
    }

    /**
     * Runs the function for the calling user only.
     *
     * @param int $basis
     * @param string $device
     * @param array $items
     * @return array
     */
    public static function execute(int $basis, string $device, array $items): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['basis' => $basis, 'device' => $device,
            'items' => $items]);
        get_plan::check_user();
        return vgstore::push((int)$USER->id, $params['basis'], $params['device'], $params['items']);
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'stale' => new external_value(PARAM_BOOL, 'Another device was faster: fetch first, then send again'),
            'seqs' => new external_multiple_structure(new external_value(PARAM_INT, 'Number of each change')),
            'last' => new external_value(PARAM_INT, 'Newest number on the server'),
        ]);
    }
}
