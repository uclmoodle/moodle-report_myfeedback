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
 * Report capabilities
 *
 * @package   report_myfeedback
 * @copyright 2022 UCL
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk> or <jgramp@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'report/myfeedback:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'report/myfeedback:progadmin' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'report/myfeedback:personaltutor' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'legacy' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'report/myfeedback:modtutor' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => [
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
        ],
    ],
    'report/myfeedback:student' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => [
            'student' => CAP_ALLOW,
        ],
    ],
    'report/myfeedback:usage' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => [
            'manager' => CAP_PREVENT,
        ],
    ],
];
