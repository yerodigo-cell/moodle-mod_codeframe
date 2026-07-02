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

/**
 * AJAX script to track time spent viewing a codeframe activity.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_sesskey();

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('codeframe', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/codeframe:view', $context);

require_once(__DIR__ . '/lib.php');
codeframe_ensure_time_table_exists();

$now = time();
$pinginterval = 30; // Max gap in seconds to be considered the same session. Pings should happen every 10s.

$record = $DB->get_record('codeframe_time', ['cmid' => $cmid, 'userid' => $USER->id]);

if (!$record) {
    // New tracking record.
    $record = new stdClass();
    $record->cmid = $cmid;
    $record->userid = $USER->id;
    $record->time_started = $now;
    $record->last_session_duration = 10;
    $record->total_duration = 10;
    $record->last_ping = $now;
    $DB->insert_record('codeframe_time', $record);
} else {
    $time_since_last_ping = $now - $record->last_ping;

    // Ensure total_duration exists on old records.
    if (!isset($record->total_duration)) {
        $record->total_duration = $record->last_session_duration;
    }
    
    // Ensure time_started exists on old records (fallback to last_ping if 0).
    if (!isset($record->time_started) || $record->time_started == 0) {
        $record->time_started = $record->last_ping;
    }

    if ($time_since_last_ping > $pinginterval) {
        // More than 30s passed -> New session
        $record->last_session_duration = 10;
        $record->total_duration += 10;
    } else {
        // Same session
        $record->last_session_duration += $time_since_last_ping;
        $record->total_duration += $time_since_last_ping;
    }
    
    $record->last_ping = $now;
    $DB->update_record('codeframe_time', $record);
}

echo json_encode(['status' => 'ok']);
