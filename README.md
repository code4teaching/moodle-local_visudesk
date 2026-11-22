# VisuDesk plugin (local_visudesk)

> Formerly **Course Files** (`local_coursefiles`). Renamed in version 1.0.0 because another plugin in the Moodle
> plugins directory already uses that name. Installed next to local_coursefiles, it takes over its settings; the web
> service functions are now called `local_visudesk_…`.

Web service functions that let external tools – for example a teacher's own dashboard or an AI assistant connected
through the [MCP web service plugin](https://github.com/onbirdev/moodle-webservice_mcp) – put files into a course
and show or hide activities.

![VisuDesk, a Mac app built on this plugin](docs/visudesk.png)

## Why this plugin exists

This plugin was developed for **[VisuDesk](https://visucode.de/visudesk/)**, a free Mac app for teachers. We wanted
teachers to manage their Moodle courses the way they manage their files: drag a worksheet from a folder into a section,
replace it with a new version, make it visible after the lesson – without opening Moodle in the browser.

The functions are not tied to VisuDesk. Any web service client can use them, for example a school's own tools or an AI
assistant connected through the MCP web service. Every call checks the same capabilities as the Moodle interface.

Moodle core cannot create file resources or folders through web services, and `core_course_edit_module`
(show/hide) and section editing are only available to the Moodle interface itself. This plugin adds:

| Function | What it does |
| --- | --- |
| `local_visudesk_create_resource` | Creates a file resource (e.g. a PDF worksheet) in a course section. |
| `local_visudesk_create_folder` | Creates a folder with several files in a course section. |
| `local_visudesk_update_files` | Replaces the file of a resource or all files of a folder. The activity keeps its ID, place, settings and completion. |
| `local_visudesk_set_visibility` | Shows or hides any activity or resource. |
| `local_visudesk_set_course_visibility` | Shows or hides a whole course (only this setting, `moodle/course:visibility`). |
| `local_visudesk_set_availability` | Plans when an activity becomes available (date restriction "from", other restrictions stay). |
| `local_visudesk_create_assign` | Creates an assignment with file submissions, due date and attached task files; feedback files on; points, a scale or no grade; optional marking workflow. |
| `local_visudesk_set_assign_duedate` | Changes the due date of an assignment. |
| `local_visudesk_update_assign` | Changes an assignment: name, task description, attached files, due date, grade type and marking workflow (only what is passed). |
| `local_visudesk_get_assign_grading` | Grading overview of an assignment: grade type and scale items, marking workflow, per participant grade, state, feedback (`mod/assign:grade`). |
| `local_visudesk_grade_submission` | Grades one participant: grade, feedback comment, marking workflow state (`mod/assign:grade`). |
| `local_visudesk_release_grades` | Releases grades of an assignment with marking workflow (`mod/assign:releasegrades`). |
| `local_visudesk_set_feedback_files` | Gives one student feedback files in an assignment, e.g. the corrected submission (`mod/assign:grade`; the grade stays). |
| `local_visudesk_create_section` | Creates a section at the end or at a given position. |
| `local_visudesk_update_section` | Changes name, summary or visibility of a section. |
| `local_visudesk_backup_course` | Creates a Moodle backup (.mbz) of a course **without user data** and returns a download address. |
| `local_visudesk_move_section` | Moves a section, with its activities, to another position. |
| `local_visudesk_request_course` | Asks for a new course (Moodle course request); with automatic approval on, a course that follows the naming scheme is created at once. |
| `local_visudesk_get_course_requests` | Lists the caller's pending requests, or all of them for approvers. |
| `local_visudesk_approve_course_request` | Approves a request, optionally with a corrected name and another category. |
| `local_visudesk_reject_course_request` | Rejects a request; the reason is sent to the requester. |
| `local_visudesk_get_autoapproved_courses` | Lists courses created automatically from requests, with their teachers, for the approver to check. |
| `local_visudesk_check_autoapproved_course` | Marks such a course as checked. |
| `local_visudesk_get_self_enrolment` | Reads the self enrolment of a course: on or off and the enrolment key. |
| `local_visudesk_set_self_enrolment` | Turns self enrolment on or off and sets the enrolment key; adds the method (role: student) if missing. |
| `local_visudesk_get_plan` | Returns the caller's **own** lesson planning for a date range (optionally only what changed since a time). |
| `local_visudesk_save_plan_entry` | Saves changes to one lesson of the caller's own planning, field by field; a field changed meanwhile on another device is refused instead of overwritten. |
| `local_visudesk_import_plan` | First upload of a planning the app kept locally; never overwrites what is already there. |

Files are sent in the request as `{filename, content}` with the content base64 encoded. Each file must not be larger
than the upload limit of the course. New resources and folders are hidden unless `visible=1`.

## Requirements

- Moodle 5.0 or later
- Web services enabled
- For the course request functions: *Site administration → Courses → Course request → Enable course requests*

## Installation

1. Install the plugin as `local/visudesk`.
2. Site administration → Server → Web services → External services: open **VisuDesk Plugin Service** and add the
   users who may use it – or add the functions to your own custom service.
3. Create a token for such a user and service.

## Permissions

Every function checks the capabilities of the token's user, as the Moodle interface does:

- `create_resource`: `moodle/course:manageactivities` and `mod/resource:addinstance`
- `create_folder`: `moodle/course:manageactivities` and `mod/folder:addinstance`
- `update_files`: `moodle/course:manageactivities` in the activity
- `set_visibility`: `moodle/course:activityvisibility` in the activity
- `create_section`, `update_section`: `moodle/course:update` (visibility also `moodle/course:sectionvisibility`)
- `move_section`: `moodle/course:movesections` and `moodle/course:update`
- `backup_course`: `moodle/backup:backupcourse` and `moodle/backup:downloadfile` (teachers have both by default)
- `request_course`: `moodle/course:request` (authenticated users have it by default; users who can create courses
  themselves cannot request one, as in Moodle)
- `get_course_requests`: own requests for everyone; `all=1` needs `moodle/site:approvecourse`
- `approve_course_request`, `reject_course_request`, `get_autoapproved_courses`, `check_autoapproved_course`:
  `moodle/site:approvecourse`
- `get_self_enrolment`, `set_self_enrolment`: `enrol/self:config` (teachers have it by default); adding the method also
  needs `moodle/course:enrolconfig`. The self enrolment plugin must be enabled on the site

## Connecting VisuDesk (no key to copy)

`local/visudesk/connect.php?passport=…&return=http://127.0.0.1:PORT/verbunden` lets VisuDesk get its key the way
the Moodle mobile app does: the teacher signs in to Moodle (single sign-on works), confirms, and Moodle sends the token
of the web service *VisuDesk* (short name `visudesk`) back to the app on the same computer. The token only goes to a
loopback address; the app accepts it only together with the random passport it sent. Moodle checks, as on the
*Security keys* page, that the user may create tokens (`moodle/webservice:createtoken`, e.g. through the site role
for teachers below) and, for a service with authorised users only, that the user is one of them.

## Lesson planning

Since 1.1.0 VisuDesk keeps each teacher's own lesson planning (topic, plan, how far the class got, links to course
material) in Moodle, so that it is the same on the Mac and on the iPad. Each lesson is one record in
`local_visudesk_plan`; every field carries the time of its last change, so changes from two devices to different
fields are merged and only a change to the same field is refused.

- Only the teacher reads and writes their planning. There is no capability to see other people's planning, not even
  for administrators.
- Planning not changed for two years is removed by the scheduled task *Remove old lesson planning* (setting *Keep
  planning for (days)*, 0 = keep until the account is deleted). Deleting an account removes its planning.
- The privacy API exports and deletes the planning (user context).

## License

GNU GPL v3 or later. © 2026 Holger Sebastião

## Site role for teachers (optional)

Web service capabilities such as `moodle/webservice:createtoken` are checked in the system context, so the course
role Teacher cannot grant them. Under *Site administration → Plugins → Local plugins → VisuDesk* you can choose a
system role (e.g. "Web service user") that everybody with the teacher role in at least one course gets
automatically. Saving the setting gives it to all current teachers; afterwards an event observer and a daily task
keep it in step. The role is not removed when someone stops teaching.
