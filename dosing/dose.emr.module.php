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

LoadObjectDependency('_FreeMED.EMRModule');

class Dose extends EMRModule {
	var $MODULE_NAME = "Dose";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Dose';
	var $table_name = 'doserecord';
	var $patient_field = 'dosepatient';
	var $order_by = 'id';

	function Dose ( ) {
		$this->table_definition = array (
			'dosepatient' => SQL__INT_UNSIGNED(0),
			'doseplanid' => SQL__INT_UNSIGNED(0),
			'doseassigneddate' => SQL__DATE,
			'dosegiven' => SQL__INT_UNSIGNED(0),
			'dosegivenstamp' => SQL__TIMESTAMP(14),
			'dosegivenuser' => SQL__INT_UNSIGNED(0),
			'dosestation' => SQL__INT_UNSIGNED(0),
			'dosemedicationtype' => SQL__VARCHAR(200),
			'dosemedicationdispensed' => SQL__INT_UNSIGNED(0),
			'dosepouredunits' => SQL__INT_UNSIGNED(0),
			'dosepreparedunits' => SQL__INT_UNSIGNED(0),
			'doseunits' => SQL__CHAR(10),
			'id' => SQL__SERIAL
		);

		$this->variables = array (
			'dosepatient' => $_REQUEST['patient'],
			'doseplanid',
			'doseassigneddate',
			'dosegivenstamp' => SQL__NOW,
			'dosegivenuser' => $GLOBALS['this_user']->user_number,
			'dosestation',
			'dosemedicationtype',
			'dosemedicationdispensed',
			'dosepouredunits',
			'dosepreparedunits',
			'doseunits'
		);

		$this->summary_vars = array (
			__("Date")    =>	"doseassigneddate",
			__("Given By") =>	"doseplangivenuser:user"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'id DESC';

		$this->EMRModule();
	} // end constructor Dose

	function modform ( ) { }
	function mod ( ) { }

	function addform ( ) {
		$q = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT id FROM doseplan WHERE doseplanpatient='".addslashes($_REQUEST['patient'])."' ORDER BY id DESC LIMIT 1"));
		syslog(LOG_INFO, "dose last doseplan = ".$q['id']);
		$_REQUEST['doseplanid'] = $GLOBALS['doseplanid'] = $q['id'];
		$_REQUEST['doseassigneddate'] = $GLOBALS['doseassigneddate'] = date ('Y-m-d');

		$GLOBALS['display_buffer'] .= html_form::form_table(array (
			__("Hold Status (by patient)") => ( module_function( 'dosehold', 'GetCurrentHoldStatusByPatient', array ( $_REQUEST['patient'] ) ) ? "ACTIVE HOLD" : "No holds" ),
			__("Dosing Plan") => module_function ('doseplan', 'widget', array ( 'doseplanid', $_REQUEST['patient'] ) ),
			__("Dosing Station") => module_function ('dosingstation', 'widget', array ( 'dosestation' ) ),
			__("Assigned Date") => fm_date_entry ( 'doseassigneddate' ),
		));

		include_once(freemed::template_file('ajax.php'));

		$GLOBALS['display_buffer'] .= "
			<script language=\"javascript\">
			function updateDoseAmount ( ) {
				var plan = document.getElementById('doseplanid').value;
				var dt = document.getElementById('doseassigneddate_cal').value;
				x_module_html('doseplan', 'ajax_doseForDate', plan + ',' + dt, updateDoseAmountPopulate);
				x_module_html('".get_class($this)."', 'ajax_alreadyDosed', '".addslashes($_REQUEST['patient'])."' + ',' + dt, alreadyDosedProcess);
			}
			function alreadyDosedProcess ( value ) {
				document.getElementById('dosedalready').innerHTML = value;
			}
			function updateDoseAmountPopulate ( value ) {
				document.getElementById('doseunits').value = value;
				document.getElementById('stageTwo').style.display = 'block';
			}
			</script>
			<div align=\"center\">
			<input type=\"button\" name=\"updateDoseAmount\" value=\"Calculate Dose\" onClick=\"updateDoseAmount(); return true;\" />
			</div>
		";

		$GLOBALS['display_buffer'] .= "
			<div id=\"stageTwo\" style=\"display:none;\">
			<center><div id=\"dosedalready\"></div></center>
			<div align=\"center\">".__("Units")."  ".html_form::text_widget( 'doseunits', array ( 'id' => 'doseunits' ) )."</div>
			";

		$GLOBALS['display_buffer'] .=
			"<center><div style=\"border: 1px solid #000000; width: 300px;\">\n".
			"<div align=\"center\">\n".
			ajax_expand_module_html(
				'dosePlanDiv',
				'doseplan',
				'ajax_display_dose_plan',
				"document.getElementById('doseplanid').value"
			)." Show Dose Plan </div>
			<div align=\"center\" id=\"dosePlanDiv\"></div>
			</div></center>
			<div align=\"center\">
			<input type=\"button\" value=\"Dispense\" id=\"dispenseButton\" onClick=\"dispenseDose(); return true;\" />
			</div>
			<input type=\"hidden\" id=\"id\" name=\"id\" value=\"0\" />
			<center><div id=\"message\" style=\"background-color: #cccccc; font-weight: bold;\"></div></center>
			<script language=\"javascript\">
			function dispenseDose ( ) {
				// Disable button so it can't be pressed again
				document.getElementById('dispenseButton').disabled = true;

				// Get values to submit
				var plan = document.getElementById('doseplanid').value;
				var dt = document.getElementById('doseassigneddate_cal').value;
				var units = document.getElementById('doseunits').value;
				var station = document.getElementById('dosestation').value;
				x_module_html('".get_class($this)."', 'dispenseDose', '".addslashes($_REQUEST['patient'])."' + ',' + dt + ',' + plan + ',' + units + ',' + station, dispenseDoseAction);
			}
			function dispenseDoseAction ( value ) {
				if ( value < 0 ) {
					// Error in dosing, negative value, make sure it's set properly
					document.getElementById('id').value = 0 - value;
					document.getElementById('message').innerHTML = 'A dosing error has occurred, but the machine has reported that it has dispensed something.';
					document.getElementById('message').style.backgroundColor = '#ff0000';
					document.getElementById('stageThree').style.display = 'block';
					return true;
				}
				if ( value == 0 ) {
					// Nothing happened, so we just don't do anything from here.
					document.getElementById('id').value = 0;
					document.getElementById('message').innerHTML = 'A dosing error has occurred, but nothing was dispensed.';
					return true;
				} else {
					// Dose okay, show green, move on.
					document.getElementById('id').value = value;
					document.getElementById('message').innerHTML = 'Dose was dispensed successfully.';
					document.getElementById('message').style.backgroundColor = '#00ff00';
					document.getElementById('stageThree').style.display = 'block';
					return true;
				}
			}
			</script>
			</div> <!-- stageTwo -->

			<center>
			<div id=\"stageThree\" style=\"display:none;\">
			Stage three, stub
			</div> <!-- stageThree -->
			</center>
		";
	} // end method addform

	//function dispenseDose ( $patient, $doseplan, $units, $station ) {
	function dispenseDose ( $blob ) {
		list ( $patient, $date, $doseplan, $units, $station ) = explode ( ',', $blob );
		syslog(LOG_INFO, "dispenseDose| blob = $blob");
		syslog(LOG_INFO, "dispenseDose| called with $patient, $date, $doseplan, $units, $station");
		$pwd = PHYSICAL_LOCATION;
		$cmd = $pwd.'/scripts/dosing_frontend '.escapeshellarg($patient).' '.escapeshellarg($units).' '.escapeshellarg($station);
		syslog(LOG_INFO, "dispenseDose| cmd = $cmd");
		$output = `$cmd`;
		list ( $code, $returned ) = explode ( ':', $output );
		syslog(LOG_INFO, "dispenseDose| returned $code, text = '$returned'");
		$dp = freemed::get_link_rec($doseplan, 'doseplan');
		$GLOBALS['this_user'] = CreateObject('_FreeMED.User');
		$vars = array (
			'dosepatient' => $patient,
			'doseassigneddate' => $date,
			'doseplanid' => $doseplan,
			'doseunits' => $units,
			'dosegivenstamp' => SQL__NOW,
			'dosegivenuser' => $GLOBALS['this_user']->user_number,
			'dosestation' => $station,
			'dosemedicationtype' => $dp['doseplantype'],
			//'dosemedicationdispensed',
			//'dosepouredunits',
			//'dosepreparedunits',
		);
		if ($code == 0) {
			syslog(LOG_INFO, "dispenseDose| successful, inserting");
			// successful, insert
			$q = $GLOBALS['sql']->insert_query(
				$this->table_name,
				$vars
			);
			syslog(LOG_INFO, "dispenseDose| q = $q");
			$res = $GLOBALS['sql']->query ( $q );
			$id = $GLOBALS['sql']->last_record( $res, $this->table_name );
			return $id;
		} elseif ($code < 100) {
			// dose codes under 100 indicate no dose was given
			return 0;
		} else {
			// over 100 means that dose was given, with problems.
			// have to still insert
			$q = $GLOBALS['sql']->insert_query(
				$this->table_name,
				$vars
			);
			$res = $GLOBALS['sql']->query ( $q );
			$id = $GLOBALS['sql']->last_record( $res, $this->table_name );

			// Negative is the actual id during error
			return 0 - abs($id);
		}
	} // end method dispenseDose

	function ajax_alreadyDosed ( $blob ) {
		list ( $patient, $doseassigneddate ) = explode ( ',', $blob );
		$already = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT COUNT(*) AS already FROM doserecord WHERE dosepatient='".addslashes($patient)."' AND doseassigneddate='".addslashes($doseassigneddate)."'"));
		syslog(LOG_INFO, "dosed already = ".$already['already']);
		return ( $already['already'] > 0 ? "<span style=\"color: #ff0000;\">ALREADY DOSED</span>" : "<b>Ready for Dosing</b>" );
	} // end ajax_alreadyDosed

} // end class Dose

register_module("Dose");

?>
