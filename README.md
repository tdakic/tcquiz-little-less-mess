# TCQuiz
Moodle in-class quiz

TCQuiz (Teacher Controlled Quiz) plugin implements a quiz in which the teacher controls which question students can answer at any given time. After each question is finished, the students get the feedback and teacher gets the summary of the students’ responses. TCQuiz is primarily meant for in-class instruction.

The plugin is implemented as a quiz accessrule and it basically breaks down the flow of a quiz, so the flow is in the hands of the teacher and the question results are displayed immediately after the question is finished.

After the plugin is installed, any quiz can be administered as a TCQuiz, by selecting that option in the quiz settings. The teacher should also specify the default time (in seconds) for questions (same for all questions) there. It is recommended to choose a “reasonably long time” as there is an option to stop the question if most of the students have answered it.

If a quiz is set up as a TCQuiz, the view page of the quiz is altered so the teacher can start the quiz:

<img width="517" alt="TCQ teacher start screen" src="https://github.com/tdakic/tcquiz/assets/9156749/777175a2-0833-47dd-96b5-eb0fb21098b0">

The teacher should choose an identifying code for the session (alphanumeric), type it in the box and click the button. Then the students should use the same code to join the session: 

<img width="350" alt="TCQ student start screen" src="https://github.com/tdakic/tcquiz/assets/9156749/f706be0b-79ca-4e94-ac33-889c9b5db94d">


It is possible that there is already a running session of the TCQuiz and the teacher somehow got disconnected. In that case, the option to rejoin the session will be given to the teacher:

<img width="507" alt="TCQ teacher start screen with an existing session" src="https://github.com/tdakic/tcquiz/assets/9156749/193d8b99-37b2-4195-8c35-dedc63bae07c">


If a student crashes, they will be able to reconnect from the quiz view page using the quiz code.

The teacher controls the quiz using a two buttons: End Question button ends the current question and displays its results. The Next button skips to the next question. The students have a Submit button which they can click only once and when they do, the number of received answers on the teachers “control board” is updated.

<img width="215" alt="Screenshot 2024-05-27 at 12 59 59 PM" src="https://github.com/tdakic/tcquiz/assets/9156749/d1160fae-4b61-406d-9380-ff9d4f28c3d0">


The code does work, but one should be gentle to it. Do not click the Next button while the students are answering a question, for example and please only one question per page for now (it might work otherwise, but not tested at all)

There are quite a few design decisions to be made and the code needs clean up. Please let me know if you have any ideas or suggestions or would like to help. Below is what I think needs work. The list is not complete, is going to be changing and is not in any particular order.

To Do List 

*	Design a nice teacher control board. For some reason the css for the question has not loaded yet when the control board is displayed – I tried using the same classes.  Should the control board have a Pause button to pause the question? Should it have a Start button to start a question – from a design point it seems better than starting the timer from the renderer, but not clear how it is from the user perspective.
*	Prevent double clicking on a button.
*	Develop unit tests (PHPUnit and behat finally  work on my Mac that has a hodge podge of composer, brew and basic download installations – took a while).
*	Develop behat tests.
*	The php parameters are flying back and forth. Limit their number and also remove the DB hack from the renderer. Joincode should likely only be used to connect after which sessionid should be passed around. Should sessionid be set up from attempt.php at the same time as attemptid?
*	Is the parameter currentquestion needed? Seems that currentpage is used instead. In DB as well.
*	Check privileges for each file. Change_question_state.php is critical 
*	Fix language strings
*	Fix $process_url in the renderer
*	Quiz with multiple questions per page is not tested at all.
*	Use the defined capability instead of mod/quiz:preview
*	Is TCQUIZ_STATUS_SHOWQUESTION (20) from locallib.php ever used?
*	Fix DB updates, so only the fields that need updated are updated.
*	Set page headers as in attempt.php (says viewing page x of y in the tab)
*	Figure out why document.getelementbyid  works but  jquerry doesn’t 
*	report_final_results.php uses a regular quiz attempt object – change it to tcquiz attempt object?
*	Comments (always last, but shouldn’t be :-))


Known bugs

*	The flag in the attempt form is clickable, but it doesn’t appear to be.
*	Sometimes if the student joins with the right joincode they don’t get the question – timing of the polling? Or case TCQUIZ_STATUS_FINISHED: in quizdatastudent.php?




