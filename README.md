moodle-report_myfeedback
==================

A Moodle Report that shows all user feedback on one page.

Introduction:
This report displays a searchable and sortable table with the User's grades and feedback across Moodle courses.
When clicking a link from this table, the user will be redirected to that course activity information (in some cases the link will be directly to the submitted files or feedback files.

Install instructions:
1. Copy the myfeedback directory to the report directory of your Moodle instance
2. Visit the notifications page

Access the report:
The report can be accessed via the user profile (Activity Reports > My Feedback report)
Access is controlled by the user context, teacher will be able to see this user's grades for the courses that they are teacher in
Users can only see their own grades
Admin and manager can see all grades for all users (unless permissions prohibit this)

This report is based on the work done by David Bezemer, which in turn is based on work done by Karen Holland, Mei Jin, Jiajia Chen. 
This plugin also uses SQL originating from Richard Havinga, but adds information from additional Moodle activities.
The code for using an external database is authored by Juan Leyva.
The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and Dr John Mitchell <j.mitchell@ucl.ac.uk>.
The authors of this plugin are Jessica Gramp <j.gramp@ucl.ac.uk> and Delvon Forrester <delvon@esparanza.co.uk>.
The tool is also based on outputs from a Jisc project on 'Assessment Careers: enhancing learning pathways 
through assessment' directed by Gwyneth Hughes with input from Tim Neumann who collaborated with UCL for this plugin.

The report requires jQuery to be enabled, otherwise sorting and searching will not be possible.

---
Change log:

2.15 (Build: 2019062400)
Resolutions for:
CONTRIB-7191: Replacing use of deprecated pix_url
CONTRIB-7258: Implementing privacy provider to make plugin GDPR compliant
CONTRIB-7254: Added behat tests to aid regression testing. Note, currently added tests do not provide full coverage

2.14 (Build: 2018052900)
Moved description of report so it only shows to those who have permission to view the tab. 
See in report/myfeedback/usage/index.php //display the description for those who have permission
Removed the white space at the top of index.php

2.13 (Build: 2018041400)
Added a description of each usage report at the top of each report type page.
Resolved missing variable notices in the usage reports.
CONTRIB-7253: Added help icons and text to explain each column in the usage reports.
CONTRIB-6846: Added help icons and text to explain the search heading table columns on the My Students tab.
CONTRIB-7249: Changed assignment string to assessment so it makes sense for all types, including quizzes.
CONTRIB-6831: Changed 'Numerrors:' label for workshops of this type to say 'Number of errors:' instead.

2.12 (Build: 2018031100)
The log table optimisation code incorrectly included the prefix mdl_. This has now been removed.
A further optimisation to the SQL code was implemented by placing brackets around '(auf.userid = $userid OR a.markingworkflow = 0)'

2.11 (Build: 2018013002)
Added usage statistics tab to the report.
Added a usage report permission to allow particular Moodle users access to usage statistics.
Changed H4 tags to H3 to maintain correct heading semantics.
Removed style colours applied directly to heading tags.

2.10 (Build: 2018013000)
Fixed pop-up self-reflective notes not appearing in front of greyed out background on some themes.

2.9 (Build: 2017112800)
Included modal.js so that self-reflective notes and Turnitin feedback can be added in Moodle 3.3+, regardless of theme.
Removed unused datatables.js files.
Closed all the recordsets that can be closed in lib.php.
Moved the few recordset closes already in the code out of the valid() if statement, so they close regardless of a valid result.
Moved some of the css out of the code and into the css file (still more cleaning up of css in other areas is required though).
Added an index on the log table to speed the queries where the date the feedback is viewed is shown.
Changed the date format to d-m-Y so the full year is shown and isn't ambiguous - e.g. 13-5-2017, instead of 13-5-17.

Change log:
2.8.12 (Build: 2017060600)
Fixed spelling errors in en lang file.
Fixed bug where Turnitin Assignment parts with the marks available changed from 100 show the correct total marks available.
Fixed postgres sql errors with missing group by and mismatched types resulting in a database connection error.

2.8.11 (Build: 2017020102)
Resolved bug where some Turnitin Assignments (v1 and v2) were showing before the post date.

2.8.10 (Build: 2017020100)
Resolved bug where some Turnitin Assignments (v1 and v2) were showing before the post date (not fully resolved).

2.8.9 (Build: 2017013102)
Resolved bug in SQL.

2.8.8 (Build: 2017013100)
Resolved bug where some Turnitin Assignments were showing before the post date (not fully resolved).

2.8.7 (Build: 2017012700)
Resolved bug where Moodle database table prefix (mdl) was hard coded into lib.php.


2.8.6 (Build: 2017012500)
Resolved bug where old (overwritten) Moodle Assignment rubric selections display in the report, alongside the latest rubric selections.
Updated language files to highlight that the archiving features are not suitable for production Moodle instances, due to issues with checking permissions on older versions of Moodle.
The workaround is to install the My feedback plugin on every Moodle instance independently.

2.8.5 (Build: 2016100400)
Resolved minor bugs.
Further settings added to admin page.
Resolved timeout on My students tab.
Added search to My students tab.
Changing name of 'progadmin' role no longer removes the Dept Admin tab.
No longer shows 'no submission' on assignment where a submission exists but no grade or feedback given.
Turnitin parts grades showing correctly - used to show one grade for all parts.
Viewed column now showing date feedback first viewed, not last viewed.
Workshop assessment feedback now showing, rather than just the submission feedback.

2.8.4 (Build: 2016100400)
Added strings in code to the lang file.
New Departmental admin dashboard.
New Module tutor dashboard.
New Personal tutor dashboard.
New My students tab for staff to view personal tutees and students on courses.
Checks for extensions granted to individual students (or groups) so the Moodle assignment and quiz due date is accurate for each student.
Now displays an icon showing where rubrics and grading forms are being used in Moodle Assignments.
Allows for archived Moodle versions to be added to the report (warning: switching this on is NOT recommended, as there are some permission issues around using this).
Multiple bug fixes.
Thoroughly tested: a production quality release.

2.0 (Build: 2015033001)
Fixed bug with hardcoded timestamps.

1.9 (Build: 2015031301)
Fixed duplicates appearing in the report in Moodle 2.7+ when a word limit is enabled in online text Assignments.

1.8 (Build: 2015031201)
Fixed duplicates appearing in the report by ensuring the context level is set to 70 so it only returns modules (e.g. assignments or quizzes).
Made the small icon in the table 12x12px via css, rather than inserting style tags into the code (bad I know!).
Removed the function instance_allow_multiple() - as this is only relevant to blocks, not reports.

1.7 (Build: 2015031001)
Added manual grade items that are added directly to the gradebook.
Doesn't show feedback or grades that have been hidden in the gradebook for an individual student.
Fixed Turnitin v2 assignments not displaying the type, submission or feedback links.
Added SQL to retrieve the gradetype (1=numeric) and scaleid in prepration for supporting letter and scale grades.
Known issue: some users have reported double-ups of data in both MySQL and Postgres, however the developer has been unable to replicate this problem so it is still outstanding.

1.6 (Build: 2015030401)
Added alert message so students know the grades are provisional.
Shortened the Assessment type titles.
Display the Moodle short course name - the full name appears on mouse over.

1.5 (Build: 2015022701)
Fixed further Postgres database bugs where types in the unions were mismatched. Only shows items in report when feedback or a grade has been left for the student. Report now indicates offline assignments. Updated 'Assessment name (part name)' table column title. Fixed on time submissions being marked as late. Fixed workshop with zero files not showing submission date (submissions could be online text).

1.4 (Build: 2015022401)
Fixed further Postgres database bugs where types in the unions were mismatched. Replaced empty strings in the SQL with null and -1 for big integers

v1.3 (Build: 2015022301)
Fixed DB bugs and made cross DB compatible - will now work with Postgres and other supported DBs, as well as MySQL

v1.2 (Build: 2015021720)
made cross DB compatible - will now work with Postgres and other supported DBs, as well as MySQL

---
FEATURE LIST
The features that have been implemented, tested and set aside for future revisions are listed below:

TESTED
Check for extensions granted to individual students (or groups) so the Moodle assignment and quiz due date is accurate for each student
Doesn't show feedback or grades that have been hidden in the gradebook for an individual student
Check both Turnitin V1 & V2 assignments show as well
Online PDF Feedback files generate the view feedback link for assignments
Show draft submission under submission date where relevant
Report checks for which plugins are installed (and visible?) so report doesn't crash if some plugins are not available
Turnitin grade shows
Turnitin submission GradeMark link
Shows all feedback regardless of whether the student has submitted anything or not
Only the student's own feedback links and grades are shown
Links to workshop
Quiz high grade displays
Workshop results display
Quiz submission (completion) date set to '-'
Quiz feedback linked via each attempt
Hidden grades (and 'hidden until' grades) as set in the gradebook don't show
Links to rubric and grading forms in assignments and workshops
Multiple response files in Moodle Assignments display
Group assignments submission files show
Submission date shows for the other group members (as well as the one who submitted)
Hidden activities and non-released grades don't show
'no submission' is displayed if none exists
The GradeMark link doesn't show when grademark is turned off in TII assignment options 
- instead it links to the inbox from where comments can be viewed
Multiple Turnitin parts display as separate submissions with (Part 1) (Part 2) after their titles
Show 'no submission' in red if a file is expected and it's overdue
Mrk offline assignments as such
Only shows feedback when the workshop is closed
Draft assignment submissions are marked as such
Online text submissions still show a submission link
The SQL statement checks that the course, course grades and activity is visible
The SQL checks that the grades for each assessment are visible
Turnitin assignments only show feedback after post date
Workshops only show feedback when they are closed
Moodle Assignments that have a workflow enabled don't show grades before they are finalised
Moodle Assignments that don't have workflow enabled show grades as soon as they are graded
Moodle Assignments 2 (assign in the db not assignment) are displayed 
- old assignments need to be upgraded via the automated assignment upgrade process to display in this report
Display an icon showing where rubrics and grading forms are being used
