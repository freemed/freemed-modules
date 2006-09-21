<?php
  // $Id$
  //
  // Authors:
  //      Jeff Buchbinder <jeff@freemedsoftware.org>
  //
  // Copyright (C) 1999-2006 FreeMED Software Foundation
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

LoadObjectDependency('_FreeMED.MaintenanceModule');

class TreatmentPlanApproval extends MaintenanceModule {

	var $MODULE_NAME = "Treatment Plan Approval";
	var $MODULE_VERSION = "0.1";
	var $MODULE_AUTHOR = "jeff@freemedsoftware.org";
	var $MODULE_DESCRIPTION = "Supervisors approve treatment plans using this module.";
	var $MODULE_HIDDEN = true;

	var $MODULE_FILE = __FILE__;

	function TreatmentPlanApproval ( ) {
		// Set menu notify on the sidebar (or wherever the current
		// template decides to hide the notify items)
		$this->_SetHandler('MenuNotifyItems', 'notify');

		// Add this as a main menu handler as well
		$this->_SetHandler('MainMenu', 'MainMenuNotify');

		// Call parent constructor
		$this->MaintenanceModule();
	} // end constructor TreatmentPlanApproval

	function notify ( ) {
		// Try to import the user object
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('FreeMED.User'); }

		// Only supervisors see these
		if (!module_function('treatmentplanmodule', 'check_for_supervisor')) { return false; }

		// Get number of unapproved plans
		$result = $GLOBALS['sql']->query("SELECT COUNT(*) AS count FROM treatmentplan WHERE approvedby='0'");
		$r = $GLOBALS['sql']->fetch_array($result);
		if ($r['count'] < 1) { return false; }

