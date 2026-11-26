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

namespace local_visudesk\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_visudesk\local\planstore;
use local_visudesk\local\vgstore;

/**
 * Privacy provider: the plugin keeps each teacher's own lesson planning (in the teacher's user context).
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(planstore::TABLE, [
            'userid' => 'privacy:metadata:plan:userid',
            'lessonkey' => 'privacy:metadata:plan:lessonkey',
            'plandata' => 'privacy:metadata:plan:plandata',
            'timemodified' => 'privacy:metadata:plan:timemodified',
        ], 'privacy:metadata:plan');
        $collection->add_database_table(vgstore::ENTRIES, [
            'userid' => 'privacy:metadata:vg:userid',
            'device' => 'privacy:metadata:vg:device',
            'timecreated' => 'privacy:metadata:vg:timecreated',
            'data' => 'privacy:metadata:vg:data',
        ], 'privacy:metadata:vgentry');
        $collection->add_database_table(vgstore::SNAPSHOTS, [
            'userid' => 'privacy:metadata:vg:userid',
            'timecreated' => 'privacy:metadata:vg:timecreated',
            'data' => 'privacy:metadata:vg:data',
        ], 'privacy:metadata:vgsnap');
        return $collection;
    }

    /**
     * Contexts with data of a user: their user context if they planned anything.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = 'SELECT DISTINCT ctx.id FROM {context} ctx
                  JOIN {' . planstore::TABLE . '} p ON p.userid = ctx.instanceid
                 WHERE ctx.contextlevel = :level AND p.userid = :userid';
        $contextlist->add_from_sql($sql, ['level' => CONTEXT_USER, 'userid' => $userid]);
        foreach ([vgstore::ENTRIES, vgstore::SNAPSHOTS] as $table) {
            $contextlist->add_from_sql('SELECT DISTINCT ctx.id FROM {context} ctx
                  JOIN {' . $table . '} v ON v.userid = ctx.instanceid
                 WHERE ctx.contextlevel = :level AND v.userid = :userid', ['level' => CONTEXT_USER, 'userid' => $userid]);
        }
        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            $userlist->add_from_sql(
                'userid',
                'SELECT DISTINCT userid FROM {' . planstore::TABLE . '} WHERE userid = :userid',
                ['userid' => $context->instanceid]
            );
            foreach ([vgstore::ENTRIES, vgstore::SNAPSHOTS] as $table) {
                $userlist->add_from_sql('userid', 'SELECT DISTINCT userid FROM {' . $table . '} WHERE userid = :userid',
                    ['userid' => $context->instanceid]);
            }
        }
    }

    /**
     * Exports the planning.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || (int)$context->instanceid !== $userid) {
                continue;
            }
            $lessons = [];
            foreach ($DB->get_records(planstore::TABLE, ['userid' => $userid], 'lessonkey') as $record) {
                $values = [];
                foreach (planstore::fields($record) as $name => $field) {
                    if ($field['v'] !== null) {
                        $values[$name] = $field['v'];
                    }
                }
                $lessons[] = (object)[
                    'lesson' => $record->lessonkey,
                    'content' => $values,
                    'timemodified' => \core_privacy\local\request\transform::datetime(intdiv((int)$record->timemodified, 1000)),
                ];
            }
            $entries = $DB->count_records(vgstore::ENTRIES, ['userid' => $userid]);
            $snapshot = vgstore::snapshot($userid);
            if ($entries || $snapshot) {
                // The content is encrypted on the teacher's devices; Moodle can only hand it out as stored.
                $records = [];
                foreach ($DB->get_records(vgstore::ENTRIES, ['userid' => $userid], 'seq') as $r) {
                    $records[] = ['seq' => (int)$r->seq, 'time' => \core_privacy\local\request\transform::datetime(
                        intdiv((int)$r->timecreated, 1000)), 'encrypted' => $r->data];
                }
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:vg', 'local_visudesk')],
                    (object)['note' => get_string('privacy:vg:encrypted', 'local_visudesk'), 'changes' => $records,
                        'snapshot' => $snapshot ? $snapshot->data : null]
                );
            }
            if ($lessons) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:plan', 'local_visudesk')],
                    (object)['lessons' => $lessons]
                );
            }
        }
    }

    /**
     * Deletes all data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context instanceof \context_user) {
            planstore::delete_user((int)$context->instanceid);
            vgstore::delete_user((int)$context->instanceid);
        }
    }

    /**
     * Deletes the data of one user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user && (int)$context->instanceid === $userid) {
                planstore::delete_user($userid);
                vgstore::delete_user($userid);
            }
        }
    }

    /**
     * Deletes the data of several users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context instanceof \context_user && in_array((int)$context->instanceid, $userlist->get_userids())) {
            planstore::delete_user((int)$context->instanceid);
            vgstore::delete_user((int)$context->instanceid);
        }
    }
}
