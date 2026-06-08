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
                        if (result && result.status) {
                            // Visually update completion badges to green without reloading the page.
                            var badges = document.querySelectorAll('.badge-light, .badge-warning, .badge-secondary, .text-bg-light, .text-bg-warning, .text-bg-secondary, [data-region="completion-info"] .badge');
                            badges.forEach(function(badge) {
                                badge.classList.remove('badge-light', 'badge-warning', 'badge-secondary', 'text-bg-light', 'text-bg-warning', 'text-bg-secondary', 'alert-warning', 'text-dark');
                                badge.classList.add('badge-success', 'bg-success', 'text-white', 'alert-success');
                                badge.innerHTML = badge.innerHTML.replace('To do:', 'Done:').replace('Por hacer:', 'Hecho:');
                                
                                // Force styles to guarantee the visual update across all Moodle themes
                                badge.style.backgroundColor = '#198754';
                                badge.style.color = '#ffffff';
                                badge.style.borderColor = '#198754';
                            });
                        }
                        return result;
                    }).catch(function(error) {
                        Log.error('AJAX completion response failure:', error);
                    });
                }
            });
        }
    };
});