		return array (sprintf(__("There are %d unapproved treatment plan(s)"), $r['count']), 
			"module_loader.php?module=".urlencode(get_class($this)).
			"&action=display");
	} // end method notify

	function MainMenuNotify ( ) {
		// Try to import the user object
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('FreeMED.User'); }

		// Only supervisors see these
		if (!module_function('treatmentplanmodule', 'check_for_supervisor')) {
			// Non supervisors need links for all treatment plans that are denied
			$result = $GLOBALS['sql']->query("SELECT * FROM treatmentplan WHERE approvedby='-1' AND createdby='".addslashes($GLOBALS['this_user']->user_number)."'");
			if (!$GLOBALS['sql']->results($result)) { return false; }	
			while ($r = $GLOBALS['sql']->fetch_array( $result )) {
				if (!isset($patients[$r['patient']])) { $patients[$r['patient']] = CreateObject('_FreeMED.Patient', $r['patient']); }
				$plans .= "<tr>\n".
					"\t<td>". $patients[$r['patient']]->fullName(true) ."</td>\n".
					"\t<td><a href=\"module_loader.php?module=treatmentplanmodule&id=".urlencode($r['id'])."&action=modform&patient=".urlencode($r['patient'])."\">${r['dateofadmission']} / ${r['periodcovered']} days</a></td>\n".
					"</tr>\n";
			}
			return array (
				__("Treatment Plan"),
				"Rejected treatment plans: <br/>\n".
				"<table border=\"0\">\n".
				"<tr><th>Patient</th><th>Plan</th></tr>".
				$plans.
				"</table>"
			); 
		} else {
			// Get number of unapproved plans
			$result = $GLOBALS['sql']->query("SELECT COUNT(*) AS count FROM treatmentplan WHERE approvedby='0'");
			$r = $GLOBALS['sql']->fetch_array($result);
			if ($r['count'] < 1) { return false; }

			return array (
				__("Treatment Plan Approval"),
				"<a href=\"module_loader.php?module=".urlencode(get_class($this)).
				"&action=display\">".
				sprintf(__("There are %d unapproved treatment plans."), $r['count']).
				"</a>"
			); 
		} // end if..else
	} // end method MainMenuNotify

	// Throw back to letters repository if they try to click 'ADD'
	function addform ( ) {
		Header('Location: module_loader.php?module=lettersrepository&action=addform');
		die();
	}

	// For some strange reason, action=display calls method view.
	// Go figure.
	function view ( ) {
		// Get current user object
		global $this_user;
		if (!is_object($this_user)) {
			$this_user = CreateObject('FreeMED.User');
		}

		global $display_buffer, $sql, $action;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }
		if ($_REQUEST['condition']) { unset($condition); }
		// Check for "view" action (actually display)
                if ($_REQUEST['action']=="view") {
			if (!($_REQUEST['submit_action'] == __("Cancel"))) {
                        	$this->display();
				return false;
			}
                }
		$query = "SELECT * FROM treatmentplan ".
			"WHERE approvedby='0' ".
                        freemed::itemlist_conditions(false)." ".
                        ( $condition ? 'AND '.$condition : '' )." ".
                        "ORDER BY createddate";
                $result = $GLOBALS['sql']->query ($query);

                $display_buffer .= freemed_display_itemlist(
                        $result,
                        $this->page_name,
                        array (
                                __("Date")        => "createddate",
				__("Patient")     => "patient",
				" "               => "patient",
				"  "              => "patient"
                        ), // array
                        array (
                                "",
				"",
				""
                        ),
			array (
				"",
				"patient" => "ptlname",
				"patient " => "ptfname",
				"patient  " => "ptid"
			),
                        NULL, NULL,
                        ITEMLIST_VIEW | ITEMLIST_DEL
                );
                $display_buffer .= "\n<p/>\n";
	} // end method view

	function del ( ) {
		module_function('treatmentplanmodule', 'del', array($_REQUEST['id']));
		Header('Location: module_loader.php?module='.$_REQUEST['module']);
		die();
	}

	function display ( ) {
		global $display_buffer, $id;

		if ($_REQUEST['submit_action'] == __("Approve")) {
			$this->mod();
			return false;
		}

		if ($_REQUEST['submit_action'] == __("Deny")) {
			$this->mod();
			return false;
		}

		if ($_REQUEST['submit_action'] == __("Delete")) {
			module_function('treatmentplanmodule', 'del', array($_REQUEST['id']));
			return false;
		}

		$r = freemed::get_link_rec($_REQUEST['id'], 'treatmentplan');
		$this_patient = CreateObject('FreeMED.Patient', $r['patient']);
		$display_buffer .= "
		<form action=\"".$this->page_name."\" method=\"post\" name=\"myform\">
		<input type=\"hidden\" name=\"id\" value=\"".prepare($_REQUEST['id'])."\"/>
		<input type=\"hidden\" name=\"module\" value=\"".prepare($_REQUEST['module'])."\"/>
		<input type=\"hidden\" name=\"action\" value=\"view\"/>
		<input type=\"hidden\" name=\"been_here\" value=\"1\"/>
		".module_function('treatmentplanmodule', 'to_html', array($id, true))."
		<div>
		<i>".__("By clicking on the 'Approve' button below, I agree that I am the user in question and have reviewed this treatment plan.")."</i>
		</div>
		<div align=\"center\">
		<input type=\"submit\" name=\"submit_action\" ".
		"class=\"button\" value=\"".__("Approve")."\"/>
		<input type=\"submit\" name=\"submit_action\" ".
		"class=\"button\" value=\"".__("Deny")."\"/>
		<input type=\"submit\" name=\"submit_action\" ".
		"class=\"button\" value=\"".__("Cancel")."\"/>
		<input type=\"submit\" name=\"submit_action\" ".
		"onClick=\"if (confirm('".addslashes(__("Are you sure that you want to permanently remove this treatment plan?"))."')) { return true; } else { return false; }\" ".
		"class=\"button\" value=\"".__("Delete")."\"/>
		</div>
		</form>
		";
	} // end method display

	function mod ($_id = -1) {
		if ($id > 0) {
			$id = $_id;
		} else {
			$id = $_REQUEST['id'];
		}

		// If we're returning for corrections ...
		if ($_REQUEST['submit_action'] == __("Deny")) {
			$query = $GLOBALS['sql']->update_query(
				'treatmentplan',
				array (
					'approvedby' => '-1'
				),
				array ( 'id' => $id )
			);
			$result = $GLOBALS['sql']->query($query);
			syslog(LOG_INFO, "TreatmentPlanApproval| query = $query, result = $result");
			if ($_id == -1) {
				$GLOBALS['display_buffer'] .= '<br/>'.
					template::link_bar(array(
						__("View Patient Record") =>
						'manage.php?id='.urlencode($rec['patient']),
						__("Return to Treatment Plan Approval") =>
						$this->page_name.'?module='.get_class($this)
					));
			}
			return false;
		} // end dealing with return for corrections
		
		// Insert new table query in unread
		$query = $GLOBALS['sql']->update_query(
			'treatmentplan',
			array (
				'approveddate' => SQL__NOW,
				'approvedby' => $GLOBALS['this_user']->user_number,
				'locked' => $GLOBALS['this_user']->user_number
			),
			array ( 'id' => $id )
		);
		$result = $GLOBALS['sql']->query( $query );

		module_function('annotations', 'createAnnotation', array(
			'treatmentplanmodule',
			$id,
			sprintf(__("Approved by %s"), $GLOBALS['this_user']->getName())
		));

		global $refresh;

		if ($_id == -1) {
			$GLOBALS['display_buffer'] .= '<br/>'.
				template::link_bar(array(
					__("View Patient Record") =>
					'manage.php?id='.urlencode($rec['patient']),
					__("Return to Treatment Plan Approval") =>
					$this->page_name.'?module='.get_class($this)
				));
		}
	} // end method mod

} // end class TreatmentPlanApproval

register_module('TreatmentPlanApproval');

?>
