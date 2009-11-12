<?php
  // $Id$
  //
  // Authors:
  //      Jeff Buchbinder <jeff@freemedsoftware.org>
  //
  // FreeMED Electronic Medical Record and Practice Management System
  // Copyright (C) 1999-2007 FreeMED Software Foundation
  //
  // This program is free software; you can redistribute it and/or modify
  // it under the terms of the GNU General Public License as published by
  // the Free Software Foundation; either version 2 of the License, or
  // (at your option) any later version.
  //
  // This program is distributed in the hope that it will be useful,
  // but WITHOUT ANY WARRANTY; without even the implied warranty of
  // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  // GNU General Public License for more details.
  //
  // You should have received a copy of the GNU General Public License
  // along with this program; if not, write to the Free Software
  // Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

$page_name = "dosing_functions.php";
include ("lib/freemed.php");

//----- Login/authenticate
freemed::connect ();

//----- Create user object
$this_user = CreateObject('FreeMED.User');

//----- Set page title
$page_title = __("Dosing Functions");

//----- Add page to stack
page_push();

//----- Check for "current_patient" in $_SESSION
if ($_SESSION['current_patient'] != 0) {
	$patient = $_SESSION['current_patient'];
}

//----- Check ACLs
if (!freemed::acl('bill', 'menu')) {
	trigger_error(__("You don't have access to do that."), E_USER_ERROR);
}

$user_to_log=$_SESSION['authdata']['user'];
if((LOGLEVEL<1)||LOG_HIPAA){syslog(LOG_INFO,"dosingfunctions.php|user $user_to_log accesses patient $patient");}	

$patient_information = "<b>".__("NO PATIENT SPECIFIED")."</b>\n";
if ($patient>0) {
	$this_patient = CreateObject('FreeMED.Patient', $patient);
	$patient_information = freemed::patient_box ($this_patient);
} // if there is a patient

// This section is the start of "Dosing v1.0". We are using "handlers"
// to assign different types of dosing, and from there, we will 

LoadObjectDependency('PHP.module');

if ( !$_REQUEST['type'] ) { $_REQUEST['action'] = ''; } // hack hack hack :(
switch ($_REQUEST['action']) {
	case 'type':
	// Execute handler
	$module_handlers = freemed::module_handler('DosingFunctions');
	GettextXML::textdomain(strtolower($_REQUEST['type']));
	$display_buffer .= module_function($_REQUEST['type'], $module_handlers[strtolower($_REQUEST['type'])]);

	// Display closing information for return to menu
	$display_buffer .= "
	<p/>
	<div align=\"center\">
		<a href=\"dosing_functions.php\" class=\"button\"
		>".__("Return to")." ".
		__("Dosing Functions")."</a>
	</div>
	";
	break; // end case 'type'

	default:
	//----- Determine handlers for dosing types
	$type_handlers = freemed::module_handler('DosingFunctions');
	if (!is_array($type_handlers)) {
		$display_buffer .= __("Your FreeMED installation has no dosing handlers defined. This should not happen.")."<br/>\n";
		template_display();
		die();
	} else {
		$display_buffer .= 
		"<div class=\"section\">".__("Dosing System")."</div><br/> ".
		"<p/>\n";
	}

	foreach ($type_handlers AS $class => $handler) {
		// Load proper GettextXML definitions for this class
		GettextXML::textdomain(strtolower($class));
		
		// Get title from meta information
		$title = freemed::module_get_meta($class, 'DosingFunctionName');
		$desc = freemed::module_get_meta($class, 'DosingFunctionDescription');
		// Add to the list
		$types[__($title)] = $class;
		$description[__($title)] = __($desc);

		if ($icon = freemed::module_get_value($class, 'ICON')) {
			$icons[__($title)] = $icon;
		} else {
			unset($icons[__($title)]);
		}
	}

	// Sort & unique values
	$types = array_unique($types);
	ksort($types);

	// Display
	$display_buffer .= "<table align=\"center\" border=\"0\" ".
		"cellspacing=\"0\" cellpadding=\"3\">\n".
		"<th class=\"reverse\">\n".
		"<td class=\"reverse\">".__("Action")."</td>\n".
		"<td class=\"reverse\">".__("Description")."</td>\n".
		"</th>\n";
	foreach ($types AS $name => $link) {
		$display_buffer .= "<tr><td valign=\"top\">".
			( isset($icons[$name]) ?
			"<a href=\"dosing_functions.php?".
			"action=type&type=".urlencode($link)."\"".
			"><img src=\"".$icons[$name]."\" border=\"0\" ".
			"alt=\"\"/></a>" :
			"&nbsp;" ).
			"</td><td valign=\"top\">".
			"<a href=\"dosing_functions.php?".
			"action=type&type=".urlencode($link)."\"".
			">".$name."</a></td>\n".
			"<td valign=\"top\">".$description[$name].
			"</td></tr>\n";
	}
	$display_buffer .= "</table>\n";
	
	break; // end of default action
} // end of master action switch

//----- Finish template display
template_display ();

?>
