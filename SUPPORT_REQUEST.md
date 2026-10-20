# Moodle Cloud support request — install local_amberton

## Site

- Site: https://moodle.amberton.edu
- Request: Install/upgrade the attached custom local plugin

## Plugin

- Component: `local_amberton`
- Install path: `local/amberton`
- Zip: `local_amberton.zip` (folder inside zip must be named `amberton`)
- Version: 1.1.1 (2026100901)
- Requires: Moodle 4.1+

## Purpose

Allow our student dashboard to publish an approved syllabus PDF into a Moodle course as a standard **File** activity named **Syllabus**, using:

1. `/webservice/upload.php` (core)
2. Web service `local_amberton_publish_syllabus` (this plugin)

Core Moodle cannot attach a draft upload as a course File resource without a custom web service.

## After install — please also

1. Open **Site administration → Server → Web services → External services**
2. On the external service used by our integration token, add function:
   - `local_amberton_publish_syllabus`
3. Confirm that service has **Can upload files** = Yes
4. Confirm the token user can manage activities in course categories used for term shells

## Verification

We can verify by calling `local_amberton_publish_syllabus` and checking `core_course_get_contents` for a module named Syllabus (`modname=resource`).
