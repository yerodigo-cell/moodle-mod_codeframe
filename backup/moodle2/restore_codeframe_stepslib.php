<?php
/**
 * Restore steps library for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_codeframe_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        
        $paths[] = new restore_path_element('codeframe', '/activity/codeframe');
        $paths[] = new restore_path_element('codeframe_completion', '/activity/codeframe/codeframe_completions/codeframe_completion');

        return $this->prepare_activity_structure($paths);
    }

    protected function process_codeframe($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Map the course ID to the new course.
        $data->course = $this->get_courseid();

        // Apply date offsets for course rolling.
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the new record into the database.
        $newitemid = $DB->insert_record('codeframe', $data);

        // CRITICAL: Tell Moodle that this is the main instance.
        // This establishes the context mapping and resolves the unknown_context_mapping / emptycmids errors.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_codeframe_completion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Map the user ID to the new user in this course.
        $data->userid = $this->get_mappingid('user', $data->userid);

        // Map the course module ID to the newly restored course module.
        $data->cmid = $this->task->get_moduleid();

        // Ensure we only insert valid records.
        if (!empty($data->userid)) {
            $newitemid = $DB->insert_record('codeframe_completion', $data);
            $this->set_mapping('codeframe_completion', $oldid, $newitemid);
        }
    }

    protected function after_execute() {
        // Add related files.
        // The 'intro' area uses itemid 0, so mapping is null.
        $this->add_related_files('mod_codeframe', 'intro', null);
        
        // The 'content' area uses the instance ID.
        // Because apply_activity_instance() was called, the mapping 'codeframe' exists.
        $this->add_related_files('mod_codeframe', 'content', 'codeframe');
    }
}
