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
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/lib.php');

// Authenticate user.
require_login();

// Reject cross-site sub-resource requests (e.g., hidden <img> or <iframe> CSRF triggers),
// direct address-bar navigation (Sec-Fetch-Site: none), and non-user-initiated redirects.
$fetchdest = isset($_SERVER['HTTP_SEC_FETCH_DEST']) ? strtolower((string)$_SERVER['HTTP_SEC_FETCH_DEST']) : '';
$fetchmode = isset($_SERVER['HTTP_SEC_FETCH_MODE']) ? strtolower((string)$_SERVER['HTTP_SEC_FETCH_MODE']) : '';
$fetchsite = isset($_SERVER['HTTP_SEC_FETCH_SITE']) ? strtolower((string)$_SERVER['HTTP_SEC_FETCH_SITE']) : '';
$fetchuser = isset($_SERVER['HTTP_SEC_FETCH_USER']) ? trim((string)$_SERVER['HTTP_SEC_FETCH_USER']) : '';
$isvaliddocumentnavigation = true;
if ($fetchdest !== '' && $fetchdest !== 'document') {
    $isvaliddocumentnavigation = false;
}
if ($fetchmode !== '' && $fetchmode !== 'navigate') {
    $isvaliddocumentnavigation = false;
}
if ($fetchsite !== '' && !in_array($fetchsite, ['cross-site', 'same-origin', 'same-site'], true)) {
    $isvaliddocumentnavigation = false;
}
if ($fetchsite !== '' && $fetchuser !== '?1') {
    $isvaliddocumentnavigation = false;
}

// Retrieve optional explicit CMID or fallback to the active CMID stored in the user's session.
$requestedcmid = optional_param('cmid', 0, PARAM_INT);
global $SESSION;
$cmid = $requestedcmid > 0
    ? $requestedcmid
    : (isset($SESSION->codeframe_active_cmid) ? (int)$SESSION->codeframe_active_cmid : 0);

// Require that this CMID was opened in the current user session within the last 4 hours.
$hasrecentview = false;
if ($cmid > 0 && isset($SESSION->codeframe_active_sessions) && is_array($SESSION->codeframe_active_sessions)) {
    if (!empty($SESSION->codeframe_active_sessions[$cmid])) {
        $openedat = (int)$SESSION->codeframe_active_sessions[$cmid];
        if ((time() - $openedat) <= (4 * 3600)) {
            $hasrecentview = true;
        }
    }
}

$success = false;
if ($isvaliddocumentnavigation && $cmid > 0 && $hasrecentview) {
    try {
        $cm = get_coursemodule_from_id('codeframe', $cmid, 0, false, MUST_EXIST);
        // Mark completed using the existing external webservice logic.
        \mod_codeframe\external\mark_completed::execute($cmid, $cm->course);
        unset($SESSION->codeframe_active_sessions[$cmid]);
        if (isset($SESSION->codeframe_active_cmid) && (int)$SESSION->codeframe_active_cmid === $cmid) {
            unset($SESSION->codeframe_active_cmid);
        }
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
    'btn'   => $btn,
];

$PAGE->requires->js_call_amd('mod_codeframe/finish_page', 'init', [$success ? $cmid : 0]);

echo $OUTPUT->render_from_template('mod_codeframe/finish_page', $templatedata);

echo $OUTPUT->footer();
