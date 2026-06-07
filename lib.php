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
 * Library of functions and callbacks for the Codeframe activity module.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a new instance of a Codeframe activity.
 *
 * @param stdClass $codeframe The form data submitted by the teacher.
 * @param mod_codeframe_mod_form $mform The form object.
 * @return int The ID of the newly created instance.
 */
function codeframe_add_instance(stdClass $codeframe, mod_codeframe_mod_form $mform = null) {
    global $DB;

    $data = new stdClass();
    $data->course             = $codeframe->course;
    $data->name               = $codeframe->name;
    $data->intro              = $codeframe->intro;
    $data->introformat        = $codeframe->introformat;
    $data->embedcode          = $codeframe->embedcode;
    $data->completioncomplete = !empty($codeframe->completioncomplete) ? 1 : 0;
    $data->timecreated        = time();
    $data->timemodified       = time();

    // Insert the record into mdl_codeframe.
    $data->id = $DB->insert_record('codeframe', $data);

    // Save files if form is provided.
    if ($mform && !empty($codeframe->files)) {
        $context = context_module::instance($codeframe->coursemodule);
        file_save_draft_area_files(
            $codeframe->files,
            $context->id,
            'mod_codeframe',
            'content',
            $data->id,
            ['subdirs' => 1]
        );
    }

    return $data->id;
}

/**
 * Update an existing instance of a Codeframe activity.
 *
 * @param stdClass $codeframe The form data submitted by the teacher.
 * @param mod_codeframe_mod_form $mform The form object.
 * @return bool True on success.
 */
function codeframe_update_instance(stdClass $codeframe, mod_codeframe_mod_form $mform = null) {
    global $DB;

    $data = new stdClass();
    $data->id                 = $codeframe->instance;
    $data->course             = $codeframe->course;
    $data->name               = $codeframe->name;
    $data->intro              = $codeframe->intro;
    $data->introformat        = $codeframe->introformat;
    $data->embedcode          = $codeframe->embedcode;
    $data->completioncomplete = !empty($codeframe->completioncomplete) ? 1 : 0;
    $data->timemodified       = time();

    // Update the record in mdl_codeframe.
    $result = $DB->update_record('codeframe', $data);

    // Save files if form is provided.
    if ($mform && !empty($codeframe->files)) {
        $context = context_module::instance($codeframe->coursemodule);
        file_save_draft_area_files(
            $codeframe->files,
            $context->id,
            'mod_codeframe',
            'content',
            $data->id,
            ['subdirs' => 1]
        );
    }

    return $result;
}

/**
 * Delete an instance of a Codeframe activity.
 *
 * @param int $id The ID of the activity instance to delete.
 * @return bool True on success.
 */
function codeframe_delete_instance($id) {
    global $DB;

    // Verify record exists before deletion.
    $codeframe = $DB->get_record('codeframe', array('id' => $id));
    if (!$codeframe) {
        return false;
    }

    // Delete associated files from Moodle storage.
    $fs = get_file_storage();
    $cm = get_coursemodule_from_instance('codeframe', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs->delete_area_files($context->id, 'mod_codeframe', 'content', $id);
    }

    // Delete the record from mdl_codeframe.
    return $DB->delete_records('codeframe', array('id' => $id));
}

/**
 * Declare support for Moodle core features.
 *
 * @param string $feature The feature constant (e.g. FEATURE_COMPLETION_TRACKS_VIEWS).
 * @return bool|null True if supported, false if not, or null if unknown.
 */
function codeframe_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Build the HTML to embed in the student view.
 *
 * Accepts either:
 *   a) A plain URL  → wraps it in a safe <iframe>.
 *   b) Raw HTML     → extracts the iframe src to prevent double-wrappers (e.g. Canva),
 *                     or returns it unchanged if it's a script/custom embed.
 *
 * @param string $embedcode Value stored in mdl_codeframe.embedcode.
 * @return string Safe HTML ready to be echoed inside the wrapper div.
 */
