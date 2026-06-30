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
 * Backup steps library for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Backup structure step class for Codeframe activity.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_codeframe_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure of the backup.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // 1. Define the XML structure.
        $codeframe = new backup_nested_element('codeframe', ['id'], [
            'course', 'name', 'intro', 'introformat', 'embedcode',
            'completioncomplete', 'timecreated', 'timemodified',
        ]);

        $completions = new backup_nested_element('codeframe_completions');

        $completion = new backup_nested_element('codeframe_completion', ['id'], [
            'userid', 'timecompleted',
        ]);

        // 2. Build the tree.
        $codeframe->add_child($completions);
        $completions->add_child($completion);

        // 3. Define the sources.
        $codeframe->set_source_table('codeframe', ['id' => backup::VAR_ACTIVITYID]);

        // Only include completion records if user data is included in the backup.
        if ($this->get_setting_value('userinfo')) {
            $completion->set_source_table('codeframe_completion', ['cmid' => backup::VAR_MODID]);
        }

        // 4. Define ID annotations for mapping during restore.
        $codeframe->annotate_ids('course', 'course');
        $completion->annotate_ids('user', 'userid');

        // 5. Annotate file areas to package the uploaded HTML5 files.
        $codeframe->annotate_files('mod_codeframe', 'intro', null);

        // Annotate the content area which uses the codeframe instance ID as the itemid.
        $codeframe->annotate_files('mod_codeframe', 'content', 'id');

        return $this->prepare_activity_structure($codeframe);
    }
}
