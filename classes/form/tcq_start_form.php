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
 * A part of the form that allows a teacher to start a TCQuiz
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir."/formslib.php");

class tcq_start_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        //I want the two items in this form to be displayed side-by-side
        //$mform->addElement('html', '<style> .fitem {display: inline-block;}</style>'); //moved to start_tcq.mustache

        $mform->addElement('submit', 'start_new_session_button', get_string('startnewquiz', 'quizaccess_tcquiz'));
        $mform->addElement('text', 'joincode', '',['placeholder'=> get_string('enterjoincode', 'quizaccess_tcquiz')]);
        $mform->addRule('joincode', get_string('err_alphanumeric', 'core_form'), 'regex', '/^[a-zA-Z0-9]+$/', 'client');

        $mform->setType('joincode', PARAM_TEXT);

        //so the page can be redisplayed
        $mform->addElement('hidden', 'id', intval($this->_customdata['cmid']));
        $mform->setType('id', PARAM_INT);

    }

    // Custom validation of the joincode field
    // Chose not to have the joincode as required, so checking if it is empty here
    // If there is an existing session of the quiz, the joincode is not technically required
    public function validation($data, $files) {

      $errors = parent::validation($data,$files);

      if ($data['joincode'] == ""){
        $errors['joincode'] = '-'.get_string('err_required', 'core_form');
        return $errors;
      }

      global $DB;
      $tcquiz = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $this->_customdata['quizid'],'joincode'=>$data['joincode']]);

      if ($tcquiz){ //teacher tried creating a new session,but a session with the same name exists
        $errors['joincode'] = '-'.get_string('sessionexisterror', 'quizaccess_tcquiz');
        return $errors;
      }
      return $errors;

    }
}
