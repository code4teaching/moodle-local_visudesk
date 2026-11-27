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

/**
 * Tests that grading through VisuDesk keeps Moodle's rules: release capability, separate groups, anonymous
 * submissions and locked grades.
 *
 * @package    local_visudesk
 * @category   test
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_assign_grading::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(grade_submission::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(release_grades::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_visudesk\local\grading::class)]
final class grading_rules_test extends \advanced_testcase {
    /**
     * A course with an assignment (15 points, marking workflow) created by an editing teacher.
     *
     * @return array [course, cmid, assignid]
     */
    private function assignment(): array {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $this->setUser($gen->create_and_enrol($course, 'editingteacher'));
        $result = create_assign::execute($course->id, 0, 'Aufgabe', '', [], 0, 1, 15, '', 1);
        return [$course, (int)$result['cmid'], (int)$result['assignid']];
    }

    /**
     * A non-editing teacher (no mod/assign:releasegrades) cannot release a grade through grade_submission either.
     */
    public function test_noneditingteacher_cannot_release(): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $this->resetAfterTest();
        [$course, $cmid, $assignid] = $this->assignment();
        $gen = $this->getDataGenerator();
        $student = $gen->create_and_enrol($course, 'student');
        $this->setUser($gen->create_and_enrol($course, 'teacher'));

        try {
            grade_submission::execute($cmid, $student->id, true, 12, false, '', 'released');
            $this->fail('A non-editing teacher released a grade.');
        } catch (\moodle_exception $e) {
            $this->assertSame('workflowstatenotallowed', $e->errorcode);
        }
        $gradebook = grade_get_grades($course->id, 'mod', 'assign', $assignid, $student->id);
        $this->assertNull($gradebook->items[0]->grades[$student->id]->grade);

        $this->expectException(\required_capability_exception::class);
        release_grades::execute($cmid, [$student->id]);
    }

    /**
     * Separate groups: a teacher without moodle/site:accessallgroups sees and grades only their groups.
     */
    public function test_separate_groups(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid] = $this->assignment();
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $cmid]);
        rebuild_course_cache($course->id, true);
        $gen = $this->getDataGenerator();
        $groupa = $gen->create_group(['courseid' => $course->id]);
        $groupb = $gen->create_group(['courseid' => $course->id]);
        $studenta = $gen->create_and_enrol($course, 'student');
        $studentb = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'teacher');
        $gen->create_group_member(['groupid' => $groupa->id, 'userid' => $studenta->id]);
        $gen->create_group_member(['groupid' => $groupb->id, 'userid' => $studentb->id]);

        // In no group: nobody.
        $this->setUser($teacher);
        $this->assertSame([], get_assign_grading::execute($cmid)['users']);

        $gen->create_group_member(['groupid' => $groupa->id, 'userid' => $teacher->id]);
        $users = get_assign_grading::execute($cmid)['users'];
        $this->assertSame([(int)$studenta->id], array_column($users, 'userid'));
        $this->assertEquals(10, grade_submission::execute($cmid, $studenta->id, true, 10)['grade']);
        try {
            grade_submission::execute($cmid, $studentb->id, true, 10);
            $this->fail('A grade for another group was accepted.');
        } catch (\moodle_exception $e) {
            $this->assertSame('notparticipant', $e->errorcode);
        }
        $this->assertNull($DB->get_record('assign_grades', ['userid' => $studentb->id]) ?: null);
    }

    /**
     * Anonymous submissions are refused: VisuDesk would show the real names.
     */
    public function test_blind_marking_refused(): void {
        global $DB;
        $this->resetAfterTest();
        [, $cmid, $assignid] = $this->assignment();
        $DB->set_field('assign', 'blindmarking', 1, ['id' => $assignid]);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('blindmarking', 'local_visudesk'));
        get_assign_grading::execute($cmid);
    }

    /**
     * A grade locked in the gradebook is not changed.
     */
    public function test_locked_grade(): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $this->resetAfterTest();
        [$course, $cmid, $assignid] = $this->assignment();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $item = \grade_item::fetch([
            'itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $assignid, 'courseid' => $course->id,
        ]);
        $item->set_locked(true);
        try {
            grade_submission::execute($cmid, $student->id, true, 10);
            $this->fail('A locked grade was changed.');
        } catch (\moodle_exception $e) {
            $this->assertSame('gradingdisabled', $e->errorcode);
        }
    }
}
