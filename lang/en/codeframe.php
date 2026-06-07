<?php
/**
 * English strings for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename']              = 'Codeframe';
$string['modulename_help']         = 'The Codeframe activity module allows teachers to embed external content, such as interactive presentations, via iframe and automatically track completion through window postMessages.';
$string['modulenameplural']        = 'Codeframe Activities';
$string['pluginname']              = 'Codeframe';
$string['pluginadministration']    = 'Codeframe administration';
$string['codeframe:addinstance']   = 'Add a new Codeframe activity';
$string['codeframe:view']          = 'View Codeframe activity';
$string['embedcode']               = 'Presentation URL';
$string['embedcode_help']          = 'Paste only the direct URL of the interactive content (e.g. https://example.com/slide). The plugin will automatically generate the embed code. You do not need to write any HTML.';
$string['uploadfiles']             = 'Or upload HTML5 presentation files';
$string['uploadfiles_help']        = 'Upload a folder or a zip containing your HTML5 content (images, JS, CSS, audio, etc.). The main file must be named "index.html" (or another .html file) at the root level.';
$string['error_url_or_files']      = 'You must either provide a Presentation URL or upload HTML5 presentation files.';
$string['nohtmlfile']              = 'Error: No HTML file (such as index.html) was found in the uploaded files. Please upload your presentation again with a valid HTML file at the root.';
$string['completioncomplete']      = 'Require iframe completion';
$string['completioncomplete_help'] = 'If enabled, the student must complete the activity within the embedded iframe (the iframe must send a codeframe_completed message) to mark this activity as complete.';
$string['completioninfo']          = '&#128274; <strong>Completion tracking:</strong> This activity will be marked complete automatically when the student <em>views the page</em> AND the <em>embedded content signals completion</em>. Both conditions are pre-enabled below under "Completion conditions".<br><br><strong>Developer Note:</strong> For automatic completion to work, your embedded HTML presentation must execute the following JavaScript code when the student finishes the activity:<br><code>&lt;script&gt;window.parent.postMessage(\'codeframe_completed\', \'*\');&lt;/script&gt;</code><br><br><em>If you are pasting this code inside a <strong>Genially</strong> presentation (using Insert &gt; Others), use this alternative version to bypass Genially\'s internal iframes:</em><br><code>&lt;script&gt;window.top.postMessage(\'codeframe_completed\', \'*\');&lt;/script&gt;</code>';

// Report strings
$string['progressreport'] = 'Progress Report';
$string['student'] = 'Student';
$string['status'] = 'Status';
$string['completed'] = 'Completed';
$string['notcompleted'] = 'Not completed';
$string['timecompleted'] = 'Time completed';
$string['nostudents'] = 'There are no students enrolled in this course.';

// Privacy / GDPR strings
$string['privacy:metadata:codeframe_completion'] = 'Information about student completions of the interactive activity.';
$string['privacy:metadata:codeframe_completion:userid'] = 'The ID of the user who completed the activity.';
$string['privacy:metadata:codeframe_completion:timecompleted'] = 'The exact timestamp when the user completed the activity.';
