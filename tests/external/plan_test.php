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

use local_visudesk\local\planstore;

/**
 * Tests for the lesson planning kept in Moodle (Mac and iPad share it).
 *
 * @package    local_visudesk
 * @category   test
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_plan::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(save_plan_entry::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(import_plan::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(planstore::class)]
final class plan_test extends \advanced_testcase {
    /** @var string A lesson. */
    const KEY = '2026-11-23|07:45|11FI2|OOP';

    /**
     * Saves changes as the app would.
     *
     * @param array $changes field => [value, basis]
     * @param string $key
     * @return array
     */
    private function save(array $changes, string $key = self::KEY): array {
        $json = [];
        foreach ($changes as $name => [$value, $basis]) {
            $json[$name] = ['v' => $value, 'basis' => $basis];
        }
        $result = save_plan_entry::execute($key, json_encode($json));
        $result['fields'] = json_decode($result['fields'], true);
        return $result;
    }

    /**
     * Reads the fields of a lesson.
     *
     * @return array
     */
    private function read(): array {
        $lessons = get_plan::execute('2026-11-23', '2026-11-23')['lessons'];
        return $lessons ? json_decode($lessons[0]['fields'], true) : [];
    }

    /**
     * Save, read and change one field.
     */
    public function test_save_and_read(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $first = $this->save(['thema' => ['Interfaces', 0], 'plan' => ["Einstieg\nÜbung", 0]]);
        $this->assertSame([], $first['conflicts']);
        $this->assertSame('Interfaces', $first['fields']['thema']['v']);
        $this->assertSame($first['fields'], $this->read());

        $second = $this->save(['thema' => ['Interfaces und Vererbung', $first['fields']['thema']['t']]]);
        $this->assertSame([], $second['conflicts']);
        $this->assertSame('Interfaces und Vererbung', $second['fields']['thema']['v']);
        $this->assertGreaterThan($first['fields']['thema']['t'], $second['fields']['thema']['t']);
        $this->assertSame("Einstieg\nÜbung", $second['fields']['plan']['v']);
    }

    /**
     * Mac changes the plan, iPad (with an older state) adds how far the class got: both are kept.
     * Both change the same field: the second one is refused.
     */
    public function test_merge_and_conflict(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $start = $this->save(['plan' => ['Plan A', 0]])['fields'];

        $mac = $this->save(['plan' => ['Plan B', $start['plan']['t']]]);
        $ipad = $this->save(['stand' => ['Bis Aufgabe 3', 0]]);
        $this->assertSame([], $mac['conflicts']);
        $this->assertSame([], $ipad['conflicts']);
        $this->assertSame('Plan B', $ipad['fields']['plan']['v']);
        $this->assertSame('Bis Aufgabe 3', $ipad['fields']['stand']['v']);

        // The iPad still knows the old plan.
        $late = $this->save(['plan' => ['Plan C', $start['plan']['t']], 'geplant' => [true, 0]]);
        $this->assertSame(['plan'], $late['conflicts']);
        $this->assertSame('Plan B', $late['fields']['plan']['v']);
        $this->assertTrue($late['fields']['geplant']['v']);
    }

    /**
     * Removing a field keeps its time, so a stale device cannot bring it back unnoticed.
     */
    public function test_remove(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $t = $this->save(['plan' => ['Plan A', 0]])['fields']['plan']['t'];

        $removed = $this->save(['plan' => ['', $t]]);
        $this->assertNull($removed['fields']['plan']['v']);
        $this->assertSame(['plan'], $this->save(['plan' => ['Alt', $t]])['conflicts']);
        $this->assertSame([], $this->save(['plan' => ['Neu', $removed['fields']['plan']['t']]])['conflicts']);
    }

    /**
     * Nobody sees or changes the planning of somebody else, not even an administrator.
     */
    public function test_own_only(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->save(['plan' => ['Geheim', 0]]);

        $this->setAdminUser();
        $this->assertSame([], $this->read());
        $this->assertSame([], $this->save(['plan' => ['Admin', 0]])['conflicts']);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame([], $this->read());
        $this->assertSame(2, $GLOBALS['DB']->count_records(planstore::TABLE));
    }

    /**
     * Guests have no planning.
     */
    public function test_guest_refused(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $this->expectException(\require_login_exception::class);
        get_plan::execute('2026-11-23', '2026-11-23');
    }

    /**
     * Invalid keys and fields are refused.
     */
    public function test_invalid(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        foreach (['23.11.2026|07:45', '2026-11-23|', str_repeat('2026-11-23|x', 20)] as $key) {
            try {
                save_plan_entry::execute($key, '{"plan":{"v":"x","basis":0}}');
                $this->fail("Key accepted: $key");
            } catch (\invalid_parameter_exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $this->expectException(\invalid_parameter_exception::class);
        save_plan_entry::execute(self::KEY, '{"Plan!":{"v":"x","basis":0}}');
    }

    /**
     * Date range and "changed since".
     */
    public function test_range_and_since(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->save(['plan' => ['A', 0]], '2026-11-22|09:35|12FI1|IT');
        $this->save(['plan' => ['B', 0]], '2026-11-23|07:45|11FI2|OOP');
        $this->save(['plan' => ['C', 0]], '2026-11-27|11:20|FOS11|PoWi');
        $this->save(['plan' => ['D', 0]], '2026-11-28|08:00|X|Y');

        $week = get_plan::execute('2026-11-23', '2026-11-27');
        $this->assertSame(
            ['2026-11-23|07:45|11FI2|OOP', '2026-11-27|11:20|FOS11|PoWi'],
            array_column($week['lessons'], 'lessonkey')
        );
        $this->assertSame([], get_plan::execute('2026-11-23', '2026-11-27', $week['servertime'] + 1)['lessons']);
    }

    /**
     * The first upload takes everything; a second one never overwrites.
     */
    public function test_import(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $lessons = [self::KEY => ['thema' => 'Interfaces', 'plan' => 'Mac', 'material' => [['cmid' => 5, 'kurs' => 2]]],
            '2026-11-24|09:35|12FI2|WEB' => ['thema' => 'Formulare', 'leer' => '']];

        $this->assertSame(['added' => 4, 'skipped' => 0], import_plan::execute(json_encode($lessons)));
        $this->assertSame([['cmid' => 5, 'kurs' => 2]], $this->read()['material']['v']);

        $this->save(['plan' => ['iPad', $this->read()['plan']['t']]]);
        $this->assertSame(['added' => 0, 'skipped' => 4], import_plan::execute(json_encode($lessons)));
        $this->assertSame('iPad', $this->read()['plan']['v']);
    }

    /**
     * Old lessons go after the retention time; deleting the account removes the planning.
     */
    public function test_cleanup_and_user_deleted(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->save(['plan' => ['alt', 0]], '2024-01-08|07:45|10A|M');
        $this->save(['plan' => ['neu', 0]]);
        $DB->set_field(
            planstore::TABLE,
            'timemodified',
            planstore::now() - 731 * DAYSECS * 1000,
            ['lessonkey' => '2024-01-08|07:45|10A|M']
        );

        set_config('planretentiondays', 0, 'local_visudesk');
        $this->assertSame(0, planstore::cleanup());
        set_config('planretentiondays', 730, 'local_visudesk');
        $this->assertSame(1, planstore::cleanup());
        $this->assertSame([self::KEY], $DB->get_fieldset_select(planstore::TABLE, 'lessonkey', '1 = 1'));

        $this->setAdminUser();
        delete_user($user);
        $this->assertSame(0, $DB->count_records(planstore::TABLE));
    }
}
