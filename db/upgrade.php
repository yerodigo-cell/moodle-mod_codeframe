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
 * Upgrade script for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Execute codeframe upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_codeframe_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade step: Force re-registration of capabilities from db/access.php.
    if ($oldversion < 2026052701) {
        update_capabilities('mod_codeframe');
        upgrade_mod_savepoint(true, 2026052701, 'codeframe');
    }

    // Upgrade step: Create the codeframe_completion table for per-user iframe tracking.
    if ($oldversion < 2026052702) {
        // Define table codeframe_completion.
        $table = new xmldb_table('codeframe_completion');

        // Add fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Add keys and indexes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('cmid_userid', XMLDB_INDEX_UNIQUE, ['cmid', 'userid']);

        // Create the table only if it doesn't already exist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026052702, 'codeframe');
    }

    // Upgrade step: Add the completioncomplete column to codeframe table.
    if ($oldversion < 2026052801) {
        $table = new xmldb_table('codeframe');
        $field = new xmldb_field('completioncomplete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'embedcode');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026052801, 'codeframe');
    }

    return true;
}
