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
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividad Completada</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f8f9fa; color: #212529; text-align: center; }
        .container { padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn { padding: 10px 20px; font-size: 16px; background: #198754; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <h2 style="color: #198754;"><?php echo get_string('finish_success_title', 'mod_codeframe'); ?></h2>
            <p><?php echo get_string('finish_success_desc', 'mod_codeframe'); ?></p>
        <?php else: ?>
            <h2 style="color: #dc3545;"><?php echo get_string('finish_error_title', 'mod_codeframe'); ?></h2>
            <p><?php echo get_string('finish_error_desc', 'mod_codeframe'); ?></p>
        <?php endif; ?>
        <p><small><?php echo get_string('finish_close_auto', 'mod_codeframe'); ?></small></p>
        <button class="btn" onclick="window.close()"><?php echo get_string('finish_btn_close', 'mod_codeframe'); ?></button>
    </div>

    <script>
        // Send a universal completion signal to all open Moodle tabs to update their UI dynamically
        try {
            var timestamp = Date.now().toString();
            localStorage.setItem('codeframe_canva_finished_universal', timestamp);
            
            // Clean it up immediately so it can be triggered again later
            setTimeout(function() {
                localStorage.removeItem('codeframe_canva_finished_universal');
            }, 500);
        } catch (e) {
            console.error('No se pudo escribir en localStorage', e);
        }

        // Close the window automatically
        setTimeout(function() {
            window.close();
        }, 1500);
    </script>
</body>
</html>
