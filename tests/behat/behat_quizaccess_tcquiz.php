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
 * Steps definitions related to mod_quiz.
 *
 * @package   mod_quiz
 * @category  test
 * @copyright 2014 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../../../question/tests/behat/behat_question_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

use Behat\Mink\Exception\ExpectationException as ExpectationException;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

/**
 * Steps definitions related to quizacess_tcquiz.
 *
 * @copyright 2024 Tamara Dakic
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_quizaccess_tcquiz extends behat_base {

    /**
     * @When /^I wait for the page to be loaded$/
     */
    public function i_wait_for_the_page_to_be_loaded()
    {
        //$xml = $this->getSession()->getDriver()->getContent();
        //var_dump($xml);
        $xml = file_get_contents($this->getSession()->getCurrentUrl());
        $this->getSession()->wait(10000, "document.readyState === 'complete'");
    }
}
