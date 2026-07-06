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
 * Finish page logic for codeframe.
 *
 * @module     mod_codeframe/finish_page
 * @copyright  2026 Yeison Diaz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    // Write to localStorage for cross-tab communication.
    try {
        const ts = Date.now().toString();
        localStorage.setItem('codeframe_canva_finished_universal', ts);
        setTimeout(() => {
            localStorage.removeItem('codeframe_canva_finished_universal');
        }, 500);
    } catch (e) {
        window.console.error('No se pudo escribir en localStorage', e);
    }

    // Automatically close the window after a short delay.
    setTimeout(() => {
        window.close();
    }, 1500);

    // Bind the close button click event.
    const closeBtn = document.getElementById('codeframe-finish-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            window.close();
        });
    }
};
