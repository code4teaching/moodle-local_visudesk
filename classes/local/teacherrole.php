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

namespace local_visudesk\local;

/**
 * Gives a site role (e.g. "Web service user") to everybody who teaches in a course (site admins need no role).
 *
 * Capabilities such as moodle/webservice:createtoken are checked in the system context, so a course role
 * like Teacher cannot grant them. This keeps a system role in step with the teacher role in courses.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacherrole {
    /**
     * The configured roles, or null when the feature is off.
     *
     * @return array|null [teacher role id, site role id]
     */
    public static function roles(): ?array {
        $teacher = (int)get_config('local_visudesk', 'teacherrole');
        $site = (int)get_config('local_visudesk', 'siterole');
        if (!$teacher || !$site || $teacher === $site) {
            return null;
        }
        return [$teacher, $site];
    }

    /**
     * Gives the site role to one user if they do not have it yet.
     *
     * @param int $userid
     * @return bool true if the role was assigned now
     */
    public static function grant(int $userid): bool {
        global $DB;
        $roles = self::roles();
        if (!$roles || isguestuser($userid) || is_siteadmin($userid)) {
            return false;
        }
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0]);
        if (!$user) {
            return false;
        }
        $system = \context_system::instance();
        if (user_has_role_assignment($userid, $roles[1], $system->id)) {
            return false;
        }
        role_assign($roles[1], $userid, $system->id, 'local_visudesk');
        return true;
    }

    /**
     * Whether the user has the teacher role in at least one course.
     *
     * @param int $userid
     * @param int $teacherrole
     * @return bool
     */
    public static function teaches(int $userid, int $teacherrole): bool {
        global $DB;
        return $DB->record_exists_sql(
            "SELECT 1
               FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :course
              WHERE ra.userid = :userid AND ra.roleid = :teacher",
            ['course' => CONTEXT_COURSE, 'userid' => $userid, 'teacher' => $teacherrole]
        );
    }

    /**
     * Takes the site role away again once the user no longer teaches any course. Only assignments this plugin made
     * (component local_visudesk) are removed; a role an administrator gave by hand stays.
     *
     * @param int $userid
     * @return bool true if a role was removed now
     */
    public static function revoke(int $userid): bool {
        $roles = self::roles();
        if (!$roles || self::teaches($userid, $roles[0])) {
            return false;
        }
        return self::unassign_own($userid, $roles[1]);
    }

    /**
     * Gives the site role to everybody who has the teacher role in any course, and takes the site roles this plugin
     * gave away from everybody who no longer does. With the feature switched off (or another site role chosen),
     * the plugin's own assignments of other roles are removed too.
     *
     * @return int number of users who got the role now
     */
    public static function sync(): int {
        global $DB;
        $roles = self::roles();
        $teachers = [];
        if ($roles) {
            $sql = "SELECT DISTINCT ra.userid
                      FROM {role_assignments} ra
                      JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :course
                     WHERE ra.roleid = :teacher";
            $teachers = array_flip($DB->get_fieldset_sql($sql, ['course' => CONTEXT_COURSE, 'teacher' => $roles[0]]));
        }
        $system = \context_system::instance();
        $own = $DB->get_records('role_assignments', ['contextid' => $system->id, 'component' => 'local_visudesk']);
        foreach ($own as $ra) {
            $keep = $roles && (int)$ra->roleid === $roles[1] && isset($teachers[$ra->userid]);
            if (!$keep) {
                role_unassign((int)$ra->roleid, (int)$ra->userid, $system->id, 'local_visudesk');
            }
        }
        $count = 0;
        foreach (array_keys($teachers) as $userid) {
            if (self::grant((int)$userid)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Removes this plugin's assignment of a role in the system context.
     *
     * @param int $userid
     * @param int $roleid
     * @return bool true if there was one
     */
    private static function unassign_own(int $userid, int $roleid): bool {
        global $DB;
        $system = \context_system::instance();
        $exists = $DB->record_exists('role_assignments', [
            'contextid' => $system->id, 'userid' => $userid, 'roleid' => $roleid, 'component' => 'local_visudesk',
        ]);
        if ($exists) {
            role_unassign($roleid, $userid, $system->id, 'local_visudesk');
        }
        return $exists;
    }
}
