<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * A4_non_embedded certificate type
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}

$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

// $pdf->addTTFfont('../../lib/tcpdf/fonts/futura.ttf', 'TrueTypeUnicode', '', 32);

// Date formatting - can be customized if necessary
$certificatedate = '';
if ($certrecord->certdate > 0) {
    $certdate = $certrecord->certdate;
} else {
    $certdate = certificate_get_date ($certificate, $course);
}
if ($certificate->printdate > 0) {
    if ($certificate->datefmt == 1) {
        $certificatedate = str_replace(' 0', ' ', strftime('%B %d, %Y', $certdate));
    } else if ($certificate->datefmt == 2) {
        $certificatedate = date('F jS, Y', $certdate);
    } else if ($certificate->datefmt == 3) {
        $certificatedate = str_replace(' 0', '', strftime('%d %B %Y', $certdate));
    } else if ($certificate->datefmt == 4) {
        $certificatedate = strftime('%B %Y', $certdate);
    } else if ($certificate->datefmt == 5) {
        $certificatedate = userdate($certdate, get_string('strftimedate', 'langconfig'));
    }
}

// Grade formatting
$grade = '';
// Print the course grade
$coursegrade = certificate_get_grade($course);
if ($certificate->printgrade == 1 && $certrecord->reportgrade) {
    $reportgrade = $certrecord->reportgrade;
    $grade = $strcoursegrade . ':  ' . $reportgrade;
} else if ($certificate->printgrade > 0) {
    if ($certificate->printgrade == 1) {
        if ($certificate->gradefmt == 1) {
            $grade = $strcoursegrade . ':  ' . $coursegrade->percentage;
        } else if ($certificate->gradefmt == 2) {
            $grade = $strcoursegrade . ':  ' . $coursegrade->points;
        } else if ($certificate->gradefmt == 3) {
            $grade = $strcoursegrade . ':  ' . $coursegrade->letter;
        }
    } else { // Print the mod grade
        $modinfo = certificate_get_grade($course, $certificate->printgrade);
        if ($certrecord->reportgrade) {
            $modgrade = $certrecord->reportgrade;
            $grade = $modinfo->name . ' ' . $strgrade . ': ' . $modgrade;
        } else if ($certificate->printgrade > 1) {
            if ($certificate->gradefmt == 1) {
                $grade = $modinfo->name . ' ' . $strgrade . ': ' . $modinfo->percentage;
            } else if ($certificate->gradefmt == 2) {
                $grade = $modinfo->name . ' ' . $strgrade . ': ' . $modinfo->points;
            } else if ($certificate->gradefmt == 3) {
                $grade = $modinfo->name . ' ' . $strgrade . ': ' . $modinfo->letter;
            }
        }
    }
}
// Print the outcome
$outcome = '';
$outcomeinfo = certificate_get_outcome($course, $certificate->printoutcome);
if ($certificate->printoutcome > 0) {
    $outcome = $outcomeinfo->name . ': ' . $outcomeinfo->grade;
}

// Add expiry date
$expdate = str_replace(' 0', ' ', strftime('%B %d, %Y',(strtotime("+1 years", $certdate))));

// Print the code number
$code = '';
if ($certificate->printnumber) {
    $code = $certrecord->code;
}

// Print the student name
$studentname = '';
$studentname = $certrecord->studentname;
$classname = '';
$classname = $certrecord->classname;
// Print the credit hours
if ($certificate->printhours) {
    $credithours = $strcredithours . ': ' . $certificate->printhours;
} else {
    $credithours = '';
}

$pdf = new TCPDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

// $pdf->SetProtection(array('print'));
$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Define variables
// Landscape
if ($certificate->orientation == 'L') {
    $x = 10;
    $y = 30;
    $sealx = 230;
    $sealy = 150;
    $sigx = 47;
    $sigy = 155;
    $custx = 47;
    $custy = 155;
    $wmarkx = 0;
    $wmarky = 0;
    $wmarkw = 212;
    $wmarkh = 300;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 297;
    $brdrh = 210;
    $codey = 175;
} else { //Portrait
    $x = 10;
    $y = 40;
    $sealx = 150;
    $sealy = 220;
    $sigx = 30;
    $sigy = 230;
    $custx = 30;
    $custy = 230;
    $wmarkx = 0;
    $wmarky = 15;
    $wmarkw = 212;
    $wmarkh = 279;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 210;
    $brdrh = 297;
    $codey = 250;
}

