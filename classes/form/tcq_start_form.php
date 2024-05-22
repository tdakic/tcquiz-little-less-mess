<?php

require_once($CFG->libdir."/formslib.php");

class tcq_start_form extends moodleform {

    public function definition() {

        $mform = $this->_form;

        //I want the two items in this form to be displayed side-by-side
        $mform->addElement('html', '<style> .fitem {display: inline-block;}</style>');

        $mform->addElement('submit', 'start_new_session_button', get_string('startnewquiz', 'quizaccess_tcquiz'));
        $mform->addElement('text', 'joincode', '',['placeholder'=> get_string('enterjoincode', 'quizaccess_tcquiz')]);
        //$mform->addRule('joincode', get_string('err_alphanumeric', 'core_form'), 'regex', '/^[a-zA-Z]+$/', 'client');
        $mform->addRule('joincode', get_string('err_alphanumeric', 'core_form'), 'regex', '/^[a-zA-Z0-9]+$/', 'client');

        //$mform->addRule('joincode', null, 'required', null, 'client'); chose to validate in the validation method
        $mform->setType('joincode', PARAM_TEXT);

        //so the page can be redisplayed
        $mform->addElement('hidden', 'id', intval($this->_customdata['cmid']));
        $mform->setType('id', PARAM_INT);

    }

    // Custom validation of the joincode field
    // Chose not to have the joincode as required, so checking if it is empty here
    // If there is an existing session of the quiz, the joincode is not technically required
    public function validation($data,$files) {

      $errors = parent::validation($data,$files);

      if ($data['joincode'] == ""){
        $errors['joincode'] = '-'.get_string('err_required', 'core_form');
        return $errors;
      }

      global $DB;
      $tcquiz = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $this->_customdata['quizid'],'joincode'=>$data['joincode']]);

      if ($tcquiz){ //teacher tried creating a new session,but a session with the same name exists
        $errors['joincode'] = '-'.get_string('sessionexisterror', 'quizaccess_tcquiz');
      }
      return $errors;

    }
}
