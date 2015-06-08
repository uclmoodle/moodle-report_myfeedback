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
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk>
 * @credits   Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on 
 * 			  block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga 
 *			  <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 *			  <http://www.twitter.com/jleyvadelgado> configurable reports block.
 *            The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and 
 *            Dr John Mitchell <j.mitchell@ucl.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugins = array(
    'dataTables'     => array('files' => array('dataTables.js')),
    'tooltip'        => array('files' => array('tooltip.js')),
    'footable'       => array('files' => array('FooTable-2/js/footable.all.js', 'FooTable-2/css/footable.core.css', 'FooTable-2/css/footable.standalone.css', 'FooTable-2/css/fonts/footable.eot', 'FooTable-2/css/fonts/footable.ttf', 'FooTable-2/css/fonts/footable.svg', 'FooTable-2/css/fonts/footable.woff', 'FooTable-2/icomoon/footable.json')),
    'tooltip'        => array('files' => array('tooltip.js', 'tooltip.css'))
);