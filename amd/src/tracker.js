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
import Ajax from 'core/ajax';
import Log from 'core/log';
import Str from 'core/str';

export const init = (cmid, courseid) => {
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
                            var completionRegion = document.querySelector('[data-region="completion-info"]');
                            if (completionRegion) {
                                    var elements = completionRegion.querySelectorAll('*');
                                    elements.forEach(function(el) {
                                        var cls = el.className || '';
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
                                        var walker = document.createTreeWalker(completionRegion, NodeFilter.SHOW_TEXT, null, false);
                                        var node = walker.nextNode();
                                        while (node) {
                                            node.nodeValue = node.nodeValue.replace(todoStr, doneStr);
                                            node = walker.nextNode();
                                        }
                                    });
                                }
                            }
                            return result;
                        }).catch(function(error) {
                            Log.error('AJAX completion response failure:', error);
                        });
                }
            });
};
