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
 * Restore task for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/codeframe/backup/moodle2/restore_codeframe_stepslib.php');

/**
 * Restore task for the Codeframe activity class.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_codeframe_activity_task extends restore_activity_task {
    /**
     * Define the specific settings for the task.
     */
    protected function define_my_settings() {
        // No particular settings required.
    }

    /**
     * Define the restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_codeframe_activity_structure_step('codeframe_structure', 'codeframe.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('codeframe', ['intro'], 'codeframe');
        return $contents;
    }

    /**
     * Define the decoding rules for links.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('CODEFRAMEINDEX', '/mod/codeframe/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('CODEFRAMEVIEWBYID', '/mod/codeframe/view.php?id=$1', 'course_module');
        return $rules;
    }

    /**
     * Define the log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        $rules = [];
        $rules[] = new restore_log_rule('codeframe', 'view', 'view.php?id={course_module}', '{codeframe}');
        return $rules;
    }

    /**
     * Define the restore log rules for course.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];
        return $rules;
    }
}
