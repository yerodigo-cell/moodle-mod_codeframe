<?php
/**
 * View page for the Codeframe activity module.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// The course module ID.
$id = required_param('id', PARAM_INT);

// Fetch all necessary database objects.
$cm        = get_coursemodule_from_id('codeframe', $id, 0, false, MUST_EXIST);
$course    = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$codeframe = $DB->get_record('codeframe', array('id' => $cm->instance), '*', MUST_EXIST);

// Security checks and course login.
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/codeframe:view', $context);

// Set up Moodle page properties.
$PAGE->set_url('/mod/codeframe/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($codeframe->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Trigger course module viewed event (for logging and course statistics).
$event = \mod_codeframe\event\course_module_viewed::create(array(
    'objectid' => $codeframe->id,
    'context'  => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('codeframe', $codeframe);
$event->trigger();

// Mark viewed in the Completion API system (implements FEATURE_COMPLETION_TRACKS_VIEWS).
$completion = new \completion_info($course);
$completion->set_module_viewed($cm);

// Output page header.
echo $OUTPUT->header();


// Render embed code inside a two-layer responsive wrapper.
// .codeframe_container  → full-width outer container
// .codeframe_wrapper    → maintains 16:9 aspect ratio via padding-bottom trick
// codeframe_build_embed_html() converts a plain URL into an iframe automatically,
// and also supports legacy activities that already stored raw HTML.
echo html_writer::start_div('codeframe_container', ['id' => 'codeframeembed']);

$wrapperattributes = [];
// Google Slides adds exactly a 29px control bar to the bottom of the iframe player.
// To prevent 16:9 slides from being letterboxed (black bars on the sides) to fit this bar,
// we dynamically add 29px of vertical space to the wrapper's 16:9 calculation.
if (strpos($codeframe->embedcode, 'docs.google.com/presentation') !== false) {
    $wrapperattributes['style'] = 'padding-bottom: calc(56.25% + 29px);';
}

echo html_writer::start_div('codeframe_wrapper', $wrapperattributes);

if (trim($codeframe->embedcode) !== '') {
    echo codeframe_build_embed_html($codeframe->embedcode);
} else {
    // Scan Moodle's storage for uploaded HTML5 files.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_codeframe', 'content', $codeframe->id, 'id', false);

    // Look for index.html by default.
    $mainfile = null;
    foreach ($files as $file) {
        if (!$file->is_directory() && strtolower($file->get_filename()) === 'index.html') {
            $mainfile = $file;
            break;
        }
    }

    // Fallback: use the first HTML file found in the package.
    if (!$mainfile) {
        foreach ($files as $file) {
            if (!$file->is_directory() && preg_match('/\.html?$/i', $file->get_filename())) {
                $mainfile = $file;
                break;
            }
        }
    }

    if ($mainfile) {
        // Construct a secure Moodle pluginfile URL.
        $url = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_codeframe',
            'content',
            $codeframe->id,
            $mainfile->get_filepath(),
            $mainfile->get_filename()
        );
        echo '<iframe'
            . ' src="' . $url . '"'
            . ' width="100%"'
            . ' height="100%"'
            . ' frameborder="0"'
            . ' allow="autoplay; fullscreen; encrypted-media"'
            . ' style="border:0;"'
            . '></iframe>';
    } else {
        echo html_writer::div(get_string('nohtmlfile', 'mod_codeframe'), 'alert alert-danger');
    }
}

echo html_writer::end_div(); // .codeframe_wrapper
echo html_writer::end_div(); // .codeframe_container

// Initialize the AMD tracking script, passing cmid and courseid as parameters.
$PAGE->requires->js_call_amd('mod_codeframe/tracker', 'init', array($cm->id, $course->id));

// Output page footer.
echo $OUTPUT->footer();
