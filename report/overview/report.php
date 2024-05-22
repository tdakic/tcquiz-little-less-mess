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
 * This file defines the quiz overview report class.
 *
 * @package   quiz_overview
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\reports\attempts_report;
use mod_quiz\question\bank\qbank_helper;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_options.php');
require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_form.php');
require_once($CFG->dirroot . '/mod/quiz/report/overview/overview_table.php');
require_once($CFG->dirroot . '/mod/quiz/report/overview/report.php');


/**
 * Quiz report subclass for the overview (grades) report.
 *
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcquiz_overview_report extends quiz_overview_report {

    public function tcq_display_final_graph($quiz, $cm, $course,$sessionid) {
        global $DB, $PAGE;

        list($currentgroup, $studentsjoins, $groupstudentsjoins, $allowedjoins) = $this->init(
                'overview', 'quiz_overview_settings_form', $quiz, $cm, $course);

        $options = new quiz_overview_options('overview', $quiz, $cm, $course);

        if ($fromform = $this->form->get_data()) {
            $options->process_settings_from_form($fromform);

        } else {
            $options->process_settings_from_params();
        }

        $this->form->set_data($options->get_initial_form_data());

        // Load the required questions.
        $questions = quiz_report_get_significant_questions($quiz);
        // Prepare for downloading, if applicable.
        $courseshortname = format_string($course->shortname, true,
                ['context' => context_course::instance($course->id)]);
        $table = new quiz_overview_table($quiz, $this->context, $this->qmsubselect,
                $options, $groupstudentsjoins, $studentsjoins, $questions, $options->get_url());
        $filename = quiz_report_download_filename(get_string('overviewfilename', 'quiz_overview'),
                $courseshortname, $quiz->name);
        $table->is_downloading($options->download, $filename,
                $courseshortname . ' ' . format_string($quiz->name, true));
        if ($table->is_downloading()) {
            raise_memory_limit(MEMORY_EXTRA);
        }

        $this->hasgroupstudents = false;
        if (!empty($groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                      FROM {user} u
                    $groupstudentsjoins->joins
                     WHERE $groupstudentsjoins->wheres";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentsjoins->params);
        }
        $hasstudents = false;
        if (!empty($studentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                    FROM {user} u
                    $studentsjoins->joins
                    WHERE $studentsjoins->wheres";
            $hasstudents = $DB->record_exists_sql($sql, $studentsjoins->params);
        }



        if ($options->attempts == self::ALL_WITH) {
            // This option is only available to users who can access all groups in
            // groups mode, so setting allowed to empty (which means all quiz attempts
            // are accessible, is not a security porblem.
            $allowedjoins = new \core\dml\sql_join();
        }

        $this->process_actions($quiz, $cm, $currentgroup, $groupstudentsjoins, $allowedjoins, $options->get_url());

        $hasquestions = quiz_has_questions($quiz->id);

        // Start output.

        // Only print headers if not asked to download data.
        $this->print_standard_header_and_messages($cm, $course, $quiz,
                    $options, $currentgroup, $hasquestions, $hasstudents);


        $hasstudents = $hasstudents && (!$currentgroup || $this->hasgroupstudents);


        if (!$table->is_downloading() && $options->usercanseegrades) {
            $output = $PAGE->get_renderer('mod_quiz');
            list($bands, $bandwidth) = self::get_bands_count_and_width($quiz);
            $labels = self::get_bands_labels($bands, $bandwidth, $quiz);

            if ($currentgroup && $this->hasgroupstudents) {
                $sql = "SELECT qg.id
                          FROM {quiz_grades} qg
                          JOIN {user} u on u.id = qg.userid
                        {$groupstudentsjoins->joins}
                          WHERE qg.quiz = $quiz->id AND {$groupstudentsjoins->wheres}";


                if ($DB->record_exists_sql($sql, $groupstudentsjoins->params)) {
                    $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, $groupstudentsjoins);
                    $chart = self::get_chart($labels, $data);
                    $groupname = format_string(groups_get_group_name($currentgroup), true, [
                        'context' => $this->context,
                    ]);
                    $graphname = get_string('overviewreportgraphgroup', 'quiz_overview', $groupname);
                    // Numerical range data should display in LTR even for RTL languages.
                    echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
                }

            }

        /*    if ($DB->record_exists('quiz_grades', ['quiz' => $quiz->id])) {
                $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, new \core\dml\sql_join());
                $chart = self::get_chart($labels, $data);
                $graphname = get_string('overviewreportgraph', 'quiz_overview');
                // Numerical range data should display in LTR even for RTL languages.
                echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
            }
            */


            if ($DB->record_exists('quiz_grades', ['quiz' => $quiz->id])) {

                $data = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, new \core\dml\sql_join());

                $sql = "SELECT sumgrades FROM {quiz_attempts} qa
                                LEFT JOIN {quizaccess_tcquiz_attempt} qta ON qa.id = qta.attemptid
                                WHERE qta.sessionid =:sessionid AND qa.preview = 0";

                $grades_to_plot = $DB->get_records_sql($sql, array('sessionid'=>$sessionid));

                //var_dump($grades_to_plot);

                $multiplier = $quiz->sumgrades;
                //var_dump($multiplier);

                $multiplier = floatval($quiz->grade)/floatval($quiz->sumgrades);

                $frequencies = array_fill(0, $bands, 0);

                foreach ($grades_to_plot as $grade){

                  if (!is_null($grade->sumgrades)){

                    $index = floor(floatval($grade->sumgrades) * $multiplier / $bandwidth);
                    if ($index == $bands){
                      $index--;
                    }
                    $frequencies[$index]++;

                  }
                  else {
                    //echo "NULL grade";
                  }

                }

                $chart = self::get_chart($labels, $frequencies);
                $graphname = get_string('overviewreportgraph', 'quiz_overview');
                echo $output->chart($chart, $graphname, ['dir' => 'ltr']);
            }


        }
        return true;
    }

}
