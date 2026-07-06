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
 * Privacy provider implementation for the Codeframe module.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codeframe\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for mod_codeframe.
 *
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('codeframe_completion', [
            'userid' => 'privacy:metadata:codeframe_completion:userid',
            'timecompleted' => 'privacy:metadata:codeframe_completion:timecompleted',
        ], 'privacy:metadata:codeframe_completion');

        $collection->add_database_table('codeframe_time', [
            'userid' => 'privacy:metadata:codeframe_time:userid',
            'time_started' => 'privacy:metadata:codeframe_time:time_started',
            'last_ping' => 'privacy:metadata:codeframe_time:last_ping',
            'total_duration' => 'privacy:metadata:codeframe_time:total_duration',
            'last_session_duration' => 'privacy:metadata:codeframe_time:last_session_duration',
        ], 'privacy:metadata:codeframe_time');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        
        $params = [
            'modname' => 'codeframe',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        // Contexts from completion.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {codeframe_completion} cc ON cc.cmid = cm.id
                 WHERE cc.userid = :userid";
        $contextlist->add_from_sql($sql, $params);

        // Contexts from time tracking.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {codeframe_time} ct ON ct.cmid = cm.id
                 WHERE ct.userid = :userid";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cmid = $context->instanceid;
            
            // Export completion data.
            $completion = $DB->get_record('codeframe_completion', ['cmid' => $cmid, 'userid' => $userid]);
            if ($completion) {
                $exportdata = (object)[
                    'timecompleted' => transform::datetime($completion->timecompleted),
                ];
                writer::with_context($context)->export_related_data([], 'codeframe_completion', $exportdata);
            }

            // Export time tracking data.
            $timetrack = $DB->get_record('codeframe_time', ['cmid' => $cmid, 'userid' => $userid]);
            if ($timetrack) {
                $exportdata = (object)[
                    'time_started' => transform::datetime($timetrack->time_started),
                    'last_ping' => transform::datetime($timetrack->last_ping),
                    'total_duration' => $timetrack->total_duration,
                    'last_session_duration' => $timetrack->last_session_duration,
                ];
                writer::with_context($context)->export_related_data([], 'codeframe_time', $exportdata);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        global $DB;
        $DB->delete_records('codeframe_completion', ['cmid' => $context->instanceid]);
        $DB->delete_records('codeframe_time', ['cmid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $cmids = [];

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $cmids[] = $context->instanceid;
            }
        }

        if (empty($cmids)) {
            return;
        }

        global $DB;
        [$insql, $inparams] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $params = array_merge(['userid' => $userid], $inparams);

        $DB->delete_records_select('codeframe_completion', "userid = :userid AND cmid $insql", $params);
        $DB->delete_records_select('codeframe_time', "userid = :userid AND cmid $insql", $params);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $params = ['cmid' => $context->instanceid];

        // Users from completion.
        $sql = "SELECT userid
                  FROM {codeframe_completion}
                 WHERE cmid = :cmid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Users from time tracking.
        $sql = "SELECT userid
                  FROM {codeframe_time}
                 WHERE cmid = :cmid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        global $DB;
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['cmid' => $context->instanceid], $inparams);

        $DB->delete_records_select('codeframe_completion', "cmid = :cmid AND userid $insql", $params);
        $DB->delete_records_select('codeframe_time', "cmid = :cmid AND userid $insql", $params);
    }
}
