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
 * External function to publish a syllabus PDF into a course.
 *
 * @package    local_amberton
 * @copyright  2026 Amberton University
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_amberton\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/resource/lib.php');
require_once($CFG->dirroot . '/mod/resource/locallib.php');
require_once($CFG->libdir . '/filelib.php');

use context_course;
use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use moodle_exception;
use stdClass;

/**
 * Create or replace a course File resource named Syllabus from a draft upload.
 */
class publish_syllabus extends external_api {

    /**
     * Describe parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'draftitemid' => new external_value(PARAM_INT, 'Draft item id from webservice/upload.php'),
            'name' => new external_value(PARAM_TEXT, 'Resource display name', VALUE_DEFAULT, 'Syllabus'),
            'section' => new external_value(PARAM_INT, 'Course section number', VALUE_DEFAULT, 0),
            'cmid' => new external_value(PARAM_INT, 'Existing course-module id to replace (0 = find/create)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Publish a draft PDF as a Syllabus File activity in a course.
     *
     * @param int $courseid Course id.
     * @param int $draftitemid Draft area item id from upload.php.
     * @param string $name Resource display name.
     * @param int $section Section number.
     * @param int $cmid Existing course-module id, or 0 to find/create.
     * @return array{cmid:int, created:bool, name:string}
     */
    public static function execute($courseid, $draftitemid, $name = 'Syllabus', $section = 0, $cmid = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'draftitemid' => $draftitemid,
            'name' => $name,
            'section' => $section,
            'cmid' => $cmid,
        ]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        self::assert_draft_has_files((int) $params['draftitemid'], (int) $USER->id);

        $displayname = trim((string) $params['name']);
        if ($displayname === '') {
            $displayname = 'Syllabus';
        }

        $existingcm = null;
        if (!empty($params['cmid'])) {
            $existingcm = get_coursemodule_from_id('resource', $params['cmid'], $course->id, false, IGNORE_MISSING);
        }
        if (!$existingcm) {
            $existingcm = self::find_syllabus_cm($course->id, $displayname);
        }

        if ($existingcm) {
            $published = self::replace_resource_files($existingcm, (int) $params['draftitemid'], $displayname);
            return [
                'cmid' => (int) $published,
                'created' => false,
                'name' => $displayname,
            ];
        }

        // add_moduleinfo() requires modules.id on $moduleinfo->module.
        $resourcemodule = $DB->get_record('modules', ['name' => 'resource'], '*', MUST_EXIST);

        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->modulename = 'resource';
        $moduleinfo->module = (int) $resourcemodule->id;
        $moduleinfo->section = (int) $params['section'];
        $moduleinfo->name = $displayname;
        $moduleinfo->intro = 'Course syllabus';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->introeditor = [
            'text' => 'Course syllabus',
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $moduleinfo->showdescription = 0;
        $moduleinfo->files = (int) $params['draftitemid'];
        $moduleinfo->display = RESOURCELIB_DISPLAY_AUTO;
        $moduleinfo->showsize = 1;
        $moduleinfo->showtype = 1;
        $moduleinfo->showdate = 0;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->cmidnumber = '';
        $moduleinfo->completion = COMPLETION_DISABLED;
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;

        $created = add_moduleinfo($moduleinfo, $course);

        return [
            'cmid' => (int) $created->coursemodule,
            'created' => true,
            'name' => $displayname,
        ];
    }

    /**
     * Describe return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'Course module id of the Syllabus resource'),
            'created' => new external_value(PARAM_BOOL, 'True when a new resource was created'),
            'name' => new external_value(PARAM_TEXT, 'Resource name'),
        ]);
    }

    /**
     * Find an existing File activity named Syllabus (or containing "syllabus").
     *
     * @param int $courseid Course id.
     * @param string $preferredname Preferred exact name.
     * @return \stdClass|null
     */
    public static function find_syllabus_cm(int $courseid, string $preferredname) {
        global $DB;

        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {resource} r ON r.id = cm.instance
                 WHERE cm.course = :courseid
                   AND m.name = :modname
                   AND cm.deletioninprogress = 0
              ORDER BY cm.id ASC";
        $cms = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'modname' => 'resource',
        ]);

        $fallback = null;
        foreach ($cms as $row) {
            $cm = get_coursemodule_from_id('resource', $row->id, $courseid, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $cmname = (string) $cm->name;
            if (strcasecmp($cmname, $preferredname) === 0) {
                return $cm;
            }
            if ($fallback === null && stripos($cmname, 'syllabus') !== false) {
                $fallback = $cm;
            }
        }

        return $fallback;
    }

    /**
     * Ensure the draft area contains at least one file.
     *
     * @param int $draftitemid Draft item id.
     * @param int $userid User id that owns the draft area.
     * @throws moodle_exception
     */
    public static function assert_draft_has_files(int $draftitemid, int $userid): void {
        $fs = get_file_storage();
        $usercontext = \context_user::instance($userid);
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);
        if (empty($draftfiles)) {
            throw new moodle_exception('errornodraft', 'local_amberton');
        }
    }

    /**
     * Replace the file(s) on an existing resource module from a draft area.
     *
     * @param \stdClass $cm Course module record.
     * @param int $draftitemid Draft item id.
     * @param string $displayname Display name.
     * @return int Course module id.
     */
    public static function replace_resource_files(\stdClass $cm, int $draftitemid, string $displayname): int {
        global $DB;

        $resource = $DB->get_record('resource', ['id' => $cm->instance], '*', MUST_EXIST);
        $resource->name = $displayname;
        $resource->timemodified = time();
        $DB->update_record('resource', $resource);

        $modulecontext = context_module::instance($cm->id);
        file_save_draft_area_files(
            $draftitemid,
            $modulecontext->id,
            'mod_resource',
            'content',
            0,
            [
                'subdirs' => 0,
                'maxfiles' => 1,
                'accepted_types' => ['.pdf'],
            ]
        );

        $resource = $DB->get_record('resource', ['id' => $cm->instance], '*', MUST_EXIST);
        if (property_exists($resource, 'revision')) {
            $resource->revision = ((int) $resource->revision) + 1;
            $DB->update_record('resource', $resource);
        }

        rebuild_course_cache($cm->course, true);

        return (int) $cm->id;
    }

    /**
     * Find the newest draft itemid that contains the given filename for a user.
     *
     * @param string $filename Filename in the draft area.
     * @param int $userid User id.
     * @return int Draft item id.
     * @throws moodle_exception
     */
    public static function latest_draft_itemid_for_filename(string $filename, int $userid): int {
        $fs = get_file_storage();
        $usercontext = \context_user::instance($userid);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', false, 'timemodified DESC, id DESC', false);
        foreach ($files as $file) {
            if ($file->get_filename() === $filename) {
                return (int) $file->get_itemid();
            }
        }

        throw new moodle_exception('errornodraft', 'local_amberton');
    }
}
