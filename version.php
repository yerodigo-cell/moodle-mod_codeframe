<?php
/**
 * Version information for the Codeframe activity module.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_codeframe';
$plugin->version   = 2026060501; // Bumped: completely removed backup/restore support files to start fresh.
$plugin->requires  = 2024100700; // Requires Moodle 4.5.0.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0 (Build: 20260528)';
