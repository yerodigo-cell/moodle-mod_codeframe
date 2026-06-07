<?php
/**
 * External function for marking a Codeframe activity as complete.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codeframe\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

/**
 * External function mod_codeframe_mark_completed.
 *
 * Flow:
 *  1. Validate & clean parameters.
 *  2. Verify context and capability.
 *  3. Insert/update a row in mdl_codeframe_completion (our tracking table).
 *  4. Call completion_info::update_state() — Moodle will now call
 *     custom_completion::get_state(), find the row we just wrote, and
 *     persist COMPLETION_COMPLETE to mdl_course_modules_completion.
 */
class mark_completed extends external_api {

    /**
     * Describe accepted parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid'     => new external_value(PARAM_INT, 'Course module ID of the activity.', VALUE_REQUIRED),
            'courseid' => new external_value(PARAM_INT, 'Course ID.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Mark the activity as completed for the current user.
     *
     * @param int $cmid
     * @param int $courseid
     * @return array
     * @throws \moodle_exception
     */
    public static function execute(int $cmid, int $courseid): array {
        global $DB, $USER;

        // 1. Validate and clean parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'     => $cmid,
            'courseid' => $courseid,
        ]);

        // 2. Fetch records and validate context.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $cm     = get_coursemodule_from_id('codeframe', $params['cmid'], 0, false, MUST_EXIST);

        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/codeframe:view', $context);

        // 3. Check completion is enabled for this module.
        $completion = new \completion_info($course);

        if (!$completion->is_enabled($cm)) {
            return [
                'status'  => false,
                'message' => 'Completion tracking is not enabled for this activity.',
            ];
        }

        // 4. Write to our own tracking table BEFORE calling update_state().
        //    custom_completion::get_state() reads from this table, so the
        //    record must exist when Moodle evaluates the completion rules.
        $existing = $DB->get_record('codeframe_completion', [
            'cmid'   => $cm->id,
            'userid' => $USER->id,
        ]);

        if ($existing) {
            // Already recorded — nothing more to do.
            $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
            return [
                'status'  => true,
                'message' => 'Activity was already marked as complete.',
            ];
        }

        // Insert the completion record.
        $record = new \stdClass();
        $record->cmid          = $cm->id;
        $record->userid        = $USER->id;
        $record->timecompleted = time();
        $DB->insert_record('codeframe_completion', $record);

        // 5. Trigger Moodle's completion engine.
        //    Now get_state() will find the row and return COMPLETION_COMPLETE.
        $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);

        return [
            'status'  => true,
            'message' => 'Activity successfully marked as complete.',
        ];
    }

    /**
     * Describe the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status'  => new external_value(PARAM_BOOL, 'True if the activity was marked complete.'),
            'message' => new external_value(PARAM_TEXT, 'Feedback message.'),
        ]);
    }
}
