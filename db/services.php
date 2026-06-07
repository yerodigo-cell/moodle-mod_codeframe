<?php
/**
 * Web service function definitions for mod_codeframe.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_codeframe_mark_completed' => array(
        'classname'     => 'mod_codeframe\external\mark_completed',
        'methodname'    => 'execute',
        'description'   => 'Marks the codeframe activity as completed for the current user.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ),
);
