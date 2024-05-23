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
 * Countdown time the students have for answering the question. The timer can also be stopped by the teacher.
 *
 * @module     quizaccess_tcquiz
 * @copyright  2024 Capilano University
 * @author     Tamara Dakic <tdakic@capilanou.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import Notification from 'core/notification';
import $ from 'jquery';

const Selectors = {
    regions: {
        timeLeft: '[data-region="quizaccess_tcquiz/timeleft_span"]',
 },
};

const registerEventListeners = (sessionid, quizid, cmid, attemptid, page, time_for_question, POLLING_INTERVAL) => {
//the timer can be stoped either by the teacher or expired time -- handle both events
//enough to check when the state of the quiz has changed to show results (30)

//the submit button is clickable twice causing problems
//name="responseformsubmit" value="Submit" class="mod_quiz-next-nav btn btn-primary"
//id="responseformsubmit" formaction="http://localhost/moodle/mod/quiz/accessrule/tcquiz/processattempt.php?

    /*does nothing
    $(document).ready(function(){
       $("responseform").submit(function() {
              $(this).submit(function() {
                  return false;
              });
              return true;
          });
    });*/

    $('#responseform').on('submit', function () {
        $('#responseformsubmit').attr('disabled', 'disabled');
    });

    var timeLeft = time_for_question; //+1 to wait for everyone?
    var timeLeft_html = document.querySelector(Selectors.regions.timeLeft);
    var teacherEndedQuestion = false;

    var timer = setInterval(function() {
        timeLeft--;
        timeLeft_html.innerHTML = timeLeft;
        if (timeLeft <= 0 || teacherEndedQuestion){
          clearInterval(timer);
          clearInterval(tecaherEndedQuestionEvent);
          timer = null;
          timeLeft_html.innerHTML = 0;
          window.goToCurrentQuizPageEvent = setInterval(async () =>
            {await go_to_current_quiz_page(sessionid, quizid, cmid, attemptid);}, POLLING_INTERVAL);
        }
    }, 1000);

    const tecaherEndedQuestionEvent = setInterval(async function() {
      teacherEndedQuestion = await check_question_state(sessionid, quizid, cmid, attemptid);
    }, POLLING_INTERVAL); //1000 means 1 sec, 5000 means 5 seconds

};

/**
 * Checks if the teacher stopped the question
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's attempt.
 * @return true if the question was stopped by the teacher, false otherwise
 */
async function check_question_state(sessionid, quizid, cmid, attemptid) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/get_question_state.php?requesttype=getnumberanswers&quizid='
    +quizid+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

  var response_xml_text = await result.text();

  return response_xml_text == "0";

}

/**
 * When time is up or the teacher stopped the question, go to the next page of the quiz.
 * That page should only be the result's page or the final result's page
 * but the method is coded more generally in case of teacher control improvements
 * @param {sessionid} sessionid The id of the current session.
 * @param {quizid} quizid The quizid of the current quiz.
 * @param {cmid} cmid Course module id of the current quiz.
 * @param {attemptid} attemptid The attemptid of the teacher's attempt.
*/
async function go_to_current_quiz_page(sessionid, quizid, cmid, attemptid) {

  var  result = await fetch(M.cfg.wwwroot+'/mod/quiz/accessrule/tcquiz/quizdatastudent.php?requesttype=getanswer&quizid='
    +quizid+'&sessionid='+sessionid+'&cmid='+ cmid +'&attempt='+attemptid
    +'&sesskey='+ M.cfg.sesskey,{method: 'POST'});

  var response_xml_text = await result.text();

  await update_quiz_page(response_xml_text);

}

/**
* Helper function to parse a response from the server and go to the specified url.
* same function is in waitforquestion.js - leave for now in case more events added
* @param {string} response_xml_text The XML returned by quizdatastudent.php
 */
function update_quiz_page(response_xml_text) {

  const parser = new DOMParser();
  const response_xml = parser.parseFromString(response_xml_text, 'text/html');

  var quizresponse = response_xml.getElementsByTagName('tcquiz').item(0);

  //ERROR handling?
  //var quizresponse = httpRequest.responseXML.getElementsByTagName('questionpage').item(0);


  if (quizresponse === null) {
    Notification.addNotification({
        message: getString('invalidserverresponse', 'quizaccess_tcquiz'),
        type: 'error'
    });
    return;

  } else {

    var quizstatus = quizresponse.getElementsByTagName('status').item(0).textContent;

    if (quizstatus == 'showquestion') {

        //window.goToCurrentQuizPageEvent = null;
        //clearInterval(window.goToCurrentQuizPageEvent);
        //var attempt_url = quizresponse.getElementsByTagName('url').item(0).textContent;
        //window.location.replace(attempt_url);

    } else if (quizstatus == 'showresults') {

        window.goToCurrentQuizPageEvent = null;
        clearInterval(window.goToCurrentQuizPageEvent);
        var result_url = quizresponse.getElementsByTagName('url').item(0).textContent;
        window.location.replace(result_url);

    } else if (quizstatus == 'finalresults') {

      window.goToCurrentQuizPageEvent = null;
      clearInterval(window.goToCurrentQuizPageEvent);

    } else if (quizstatus == 'quiznotrunning' || quizstatus == 'waitforquestion'|| quizstatus == 'waitforresults' ||
            quizstatus == 'noaction' ){
            //keep trying

    } else if (quizstatus == 'error') {
      var errmsg = quizresponse.getElementsByTagName('message').item(0).textContent;

      Notification.addNotification({
          message: errmsg,
          type: 'error'
      });

    }
    else{
      Notification.addNotification({
          message: getString('unknownserverresponse', 'quizaccess_tcquiz') + quizstatus,
          type: 'error'
      });

    }
  }

}

export const init = (sessionid, quizid, cmid, attemptid, page, time_for_question, POLLING_INTERVAL) => {

  registerEventListeners(sessionid, quizid, cmid, attemptid, page, time_for_question, POLLING_INTERVAL);
};
