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
 * Allows a student to join start tcquiz
 * Note that the $("#page-content") of the quiz view page is replaced by $("#studentjointcquizform")
 * defined in quizaccess_tcquiz/student_join_tcquiz.mustache
 *
 * The actual form where the student can type a joincode is in a moodle form
 * /mod/quiz/accessrule/tcquiz/classes/form/tcq_student_join_form.php
 * and is validated by the validation method
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

const registerEventListeners = () => {

  /* window.addEventListener('load', function(){

      $("#page-content").html($("#studentjointcquizform"));
    });*/
    if (document.readyState === "complete") {
        $("#page-content").html($("#studentjointcquizform"));
    } else {
      window.addEventListener('load', function(){
        $("#page-content").html($("#studentjointcquizform"));
        });
    }

};

  export const init = () => {

    registerEventListeners();
};
