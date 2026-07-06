<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_codeframe\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/../../lib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * External service for tracking time spent viewing a codeframe activity.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class track_time extends external_api {

    /**
     * Parameters for track_time.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Execute the track time.
     *
     * @param int $cmid
     * @return array
     */
    public static function execute($cmid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);
        $cmid = $params['cmid'];

        $cm = get_coursemodule_from_id('codeframe', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/codeframe:view', $context);

        codeframe_ensure_time_table_exists();

        $now = time();
        $pinginterval = 30; // Max gap in seconds to be considered the same session. Pings should happen every 10s.

        $record = $DB->get_record('codeframe_time', ['cmid' => $cmid, 'userid' => $USER->id]);

        if (!$record) {
            // New tracking record.
            $record = new \stdClass();
            $record->cmid = $cmid;
            $record->userid = $USER->id;
            $record->time_started = $now;
            $record->last_session_duration = 10;
            $record->total_duration = 10;
            $record->last_ping = $now;
            $DB->insert_record('codeframe_time', $record);
        } else {
            $timesincelastping = $now - $record->last_ping;

            // Ensure total_duration exists on old records.
            if (!isset($record->total_duration)) {
                $record->total_duration = $record->last_session_duration;
            }

            // Ensure time_started exists on old records (fallback to last_ping if 0).
            if (!isset($record->time_started) || $record->time_started == 0) {
                $record->time_started = $record->last_ping;
            }

            if ($timesincelastping > $pinginterval) {
                // More than 30s passed -> New session.
                $record->last_session_duration = 10;
                $record->total_duration += 10;
            } else {
                // Same session.
                $record->last_session_duration += $timesincelastping;
                $record->total_duration += $timesincelastping;
            }

            $record->last_ping = $now;
            $DB->update_record('codeframe_time', $record);
        }

        return ['status' => 'ok'];
    }

    /**
     * Return structure for track_time.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status ok'),
        ]);
    }
}
