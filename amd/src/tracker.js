/**
 * JavaScript tracking module for Codeframe activity.
 *
 * @module     mod_codeframe/tracker
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/log'], function(Ajax, Log) {
    return {
        /**
         * Initialize the event listener for window messages.
         *
         * @param {number} cmid The course module ID.
         * @param {number} courseid The course ID.
         */
        init: function(cmid, courseid) {
            Log.debug('Codeframe tracker AMD module initialized for CMID: ' + cmid);

            window.addEventListener('message', function(event) {
                 // Determine if the message data is equal to 'codeframe_completed'
                 // or if it's an object containing this message.
                 var isCompleted = false;
 
                 if (typeof event.data === 'string' && event.data === 'codeframe_completed') {
                     isCompleted = true;
                 } else if (event.data && typeof event.data === 'object' && event.data.message === 'codeframe_completed') {
                     isCompleted = true;
                 }

                if (isCompleted) {
                    Log.debug('Codeframe completed signal received. Sending AJAX request...');

                    // Perform the native Moodle AJAX call to report completion.
                    Ajax.call([{
                        methodname: 'mod_codeframe_mark_completed',
                        args: {
                            cmid: cmid,
                            courseid: courseid
                        }
                    }])[0].then(function(result) {
                        Log.debug('AJAX completion response success:', result);
                    }).catch(function(error) {
                        Log.error('AJAX completion response failure:', error);
                    });
                }
            });
        }
    };
});
