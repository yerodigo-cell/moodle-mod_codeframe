<?php
/**
 * Codeframe overview for the course activities tab (Moodle 5.x architecture).
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codeframe\courseformat;

defined('MOODLE_INTERNAL') || die();

use core_courseformat\activityoverviewbase;
use core_courseformat\local\overview\overviewitem;
use html_writer;
use moodle_url;

class overview extends activityoverviewbase {

    /**
     * Provides custom columns/data to be displayed in the Moodle 5 Activities tab table.
     *
     * @return array of overviewitem objects indexed by shortname.
     */
    public function get_extra_overview_items(): array {
        $items = [];

        // 1. "View" button
        $viewhtml = html_writer::link(
            new moodle_url('/mod/codeframe/view.php', ['id' => $this->cm->id]),
            get_string('view'),
            ['class' => 'btn btn-secondary btn-sm']
        );
        
        $items['viewaction'] = new overviewitem(
            'Actions',
            'view',
            $viewhtml
        );

        // 2. "Progress Report" button
        // Only show if the user has permission to view the report.
        if (has_capability('mod/codeframe:addinstance', $this->context)) {
            $reporthtml = html_writer::link(
                new moodle_url('/mod/codeframe/report.php', ['id' => $this->cm->id]),
                get_string('progressreport', 'mod_codeframe'),
                ['class' => 'btn btn-info btn-sm text-white ml-2']
            );
            
            $items['progressreport'] = new overviewitem(
                get_string('progressreport', 'mod_codeframe'),
                'progressreport',
                $reporthtml
            );
        }

        return $items;
    }
}
