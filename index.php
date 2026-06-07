<?php
/**
 * Activity overview page for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);

// In Moodle 4.3 / 5.0+, redirect seamlessly to the centralized course overview page.
if (class_exists('core_courseformat\activityoverviewbase')) {
    \core_courseformat\activityoverviewbase::redirect_to_overview_page($id, 'codeframe');
    exit;
}

// Fallback for older Moodle versions.
$PAGE->set_url('/mod/codeframe/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_codeframe'));

$codeframes = get_all_instances_in_course('codeframe', $course);

if (empty($codeframes)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_codeframe')), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head  = array(get_string('name'));
$table->align = array('left');

foreach ($codeframes as $codeframe) {
    if (!$codeframe->visible) {
        $link = html_writer::link(new moodle_url('/mod/codeframe/view.php', array('id' => $codeframe->coursemodule)), format_string($codeframe->name), array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(new moodle_url('/mod/codeframe/view.php', array('id' => $codeframe->coursemodule)), format_string($codeframe->name));
    }
    $table->data[] = array($link);
}

echo html_writer::table($table);
echo $OUTPUT->footer();
