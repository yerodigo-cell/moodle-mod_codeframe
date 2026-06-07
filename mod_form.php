<?php
/**
 * Settings form for the Codeframe activity module.
 *
 * @package    mod_codeframe
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_codeframe_mod_form extends moodleform_mod {

    /**
     * Define the form elements.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // ── General section ──────────────────────────────────────────────────
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_RAW);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Introduction / Description.
        $this->standard_intro_elements();

        // ── Codeframe settings section ────────────────────────────────────────
        $mform->addElement('header', 'codeframe_settings', get_string('pluginadministration', 'mod_codeframe'));
        $mform->setExpanded('codeframe_settings');

        // Presentation URL — teacher only needs to paste a plain link.
        $mform->addElement(
            'text',
            'embedcode',
            get_string('embedcode', 'mod_codeframe'),
            ['size' => '80', 'placeholder' => 'https://example.com/my-presentation']
        );
        // PARAM_RAW preserves backward-compat with existing activities that
        // stored raw iframe HTML before this change.
        $mform->setType('embedcode', PARAM_RAW);
        $mform->addHelpButton('embedcode', 'embedcode', 'mod_codeframe');

        // Filemanager for local HTML5 slide/presentation code files.
        $mform->addElement(
            'filemanager',
            'files',
            get_string('uploadfiles', 'mod_codeframe'),
            null,
            ['subdirs' => 1, 'accepted_types' => '*']
        );
        $mform->addHelpButton('files', 'uploadfiles', 'mod_codeframe');

        // ── Completion info notice (right below the inputs) ─────────────────
        $mform->addElement(
            'static',
            'completioninfo',
            '',
            html_writer::div(
                get_string('completioninfo', 'mod_codeframe'),
                '',
                ['style' => 'margin-top:8px; padding:10px 14px; background:#eef4fb; border-left:4px solid #4a90d9; border-radius:4px; font-size:0.92em; line-height:1.5;']
            )
        );

        // ── Standard Moodle module settings (groups, availability, completion) ─
        $this->standard_coursemodule_elements();

        // Standard save buttons.
        $this->add_action_buttons();
    }

    /**
     * Custom form validation.
     * Ensure the teacher provided either a remote URL or uploaded files.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        $hasfiles = false;
        if (!empty($data['files'])) {
            $usercontext = context_user::instance($USER->id);
            $fs = get_file_storage();
            $draftfiles = $fs->get_area_files(
                $usercontext->id,
                'user',
                'draft',
                $data['files'],
                'id',
                false
            );
            foreach ($draftfiles as $file) {
                if (!$file->is_directory()) {
                    $hasfiles = true;
                    break;
                }
            }
        }

        if (empty(trim($data['embedcode'])) && !$hasfiles) {
            $msg = get_string('error_url_or_files', 'mod_codeframe');
            $errors['embedcode'] = $msg;
            $errors['files'] = $msg;
        }

        return $errors;
    }

    /**
     * Register our custom completion rule so Moodle's engine tracks it.
     * The checkbox appears inside the standard "Completion conditions" section.
     *
     * @return array Rule element names.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();

        $elementname = 'completioncomplete' . $suffix;
        $mform->addElement(
            'checkbox',
            $elementname,
            get_string('completioncomplete', 'mod_codeframe')
        );
        $mform->setType($elementname, PARAM_INT);
        $mform->addHelpButton($elementname, 'completioncomplete', 'mod_codeframe');
        $mform->disabledIf($elementname, 'completion' . $suffix, 'ne', COMPLETION_TRACKING_AUTOMATIC);

        return ['completioncomplete'];
    }

    /**
     * Tell Moodle whether our custom rule is currently active.
     *
     * @param array|stdClass $data Submitted form data.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        $dataarr = (array)$data;
        return !empty($dataarr['completioncomplete']) || !empty($dataarr['completioncomplete' . $suffix]);
    }

    /**
     * Pre-populate defaults so that, when a teacher creates a new activity,
     * BOTH "View the activity" AND "Require iframe completion" are already
     * ticked — they do not need to configure completion manually.
     *
     * @param array $default_values
     */
    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        $suffix = $this->get_suffix();

        // Load files when editing.
        if (!empty($default_values['id'])) {
            $draftitemid = 0; // Will be populated by the function by reference
            file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_codeframe',
                'content',
                $default_values['id'],
                ['subdirs' => 1]
            );
            $default_values['files'] = $draftitemid;
        }

        // Only apply defaults for a brand-new instance (no $cm yet).
        if (empty($this->_cm)) {
            // Activate automatic completion tracking.
            $default_values['completion' . $suffix]          = COMPLETION_TRACKING_AUTOMATIC;
            // Pre-tick "View the activity".
            $default_values['completionview' . $suffix]      = 1;
            // Pre-tick "Require iframe completion".
            $default_values['completioncomplete' . $suffix]  = 1;
        }
    }
}
