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
 * Time tracking for codeframe.
 *
 * @module     mod_codeframe/time_tracker
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';

export const init = (cmid) => {
    // Send a ping immediately and then every 10 seconds.
    const sendPing = () => {
        Ajax.call([{
            methodname: 'mod_codeframe_track_time',
            args: {
                cmid: cmid
            }
        }])[0].fail(() => {
            // Log or handle failure if needed.
        });
    };

    sendPing();
    setInterval(sendPing, 10000);
};
