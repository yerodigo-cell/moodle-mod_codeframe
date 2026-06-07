<?php
/**
 * Course module viewed event for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_codeframe\event;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initialize standard properties for the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'codeframe';
        parent::init();
    }
}
