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
 * Common functions for the tcquiz statistics report.
 *
 * @package    tcquiz_statistics
 * @copyright  2013 The Open University
 * @author     James Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\quiz_attempt;

defined('MOODLE_INTERNAL') || die;

/**
 * SQL to fetch relevant 'tcquiquizz_attempts' records.
 *
 * @param int    $tcquizid        tcquiquizz id to get attempts for
 * @param \core\dml\sql_join $groupstudentsjoins Contains joins, wheres, params, empty if not using groups
 * @param string $whichattempts which attempts to use, represented internally as one of the constants as used in
 *                                   $tcquiquizz->grademethod ie.
 *                                   QUIZ_GRADEAVERAGE, QUIZ_GRADEHIGHEST, QUIZ_ATTEMPTLAST or QUIZ_ATTEMPTFIRST
 *                                   we calculate stats based on which attempts would affect the grade for each student.
 * @param bool   $includeungraded whether to fetch ungraded attempts too
 * @return array FROM and WHERE sql fragments and sql params
 */
function tcquiz_statistics_attempts_sql($tcquizid, $sessionid, \core\dml\sql_join $groupstudentsjoins,
        $whichattempts = QUIZ_GRADEAVERAGE, $includeungraded = false) {

    $fromqa = "{quiz_attempts} tcquiza LEFT JOIN {quizaccess_tcquiz_attempt} qata ON qata.attemptid = tcquiza.id ";

    $whereqa = 'qata.sessionid = :tcsessionid AND tcquiza.quiz = :tcquizid AND tcquiza.state = :quiz_state AND tcquiza.preview = 0';

    $qaparams = ['tcquizid' => (int)$tcquizid, 'tcsessionid'  => (int)$sessionid, 'quiz_state' => quiz_attempt::IN_PROGRESS];

    $whichattempts = quiz_attempt::IN_PROGRESS;

    $includeungraded = true;

    if (!empty($groupstudentsjoins->joins)) {
        $fromqa .= "\nJOIN {user} u ON u.id = quiza.userid
            {$groupstudentsjoins->joins} ";
        $whereqa .= " AND {$groupstudentsjoins->wheres}";
        $qaparams += $groupstudentsjoins->params;
    }

    $whichattemptsql = quiz_report_grade_method_sql($whichattempts);
    if ($whichattemptsql) {
        $whereqa .= ' AND ' . $whichattemptsql;
    }

    if (!$includeungraded) {
        $whereqa .= ' AND tcquiza.sumgrades IS NOT NULL';
    }

    return [$fromqa, $whereqa, $qaparams];
}

/**
 * Return a {@link qubaid_condition} from the values returned by {@link tcquiz_statistics_attempts_sql}.
 *
 * @param int     $tcquizid
 * @param \core\dml\sql_join $groupstudentsjoins Contains joins, wheres, params
 * @param string $whichattempts which attempts to use, represented internally as one of the constants as used in
 *                                   $tcquiz->grademethod ie.
 *                                   QUIZ_GRADEAVERAGE, QUIZ_GRADEHIGHEST, QUIZ_ATTEMPTLAST or QUIZ_ATTEMPTFIRST
 *                                   we calculate stats based on which attempts would affect the grade for each student.
 * @param bool    $includeungraded
 * @return        \qubaid_join
 */
function tcquiz_statistics_qubaids_condition($tcquizid, $sessionid, \core\dml\sql_join $groupstudentsjoins, $whichattempts = QUIZ_ATTEMPTLAST , $includeungraded = true) {
    list($fromqa, $whereqa, $qaparams) = tcquiz_statistics_attempts_sql(
            $tcquizid, $sessionid, $groupstudentsjoins, $whichattempts, $includeungraded);

    return new qubaid_join($fromqa, 'tcquiza.uniqueid', $whereqa, $qaparams);
}
