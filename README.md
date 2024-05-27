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



Here is the teacher's view while a question is being administered:

<img width="518" alt="Teacher question view" src="https://github.com/tdakic/tcquiz/assets/9156749/9e2210fc-64b2-43af-90c2-f0da21367529">



and here is the students':

<img width="550" alt="Student question view" src="https://github.com/tdakic/tcquiz/assets/9156749/220450bb-db0c-4ba7-bd17-8ba15e6f0428">


(The timers should match - it is me who was slow to switch)



After the time has elapsed the student will see:

![Screenshot 2024-05-27 at 1 18 44 PM](https://github.com/tdakic/tcquiz/assets/9156749/bef7280e-75d1-4493-a766-9ee0bdbaadbc)

and the teacher will see:

<img width="491" alt="Screenshot 2024-05-27 at 1 18 30 PM" src="https://github.com/tdakic/tcquiz/assets/9156749/debf8c8f-df55-4187-aa3b-3c22be0846ce">

After the teacher administered all the questions, the students will see their score and the teacher will see the histogram of the student scores.


## To Do List 

There are quite a few design decisions to be made and the code needs more clean up. Please let me know if you have any ideas or suggestions or would like to help. Below is what I think needs work. The list is not complete, is going to be changing and is not in any particular order.

*	Design a nice teacher control board.  Should the control board have a Pause button to pause a question? Should it have a Start button to start a question – from a design point it seems better than starting the timer from the renderer, but not clear how it is from the user perspective.
*	Develop more unit tests (PHPUnit and behat finally work on my Mac that has a hodge podge of composer, brew and basic download installations – took a while).
*	Develop more behat tests.
*	Fix language strings
*	Quiz with multiple questions per page is not tested at all.
*	Use the defined capability instead of mod/quiz:preview
*	Is TCQUIZ_STATUS_SHOWQUESTION (20) from locallib.php ever used? Left for now, for when the teacher controls are improved.
*	Fix DB updates, so only the fields that need updated are updated.

