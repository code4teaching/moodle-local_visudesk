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
 * Language strings.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['advancedgrading'] = 'This assignment uses an advanced grading method (for example a rubric). Grade it in Moodle.';
$string['autoapprove'] = 'Approve course requests automatically';
$string['autoapprove_desc'] = 'If a request follows the naming scheme below and the class already has a course, the course is created at once: hidden, in the category of the newest course of that class (archive categories left out), with the requester as teacher. Otherwise it stays a normal request. The default scheme is “school year class initials subject”, e.g. 2026 11FI2 SebH Objektorientierte Programmierung, short name 2026 11FI2 SebH OOP.';
$string['autoapprovearchive'] = 'Archive categories';
$string['autoapprovearchive_desc'] = 'Categories whose path contains this text (not case-sensitive) are left out when looking for the category of a class. Empty: none.';
$string['autoapprovedbody'] = '{$a->requester} requested a course, and it was created at once.

Name: {$a->fullname}
Short name: {$a->shortname}
Category: {$a->category}

The course is hidden. Please check the name and the category: {$a->url}';
$string['autoapprovedsubject'] = 'Course created automatically: {$a->fullname}';
$string['autoapprovefullname'] = 'Scheme for the full name';
$string['autoapprovefullname_desc'] = 'Regular expression with delimiters. The group (?&lt;class&gt;…) names the class; the courses of the same class decide the category. An optional group (?&lt;key&gt;…) has to be the same in full and short name.';
$string['autoapproveheading'] = 'Course requests';
$string['autoapproveheading_desc'] = 'Teachers request courses from VisuDesk. Courses can be created at once and checked afterwards.';
$string['autoapprovenotify'] = 'Notify about created courses';
$string['autoapprovenotify_desc'] = 'User name of the person who checks automatically created courses. Empty: the main administrator.';
$string['autoapproveshortname'] = 'Scheme for the short name';
$string['autoapproveshortname_desc'] = 'Regular expression with delimiters, with the same groups (?&lt;class&gt;…) and, if used, (?&lt;key&gt;…) as the full name.';
$string['availabilitydisabled'] = 'Restrict access is turned off on this site (Site administration → Advanced features → Enable restricted access), so a release date would have no effect.';
$string['backupfailed'] = 'The backup could not be created.';
$string['backupwithusers'] = 'The site settings force user data into course backups. No backup without user data is possible.';
$string['blindmarking'] = 'This assignment uses anonymous submissions. Grade it in Moodle.';
$string['connectbutton'] = 'Connect to VisuDesk';
$string['connectinvalid'] = 'This link to connect an app is not valid. Start again in the app with “Connect to Moodle”.';
$string['connectnoservice'] = 'The web service {$a} is not available for connecting apps. Please contact the Moodle administration.';
$string['connectnotallowed'] = 'You cannot get a key for the web service {$a} yet. Please contact the Moodle administration.';
$string['connectquestion'] = '<p>VisuDesk on this computer wants to work with your Moodle account <strong>{$a->name}</strong> (web service “{$a->service}”).</p><p>VisuDesk can then do in your courses what you can do in Moodle: upload files, create quizzes and look at submissions. The key goes only to VisuDesk on this computer and is stored in its keychain.</p><p>You can remove the key at any time under Preferences → Security keys.</p>';
$string['connecttitle'] = 'Connect VisuDesk';
$string['errornamepattern'] = 'This needs to be a valid regular expression with delimiters and a group named “class”, for example /^(?<class>\\S+) .+$/u.';
$string['feedbackcommentsdisabled'] = 'Feedback comments are switched off for this assignment.';
$string['feedbackfilesdisabled'] = 'Feedback files are turned off in this assignment. Turn on “Feedback files” under Feedback types in the assignment settings.';
$string['filetoobig'] = 'The file {$a->filename} ({$a->size}) is larger than the upload limit of this course ({$a->max}).';
$string['gradenotinscale'] = 'This grade is not an item of the scale of the assignment.';
$string['gradetoohigh'] = 'The grade is higher than the maximum of {$a} points.';
$string['gradingdisabled'] = 'This grade cannot be changed here: it is locked or overridden in the gradebook, or its marking workflow state is beyond what you may change.';
$string['gradingstarted'] = 'Somebody already has a grade in this assignment, so its grade type cannot change any more.';
$string['invalidcontent'] = 'The content of {$a} is not valid base64.';
$string['invalidfilename'] = 'Invalid or duplicate file name: {$a}';
$string['messageprovider:courseautoapproved'] = 'Course created automatically from a request';
$string['nofiles'] = 'No files given.';
$string['nogradetype'] = 'This assignment is not graded.';
$string['nomarkingworkflow'] = 'This assignment has no marking workflow, so there is nothing to release: grades are visible at once.';
$string['notfilemodule'] = 'This activity is neither a file resource nor a folder.';
$string['notparticipant'] = 'This person is not a participant of the assignment, or you may not grade them (groups or marker allocation).';
$string['onefileonly'] = 'A file resource takes exactly one file.';
$string['planheading'] = 'Lesson planning';
$string['planheading_desc'] = 'VisuDesk keeps each teacher\'s own planning (topic, plan, how far the class got) here, so that it is the same on the Mac and on the iPad. Only the teacher reads and writes it; administrators do not see it in VisuDesk either.';
$string['planretentiondays'] = 'Keep planning for (days)';
$string['planretentiondays_desc'] = 'A planned lesson is removed this many days after its last change. 0 keeps it until the account is deleted.';
$string['pluginname'] = 'VisuDesk';
$string['privacy:metadata:plan'] = 'The teacher\'s own lesson planning, one record per lesson. Only the teacher can read and change it.';
$string['privacy:metadata:plan:lessonkey'] = 'The lesson: date, start time, classes and subject';
$string['privacy:metadata:plan:plandata'] = 'What the teacher wrote: topic, plan, how far the class got, links to course material, homework';
$string['privacy:metadata:plan:timemodified'] = 'When the lesson was last changed';
$string['privacy:metadata:plan:userid'] = 'The teacher who owns the planning';
$string['privacy:metadata:vg:data'] = 'The encrypted content';
$string['privacy:metadata:vg:device'] = 'Random id of the device that sent the change';
$string['privacy:metadata:vg:timecreated'] = 'When it arrived';
$string['privacy:metadata:vg:userid'] = 'The teacher who owns the data';
$string['privacy:metadata:vgentry'] = 'VisuGrade sync: changes to the teacher\'s grade book, encrypted on the teacher\'s devices. Moodle cannot read them.';
$string['privacy:metadata:vgsnap'] = 'VisuGrade sync: snapshot of the teacher\'s whole grade book, encrypted on the teacher\'s devices.';
$string['privacy:path:plan'] = 'Lesson planning';
$string['privacy:path:vg'] = 'VisuGrade sync (encrypted)';
$string['privacy:vg:encrypted'] = 'Encrypted on the teacher\'s devices; only VisuGrade with the teacher\'s key can read it.';
$string['scalenotfound'] = 'There is no scale named “{$a}” on this site or in this course.';
$string['selfenrolcannotadd'] = 'Self enrolment cannot be added to this course.';
$string['selfenroldisabled'] = 'Self enrolment is turned off on this site (Site administration → Plugins → Enrolments → Manage enrol plugins).';
$string['selfenrolkeyrequired'] = 'This site requires an enrolment key for self enrolment.';
$string['siterole'] = 'Site role for teachers';
$string['siterole_desc'] = 'This role is given in the system context to everybody who has the teacher role above in any course, e.g. a role "Web service user" with moodle/webservice:createtoken and webservice/mcp:use. When you change this setting, all current teachers get the role at once; later the role follows every new teacher. Removing a teacher from a course does not take the role away. "None" turns this off.';
$string['task_cleanupplan'] = 'Remove old lesson planning';
$string['task_cleanupvg'] = 'Remove old VisuGrade sync changes';
$string['task_syncteacherrole'] = 'Give the site role to all teachers';
$string['teacherrole'] = 'Teacher role in courses';
$string['teacherrole_desc'] = 'Everybody with this role in at least one course gets the site role below.';
$string['teacherroleheading'] = 'Site role for teachers';
$string['teacherroleheading_desc'] = 'Capabilities such as creating a web service key are checked for the whole site, so a course role like Teacher cannot grant them. VisuDesk can give teachers a site role automatically.';
$string['vgfull'] = 'The VisuGrade sync data of this account has reached its size limit.';
$string['vgnosnapshot'] = 'Changes can only be deleted after a snapshot covers them.';
$string['vgpairgone'] = 'This pairing request has expired. Start again on the new device.';
$string['workflowstatenotallowed'] = 'This marking workflow state is not available to you here ({$a}).';
