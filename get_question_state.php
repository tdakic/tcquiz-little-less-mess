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
 * This script check the state of the the current tcquiz question
 * 1 question is running, 0 it is not running
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tcquiz;

require_once(__DIR__ . '/../../../../config.php');
global $DB;

$sessionid = required_param('sessionid', PARAM_INT);
$attemptid = required_param('attempt', PARAM_INT);

//add privilege checks??? what should be checked - using this at the wrong time?
//a lot DB querries (because of polling) for nothing ???
//What if someone knows the state of the questions? Doesn't sound like a big deal.

require_sesskey();

$session = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid));

header('content-type: text');
echo $session->currentpagestate;
