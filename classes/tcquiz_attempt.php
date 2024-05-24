<?php
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

namespace quizaccess_tcquiz;

/**
 * This class adds tcq functionallity to a quiz attempt
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @ Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use question_engine;
//use question_display_options;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;
use mod_quiz\access_manager;
//use quizaccess_tcquiz\output\renderer;
use mod_quiz\output\renderer;

class tcquiz_attempt extends quiz_attempt{

  public function __construct($attempt, $quiz, $cm, $course, $loadquestions = true) {
    parent::__construct($attempt, $quiz, $cm, $course, $loadquestions);
  }

  /**
   * Used by {create()} and {create_from_usage_id()}.
   *
   * @param array $conditions passed to $DB->get_record('quiz_attempts', $conditions).
   * @return quiz_attempt the desired instance of this class.
   */
  protected static function create_helper($conditions) {
      global $DB;

      $attempt = $DB->get_record('quiz_attempts', $conditions, '*', MUST_EXIST);
      $quiz = access_manager::load_quiz_and_settings($attempt->quiz);
      $course = get_course($quiz->course);
      $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

      // Update quiz with override information.
      $quiz = quiz_update_effective_access($quiz, $attempt->userid);

      return new tcquiz_attempt($attempt, $quiz, $cm, $course);
  }

  /**
   * Static function to create a new quiz_attempt object given an attemptid.
   *
   * @param int $attemptid the attempt id.
   * @return quiz_attempt the new quiz_attempt object
   */
  public static function create($attemptid) {
      return self::create_helper(['id' => $attemptid]);
  }

  /**
   * Wrapper that the correct display_options for this tcquiz at the
   * moment.
   * Added fixed review display options - could possibly be a part of TCQ configuration?
   *
   * @param bool $reviewing true for options when reviewing, false for when attempting.
   * @return question_display_options the render options for this user on this attempt.
   */
  public function get_display_options($reviewing) {

      if ($reviewing) {
          if (is_null($this->reviewoptions)) {
              $this->reviewoptions = quiz_get_review_options($this->get_quiz(),
                      $this->attempt, $this->quizobj->get_context());
              if ($this->is_own_preview()) {
                  // It should  always be possible for a teacher to review their
                  // own preview irrespective of the review options settings.
                  $this->reviewoptions->attempt = true;
              }
          }
          //TTT added
          $this->reviewoptions->feedback = display_options::VISIBLE;
          $this->reviewoptions->overallfeedback = display_options::VISIBLE;
          $this->reviewoptions->generalfeedback = display_options::VISIBLE;
          $this->reviewoptions->numpartscorrect = display_options::VISIBLE;
          $this->reviewoptions->correctness = display_options::VISIBLE;
          //deprecated
          //$this->reviewoptions->specificfeedback = display_options::VISIBLE;
          $this->reviewoptions->rightanswer = display_options::VISIBLE;
          $this->reviewoptions->marks = display_options::VISIBLE;
          $this->reviewoptions->correctness = display_options::VISIBLE;
          $this->reviewoptions->flags = display_options::EDITABLE;
          //var_dump($this->reviewoptions);
          return $this->reviewoptions;
        }
        else { //attempting
            $options = display_options::make_from_quiz($this->get_quiz(),
                    display_options::DURING);
            $options->attempt = true;
            $options->flags = quiz_get_flag_option($this->attempt, $this->quizobj->get_context());
            return $options;
        }
    }

    /**
     * Generate the HTML that displays the question in its current state, with
     * the appropriate display options.
     *
     * @param int $slot identifies the question in the attempt.
     * @param bool $reviewing is the being printed on an attempt or a review page.
     * @param renderer $renderer the quiz renderer.
     * @param moodle_url $thispageurl the URL of the page this question is being printed on.
     * @param bool  $is_preview_user is the user for whom the question is being rendered a preview user
     * @return string HTML for the question in its current state.
     */
    public function render_question($slot, $reviewing, renderer $renderer, $thispageurl = null, $is_preview_user = false) {

      $displayoptions = clone($this->get_display_options($reviewing));

      //no flags for teacher
      if ($is_preview_user){
        // also set  $editquestionparams?
        $displayoptions->flags = 0;
      }
      //this is the code from render_question_helper
      if ($this->is_blocked_by_previous_question($slot)) {
            $placeholderqa = $this->make_blocked_question_placeholder($slot);
            $displayoptions->manualcomment = question_display_options::HIDDEN;
            $displayoptions->history = question_display_options::HIDDEN;
            $displayoptions->readonly = true;

            return html_writer::div($placeholderqa->render($displayoptions,
                    $this->get_question_number($this->get_original_slot($slot))),
                    'mod_quiz-blocked_question_warning');
        }

        $originalslot = $this->get_original_slot($slot);
        $number = $this->get_question_number($originalslot);

        if ($slot != $originalslot) {
            $originalmaxmark = $this->get_question_attempt($slot)->get_max_mark();
            $this->get_question_attempt($slot)->set_max_mark($this->get_question_attempt($originalslot)->get_max_mark());
        }

        if ($this->can_question_be_redone_now($slot)) {
            $displayoptions->extrainfocontent = $renderer->redo_question_button(
                    $slot, $displayoptions->readonly);
        }

        if ($displayoptions->history && $displayoptions->questionreviewlink) {
            $links = $this->links_to_other_redos($slot, $displayoptions->questionreviewlink);
            if ($links) {
                $displayoptions->extrahistorycontent = html_writer::tag('p',
                        get_string('redoesofthisquestion', 'quiz', $renderer->render($links)));
            }
        }

        $output = $this->quba->render_question($slot, $displayoptions, $number);

        if ($slot != $originalslot) {
            $this->get_question_attempt($slot)->set_max_mark($originalmaxmark);
        }

        return $output;
    }

    /**
     * Submit the attempt.
     *
     * The separate $timefinish argument should be used when the quiz attempt
     * is being processed asynchronously (for example when cron is submitting
     * attempts where the time has expired).
     * Based on process_finish of quiz_attempt - all questions should have been finished and
     * saved when this function is called
     *
     * @param int $timestamp the time to record as last modified time.
     * @param bool $processsubmitted if true, and question responses in the current
     *      POST request are stored to be graded, before the attempt is finished.
     * @param ?int $timefinish if set, use this as the finish time for the attempt.
     *      (otherwise use $timestamp as the finish time as well).
     * @param bool $studentisonline is the student currently interacting with Moodle?
     */
    public function process_finish_tcq($timestamp, $timefinish = null, $studentisonline = false) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $this->attempt->timemodified = $timestamp;
        $this->attempt->timefinish = $timefinish ?? $timestamp;
        $this->attempt->sumgrades = $this->quba->get_total_mark();
        $this->attempt->state = self::FINISHED;
        $this->attempt->timecheckstate = null;
        $this->attempt->gradednotificationsenttime = null;

        if (!$this->requires_manual_grading() ||
                !has_capability('mod/quiz:emailnotifyattemptgraded', $this->get_quizobj()->get_context(),
                        $this->get_userid())) {
            $this->attempt->gradednotificationsenttime = $this->attempt->timefinish;
        }

        $DB->update_record('quiz_attempts', $this->attempt);

        if (!$this->is_preview()) {
            $this->recompute_final_grade();

            // Trigger event.
            $this->fire_state_transition_event('\mod_quiz\event\attempt_submitted', $timestamp, $studentisonline);

            // Tell any access rules that care that the attempt is over.
            $this->get_access_manager($timestamp)->current_attempt_finished();
        }

        $transaction->allow_commit();
    }

    /**
     * Process responses during an attempt at a tcquiz.
     * Based on process_attempt of quiz_attempt, the difference being that
     * the tcquiz cannot be abandoned and the time doesn't matter
     *
     * @param  int $timenow time when the processing started.
     * @param  int $thispage current page number.
     * @return string the attempt state once the data has been processed.
     * @since  Moodle 3.1
     */
    public function process_attempt_tcq($timenow, $thispage) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $slots_on_this_page = $this->get_slots($thispage);

        try {
          $this->quba->process_all_actions($timestamp);
          foreach ($slots_on_this_page as $slot) {
            $this->quba->finish_question($slot,$timenow);
          }
          question_engine::save_questions_usage_by_activity($this->quba);

        } catch (question_out_of_sequence_exception $e) {
            throw new moodle_exception('submissionoutofsequencefriendlymessage', 'question',
                    $this->attempt_url(null, $thispage));

        } catch (Exception $e) {
            // This sucks, if we display our own custom error message, there is no way
            // to display the original stack trace.
            $debuginfo = '';
            if (!empty($e->debuginfo)) {
                $debuginfo = $e->debuginfo;
            }
            throw new moodle_exception('errorprocessingresponses', 'question',
                    $this->attempt_url(null, $thispage), $e->getMessage(), $debuginfo);
        }

        $this->fire_attempt_updated_event();

        $this->attempt->timemodified = $timenow;
        $this->attempt->state = self::IN_PROGRESS;
        $DB->update_record('quiz_attempts', $this->attempt);

        $transaction->allow_commit();

        return self::IN_PROGRESS;
    }

}
