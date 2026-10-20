# local_amberton

Amberton University Moodle local plugin. Provides web services to publish a syllabus PDF as a course **File** activity named **Syllabus**.

## Requirements

- Moodle 4.1 or later
- Web services + REST protocol enabled
- External service with **Can upload files** enabled
- Token user with `moodle/course:manageactivities` in target courses

## Install

1. Unzip so the folder is `moodle/local/amberton`
2. Visit **Site administration → Notifications** to install/upgrade
3. **Server → Web services → External services** → edit your service → add:
   - `local_amberton_publish_syllabus` (required)
   - `local_amberton_create_syllabus` (optional legacy)
   - `local_amberton_replace_syllabus` (optional legacy)

## API flow

1. Upload PDF:

```bash
curl -X POST "https://YOUR_MOODLE/webservice/upload.php" \
  -F "token=TOKEN" \
  -F "file_1=@syllabus.pdf"
```

2. Publish into course (use `itemid` from upload response):

| Parameter | Required | Description |
|-----------|----------|-------------|
| `courseid` | yes | Moodle course id |
| `draftitemid` | yes | `itemid` from upload.php |
| `name` | no | Default `Syllabus` |
| `section` | no | Default `0` |
| `cmid` | no | Existing module id to replace; otherwise finds “Syllabus” or creates |

Returns `{ "cmid": 123, "created": true, "name": "Syllabus" }`.

## Privacy

This plugin does not store personal data (null privacy provider).
