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
 * Progress Report for Codeframe activity.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.

$cm = get_coursemodule_from_id('codeframe', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$codeframe = $DB->get_record('codeframe', ['id' => $cm->instance], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Security: Only allow users who can add instances (teachers/managers) to view the report.
require_capability('mod/codeframe:addinstance', $context);

// Set up the page.
$url = new moodle_url('/mod/codeframe/report.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_title(format_string($codeframe->name) . ': ' . get_string('progressreport', 'mod_codeframe'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('progressreport', 'mod_codeframe'));

// Fetch users enrolled in the course who can view the activity.
$coursecontext = context_course::instance($course->id);
$userfields = 'u.id, u.firstname, u.lastname, u.email, u.picture, u.imagealt';
$enrolledusers = get_enrolled_users($coursecontext, 'mod/codeframe:view', 0, $userfields);

// Filter out users who are teachers (i.e., those who can add instances).
$students = [];
foreach ($enrolledusers as $user) {
    if (!has_capability('mod/codeframe:addinstance', $context, $user->id)) {
        $students[] = $user;
    }
}

if (empty($students)) {
    echo html_writer::div(get_string('nostudents', 'mod_codeframe'), 'alert alert-info mt-3');
} else {
    // Fetch all completions for this activity to avoid querying inside the loop.
    $completions = $DB->get_records('codeframe_completion', ['cmid' => $cm->id], '', 'userid, timecompleted, time_spent');

    // Fetch last accesses from standard logstore if it exists.
    $lastaccesses = [];
    if ($DB->get_manager()->table_exists('logstore_standard_log')) {
        $sql = "SELECT userid, MAX(timecreated) AS lastaccess
                  FROM {logstore_standard_log}
                 WHERE contextid = :contextid
              GROUP BY userid";
        $lastaccesses = $DB->get_records_sql($sql, ['contextid' => $context->id]);
    }

    // Fetch time tracking data.
    $timetracks = [];
    require_once(__DIR__ . '/lib.php');
    codeframe_ensure_time_table_exists();
    if ($DB->get_manager()->table_exists('codeframe_time')) {
        $timetracks = $DB->get_records('codeframe_time', ['cmid' => $cm->id], '', 'userid, time_started, total_duration, last_session_duration');
    }

    // Build the table using flexible_table for sorting capabilities.
    require_once($CFG->libdir . '/tablelib.php');
    $table = new flexible_table('mod-codeframe-report-' . $cm->id);
    $table->define_baseurl($url);
    $table->define_columns(['fullname', 'email', 'status', 'started', 'completed', 'duration']);
    $table->define_headers([
        get_string('fullname'),
        get_string('email'),
        get_string('status', 'mod_codeframe'),
        get_string('started', 'mod_codeframe'),
        get_string('completed', 'mod_codeframe'),
        get_string('duration', 'mod_codeframe'),
    ]);
    
    $table->sortable(true, 'fullname');
    $table->set_attribute('class', 'generaltable mt-4');
    $table->setup();

    // Initialize core completion info
    $completion = new \completion_info($course);

    $rows = [];

    foreach ($students as $student) {
        // Render student picture and name.
        $userpicture = new user_picture($student);
        $userpicture->size = 35;
        $picturehtml = $OUTPUT->render($userpicture);
        $fullname = fullname($student);
        $studentcell = html_writer::div($picturehtml . ' ' . $fullname, 'd-flex align-items-center gap-2');

        // Determine completion status using Moodle core and plugin custom table as fallback.
        $cdata = $completion->get_data($cm, false, $student->id);
        $corecompleted = (isset($cdata->completionstate) && ($cdata->completionstate == COMPLETION_COMPLETE || $cdata->completionstate == COMPLETION_COMPLETE_PASS));
        $customcompleted = isset($completions[$student->id]);
        
        $hascompleted = $corecompleted || $customcompleted;
        $hasstarted = isset($timetracks[$student->id]) && $timetracks[$student->id]->time_started > 0;

        $timecompleted = '-';
        $timecompleted_timestamp = 0;
        $timestarted = '-';
        $duration = '-';
        $duration_seconds = 0;

        if ($hasstarted) {
            $timestarted = userdate($timetracks[$student->id]->time_started);
        }

        if ($hascompleted) {
            $statustext = get_string('finished', 'mod_codeframe');
            $icon = '&#10004; ' . $statustext;
            $class = 'badge badge-success bg-success text-white px-2 py-1';
            $statushtml = html_writer::span($icon, $class, ['style' => 'font-size:14px;']);
            
            if ($corecompleted && !empty($cdata->timemodified)) {
                $timecompleted = userdate($cdata->timemodified);
                $timecompleted_timestamp = $cdata->timemodified;
            } elseif ($customcompleted) {
                $timecompleted = userdate($completions[$student->id]->timecompleted);
                $timecompleted_timestamp = $completions[$student->id]->timecompleted;
            }

            // Format completed duration.
            if ($customcompleted && isset($completions[$student->id]->time_spent) && $completions[$student->id]->time_spent > 0) {
                $duration_seconds = $completions[$student->id]->time_spent;
                $mins = floor($duration_seconds / 60);
                $secs = $duration_seconds % 60;
                if ($mins > 0) {
                    $duration = $mins . ' mins ' . $secs . ' secs';
                } else {
                    $duration = $secs . ' secs';
                }
            }
        } elseif ($hasstarted) {
            $statustext = get_string('inprogress', 'mod_codeframe');
            $class = 'badge badge-primary bg-primary text-white px-2 py-1';
            $statushtml = html_writer::span($statustext, $class, ['style' => 'font-size:14px;']);
            
            // Format current total duration.
            if (isset($timetracks[$student->id]->total_duration) && $timetracks[$student->id]->total_duration > 0) {
                $duration_seconds = $timetracks[$student->id]->total_duration;
                $mins = floor($duration_seconds / 60);
                $secs = $duration_seconds % 60;
                if ($mins > 0) {
                    $duration = $mins . ' mins ' . $secs . ' secs';
                } else {
                    $duration = $secs . ' secs';
                }
            }
        } else {
            $statustext = get_string('notstarted', 'mod_codeframe');
            $icon = '&#10006; ' . $statustext;
            $class = 'badge badge-danger bg-danger text-white px-2 py-1';
            $statushtml = html_writer::span($icon, $class, ['style' => 'font-size:14px;']);
        }

        $row = new stdClass();
        $row->fullname_sort = $fullname;
        $row->email_sort = $student->email;
        $row->status_sort = $hascompleted ? 3 : ($hasstarted ? 2 : 1);
        $row->started_sort = $hasstarted ? $timetracks[$student->id]->time_started : 0;
        $row->completed_sort = $timecompleted_timestamp;
        $row->duration_sort = $duration_seconds;

        $row->display = [
            $studentcell,
            $student->email,
            $statushtml,
            $timestarted,
            $timecompleted,
            $duration,
        ];
        
        $rows[] = $row;
    }

    // Sort the rows based on the columns clicked by the user.
    $sortcolumns = $table->get_sort_columns();
    if (empty($sortcolumns)) {
        $sortcolumns = ['fullname' => SORT_ASC];
    }
    
    usort($rows, function($a, $b) use ($sortcolumns) {
        foreach ($sortcolumns as $column => $direction) {
            $sortkey = $column . '_sort';
            if (!isset($a->$sortkey) || !isset($b->$sortkey) || $a->$sortkey == $b->$sortkey) {
                continue;
            }
            $cmp = ($a->$sortkey < $b->$sortkey) ? -1 : 1;
            return ($direction == SORT_DESC) ? -$cmp : $cmp;
        }
        return 0;
    });

    foreach ($rows as $row) {
        $table->add_data($row->display);
    }

    $table->finish_output();
}

echo $OUTPUT->footer();
