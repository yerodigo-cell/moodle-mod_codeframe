<?php
/**
 * Backup task for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/codeframe/backup/moodle2/backup_codeframe_stepslib.php');

class backup_codeframe_activity_task extends backup_activity_task {

    /**
     * Define the specific settings for the task.
     */
    protected function define_my_settings() {
        // No particular settings required.
    }

    /**
     * Define the backup steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_codeframe_activity_structure_step('codeframe_structure', 'codeframe.xml'));
    }

    /**
     * Code the transformations to perform in the activity content.
     *
     * @param string $content
     * @return string
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of codeframes
        $search = "/(" . $base . "\/mod\/codeframe\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@CODEFRAMEINDEX*$2@$', $content);

        // Link to codeframe view by moduleid
        $search = "/(" . $base . "\/mod\/codeframe\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@CODEFRAMEVIEWBYID*$2@$', $content);

        return $content;
    }
}
