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
 * JavaScript tracking module for Codeframe activity.
 *
 * @module     mod_codeframe/tracker
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import * as Ajax from 'core/ajax';
import * as Log from 'core/log';
import * as Str from 'core/str';
import * as Notification from 'core/notification';

export const init = (cmid, courseid) => {
            Log.debug('Codeframe tracker AMD module initialized for CMID: ' + cmid);

            window.addEventListener('message', function(event) {
                 // Determine if the message data is equal to 'codeframe_completed'
                 // or if it's an object containing this message.
                 var isCompleted = false;
                 var showAlert = false;

                 var msgString = '';
                 if (typeof event.data === 'string') {
                     msgString = event.data;
                 } else if (event.data && typeof event.data === 'object' && event.data.message) {
                     msgString = event.data.message;
                 }

                 if (msgString === 'codeframe_completed') {
                     isCompleted = true;
                 } else if (msgString === 'codeframe_completed_alert') {
                     isCompleted = true;
                     showAlert = true;
                 }

                if (isCompleted) {
                     triggerCompletion(showAlert);
                 }
            });

            // Listen to localStorage for cross-tab communication (from finish.php)
            window.addEventListener('storage', function(e) {
                if (e.key === 'codeframe_canva_finished_universal' && e.newValue) {
                    Log.debug('Codeframe universal cross-tab completion signal received.');
                    triggerCompletion(true); // Always show alert for cross-tab
                } else if (e.key === 'codeframe_completed_cmid' && e.newValue) {
                    // Fallback for the old specific ID method just in case
                    if (String(e.newValue) === String(cmid)) {
                        Log.debug('Codeframe cross-tab completion signal received.');
                        triggerCompletion(true);
                    }
                }
            });

            /**
             * Triggers the completion state for this Codeframe instance.
             *
             * @param {Boolean} showAlert Whether to show a success notification after completion.
             */
            function triggerCompletion(showAlert) {
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
                            if (showAlert) {
                                Str.get_string('success_completed', 'mod_codeframe').done(function(msg) {
                                    Notification.addNotification({
                                        message: msg,
                                        type: 'success'
                                    });
                                }).fail(function() {
                                    Notification.addNotification({
                                        message: 'Activity successfully marked as complete.',
                                        type: 'success'
                                    });
                                });
                            }
                            var completionRegion = document.querySelector('[data-region="completion-info"]');
                            if (completionRegion) {
                                    var elements = completionRegion.querySelectorAll('*');
                                    elements.forEach(function(el) {
                                        var cls = el.className || '';
                                        // Moodle 4.5 - 5.1 badge styles
                                        if (typeof cls === 'string' && (cls.indexOf('badge') > -1 ||
                                                cls.indexOf('btn') > -1 || cls.indexOf('alert') > -1 ||
                                                cls.indexOf('bg-') > -1)) {
                                            el.style.setProperty('background-color', '#198754', 'important');
                                            el.style.setProperty('color', '#ffffff', 'important');
                                            el.style.setProperty('border-color', '#198754', 'important');
                                        }
                                    });
                                    Str.get_strings([
                                        {key: 'todo', component: 'mod_codeframe'},
                                        {key: 'done', component: 'mod_codeframe'}
                                    ]).done(function(strings) {
                                        var todoStr = strings[0];
                                        var doneStr = strings[1];

                                        var listItems = completionRegion.querySelectorAll('li');
                                        if (listItems.length > 0) {
                                            // Moodle 5.2 style
                                            listItems.forEach(function(li) {
                                                li.style.setProperty('color', '#198754', 'important');
                                                li.style.setProperty('font-weight', 'bold', 'important');
                                            });
                                        } else {
                                            // Moodle 4.5 - 5.1 style fallback text replacement
                                            var walker = document.createTreeWalker(
                                                completionRegion,
                                                NodeFilter.SHOW_TEXT,
                                                null,
                                                false
                                            );
                                            var node = walker.nextNode();
                                            while (node) {
                                                node.nodeValue = node.nodeValue.replace(todoStr, doneStr);
                                                node = walker.nextNode();
                                            }
                                        }
                                    });
                                }
                            }
                            return result;
                        }).catch(function(error) {
                            Log.error('AJAX completion response failure:', error);
                        });
            }
};
