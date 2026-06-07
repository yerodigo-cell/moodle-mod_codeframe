<?php
/**
 * Restore task for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/codeframe/backup/moodle2/restore_codeframe_stepslib.php');

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
     */
    static public function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('codeframe', ['intro'], 'codeframe');
        return $contents;
    }

    /**
     * Define the decoding rules for links.
     */
    static public function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('CODEFRAMEINDEX', '/mod/codeframe/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('CODEFRAMEVIEWBYID', '/mod/codeframe/view.php?id=$1', 'course_module');
        return $rules;
    }

    /**
     * Define the log rules.
     */
    static public function define_restore_log_rules() {
        $rules = [];
        $rules[] = new restore_log_rule('codeframe', 'view', 'view.php?id={course_module}', '{codeframe}');
        return $rules;
    }

    static public function define_restore_log_rules_for_course() {
        $rules = [];
        return $rules;
    }
}
