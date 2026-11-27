# Changes

## 1.2.1 (2026-11-28)

Changes from the plugin review.

- Grading follows the same rules as Moodle's grading page: participants are limited to the groups the teacher may
  access and, with marker allocation, to the participants allocated to them; marking workflow states are limited to
  the ones Moodle offers the teacher (releasing needs *mod/assign:releasegrades*).
- Assignments with anonymous submissions or an advanced grading method (for example a rubric) are refused with a
  message to grade them in Moodle; grades locked or overridden in the gradebook are not changed.
- Feedback comments are saved through the feedback comments plugin.
- The site role for teachers is taken away again when someone no longer teaches any course, and by the daily task;
  only assignments this plugin made are removed. With the feature switched off, the task removes them all.
- Tests for these rules; code style.

## 1.2.0 (2026-11-27)

- VisuGrade sync: new functions `local_visudesk_vg_fetch`, `vg_push`, `vg_set_snapshot`, `vg_purge`, `vg_pair` and
  `vg_delete_all`, new tables `local_visudesk_vgentry`, `local_visudesk_vgsnap` and `local_visudesk_vgpair`. VisuGrade
  (the grade book app) encrypts every change on the teacher's device before sending it; Moodle only numbers and keeps
  them and cannot read names or grades. A device may only send on top of the newest number, so no change is lost
  between the Mac, the iPad and the iPhone.
- Own web service **VisuGrade Sync** (`local_visudesk_visugrade`) with nothing but these functions; the connect page
  hands out tokens for it, so VisuGrade's token can do nothing else in Moodle and stays separate from VisuDesk.
- Nightly task: changes older than 30 days that a snapshot covers are deleted (the history VisuGrade shows), pairing
  requests after ten minutes. Deleting an account or ending the sync removes everything.
- Privacy API: declares, exports (as stored, encrypted) and deletes the sync data.

## 1.1.0 (2026-11-22)

- Lesson planning in Moodle: new functions `local_visudesk_get_plan`, `local_visudesk_save_plan_entry` and
  `local_visudesk_import_plan`, new table `local_visudesk_plan`. Each teacher only reads and writes their own planning.
  Changes are saved field by field with the server time, so the Mac and the iPad can work on the same lesson; a field
  changed meanwhile on the other device is reported as a conflict instead of overwritten.
- Planning not changed for 730 days is removed every night (setting *Keep planning for (days)*); deleting an account
  removes its planning too.
- Privacy API: the plugin now declares, exports and deletes the planning (before: no personal data).

## 1.0.0 (2026-11-21)

- New name: **local_visudesk** (shown as “VisuDesk”), formerly local_coursefiles (Course Files) 0.16.0. Another plugin
  in the Moodle plugins directory already uses the name local_coursefiles, and Moodle allows each name only once.
- All web service functions are now called `local_visudesk_…` instead of `local_coursefiles_…`, with the same
  parameters; capabilities, settings and the connect page (`/local/visudesk/connect.php`) moved along.
- When installed next to local_coursefiles, it takes over its settings and the list of automatically created courses.
  Both can run side by side while apps switch over; uninstall local_coursefiles afterwards.

The entries below are the history of local_coursefiles.

## 0.16.0 (2026-11-21)

- Automatic approval of course requests works with any naming scheme: new settings *Scheme for the full name* and
  *Scheme for the short name* (regular expressions with a group `class` and an optional group `key`, which has to be the
  same in both names) and *Archive categories* (text in the category path, default “Archiv”). The defaults keep the
  scheme “school year class initials subject”.

## 0.15.0 (2026-11-20)

- `create_assign`: new assignments require students to click the submit button (`submissiondrafts`, default 1). Until
  then students can upload parts and add to them later; the teacher sees the draft. A submitted assignment is fixed,
  so a model solution shown "only to students who submitted" cannot be copied into the submission afterwards.
  Pass `submissiondrafts` 0 for the old behaviour.

## 0.14.0 (2026-11-20)

- Course requests can be approved automatically (setting *Approve course requests automatically*, off by default). A
  request that follows the scheme “school year class initials subject” for a class that already has a course is created
  at once: hidden, in the category of the newest course of that class (archive categories left out), with the requester
  as teacher. The person named in *Notify about created courses* (default: main administrator) gets a message to check
  it; approvers do not get the usual “new request” message. Everything else stays a normal request.
- `request_course` also returns `courseid` and `categoryname` when the course was created at once (`requestid` is 0).
- New `get_autoapproved_courses` and `check_autoapproved_course`: the approver sees the courses created automatically
  (with their teachers) and marks them as checked. The plugin stores only course, time created and time checked
  (new table `local_visudesk_autoappr`), no personal data.

## 0.13.0 (2026-11-20)

