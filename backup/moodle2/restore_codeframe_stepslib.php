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
 * Restore steps for the Codeframe module.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure step class.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_codeframe_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure of the restore.
     *
     * @return array The structure.
     */
    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element('codeframe', '/activity/codeframe');
        $paths[] = new restore_path_element('codeframe_completion', '/activity/codeframe/codeframe_completions/codeframe_completion');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the codeframe record.
     *
     * @param array $data The data.
     */
    protected function process_codeframe($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('codeframe', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process the codeframe_completion record.
     *
     * @param array $data The data.
     */
    protected function process_codeframe_completion($data) {
        global $DB;

        $data = (object)$data;
        $data->cmid = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        // Only insert if the user was mapped successfully.
        if ($data->userid) {
            $data->timecompleted = $this->apply_date_offset($data->timecompleted);
            $DB->insert_record('codeframe_completion', $data);
        }
    }

    /**
     * Actions to execute after the restore is completed.
     */
    protected function after_execute() {
        // Add related files, e.g. the HTML package.
        $this->add_related_files('mod_codeframe', 'intro', null);
        $this->add_related_files('mod_codeframe', 'content', 'codeframe');
    }
}
