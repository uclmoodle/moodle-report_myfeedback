<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * My Feedback Report.
 *
 * @package   report_myfeedback
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk> or <jgramp@gmail.com>
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @credits   Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on 
 * 			  block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga 
 * 			  <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 * 			  <http://www.twitter.com/jleyvadelgado> configurable reports block.
 *            The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and 
 *            Prof John Mitchell <j.mitchell@ucl.ac.uk>
 *            The tool is also based on outputs from a Jisc project on 'Assessment Careers: enhancing learning pathways 
 *            through assessment' directed by Dr Gwyneth Hughes with input from Tim Neumann who collaborated with UCL for this plugin.
 *            http://bit.ly/IoEAssessmentCareersProject
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$plugin->version = 2019062400;
$plugin->component = 'report_myfeedback';
$plugin->requires = 2017051500;
$plugin->release = '2.15 (Build: 2019062400)';
$plugin->maturity = MATURITY_STABLE;