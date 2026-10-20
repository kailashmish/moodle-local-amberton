<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Legacy create helper using newest draft file by filename.
 *
 * @package    local_amberton
 * @copyright  2026 Amberton University
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_amberton\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;

/**
 * Create a Syllabus File resource from a draft filename.
 */
class create_syllabus extends external_api {

    /**
     * Describe parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'filename' => new external_value(PARAM_FILE, 'Draft filename uploaded via webservice/upload.php'),
            'name' => new external_value(PARAM_TEXT, 'Resource name', VALUE_DEFAULT, 'Syllabus'),
            'section' => new external_value(PARAM_INT, 'Section number', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Create (or update matching) Syllabus resource from draft filename.
     *
     * @param int $courseid Course id.
     * @param string $filename Draft filename.
     * @param string $name Resource name.
     * @param int $section Section number.
     * @return array
     */
    public static function execute($courseid, $filename, $name = 'Syllabus', $section = 0) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'filename' => $filename,
            'name' => $name,
            'section' => $section,
        ]);

        $draftitemid = publish_syllabus::latest_draft_itemid_for_filename($params['filename'], (int) $USER->id);

        return publish_syllabus::execute(
            $params['courseid'],
            $draftitemid,
            $params['name'],
            $params['section'],
            0
        );
    }

    /**
     * Describe return value.
     *
     * @return \external_single_structure
     */
    public static function execute_returns() {
        return publish_syllabus::execute_returns();
    }
}
