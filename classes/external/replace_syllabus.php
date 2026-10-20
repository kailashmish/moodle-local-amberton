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
 * Legacy replace helper using newest draft file by filename.
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
use moodle_exception;

/**
 * Replace an existing Syllabus File resource from a draft filename.
 */
class replace_syllabus extends external_api {

    /**
     * Describe parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Existing Syllabus course-module id'),
            'filename' => new external_value(PARAM_FILE, 'Draft filename uploaded via webservice/upload.php'),
            'name' => new external_value(PARAM_TEXT, 'Resource name', VALUE_DEFAULT, 'Syllabus'),
        ]);
    }

    /**
     * Replace Syllabus resource files from draft filename.
     *
     * @param int $cmid Course module id.
     * @param string $filename Draft filename.
     * @param string $name Resource name.
     * @return array
     */
    public static function execute($cmid, $filename, $name = 'Syllabus') {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'filename' => $filename,
            'name' => $name,
        ]);

        $cm = get_coursemodule_from_id('resource', $params['cmid'], 0, false, IGNORE_MISSING);
        if (!$cm) {
            throw new moodle_exception('errornocmid', 'local_amberton');
        }

        $draftitemid = publish_syllabus::latest_draft_itemid_for_filename($params['filename'], (int) $USER->id);

        return publish_syllabus::execute(
            (int) $cm->course,
            $draftitemid,
            $params['name'],
            0,
            (int) $params['cmid']
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
