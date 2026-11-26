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


namespace local_visudesk\task;

use local_visudesk\local\vgstore;

/**
 * Nightly: removes VisuGrade sync changes older than 30 days that a snapshot covers, and old pairing requests.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_vg extends \core\task\scheduled_task {
    /**
     * Name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanupvg', 'local_visudesk');
    }

    /**
     * Runs the task.
     */
    public function execute(): void {
        $r = vgstore::cleanup();
        mtrace("Removed {$r['deleted']} VisuGrade change(s) older than " . vgstore::KEEPDAYS . ' days.');
        if ($r['waiting']) {
            mtrace("{$r['waiting']} user(s) have changes older than " . (2 * vgstore::KEEPDAYS) .
                ' days without a snapshot; they are removed once a device of theirs syncs again.');
        }
    }
}
