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
 * Used by the teacher to go to the next quiz question or display the final report_final_results - i.e.
 * handeles the teacher clicking the next button even when question solutions are being displayed
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const Selectors = {
    actions: {
        nextquestionButtonR: '[data-action="quizaccess_tcquiz/next-question_in_review_button"]',
    },

};

const registerEventListeners = (sessionid, quizid, cmid, attemptid, page) => {
  document.addEventListener('click', async(e) => {
        if (e.target.closest(Selectors.actions.nextquestionButtonR)) {
          e.preventDefault();
          //the page of the quiz attempt that will bedsiplayed is detrmined by quizdatateacher.php
          //this is left here for possible error checking additions later
          page++;

          var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&quizid='
            +quizid+'&cmid='+ cmid +'&attempt='+attemptid
            +'&sessionid='+sessionid+'&rejoin=0&page='+page+'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

          var response_xml_text = await result.text();

          await  parse_next_url(response_xml_text);

        }
      },{once: true});
};


/**
 * helper function to replace the current page with the attempt page specified in the response_xml_text
 * @param {string} response_xml_text
 */
function parse_next_url(response_xml_text){

  var parser = new DOMParser();
  var response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);

  var next_url = quizresponse.getElementsByTagName('url').item(0).textContent;

  window.location.replace(next_url);

}

export const init = (sessionid, quizid, cmid, attemptid, page) => {

  registerEventListeners(sessionid, quizid, cmid, attemptid, page);
};
