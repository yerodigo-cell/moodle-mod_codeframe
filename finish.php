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
 * Script to handle completion signals from external Canva embeds (Universal version).
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

// Authenticate user.
require_login();

// Retrieve the active CMID from the user's session (set by view.php).
// This allows the link to be universal without hardcoding the ID in Canva.
global $SESSION;
$cmid = isset($SESSION->codeframe_active_cmid) ? $SESSION->codeframe_active_cmid : 0;

$success = false;
if ($cmid > 0) {
    try {
        $cm = get_coursemodule_from_id('codeframe', $cmid, 0, false, MUST_EXIST);
        // Mark completed using the existing external webservice logic.
        \mod_codeframe\external\mark_completed::execute($cmid, $cm->course);
        $success = true;
    } catch (\Exception $e) {
        // Log or ignore errors if the module doesn't exist or permissions fail.
        debugging('Codeframe universal finish error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

$title = get_string('finish_' . ($success ? 'success' : 'error') . '_title', 'mod_codeframe');
$desc = get_string('finish_' . ($success ? 'success' : 'error') . '_desc', 'mod_codeframe');
$color = $success ? '#198754' : '#dc3545';
$auto = get_string('finish_close_auto', 'mod_codeframe');
$btn = get_string('finish_btn_close', 'mod_codeframe');

// Set up the page using Moodle's Page API.
$PAGE->set_url('/mod/codeframe/finish.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('popup');

echo $OUTPUT->header();

$templatedata = [
    'color' => $color,
    'title' => $title,
    'desc'  => $desc,
    'auto'  => $auto,
    'btn'   => $btn
];

$PAGE->requires->js_call_amd('mod_codeframe/finish_page', 'init');

echo $OUTPUT->render_from_template('mod_codeframe/finish_page', $templatedata);

echo $OUTPUT->footer();