function codeframe_build_embed_html(string $embedcode): string {
    $value = trim($embedcode);

    if ($value === '') {
        return '';
    }

    // Check if the user pasted a raw HTML snippet containing an iframe (like Canva's embed code).
    // If so, we extract just the 'src' URL to use our own clean responsive wrapper.
    if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $value, $matches)) {
        // Decode HTML entities (like Canva's &#x2F;) back into a normal URL.
        $value = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } else if (strpos($value, '<') === 0) {
        // If it's HTML but NOT an iframe (e.g. a script embed), return it as-is.
        return $value;
    }

    // Now $value is definitely a plain URL.
    
    // --- SMART URL CONVERTERS ---
    // YouTube
    if (preg_match('#^https?://(?:www\.)?youtube\.com/watch\?v=([^&]+)#i', $value, $matches)) {
        $value = 'https://www.youtube.com/embed/' . $matches[1];
    } else if (preg_match('#^https?://youtu\.be/([^?]+)#i', $value, $matches)) {
        $value = 'https://www.youtube.com/embed/' . $matches[1];
    }
    // Google Docs (Publish to web -> /pub)
    else if (preg_match('#^(https://docs\.google\.com/document/d/(?:e/)?[^/]+/pub)(\?.*)?$#i', $value, $matches)) {
        $qs = !empty($matches[2]) ? $matches[2] . '&embedded=true' : '?embedded=true';
        $value = $matches[1] . $qs;
    }
    // Google Slides (Publish to web -> /pub)
    else if (preg_match('#^(https://docs\.google\.com/presentation/d/(?:e/)?[^/]+)/pub(\?.*)?$#i', $value, $matches)) {
        $qs = !empty($matches[2]) ? $matches[2] : '';
        $value = $matches[1] . '/embed' . $qs;
    }
    // Google Forms
    else if (preg_match('#^(https://docs\.google\.com/forms/d/(?:e/)?[^/]+/viewform)(\?.*)?$#i', $value, $matches)) {
        $qs = !empty($matches[2]) ? $matches[2] . '&embedded=true' : '?embedded=true';
        $value = $matches[1] . $qs;
    }
    // Google Sheets
    else if (preg_match('#^(https://docs\.google\.com/spreadsheets/d/(?:e/)?[^/]+/pubhtml)(\?.*)?$#i', $value, $matches)) {
        $qs = !empty($matches[2]) ? $matches[2] . '&widget=true&headers=false' : '?widget=true&headers=false';
        $value = $matches[1] . $qs;
    }
    // ----------------------------

    // htmlspecialchars prevents XSS via crafted URLs.
    $safeurl = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

    return '<iframe'
        . ' src="' . $safeurl . '"'
        . ' width="100%"'
        . ' height="100%"'
        . ' frameborder="0"'
        . ' allow="autoplay; fullscreen; encrypted-media"'
        . ' style="border:0;"'
        . '></iframe>';
}

/**
 * Serves the embedded HTML5 slide files (JavaScript, CSS, images, audio, etc.).
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context object.
 * @param string $filearea The file area ('content').
 * @param array $args Extra arguments from the URL.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool False on failure, or sends the file and exits.
 */
function codeframe_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;

    // Ensure user is logged in.
    require_course_login($course, true, $cm);

    // Only serve files from the 'content' area.
    if ($filearea !== 'content') {
        return false;
    }

    // The first argument is the activity instance ID.
    $instanceid = array_shift($args);

    // Reconstruct the filepath and filename from the remaining arguments.
    $filepath = '/';
    $filename = array_pop($args);
    if (!empty($args)) {
        $filepath = '/' . implode('/', $args) . '/';
    }

    // Fetch the file from storage.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_codeframe', 'content', $instanceid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    // Send the stored file to the browser.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Extends the settings navigation for the Codeframe activity.
 * This injects the "Progress Report" tab into the secondary navigation (Moodle 4.x).
 *
 * @param settings_navigation $settingsnav The settings navigation tree.
 * @param navigation_node $codeframenode The current activity node.
 */
function codeframe_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $codeframenode) {
    global $PAGE;

    // Ensure we are inside a codeframe context.
    if (!isset($PAGE->cm->id) || $PAGE->cm->modname !== 'codeframe') {
        return;
    }

    // Only show the report to users with permission to add instances (teachers/managers).
    if (has_capability('mod/codeframe:addinstance', $PAGE->context)) {
        // Create the URL for the report page.
        $url = new moodle_url('/mod/codeframe/report.php', ['id' => $PAGE->cm->id]);
        
        // Add the node to the settings navigation.
        $node = $codeframenode->add(
            get_string('progressreport', 'mod_codeframe'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'codeframereport',
            new pix_icon('i/report', '')
        );

        // Make it active if we are currently on the report page.
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $node->make_active();
        }
    }
}


