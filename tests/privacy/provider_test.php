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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use local_visudesk\local\planstore;

/**
 * Privacy tests: export and deletion of the lesson planning.
 *
 * @package    local_visudesk
 * @category   test
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends provider_testcase {
    /**
     * Export and delete.
     */
    public function test_export_and_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        planstore::save($user->id, '2026-11-23|07:45|11FI2|OOP', ['plan' => ['v' => 'Interfaces', 'basis' => 0]]);
        planstore::save($other->id, '2026-11-23|07:45|11FI1|DB', ['plan' => ['v' => 'Joins', 'basis' => 0]]);
        $context = \context_user::instance($user->id);

        $contexts = provider::get_contexts_for_userid($user->id);
        $this->assertSame([$context->id], array_map('intval', $contexts->get_contextids()));
        $userlist = new userlist($context, 'local_visudesk');
        provider::get_users_in_context($userlist);
        $this->assertSame([(int)$user->id], array_map('intval', $userlist->get_userids()));

        provider::export_user_data(new approved_contextlist($user, 'local_visudesk', [$context->id]));
        $data = writer::with_context($context)->get_data([get_string('privacy:path:plan', 'local_visudesk')]);
        $this->assertSame('Interfaces', $data->lessons[0]->content['plan']);

        provider::delete_data_for_users(new approved_userlist($context, 'local_visudesk', [$user->id]));
        $this->assertSame(0, $DB->count_records(planstore::TABLE, ['userid' => $user->id]));
        $this->assertSame(1, $DB->count_records(planstore::TABLE, ['userid' => $other->id]));

        provider::delete_data_for_all_users_in_context(\context_user::instance($other->id));
        $this->assertSame(0, $DB->count_records(planstore::TABLE));
    }
}
