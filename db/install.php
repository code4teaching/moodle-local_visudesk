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

/**
 * Install: takes over the settings and the list of automatically created courses from local_coursefiles,
 * the former name of this plugin, if it is installed.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Takes over what local_coursefiles had.
 *
 * @return bool
 */
function xmldb_local_visudesk_install() {
    global $DB;
    $keys = ['teacherrole', 'siterole', 'autoapprove', 'autoapprovefullname', 'autoapproveshortname',
        'autoapprovearchive', 'autoapprovenotify'];
    foreach ($keys as $key) {
        $value = get_config('local_coursefiles', $key);
        if ($value !== false) {
            set_config($key, $value, 'local_visudesk');
        }
    }
    $dbman = $DB->get_manager();
    if ($dbman->table_exists('local_coursefiles_autoappr')) {
        foreach ($DB->get_records('local_coursefiles_autoappr') as $row) {
            if (!$DB->record_exists('local_visudesk_autoappr', ['courseid' => $row->courseid])) {
                unset($row->id);
                $DB->insert_record('local_visudesk_autoappr', $row);
            }
        }
    }
    return true;
}
