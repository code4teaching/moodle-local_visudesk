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

namespace local_visudesk;

/**
 * Event observers.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Someone got a role: if it is the teacher role in a course, give them the site role as well.
     *
     * @param \core\event\role_assigned $event
     */
    public static function role_assigned(\core\event\role_assigned $event): void {
        $roles = local\teacherrole::roles();
        if (!$roles || (int)$event->objectid !== $roles[0]) {
            return;
        }
        if ((int)$event->get_context()->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        local\teacherrole::grant((int)$event->relateduserid);
    }

    /**
     * Someone lost a role: if it was the teacher role in a course and they teach no other course, take the site
     * role away again (only if this plugin gave it).
     *
     * @param \core\event\role_unassigned $event
     */
    public static function role_unassigned(\core\event\role_unassigned $event): void {
        $roles = local\teacherrole::roles();
        if (!$roles || (int)$event->objectid !== $roles[0]) {
            return;
        }
        if ((int)$event->get_context()->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        local\teacherrole::revoke((int)$event->relateduserid);
    }

    /**
     * Forgets an automatically created course when it is deleted.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;
        $DB->delete_records('local_visudesk_autoappr', ['courseid' => $event->objectid]);
    }

    /**
     * Removes the planning of a deleted user (also when local_inactiveusers deletes old accounts).
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        local\planstore::delete_user((int)$event->objectid);
    }
}
