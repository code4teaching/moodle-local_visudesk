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

use local_visudesk\local\vgstore;

/**
 * Tests for the VisuGrade sync: numbering, sending on the newest number only, snapshots, deletion, pairing.
 *
 * @package    local_visudesk
 * @category   test
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(vg_fetch::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vg_push::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vg_set_snapshot::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vg_purge::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vg_pair::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vg_delete_all::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(vgstore::class)]
final class vg_test extends \advanced_testcase {
    /** @var string Device of the Mac. */
    const MAC = 'aaaaaaaaaaaaaaaa';
    /** @var string Device of the iPad. */
    const IPAD = 'bbbbbbbbbbbbbbbb';

    /**
     * Fake encrypted payload.
     *
     * @param string $text
     * @return string
     */
    private static function blob(string $text): string {
        return base64_encode($text);
    }

    /**
     * Numbers in order, other devices fetch, a device that is behind has to fetch first.
     */
    public function test_push_and_fetch(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $r = vg_push::execute(0, self::MAC, [self::blob('a'), self::blob('b')]);
        $this->assertFalse($r['stale']);
        $this->assertSame([1, 2], $r['seqs']);

        $stale = vg_push::execute(0, self::IPAD, [self::blob('c')]);
        $this->assertTrue($stale['stale']);
        $this->assertSame(2, $stale['last']);

        $f = vg_fetch::execute(0);
        $this->assertFalse($f['hassnapshot']);
        $this->assertSame([1, 2], array_column($f['entries'], 'seq'));
        $this->assertSame(self::blob('b'), $f['entries'][1]['data']);

        $this->assertSame([3], vg_push::execute(2, self::IPAD, [self::blob('c')])['seqs']);
        $this->assertSame([3], array_column(vg_fetch::execute(2)['entries'], 'seq'));
    }

    /**
     * Each user only sees their own data.
     */
    public function test_own_data_only(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        vg_push::execute(0, self::MAC, [self::blob('mine')]);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame([], vg_fetch::execute(0)['entries']);
        $this->assertSame([1], vg_push::execute(0, self::MAC, [self::blob('other')])['seqs']);
    }

    /**
     * A device with a gap gets the snapshot and the changes after it; deleting needs a snapshot.
     */
    public function test_snapshot_and_purge(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        vg_push::execute(0, self::MAC, [self::blob('1'), self::blob('2'), self::blob('3')]);

        try {
            vg_purge::execute(3);
            $this->fail('Purge without snapshot');
        } catch (\moodle_exception $e) {
            $this->assertSame('vgnosnapshot', $e->errorcode);
        }

        vg_set_snapshot::execute(2, self::blob('stand'));
        $this->assertSame(2, vg_purge::execute(2)['deleted']);
        vg_push::execute(3, self::MAC, [self::blob('4')]);

        $f = vg_fetch::execute(0);
        $this->assertTrue($f['hassnapshot']);
        $this->assertSame(2, $f['snapshot']['seq']);
        $this->assertSame([3, 4], array_column($f['entries'], 'seq'));

        $this->assertFalse(vg_fetch::execute(3)['hassnapshot']);
        $this->assertSame([4], array_column(vg_fetch::execute(3)['entries'], 'seq'));
    }

    /**
     * After a deletion (school data protection regulation) everything is in the snapshot, numbering goes on.
     */
    public function test_purge_all_numbering_goes_on(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        vg_push::execute(0, self::MAC, [self::blob('1'), self::blob('2')]);
        vg_set_snapshot::execute(2, self::blob('stand'));
        vg_purge::execute(2);
        $this->assertSame([3], vg_push::execute(2, self::MAC, [self::blob('3')])['seqs']);
        $f = vg_fetch::execute(1);
        $this->assertTrue($f['hassnapshot']);
    }

    /**
     * Nightly: old changes covered by a snapshot go, others stay.
     */
    public function test_cleanup(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        vg_push::execute(0, self::MAC, [self::blob('1'), self::blob('2'), self::blob('3')]);
        $old = vgstore::now() - 31 * DAYSECS * 1000;
        $DB->set_field_select(vgstore::ENTRIES, 'timecreated', $old, 'seq <= 2');
        vg_set_snapshot::execute(1, self::blob('stand'));
        $r = vgstore::cleanup();
        $this->assertSame(1, $r['deleted']);
        $this->assertSame([2, 3], array_map('intval', $DB->get_fieldset_select(vgstore::ENTRIES, 'seq', 'userid = ?',
            [$user->id])));
    }

    /**
     * Pairing: request, open, answer, collect once.
     */
    public function test_pairing(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $id = vg_pair::execute('request', 0, self::blob('pubkey'), 'iPhone')['id'];
        $open = vg_pair::execute('open')['open'];
        $this->assertSame('iPhone', $open[0]['devicename']);
        $this->assertSame('', vg_pair::execute('collect', $id)['answer']);
        vg_pair::execute('answer', $id, '', '', self::blob('key'));
        $this->assertSame([], vg_pair::execute('open')['open']);
        $this->assertSame(self::blob('key'), vg_pair::execute('collect', $id)['answer']);
        $this->expectException(\moodle_exception::class);
        vg_pair::execute('collect', $id);
    }

    /**
     * Wrong input is refused.
     */
    public function test_invalid(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\invalid_parameter_exception::class);
        vg_push::execute(0, 'not-a-device!', [self::blob('x')]);
    }

    /**
     * Ending the sync deletes everything of the user.
     */
    public function test_delete_all(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        vg_push::execute(0, self::MAC, [self::blob('1')]);
        vg_set_snapshot::execute(1, self::blob('stand'));
        vg_delete_all::execute();
        $this->assertSame(0, $DB->count_records(vgstore::ENTRIES, ['userid' => $user->id]));
        $this->assertNull(vgstore::snapshot((int)$user->id));
    }
}
