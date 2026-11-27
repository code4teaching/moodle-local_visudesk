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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_visudesk\local\grading;

/**
 * Grades one participant of an assignment: grade and feedback comment, and with marking workflow its state
 * (e.g. "readyforrelease"; students only see the grade once it is "released"). Feedback files stay
 * (see set_feedback_files).
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_submission extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID of the assignment'),
            'userid' => new external_value(PARAM_INT, 'The participant'),
            'changegrade' => new external_value(PARAM_BOOL, 'Set the grade', VALUE_DEFAULT, true),
            'grade' => new external_value(
                PARAM_FLOAT,
                'Points, or for a scale the item number (1 = worst); -1 = none',
                VALUE_DEFAULT,
                -1
            ),
            'changefeedback' => new external_value(PARAM_BOOL, 'Replace the feedback comment', VALUE_DEFAULT, false),
            'feedback' => new external_value(PARAM_RAW, 'Feedback comment (HTML)', VALUE_DEFAULT, ''),
            'workflowstate' => new external_value(PARAM_ALPHA, 'Marking workflow state ("" = unchanged)', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Saves the grade.
     *
     * @param int $cmid
     * @param int $userid
     * @param bool $changegrade
     * @param float $grade
     * @param bool $changefeedback
     * @param string $feedback
     * @param string $workflowstate
     * @return array
     */
    public static function execute(
        int $cmid,
        int $userid,
        bool $changegrade = true,
        float $grade = -1,
        bool $changefeedback = false,
        string $feedback = '',
        string $workflowstate = ''
    ): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/grade/grading/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid, 'userid' => $userid, 'changegrade' => $changegrade, 'grade' => $grade,
            'changefeedback' => $changefeedback, 'feedback' => $feedback, 'workflowstate' => $workflowstate,
        ]);
        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        $assign = new \assign($context, $cm, get_course($cm->course));
        $instance = $assign->get_instance();
        grading::require_supported($assign);
        grading::require_gradable($assign, $params['userid']);
        // Rubrics and other advanced grading methods need their own form: grade those in Moodle.
        if (get_grading_manager($context, 'mod_assign', 'submissions')->get_active_controller()) {
            throw new \moodle_exception('advancedgrading', 'local_visudesk');
        }
        // Locked or overridden in the gradebook, or in a workflow state this user may not change (e.g. released).
        if ($assign->grading_disabled($params['userid'])) {
            throw new \moodle_exception('gradingdisabled', 'local_visudesk');
        }
        $state = $params['workflowstate'];
        if ($state !== '') {
            // Only the states Moodle offers this user; "released" needs mod/assign:releasegrades.
            $allowed = $instance->markingworkflow ? array_keys($assign->get_marking_workflow_states_for_current_user()) : [];
            if (!in_array($state, $allowed, true)) {
                throw new \moodle_exception('workflowstatenotallowed', 'local_visudesk', '', s($state));
            }
            if ($state === ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
                require_capability('mod/assign:releasegrades', $context);
            }
        }

        $record = $assign->get_user_grade($params['userid'], true);
        if ($params['changegrade']) {
            $value = $params['grade'];
            if ($value < 0) {
                $value = -1;
            } else if ((int)$instance->grade > 0) {
                if ($value > (float)$instance->grade) {
                    throw new \moodle_exception('gradetoohigh', 'local_visudesk', '', format_float($instance->grade, -1));
                }
            } else if ((int)$instance->grade < 0) {
                $items = (int)count(explode(',', (string)$DB->get_field('scale', 'scale', ['id' => -(int)$instance->grade])));
                if ($value != round($value) || $value < 1 || $value > $items) {
                    throw new \moodle_exception('gradenotinscale', 'local_visudesk');
                }
            } else {
                throw new \moodle_exception('nogradetype', 'local_visudesk');
            }
            $record->grade = $value;
            $record->grader = $USER->id;
        }
        if ($params['changefeedback']) {
            // Through the feedback comments plugin, like the grading form.
            $comments = $assign->get_feedback_plugin_by_type('comments');
            if (!$comments || !$comments->is_enabled() || !$comments->is_visible()) {
                throw new \moodle_exception('feedbackcommentsdisabled', 'local_visudesk');
            }
            $comments->save($record, (object)[
                'assignfeedbackcomments_editor' => ['text' => $params['feedback'], 'format' => FORMAT_HTML],
            ]);
            if (get_config('assign', 'feedback_plugin_for_gradebook') === 'assignfeedback_comments') {
                $record->feedbacktext = $comments->text_for_gradebook($record);
                $record->feedbackformat = $comments->format_for_gradebook($record);
            }
        }
        if ($state !== '') {
            $flags = $assign->get_user_flags($params['userid'], true);
            $flags->workflowstate = $state;
            $assign->update_user_flags($flags);
        }
        // Saves the grade and passes it to the gradebook (with marking workflow only once released). update_grade()
        // sets ->grade of the object to -1 when the grade is not released yet, so the saved value is kept first.
        $saved = $record->grade !== null ? (float)$record->grade : -1.0;
        $assign->update_grade($record);

        $flags = $assign->get_user_flags($params['userid'], false);
        return [
            'grade' => $saved,
            'workflowstate' => $flags && $flags->workflowstate ? $flags->workflowstate : '',
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'grade' => new external_value(PARAM_FLOAT, 'Grade now, -1 = none'),
            'workflowstate' => new external_value(PARAM_ALPHA, 'Marking workflow state now'),
        ]);
    }
}
