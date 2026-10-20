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
 * External service definitions for local_amberton.
 *
 * @package    local_amberton
 * @copyright  2026 Amberton University
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_amberton_publish_syllabus' => [
        'classname'   => 'local_amberton\external\publish_syllabus',
        'methodname'  => 'execute',
        'description' => 'Create or replace a Syllabus File resource using a draft itemid from webservice/upload.php',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities',
        'ajax'        => false,
    ],
    'local_amberton_create_syllabus' => [
        'classname'   => 'local_amberton\external\create_syllabus',
        'methodname'  => 'execute',
        'description' => 'Create a Syllabus File resource from the newest draft file with the given filename',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities',
        'ajax'        => false,
    ],
    'local_amberton_replace_syllabus' => [
        'classname'   => 'local_amberton\external\replace_syllabus',
        'methodname'  => 'execute',
        'description' => 'Replace files on an existing Syllabus course module from the newest draft file',
        'type'        => 'write',
        'capabilities' => 'moodle/course:manageactivities',
        'ajax'        => false,
    ],
];

$services = [
    'Amberton integrations' => [
        'functions' => [
            'local_amberton_publish_syllabus',
            'local_amberton_create_syllabus',
            'local_amberton_replace_syllabus',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_amberton',
        'downloadfiles' => 1,
        'uploadfiles' => 1,
    ],
];
