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
 * Backup task for the Codeframe module.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/codeframe/backup/moodle2/backup_codeframe_stepslib.php');

/**
 * Backup task for the Codeframe module class.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_codeframe_activity_task extends backup_activity_task {
    /**
     * Define the backup settings for the activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Define the backup steps for the activity.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_codeframe_activity_structure_step('codeframe_structure', 'codeframe.xml'));
    }

    /**
     * Encode content links.
     *
     * @param string $content The content to encode.
     * @return string The encoded content.
     */
    protected static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of codeframes.
        $search = '/(' . $base . '\/mod\/codeframe\/index.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@CODEFRAMEINDEX*$2@$', $content);

        // Link to codeframe view by moduleid.
        $search = '/(' . $base . '\/mod\/codeframe\/view.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@CODEFRAMEVIEWBYID*$2@$', $content);

        return $content;
    }
}
