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
 * Activity overview page for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

// In Moodle 4.3 / 5.0+, redirect seamlessly to the centralized course overview page.
if (class_exists('core_courseformat\activityoverviewbase')) {
    \core_courseformat\activityoverviewbase::redirect_to_overview_page($id, 'codeframe');
    exit;
}

// Fallback for older Moodle versions.
$PAGE->set_url('/mod/codeframe/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_codeframe'));

$codeframes = get_all_instances_in_course('codeframe', $course);

if (empty($codeframes)) {
    $coursenourl = new moodle_url('/course/view.php', ['id' => $course->id]);
    $message = get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_codeframe'));
    notice($message, $coursenourl);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head  = [get_string('name')];
$table->align = ['left'];

foreach ($codeframes as $codeframe) {
    $url = new moodle_url('/mod/codeframe/view.php', ['id' => $codeframe->coursemodule]);
    if (!$codeframe->visible) {
        $link = html_writer::link($url, format_string($codeframe->name), ['class' => 'dimmed']);
    } else {
        $link = html_writer::link($url, format_string($codeframe->name));
    }
    $table->data[] = [$link];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
