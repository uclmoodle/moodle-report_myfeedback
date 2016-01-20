moodle-report_myfeedback
==================

A Moodle Report that shows all user feedback on one page

Introduction: This report displays a searchable and sortable table with the User's grades and feedback across Moodle courses. When clicking a link from this table, the user will be redirected to that course activity information (in some cases the link will be directly to the submitted files or feedback files.

Install instructions: 1. Copy the myfeedback directory to the report directory of your Moodle instance 2. Visit the site admin notifications page. 3. Verify that the plugin is listed to be upgraded and then select 'Upgrade the database'. 	4. In the settings page of My Feedback report all fields should be blank, unless you want to define an alternative database to use for the queries (e.g. a replicated database to avoid any performance issues) and then save changes.

Access the report: The report can be accessed via the user profile (Activity Reports > My Feedback report) Access is controlled by the user context, teacher will be able to see this user's grades for the courses that they are teacher in Users can only see their own grades Admin and manager can see all grades for all users (unless permissions prohibit this)

This report is based on the work done by David Bezemer, which in turn is based on work done by Karen Holland, Mei Jin, Jiajia Chen. This plugin also uses SQL originating from Richard Havinga, but adds information from additional Moodle activities. The code for using an external database is authored by Juan Leyva. The idea for this reporting tool originated with Dr Jason Davies j.p.davies@ucl.ac.uk and Dr John Mitchell j.mitchell@ucl.ac.uk The tool is also based on outputs from a Jisc project on 'Assessment Careers: enhancing learning pathways through assessment' directed by Gwyneth Hughes with input from Tim Neumann who collaborated with UCL for this plugin.

The report requires jQuery to be enabled, otherwise sorting and searching will not be possible.

Change log:

2.7 (Build: 2016011900) 
Fixes
------
Fixed the issue further due to other issues popped up with some quiz submissions shown as late when they aren't
Fixed an issue with the self-reflective notes and turnitin feedback where an iteminstance had to be added to the DB table as turnitin uses the same grade item for multiple parts but used the subparts id for the different instances.
Fixed the decimal places to return the amount of decimal places as is set in Moodle.
Fixed the a further issue with max grade not updated when this is modified affter students are already graded.
Fixed the issue where tutors/admin could  edit or add to the 'related' user's self-reflective notes 
Fixed the hardcoded link in the mytutees tab which was causing an error.
Fixed the mytutee page where users in inactive courses are showing in the tutee list and inactive courses showing in the course shortname list.
Fixed the display of self-reflective notes to only the students themselves, their personal tutor and program admin but not module tutor (unless you are also their personal tutor).
Fixed any possibility of Cross site scripting (XSS) when adding turnitin feedback and self-reflective notes.
Fixed the assignment and workshop feedback files which were showing an icon if the grade item has a feedbackfile for any user and not if the specific individual has a feedback file.
Fixed the decimal places to only show decimals if the grade has a fraction.
Fixed the issue where module tutors were seeing self-reflective notes which are only for the students personal tutors and programme admin.
Fixed turnitintool and turnitintooltwo feedback to take students and tutors to the individual feedback page.
Fixed the turnitin feedback to not show a link if no grade is yet added.

New Features
-------------
Added the ellipsis(...) styling for the long shortname, also for the mytutees course shortnames and ensure it doesn't wrap to two or more lines.
Added a background colour if the non-moolde feedback is student-added.
Added the user's name to the Feedback heading to say My feedback report for xxx.
Tutors and admin were now allowed to see their own my feedback report if they are a student on any course
Enlarged the size of the My students tab table so that relationship column in particular can show more module shortnames and also add a media query for smaller screens to default to the original size of max 420px.
Modified the quiz attempts to show the review page and for multiple attempts, show the feedback text with a link to show the last of the number of attempts.
Added a tooltip to the reflective notes header explaining to students that personal tutor and programme admin will be able to see their self-reflective notes.


2.6 (Build: 2015120302) Fixed excel export. The ColReorder now works on the feedback table. Reset table now available.
Changed overview table blank column heading to Available grade. Comment out the bar graph and range and put highest grade.
New function to get the available grade if it is a scale and not manual.
New function to get the available grade if it is a letter.
New function to get the available grade if manual and scale.
Manual item shows when display type is default.
The turnitin link now works. Added module id. Removed the header ‘Feedback comments’ as the Tab already says this Renamed ‘Submission / Full feedback’ label to ‘Submission / Feedback’ on overview tab, but on page 2 – left is as ‘Full feedback’ Added ‘Assessment’ label to Feedback comments tab Added ‘Submission date’ coln to Feedback comments tab MyFeedback report now sorts by Submission date (latest first) by default Added bar graphs to Overview tab Have only view feedback or view submission if there is only one or the other but feedback will supercede. Added Submission date to the feedback table
Added a different destroy button for the feedback table as it was clashing with the table on the overview tab. Fixed table not resetting on second click without refresh Removed the savestate from both tables.
Look back at the destroy table so it destroys the other datatables plugins as well.
Colvis label - set to overflow hidden.
Export table unicode/utf8 - symbols now show properly.

