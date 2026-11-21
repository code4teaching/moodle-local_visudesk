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
 * Install step: settings of local_coursefiles, the former name, are taken over.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::xmldb_local_visudesk_install
 */
final class install_test extends \advanced_testcase {
    /**
     * Settings come over, missing ones stay as they are.
     */
    public function test_takes_over_settings(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/visudesk/db/install.php');
        $this->resetAfterTest();
        set_config('autoapprove', 1, 'local_coursefiles');
        set_config('autoapprovenotify', 'pruefer', 'local_coursefiles');
        set_config('autoapprovearchive', 'Ehemalige', 'local_coursefiles');
        unset_config('siterole', 'local_visudesk');
        $this->assertTrue(xmldb_local_visudesk_install());
        $this->assertEquals('1', get_config('local_visudesk', 'autoapprove'));
        $this->assertSame('pruefer', get_config('local_visudesk', 'autoapprovenotify'));
        $this->assertSame('Ehemalige', get_config('local_visudesk', 'autoapprovearchive'));
        $this->assertFalse(get_config('local_visudesk', 'siterole'));
    }
}