- `create_assign` and `update_assign` set how an assignment is graded: maximum points, no grade (`grade` 0), or a
  scale by name (`gradescale`, a site scale or one of the course), and the marking workflow (`markingworkflow`), so
  students see grades only once they are released. The grade type can only change while nobody has a grade.
- New `get_assign_grading`: grade type with the scale items, marking workflow and, per participant, grade, workflow
  state, feedback comment and number of feedback files.
- New `grade_submission`: grades one participant – grade, feedback comment and marking workflow state.
- New `release_grades`: releases grades (all graded participants or the ones given), so they reach the gradebook.

## 0.12.0 (2026-11-19)

- New `update_assign`: changes an existing assignment – name, task description, attached files (keep some, add or
  replace others) and due date. Only what is passed changes; submissions, grades and all other settings stay.

## 0.11.0 (2026-11-19)

- New `create_assign`: creates an assignment in a section – file submissions, a due date, the task as text and
  attached files (e.g. the worksheet), feedback comments and feedback files switched on. Hidden by default.
- New `set_assign_duedate`: changes the due date of an assignment (calendar event included); a cut-off date before the
  new due date is removed.

## 0.10.0 (2026-11-19)

- New `set_feedback_files`: gives one student feedback files in an assignment (e.g. the corrected, annotated
  submission), replacing earlier feedback files of this student. Needs `mod/assign:grade`; the feedback type
  "Feedback files" must be enabled in the assignment. The grade itself is not changed.

## 0.9.1 (2026-11-19)

- `set_course_visibility` returns the new visibility from the database (not the cached course).

## 0.9.0 (2026-11-19)

- New `set_course_visibility`: shows or hides a whole course, checking `moodle/course:visibility` – teachers can
  publish their own course without getting `core_course_update_courses`, which can change much more.

## 0.8.0 (2026-11-19)

- New `set_availability`: plans when an activity or resource becomes available – a date restriction "from" at the top
  level (replacing an earlier one, other restrictions stay), and the activity is made visible so Moodle releases it by
  itself. `hidebefore` (default 1) hides it completely before the date. `timefrom=0` removes the date again. Refused
  when restricted access is turned off on the site.

## 0.7.0 (2026-11-19)

- New page `local/visudesk/connect.php`: connects VisuDesk without copying a key. The teacher signs in to Moodle
  as usual (single sign-on too), confirms, and Moodle hands the token for the service VisuDesk to the app on the same
  computer – like the launch of the Moodle mobile app. The app sends a random passport and accepts only a token that
  comes back with it; the token only goes to a loopback address (127.0.0.1, localhost) on the path `/verbunden`.
  Moodle checks as on the security keys page whether the user may create a token for the service. German strings.

## 0.6.0 (2026-11-18)

- New `get_self_enrolment` and `set_self_enrolment`: read and set the self enrolment of a course – on or off and the
  enrolment key. If the course has no self enrolment method yet, it is added with the site defaults (role: student).
  Moodle core only offers students a web service for self enrolment, none for teachers to configure it. Respects
  *Use password policy* and *Require enrolment key* of the self enrolment plugin.

## 0.5.1 (2026-11-13)

- Saving the setting now really gives the role to all current teachers (the callback was not reachable).
- Site admins do not get the role; they have every capability anyway.

## 0.5.0 (2026-11-13)

- New setting *Site role for teachers*: everybody who has the teacher role in a course gets a chosen system role
  (e.g. "Web service user" with `moodle/webservice:createtoken` and `webservice/mcp:use`), because such
  capabilities are checked for the whole site and cannot come from a course role. An event observer assigns the
  role when someone becomes a teacher; a daily task and saving the setting catch everybody else. Off by default.

## 0.4.0 (2026-11-13)

- New `request_course`, `get_course_requests`, `approve_course_request` and `reject_course_request`: Moodle course
  requests through web services. Teachers ask for a course; approvers list all pending requests, approve them with a
  corrected name and another category, or reject them with a reason. Moodle core sends the notifications and enrols
  the requester as teacher, exactly as on `course/pending.php`. Needs *Enable course requests* in the site settings.

## 0.3.0 (2026-11-12)

- New `backup_course`: a Moodle backup (.mbz) of a course without user data (no users, submissions, grades,
  logs). Refuses if the site forces user data into backups. The file is kept for the calling user only (one per
  course) and is downloaded with the token through `webservice/pluginfile.php`.

## 0.2.0 (2026-11-05)

- New `create_section`, `update_section` and `move_section`: add a course section (at the end or at a position),
  change its name, summary or visibility, and move it with its activities. Moodle core offers none of these
  to web services.

## 0.1.0 (2026-11-05)

- First version: `create_resource`, `create_folder`, `update_files`, `set_visibility`.