2.5 (Build: 2015090401) Only works on Moodle v2.8+ due to database schema changes (potentially v2.7, but this hasn't been tested). Shows scale grades, exports to excel, print view, filter each column. Uses dataTables rather than footable for table sorting and mobile responsiveness. Feedback comments tabs shows general feedback, rubrics and marking guides/grading forms for Moodle and Turnitin assignments. Feedback comments tab shows whether student has viewed their feedback.

2.4 (Build: 2015090400) Removed the dataTables plugin from the plugin.php file and the dataTables.js file from the jquery folder, as it is now replaced with footables. Fixed turnitintooltwo so these assignments now point to the correct feedback and submission links - they used to point to turnitintool (V1).

2.3 (Build: 2015060803) Responsive table layout and tooltips for mobile access - both phone and tablet. Uses footable (http://fooplugins.com/footable-demos/) to dymanically shrink the table to show only core information. The rest is displayed underneath with a drop down toggle to view it. Features expand all and collapse all toggles, search, pagination and course filter. Uses tooltips (http://osvaldas.info/elegant-css-and-jquery-tooltip-responsive-mobile-friendly) that display when hovered-over on desktops and when clicked-on on mobile devices. Moved some hard-coded text to the languages file to ensure cross-language compatibility.

2.2 (Build: 2015051001) Separated the data from the interface code. get_data() returns the resultset, get_content() calls get_data(). Enables re-use of the query and data elsewhere in moodle, without rendering the data in the way that the report does. Change contributed by Mike Grant.

2.1 (Build: 2015033101) Fixed bug with hidden grades still showing.

2.0 (Build: 2015033001) Fixed bug with hardcoded timestamps.

1.9 (Build: 2015031301) Fixed duplicates appearing in the report in Moodle 2.7+ when a word limit is enabled in online text Assignments.

1.8 (Build: 2015031201) Fixed duplicates appearing in the report by ensuring the context level is set to 70 so it only returns modules (e.g. assignments or quizzes). Made the small icon in the table 12x12px via css, rather than inserting style tags into the code (bad I know!). Removed the function instance_allow_multiple() - as this is only relevant to blocks, not reports.

1.7 (Build: 2015031001) Added manual grade items that are added directly to the gradebook. Doesn't show feedback or grades that have been hidden in the gradebook for an individual student. Fixed Turnitin v2 assignments not displaying the type, submission or feedback links. Added SQL to retrieve the gradetype (1=numeric) and scaleid in prepration for supporting letter and scale grades. Known issue: some users have reported double-ups of data in both MySQL and Postgres, however the developer has been unable to replicate this problem so it is still outstanding.

1.6 (Build: 2015030401) Added alert message so students know the grades are provisional. Shortened the Assessment type titles. Display the Moodle short course name - the full name appears on mouse over.

1.5 (Build: 2015022701) Fixed further Postgres database bugs where types in the unions were mismatched. Only shows items in report when feedback or a grade has been left for the student. Report now indicates offline assignments. Updated 'Assessment name (part name)' table column title. Fixed on time submissions being marked as late. Fixed workshop with zero files not showing submission date (submissions could be online text).

1.4 (Build: 2015022401) Fixed further Postgres database bugs where types in the unions were mismatched. Replaced empty strings in the SQL with null and -1 for big integers

v1.3 (Build: 2015022301) Fixed DB bugs and made cross DB compatible - will now work with Postgres and other supported DBs, as well as MySQL

v1.2 (Build: 2015021720) made cross DB compatible - will now work with Postgres and other supported DBs, as well as MySQL

FEATURE LIST The features that have been implemented, tested and set aside for future revisions are listed below:

TODO Check for extensions granted to individual students (or groups) so the Moodle assignment and quiz due date is accurate for each student

TESTED Doesn't show feedback or grades that have been hidden in the gradebook for an individual student Check both Turnitin V1 & V2 assignments show as well Online PDF Feedback files generate the view feedback link for assignments Show draft submission under submission date where relevant Report checks for which plugins are installed (and visible?) so report doesn't crash if some plugins are not available Turnitin grade shows Turnitin submission GradeMark link Shows all feedback regardless of whether the student has submitted anything or not Only the student's own feedback links and grades are shown Links to workshop Quiz high grade displays Workshop results display Quiz submission (completion) date set to '-' Quiz feedback linked via each attempt Hidden grades (and 'hidden until' grades) as set in the gradebook don't show Links to rubric and grading forms in assignments and workshops Multiple response files in Moodle Assignments display Group assignments submission files show Submission date shows for the other group members (as well as the one who submitted) Hidden activities and non-released grades don't show 'no submission' is displayed if none exists The GradeMark link doesn't show when grademark is turned off in TII assignment options

instead it links to the inbox from where comments can be viewed Multiple Turnitin parts display as separate submissions with (Part 1) (Part 2) after their titles Show 'no submission' in red if a file is expected and it's overdue Mrk offline assignments as such Only shows feedback when the workshop is closed Draft assignment submissions are marked as such Online text submissions still show a submission link The SQL statement checks that the course, course grades and activity is visible The SQL checks that the grades for each assessment are visible Turnitin assignments only show feedback after post date Workshops only show feedback when they are closed Moodle Assignments that have a workflow enabled don't show grades before they are finalised Moodle Assignments that don't have workflow enabled show grades as soon as they are graded Moodle Assignments 2 (assign in the db not assignment) aare displayed
old assignments need to be upgraded via the automated assignment upgrade process to display in this report
NICE TO HAVE'S Display an icon showing where rubrics and grading forms are being used