// Add images and lines
certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
certificate_draw_frame_letter($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate,CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x + 15, $y + 5, 'L', 'freeserif', 'B', 10, $certificatedate);
certificate_print_text($pdf, $x + 15, $y + 20, 'L', 'freeserif', 'B', 10, 'Dear');
certificate_print_text($pdf, $x + 25, $y + 20, 'L', 'freeserif', 'B', 10, fullname($USER));
certificate_print_text($pdf, $x + 15, $y + 38, 'L', 'freeserif', '', 10, 'Thank You for completing the Opera House on-line Safety Induction ');
certificate_print_text($pdf, $x + 15, $y + 45, 'L', 'freeserif', '', 10, 'Below you will find your Opera House Site Safety Induction Card.');
certificate_print_text($pdf, $x + 15, $y + 50, 'L', 'freeserif', '', 10, 'Unless you have an electronic Opera House swipe card, you will need to keep a copy of the Induction');
certificate_print_text($pdf, $x + 15, $y + 55, 'L', 'freeserif', '', 10, 'Card below with you whenever you are on site.  You may be asked to produce this card, along with ');
certificate_print_text($pdf, $x + 15, $y + 60, 'L', 'freeserif', '', 10, 'your identification. ');
certificate_print_text($pdf, $x + 15, $y + 65, 'L', 'freeserif', '', 10, '');
certificate_print_text($pdf, $x + 15, $y + 72, 'L', 'freeserif', '', 10, 'A copy of this letter has been emailed to you, Should you misplace the email or this card, you can ');
certificate_print_text($pdf, $x + 15, $y + 77, 'L', 'freeserif', '', 10, 'return to the eLearning portal learning.sydneyoperahouse.com and print a duplicate at any time.');
// cert_printtext($pdf, $x + 15, $y + 84, 'L', 'freeserif', '', 10, 'You will receive not receive any reminders from Harris Scarfe and it is up to you to ensure your card');
// cert_printtext($pdf, $x + 15, $y + 88, 'L', 'freeserif', '', 10, 'remains current. You can obtain a new card at any time before the expiry.');
certificate_print_text($pdf, $x + 15, $y + 5 + 85, 'L', 'freeserif', 'B', 10, 'Important points:');
certificate_print_text($pdf, $x + 15, $y + 5 + 92, 'L', 'freeserif', 'I', 10, '- To report an emergency, dial 2 from an internal phone, or phone 9250-7200 from a mobile');
certificate_print_text($pdf, $x + 15, $y + 5 + 98, 'L', 'freeserif', 'I', 10, '- In an emergency evacuation, follow the directions of your warden or instructions over the loud speaker');
certificate_print_text($pdf, $x + 15, $y + 5 + 104, 'L', 'freeserif', 'I', 10, '- First aid is available in the Health Centre off the Greenroom, or for urgent medical attention, dial 2');
certificate_print_text($pdf, $x + 15, $y + 5 + 109, 'L', 'freeserif', 'I', 10, '- Unless you are in an authorised work zone, keep to green paths and pedestrian crossing in Central Passage.');

// cert_printtext($pdf, $x + 15, $y + 108, 'L', 'freeserif', '', 10, 'When entering any worksite you will be required to sign in using the sites visitor/contractor');
// cert_printtext($pdf, $x + 15, $y + 113, 'L', 'freeserif', '', 10, 'register. On the sign in sheet you must note your name, organization, contact number, and unique ID');
// cert_printtext($pdf, $x + 15, $y + 118, 'L', 'freeserif', '', 10, 'number [found on the bottom of your induction card]. You will be required to undertake a site');
// cert_printtext($pdf, $x + 15, $y + 123, 'L', 'freeserif', '', 10, 'orientation when entering any Harris Scarfe worksite.');
// cert_printtext($pdf, $x + 15, $y + 130, 'L', 'freeserif', '', 10, 'If you have any questions or would like to discuss your obligations further please contact a member of the');
// cert_printtext($pdf, $x + 15, $y + 135, 'L', 'freeserif', '', 10, 'Operations Department who will be happy to assist.');
// cert_printtext($pdf, $x + 15, $y + 118, 'L', 'freeserif', '', 10, 'Kind Regards,');
// cert_printtext($pdf, $x + 15, $y + 126, 'L', 'freeserif', '', 10, 'Firstname Lastname');
// cert_printtext($pdf, $x + 15, $y + 134, 'L', 'freeserif', '', 10, 'Facilities & Maintenance');
// cert_printtext($pdf, $x + 15, $y + 138, 'L', 'freeserif', '', 10, 'Coordinator');
certificate_print_text($pdf, $x + 25, $y + 184, 'L', 'futura', 'B', 12, 'Sydney Opera House Contractor Induction Card');
certificate_print_text($pdf, $x + 30, $y + 198, 'L', 'futura', 'B', 16, fullname($USER));
certificate_print_text($pdf, $x + 30, $y + 207, 'L', 'freeserif', '', 8, 'has completed ' . $course->fullname);
certificate_print_text($pdf, $x + 83, $y + 200, 'L', 'futura', '', 8,'Unique Code:');
certificate_print_text($pdf, $x + 83, $y + 205, 'L', 'futura', 'B', 11, $code);
// cert_printtext($pdf, $x + 25, $y + 212, 'L', 'freesans', 'B', 7, 'I will conduct all work in a safe manner and in accordance');
// cert_printtext($pdf, $x + 25, $y + 215, 'L', 'freesans', 'B', 7, 'with Harris Scarfe policy and procedure:');
certificate_print_text($pdf, $x + 32, $y + 226, 'L', 'freesans', 'I', 6,'Contractor Signature');
certificate_print_text($pdf, $x + 32, $y + 230, 'L', 'freeserif', 'B', 8, "Date Issued : ".userdate($certdate, get_string('strftimedate', 'langconfig')));
//cert_printtext($pdf, $x + 25, $y + 233, 'L', 'freesans', '', 10, 'Authorised by:');
//cert_printtext($pdf, $x + 50, $y + 233, 'L', 'freesans', '', 10, 'Belinda Chadwick');


$i = 0;
if ($certificate->printteacher) {
    $context = context_module::instance($cm->id);
    if ($teachers = get_users_by_capability($context, 'mod/certificate:printteacher', '', $sort = 'u.lastname ASC', '', '', '', '', false)) {
        foreach ($teachers as $teacher) {
            $i++;
	        certificate_print_text($pdf, $sigx, $sigy + ($i * 4), 'L', 'freeserif', '', 12, fullname($teacher));
        }
    }
}

certificate_print_text($pdf, $custx, $custy, 'L', '', '', '', $certificate->customtext);
?>