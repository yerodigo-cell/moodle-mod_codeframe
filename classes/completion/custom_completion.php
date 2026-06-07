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
 * Custom completion rule definitions for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codeframe\completion;

use core_completion\activity_custom_completion;

/**
 * Class custom_completion
 *
 * Handles the "Require iframe completion" custom rule.
 * The completion state is determined by looking up our own
 * mdl_codeframe_completion table, which the AJAX endpoint writes to
 * when the iframe broadcasts the codeframe_completed postMessage.
 *
 * This deliberately avoids querying mdl_course_modules_completion, which
 * would create a circular dependency inside update_state().
 */
class custom_completion extends activity_custom_completion {
    /**
     * Return the completion state for the given rule and current user.
     *
     * Uses a static per-request cache so that repeated calls within the same
     * page load (e.g. course page, dashboard, reports) never hit the DB more
     * than once per cmid+userid combination.
     *
     * @param string $rule Rule identifier (must be 'completioncomplete').
     * @return int COMPLETION_COMPLETE | COMPLETION_INCOMPLETE
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        if ($rule !== 'completioncomplete') {
            return COMPLETION_INCOMPLETE;
        }

        // Static cache: lives for the duration of this PHP request only.
        // Key = "cmid_userid" — unique per activity instance and student.
        static $cache = [];
        $cachekey = $this->cm->id . '_' . $this->userid;

        if (!array_key_exists($cachekey, $cache)) {
            $cache[$cachekey] = $DB->record_exists('codeframe_completion', [
                'cmid'   => $this->cm->id,
                'userid' => $this->userid,
            ]);
        }

        return $cache[$cachekey] ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Return the list of custom rules this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completioncomplete'];
    }

    /**
     * Return human-readable descriptions shown to students on the course page.
     *
     * @return array  rule => description
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completioncomplete' => get_string('completioncomplete', 'mod_codeframe'),
        ];
    }

    /**
     * Return the list of custom rules that are currently active for this activity instance.
     * Moodle requires this to know if the teacher actually enabled the rule in the settings.
     *
     * @return array
     */
    public function get_available_custom_rules(): array {
        global $DB;

        // Fast path: if the module info already has the codeframe table fields joined.
        if (isset($this->cm->completioncomplete) && $this->cm->completioncomplete) {
            return ['completioncomplete'];
        }

        // Fallback: fetch the specific setting from the codeframe table.
        if (isset($this->cm->instance)) {
            $completioncomplete = $DB->get_field('codeframe', 'completioncomplete', ['id' => $this->cm->instance], IGNORE_MISSING);
            if (!empty($completioncomplete)) {
                return ['completioncomplete'];
            }
        }

        return [];
    }

    /**
     * Return the display order of all completion rules for this module.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview', // View the activity standard rule.
            'completioncomplete', // Require iframe completion custom rule.
        ];
    }
}
