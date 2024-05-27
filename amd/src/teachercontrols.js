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
 * Allows the teacher to control the polling of one question.
 * Currently, the teacher can only stop the question.
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const Selectors = {
    actions: {
        endquestionButton: '[data-action="quizaccess_tcquiz/end-question_button"]',
        //nextquestionButton: '[data-action="quizaccess_tcquiz/next-question_button"]',
    },
    regions: {
        numAnswers: '[data-region="quizaccess_tcquiz/numberanswers_span"]',
        timeLeft: '[data-region="quizaccess_tcquiz/timeleft_span"]',
 },
};

const registerEventListeners = (sessionid, quizid, cmid, attemptid, page, time_for_question, POLLING_INTERVAL) => {

/*  left here to use when a better teacher controls are added
document.addEventListener('click', async(e) => {
        if (e.target.closest(Selectors.actions.nextquestionButton)) {
          e.preventDefault();
          page++;
          clearInterval(updateNumAnswersEvent);
          updateNumAnswersEvent = null;
          document.querySelector(Selectors.regions.timeLeft).innerHTML = 0; //will this stop setInterval?

          var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getquestion&quizid='
            +quizid+'&joincode='+joincode+'&cmid='+ cmid +'&attempt='+attemptid
          +'&sessionid='+sessionid+'&rejoin=false&page='+page+'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

          var response_xml_text = await result.text();
          await  parse_next_url(response_xml_text);

        }
  },{once: true} );
*/

  //this should prevent "Unsaved changes" pop-up which might happen if the teacher interacts with the
  //question and then clicks on the End question button
  window.addEventListener('beforeunload', function (event) {
    event.stopImmediatePropagation();
  });

  // handles teacher clicking on the End question button
  const endQuestionAction = document.querySelector(Selectors.actions.endquestionButton);
  endQuestionAction.addEventListener('click', async(e) => {
            e.preventDefault();
            clearInterval(updateNumAnswersEvent);
            updateNumAnswersEvent = null;

            document.querySelector(Selectors.regions.timeLeft).innerHTML = 0; //will this stop setInterval?
            clearInterval(timer);
            timer = null;
            const req = new XMLHttpRequest();
            req.open("POST", M.cfg.wwwroot+
              '/mod/quiz/accessrule/tcquiz/change_question_state.php?sessionid='+sessionid+'&cmid='+cmid);
            req.send();

            req.onload = () => {
              document.getElementById('responseform').submit();
            };
    },{once: true} );

    var updateNumAnswersEvent = setInterval(async () =>
      {await update_number_of_answers(sessionid, quizid, cmid, attemptid);}, POLLING_INTERVAL);


    var timeLeft = time_for_question; //+1 to wait for everyone?

    //teacher timer
    var timer = setInterval(function() {
        var timeLeft_html = document.querySelector(Selectors.regions.timeLeft);
        timeLeft--;
        timeLeft_html.innerHTML = timeLeft;

        if (timeLeft <= 0) {
          clearInterval(timer);
          timer = null;
          clearInterval(updateNumAnswersEvent);
          updateNumAnswersEvent = null;
          timeLeft_html.innerHTML = 0;
          document.getElementById('responseform').submit();
        }
    }, 1000);

};

/**
 * Retrieves and updates the number of received student answers
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's attempt.
 */
async function update_number_of_answers(sessionid, quizid, cmid, attemptid) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatateacher.php?requesttype=getnumberanswers&quizid='
    +quizid+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

  var response_xml_text = await result.text();

  await update_num_answers_html(response_xml_text);

}

/**
 * helper function to update the html with number of submitted answers
 * @param {string} response_xml_text
 */
function update_num_answers_html(response_xml_text){

  var parser = new DOMParser();
  var response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);

  var number_of_answers = quizresponse.getElementsByTagName('numanswers').item(0).textContent;
  document.querySelector(Selectors.regions.numAnswers).innerHTML = number_of_answers;
}

/**
 * helper function to replace the current page with the attempt page specified in the response_xml_text
 * @param {string} response_xml_text
 */
/*function parse_next_url(response_xml_text){

  var parser = new DOMParser();
  var response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);
  var next_url = quizresponse.getElementsByTagName('url').item(0).textContent;

  window.location.replace(next_url);

}*/

export const init = (sessionid, quizid, cmid, attemptid, page, time_for_question,POLLING_INTERVAL) => {

  registerEventListeners(sessionid, quizid, cmid, attemptid, page, time_for_question, POLLING_INTERVAL);
};
