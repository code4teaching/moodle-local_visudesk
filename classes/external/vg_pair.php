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
 * VisuGrade sync: relays the pairing of a new device (request, open, answer, collect). Only for devices without iCloud keychain.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vg_pair extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'action' => new external_value(PARAM_ALPHA, 'request, open, answer or collect'),
            'id' => new external_value(PARAM_INT, 'Pairing id (answer, collect)', VALUE_DEFAULT, 0),
            'publickey' => new external_value(PARAM_RAW, 'X25519 public key of the new device, base64 (request)', VALUE_DEFAULT, ''),
            'devicename' => new external_value(PARAM_TEXT, 'Name of the new device (request)', VALUE_DEFAULT, ''),
            'answer' => new external_value(PARAM_RAW, 'Sync key encrypted for the new device, base64 (answer)', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Runs the function for the calling user only.
     *
     * @param string $action
     * @param int $id
     * @param string $publickey
     * @param string $devicename
     * @param string $answer
     * @return array
     */
    public static function execute(string $action, int $id = 0, string $publickey = '', string $devicename = '', string $answer = ''): array {
        global $USER;
        $p = self::validate_parameters(self::execute_parameters(), ['action' => $action, 'id' => $id,
            'publickey' => $publickey, 'devicename' => $devicename, 'answer' => $answer]);
        get_plan::check_user();
        $userid = (int)$USER->id;
        $out = ['id' => 0, 'open' => [], 'answer' => ''];
        switch ($p['action']) {
            case 'request':
                $out['id'] = vgstore::pair_request($userid, $p['publickey'], $p['devicename']);
                break;
            case 'open':
                $out['open'] = vgstore::pair_open($userid);
                break;
            case 'answer':
                vgstore::pair_answer($userid, $p['id'], $p['answer']);
                $out['id'] = $p['id'];
                break;
            case 'collect':
                $out['answer'] = vgstore::pair_collect($userid, $p['id']);
                $out['id'] = $p['id'];
                break;
            default:
                throw new \invalid_parameter_exception('Unknown action');
        }
        return $out;
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Pairing id'),
            'open' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Pairing id'),
                'publickey' => new external_value(PARAM_RAW, 'Public key, base64'),
                'devicename' => new external_value(PARAM_TEXT, 'Device name'),
                'timecreated' => new external_value(PARAM_INT, 'Milliseconds'),
            ])),
            'answer' => new external_value(PARAM_RAW, 'Encrypted sync key, empty while waiting'),
        ]);
    }
}
