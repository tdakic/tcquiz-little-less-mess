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

/**
 * Quiz statistics report class.
 *
 * @package   quiz_statistics
 * @copyright 2014 Open University
 * @author    James Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/statistics/report.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/report/statistics/tcqstatisticslib.php');
use core_question\statistics\responses\analyser;

/**
 * Adds to quiz_statistics_report in order to display question stats
 * after the question polling ended
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @ Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class tcquiz_statistics_report extends quiz_statistics_report {

  /**
   * Displays the question statistics for the teacher after the question polling ended
   *
   * @param stdClass         $quiz  the quiz settings.
   * @param int              $sessionid the tcq session id
   * @param int              $slot the slot of the question being analyzed
   * @param stdClass         $cm course module
   * @param stdClass         $course
   */
  public function tcq_display_question_stats($quiz, $sessionid, $slot, $cm, $course) {
        global $OUTPUT, $DB;

        raise_memory_limit(MEMORY_HUGE);

        $this->context = context_module::instance($cm->id);

        if (!quiz_has_questions($quiz->id)) {
            $this->print_header_and_tabs($cm, $course, $quiz, 'statistics');
            echo quiz_no_questions_message($quiz, $cm, $this->context);
            return true;
        }

        $variantno = optional_param('variant', null, PARAM_INT);
        $whichtries = optional_param('whichtries', question_attempt::LAST_TRY, PARAM_ALPHA);

        $pageoptions = [];
        $pageoptions['id'] = $cm->id;
        $pageoptions['mode'] = 'statistics';

        $whichattempts = mod_quiz\quiz_attempt::IN_PROGRESS;

        $reporturl = new moodle_url('/mod/quiz/report.php', $pageoptions);

        if ($whichattempts != $quiz->grademethod) {
            $reporturl->param('whichattempts', $whichattempts);
        }

        if ($whichtries != question_attempt::LAST_TRY) {
            $reporturl->param('whichtries', $whichtries);
        }

        // Find out current groups mode.
        $currentgroup = $this->get_current_group($cm, $course, $this->context);
        $nostudentsingroup = false; // True if a group is selected and there is no one in it.
        if (empty($currentgroup)) {
            $currentgroup = 0;
            $groupstudentsjoins = new \core\dml\sql_join();

        } else if ($currentgroup == self::NO_GROUPS_ALLOWED) {
            $groupstudentsjoins = new \core\dml\sql_join();
            $nostudentsingroup = true;

        } else {
            // All users who can attempt quizzes and who are in the currently selected group.
            $groupstudentsjoins = get_enrolled_with_capabilities_join($this->context, '',
                    ['mod/quiz:reviewmyattempts', 'mod/quiz:attempt'], $currentgroup);
            if (!empty($groupstudentsjoins->joins)) {
                $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    {$groupstudentsjoins->joins}
                    WHERE {$groupstudentsjoins->wheres}";
                if (!$DB->record_exists_sql($sql, $groupstudentsjoins->params)) {
                    $nostudentsingroup = true;
                }
            }
        }

        $qubaids = tcquiz_statistics_qubaids_condition($quiz->id, $sessionid, $groupstudentsjoins, $whichattempts);


        $this->table = new quiz_statistics_table();

        $questions = $this->load_and_initialise_questions_for_calculations($quiz);

        if (!$nostudentsingroup) {
            // Get the data to be displayed.
            $progress = $this->get_progress_trace_instance();
            list($quizstats, $questionstats) =
                $this->tcq_get_all_stats_and_analysis($quiz, $sessionid, $whichattempts, $whichtries, $groupstudentsjoins, $questions, $progress);
        } else {
            // Or create empty stats containers.
            $quizstats = new \quiz_statistics\calculated($whichattempts);
            $questionstats = new \core_question\statistics\questions\all_calculated_for_qubaid_condition();
        }

        // Report on an individual question indexed by position.
        if (!isset($questions[$slot])) {
            throw new \moodle_exception('questiondoesnotexist', 'question');
        }


        if ($questionstats->for_slot($slot, $variantno)->s == 0)
        {
          return false;
        }

        return $this->output_individual_question_response_analysis($questions[$slot],
                                                          $variantno,
                                                          $questionstats->for_slot($slot, $variantno)->s,
                                                          $reporturl,
                                                          $qubaids,
                                                          $whichtries);
    }




    /**
     * Display the response analysis for a question.
     *
     * @param stdClass         $question  the question to report on.
     * @param int|null         $variantno the variant
     * @param int              $s
     * @param moodle_url       $reporturl the URL to redisplay this report.
     * @param qubaid_condition $qubaids
     * @param string           $whichtries
     */
    protected function output_individual_question_response_analysis($question, $variantno, $s, $reporturl, $qubaids,
                                                                    $whichtries = question_attempt::LAST_TRY) {
        global $OUTPUT;
        $output_str='';

        if (!question_bank::get_qtype($question->qtype, false)->can_analyse_responses()) {
            return false;
        }

        $qtable = new quiz_statistics_question_table($question->id);

        if (!$this->table->is_downloading()) {
            // Output an appropriate title.
            $output_str .= $OUTPUT->heading(get_string('analysisofresponses', 'quiz_statistics'), 3);

        } else {
            // Work out an appropriate title.
            $a = clone($question);
            $a->variant = $variantno;

            if (!empty($question->number) && !is_null($variantno)) {
                $questiontabletitle = get_string('analysisnovariant', 'quiz_statistics', $a);
            } else if (!empty($question->number)) {
                $questiontabletitle = get_string('analysisno', 'quiz_statistics', $a);
            } else if (!is_null($variantno)) {
                $questiontabletitle = get_string('analysisvariant', 'quiz_statistics', $a);
            } else {
                $questiontabletitle = get_string('analysisnameonly', 'quiz_statistics', $a);
            }

            if ($this->table->is_downloading() == 'html') {
                $questiontabletitle = get_string('analysisofresponsesfor', 'quiz_statistics', $questiontabletitle);
            }

            // Set up the table.
            //$output_str .= $exportclass->start_table($questiontabletitle);

            if ($this->table->is_downloading() == 'html') {
                $output_str .= $this->render_question_text($question);
            }
        }

        $responesanalyser = new analyser($question, $whichtries);


        // TTT the line below might be tricky ... returning null - decided just to calculate
        //$responseanalysis = $responesanalyser->load_cached($qubaids, $whichtries);
        $responseanalysis = $responesanalyser->calculate($qubaids, $whichtries);


        $output_str .= $qtable->question_setup($reporturl, $question, $s, $responseanalysis);
        if ($this->table->is_downloading()) {
            //$exportclass->output_headers($qtable->headers);
        }

        // Where no variant no is specified the variant no is actually one.
        if ($variantno === null) {
            $variantno = 1;
        }
        foreach ($responseanalysis->get_subpart_ids($variantno) as $partid) {
            $subpart = $responseanalysis->get_analysis_for_subpart($variantno, $partid);
            foreach ($subpart->get_response_class_ids() as $responseclassid) {
                $responseclass = $subpart->get_response_class($responseclassid);
                $tabledata = $responseclass->data_for_question_response_table($subpart->has_multiple_response_classes(), $partid);
                foreach ($tabledata as $row) {

                    ob_start(); // begin collecting output
                    $output_str .= $qtable->add_data_keyed($qtable->format_row($row));
                    $tmp_row = ob_get_clean();

                    $output_str .= $tmp_row;
                }
            }
        }

        //$output_str .= $qtable->finish_output(!$this->table->is_downloading());
        $output_str .= ".</table>";   //I tried having ob_start(); at the beginning of  the function, but that
                                      //wouldnot get me the title of the pages
                                      //This hackis here, because the line above seems to be causing some extra lines at the start of the html document

        //var_dump($output_str);
        return $output_str;
    }


    /**
     * Get the tcqquiz and question statistics
     *
     * @param stdClass $quiz             the quiz settings.
     * @param string $whichattempts      which attempts to use, represented internally as one of the constants as used in
     *                                   $quiz->grademethod ie.
     *                                   QUIZ_GRADEAVERAGE, QUIZ_GRADEHIGHEST, QUIZ_ATTEMPTLAST or QUIZ_ATTEMPTFIRST
     *                                   we calculate stats based on which attempts would affect the grade for each student.
     * @param string $whichtries         which tries to analyse for response analysis. Will be one of
     *                                   question_attempt::FIRST_TRY, LAST_TRY or ALL_TRIES.
     * @param \core\dml\sql_join $groupstudentsjoins Contains joins, wheres, params for students in this group.
     * @param array  $questions          full question data.
     * @param \core\progress\base|null   $progress
     * @param bool $calculateifrequired  if true (the default) the stats will be calculated if not already stored.
     *                                   If false, [null, null] will be returned if the stats are not already available.
     * @param bool $performanalysis      if true (the default) and there are calculated stats, analysis will be performed
     *                                   for each question.
     * @return array with 2 elements:    - $quizstats The statistics for overall attempt scores.
     *                                   - $questionstats \core_question\statistics\questions\all_calculated_for_qubaid_condition
     *                                   Both may be null, if $calculateifrequired is false.
     */
    public function tcq_get_all_stats_and_analysis(
            $quiz, $sessionid, $whichattempts, $whichtries, \core\dml\sql_join $groupstudentsjoins,
            $questions, $progress = null, bool $calculateifrequired = true, bool $performanalysis = true) {

        if ($progress === null) {
            $progress = new \core\progress\none();
        }

        $qubaids = tcquiz_statistics_qubaids_condition($quiz->id, $sessionid, $groupstudentsjoins, $whichattempts);

        $qcalc = new \core_question\statistics\questions\calculator($questions, $progress);

        $quizcalc = new \quiz_statistics\calculator($progress);

        $progress->start_progress('', 4);

        // Get a lock on this set of qubaids before performing calculations. This prevents the same calculation running
        // concurrently and causing database deadlocks. We use a long timeout here as a big quiz with lots of attempts may
        // take a long time to process.
        $lockfactory = \core\lock\lock_config::get_lock_factory('quiz_statistics_get_stats');
        $lock = $lockfactory->get_lock($qubaids->get_hash_code(), 0);
        if (!$lock) {
            if (!$calculateifrequired) {
                // We're not going to do the calculation in this request anyway, so just give up here.
                $progress->progress(4);
                $progress->end_progress();
                return [null, null];
            }
            $locktimeout = get_config('quiz_statistics', 'getstatslocktimeout');
            $lock = \core\lock\lock_utils::wait_for_lock_with_progress(
                $lockfactory,
                $qubaids->get_hash_code(),
                $progress,
                $locktimeout,
                get_string('getstatslockprogress', 'quiz_statistics'),
            );
            if (!$lock) {
                // Lock attempt timed out.
                $progress->progress(4);
                $progress->end_progress();
                debugging('Could not get lock on ' .
                        $qubaids->get_hash_code() . ' (Quiz ID ' . $quiz->id . ') after ' .
                        $locktimeout . ' seconds');
                return [null, null];
            }
        }

        try {
            if ($quizcalc->get_last_calculated_time($qubaids) === false) {
                if (!$calculateifrequired) {
                    $progress->progress(4);
                    $progress->end_progress();
                    $lock->release();
                    return [null, null];
                }

                // Recalculate now.
                $questionstats = $qcalc->calculate($qubaids);
                $progress->progress(2);

                $quizstats = $quizcalc->calculate(
                    $quiz->id,
                    $whichattempts,
                    $groupstudentsjoins,
                    count($questions),
                    $qcalc->get_sum_of_mark_variance()
                );
                $progress->progress(3);
            } else {
                $quizstats = $quizcalc->get_cached($qubaids);
                $progress->progress(2);
                $questionstats = $qcalc->get_cached($qubaids);
                $progress->progress(3);
            }

            if ($quizstats->s() && $performanalysis) {
                $subquestions = $questionstats->get_sub_questions();
                $this->analyse_responses_for_all_questions_and_subquestions(
                    $questions,
                    $subquestions,
                    $qubaids,
                    $whichtries,
                    $progress
                );
            }
            $progress->progress(4);
            $progress->end_progress();
        } finally {
            $lock->release();
        }

        return [$quizstats, $questionstats];
    }

}
