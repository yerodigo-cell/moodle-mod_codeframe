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
 * @copyright  2026 EduPlugins Studio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import * as Ajax from 'core/ajax';
import * as Log from 'core/log';
import * as Str from 'core/str';
import * as Notification from 'core/notification';

export const init = (cmid, courseid) => {
    Log.debug('Codeframe tracker AMD module initialized for CMID: ' + cmid);
    var lastTriggerTime = 0;

    /**
     * Checks whether sourceWin is targetWin or one of its descendant frames.
     *
     * @param {Window} sourceWin The window that posted the message.
     * @param {Window} targetWin The activity iframe contentWindow.
     * @return {Boolean}
     */
    function isDescendantWindow(sourceWin, targetWin) {
        if (!sourceWin || !targetWin) {
            return false;
        }
        if (sourceWin === targetWin) {
            return true;
        }
        try {
            var current = sourceWin;
            var depth = 0;
            while (current && current !== current.parent && depth < 10) {
                current = current.parent;
                if (current === targetWin) {
                    return true;
                }
                depth++;
            }
        } catch (err) {
            return false;
        }
        return false;
    }

    /**
     * Validates that a postMessage event comes from the activity iframe.
     *
     * @param {MessageEvent} event The received message event.
     * @return {Boolean}
     */
    function isTrustedMessageSource(event) {
        if (!event.source || event.source === window) {
            return false;
        }

        var iframe = document.querySelector('#codeframeembed iframe');
        if (!iframe || !iframe.getAttribute('src')) {
            return false;
        }

        var iframeOrigin = '';
        var iframeProtocol = '';
        try {
            var iframeUrl = new URL(iframe.src, window.location.href);
            iframeOrigin = iframeUrl.origin;
            iframeProtocol = iframeUrl.protocol;
        } catch (err) {
            return false;
        }

        if (iframeOrigin === window.location.origin) {
            return event.origin === window.location.origin && event.source === iframe.contentWindow;
        }

        if (iframeProtocol === 'http:' || iframeProtocol === 'https:') {
            var isHttpsOrigin = false;
            try {
                isHttpsOrigin = (new URL(event.origin)).protocol === 'https:';
            } catch (err) {
                isHttpsOrigin = false;
            }
            return (event.source === iframe.contentWindow) ||
                (event.origin === iframeOrigin) ||
                (isHttpsOrigin && isDescendantWindow(event.source, iframe.contentWindow));
        }

        return false;
    }

    window.addEventListener('message', function(event) {
        if (!isTrustedMessageSource(event)) {
            return;
        }

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

    // Listen to localStorage for cross-tab communication (from finish.php).
    window.addEventListener('storage', function(e) {
        if (e.key === 'codeframe_canva_finished_universal' && e.newValue) {
            var rawVal = String(e.newValue);
            var targetCmid = rawVal.indexOf(':') > -1 ? rawVal.split(':')[0] : rawVal;
            if (targetCmid === String(cmid)) {
                Log.debug('Codeframe universal cross-tab completion signal received.');
                triggerCompletion(true);
            }
        } else if (e.key === 'codeframe_completed_cmid' && e.newValue) {
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
        var now = Date.now();
        if (now - lastTriggerTime < 2000) {
            return;
        }
        lastTriggerTime = now;

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
                        // Moodle 4.5 - 5.1 badge styles.
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
                            // Moodle 5.2 style.
                            listItems.forEach(function(li) {
                                li.style.setProperty('color', '#198754', 'important');
                                li.style.setProperty('font-weight', 'bold', 'important');
                            });
                        } else {
                            // Moodle 4.5 - 5.1 style fallback text replacement.
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
