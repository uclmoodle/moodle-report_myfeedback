moodle-report_myfeedback
==================

A Moodle Report that shows all userfeedback on one page

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

The report requires jQuery to be enabled, otherwise sorting and searching will not be possible.

The features that have been implemented, tested and set aside for future revisions are listed below:

TODO
Check both Turnitin V1 & V2 assignments show as well

TESTED
Online PDF Feedback files generate the view feedback link for assignments
Show draft submission under submission date where relevant
Report checks for which plugins are installed (and visible?) so report doesn't crash if some plugins are not available
Turnitin grade shows
Turnitin submission GradeMark link
Shows all feedback regardless of whether the student has submitted or not
Only the student's own feedback and grades are shown
Response files for Moodle Assignments are shown
Links to workshop
Quiz high grade displays
Workshop results display
Quiz submission (completion) date set to -
Quiz feedback linked via each attempt
Hidden activities are not shown
Hidden grades (and 'hidden until' grades) as set in the gradebook don't show
Links to rubric and grading forms in assignments and workshops
Multiple response files in Moodle Assignments display
Group assignments submission files show
Sumission date shows for the other group members (as well as the one who submitted)
Hidden activities and non-released grades don't show!
'no submission' is displayed if none exists
Multiple submission files in Moodle Assignments display
The GradeMark link doesn't show when grademark is turned off in TII assignment options
multiple parts display in TII - they show as separate submissions with (Part 1) (Part 2) after their titles
Show 'no submission' in red if a file is expected and it's overdue
mark offline assignments as such
only show feedback when the workshop is closed!
Draft assignment submissions are marked as such
online text submissions
The SQL statement check that the course, course grades and activity is visiable
The SQL checks that the grades for each assessment are visible
Turnitin assignments only show feedback after post date
workshops only show feedback when they are closed
Check that Moodle Assignments that have a workflow enabled don't show grades before they are finalised!
Moodle Assignments that don't have workflow enabled show grades as soon as they are graded
Check Moodle old Assignments and new work - assume they will all be upgraded to new ones as part of the assignment upgrade process

NICE TO HAVE'S
Display an icon showing where rubrics and grading forms are being used
Check for extensions granted to individual students (or groups) so the Moodle assignment and quiz due date is accurate for each student!
