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
    $completions = $DB->get_records('codeframe_completion', ['cmid' => $cm->id], '', 'userid, timecompleted');

    // Build the table.
    $table = new html_table();
    $table->attributes['class'] = 'generaltable mt-4';
    $table->head = [
        get_string('student', 'mod_codeframe'),
        get_string('email'),
        get_string('status', 'mod_codeframe'),
        get_string('timecompleted', 'mod_codeframe'),
    ];

    foreach ($students as $student) {
        // Render student picture and name.
        $userpicture = new user_picture($student);
        $userpicture->size = 35;
        $picturehtml = $OUTPUT->render($userpicture);
        $fullname = fullname($student);
        $studentcell = html_writer::div($picturehtml . ' ' . $fullname, 'd-flex align-items-center gap-2');

        // Determine completion status.
        $hascompleted = isset($completions[$student->id]);

        if ($hascompleted) {
            $statustext = get_string('completed', 'mod_codeframe');
            $icon = '&#10004; ' . $statustext;
            $class = 'badge badge-success bg-success text-white px-2 py-1';
            $statushtml = html_writer::span($icon, $class, ['style' => 'font-size:14px;']);
            $timecompleted = userdate($completions[$student->id]->timecompleted);
        } else {
            $statustext = get_string('notcompleted', 'mod_codeframe');
            $icon = '&#10006; ' . $statustext;
            $class = 'badge badge-secondary bg-secondary text-white px-2 py-1';
            $statushtml = html_writer::span($icon, $class, ['style' => 'font-size:14px;']);
            $timecompleted = '-';
        }

        $table->data[] = [
            $studentcell,
            $student->email,
            $statushtml,
            $timecompleted,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
