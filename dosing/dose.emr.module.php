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
			'dosecomment' => SQL__VARCHAR(250),
			'dosebottleid'=> SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);

		$this->variables = array (
			'dosepatient' => $_REQUEST['patient'],
			'doseplanid',
			'doseassigneddate',
			'dosegiven' => 1,
			'dosegivenstamp' => SQL__NOW,
			'dosegivenuser' => $GLOBALS['this_user']->user_number,
			'dosestation',
			'dosemedicationtype',
			'dosemedicationdispensed',
			'dosepouredunits',
			'dosepreparedunits',
			'dosebottleid',
			'doseunits',
			'dosecomment'
		);

		$this->summary_vars = array (
			__("Date") =>		"doseassigneddate",
			__("When") =>		"dosegivenstamp",
			__("Given By") =>	"doseplangivenuser:user",
			__("Status") =>		"_dosestatus",
			__("Amount") =>		"doseunits"
		);
		$this->summary_query = array (
			"CASE dosegiven WHEN 1 THEN 'dosed' WHEN 2 THEN 'mistake' ELSE 'not dosed' END AS _dosestatus",
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT | SUMMARY_MODIFY;
		$this->summary_order_by = 'id';

		$this->_SetHandler('DosingFunctions', 'addform');
		$this->_SetMetaInformation('DosingFunctionName', __("Dispense Dose"));
		$this->_SetMetaInformation('DosingFunctionDescription', __("Wizard for complete dosing procedure.") );

		$this->EMRModule();
	} // end constructor Dose

	function modform ( ) {
		include_once(freemed::template_file('ajax.php'));
		// This is really only being used for "mistakes"
		$GLOBALS['display_buffer'] .= "
			<script language=\"javascript\">
			function recordMistake ( ) {
				var id = document.getElementById('id').value;
				var comment = document.getElementById('dosecomment').value;
				var poured = document.getElementById('dosepouredunits').value;
				var prepared = document.getElementById('dosepreparedunits').value;
				// A sanity clause?
				if ( comment.length < 3 ) {
					alert('You must specify a reason for the dose failing.');
					return false;
				}
				// Avoid duplicate clicks
				alert(id + '##' + poured + '##' + prepared + '##' + comment);				
				document.getElementById('mistakeButton').disabled = true;
				// XmlHttpRequest send

				x_module_html('dose', 'ajax_recordMistake', id + '##' + poured + '##' + prepared + '##' + comment, updateRecordMistake);
			}
			function updateRecordMistake ( value ) {
				// Kick to another window
				
				window.location = '".( $_REQUEST['return'] == 'manage' ? "manage.php?id=".$_REQUEST['patient'] : "module_loader.php?module=".get_class($this)."&patient=".$_REQUEST['patient'] )."';
			}
			</script>
			<form>
			<input type=\"hidden\" name=\"id\" id=\"id\" value=\"".prepare($_REQUEST['id'])."\" />
			<input type=\"hidden\" name=\"return\" id=\"return\" value=\"".prepare($_REQUEST['return'])."\" />
			<table border=\"0\">
			<tr>
				<td colspan=\"2\" align=\"center\">Dosing Mistake Entry</td>
			</tr>
			<tr>
				<td>Poured Units</td>
				<td><input type=\"text\" name=\"dosepouredunits\" id=\"dosepouredunits\" value=\"0\" /></td>
			</tr>
			<tr>
				<td>Prepared Units</td>
				<td><input type=\"text\" name=\"dosepreparedunits\" id=\"dosepreparedunits\" value=\"0\" /></td>
			</tr>
			<tr>
				<td>Reason / Comment</td>
				<td><input type=\"text\" name=\"dosecomment\" id=\"dosecomment\" /></td>
			</tr>
			<tr>
				<td colspan=\"2\" align=\"center\"><input type=\"button\" id=\"mistakeButton\" value=\"Record Mistake\" onClick=\"alert(123);recordMistake(); return true;\" />
				<input type=\"button\" id=\"noMistakeButton\" value=\"Dosed Correctly\" onClick=\"window.location = '".( $_REQUEST['return'] == 'manage' ? "manage.php?id=".$_REQUEST['patient'] : "module_loader.php?module=".get_class($this)."&patient=".$_REQUEST['patient'] )."'; return true;\" /></td>
			</tr>
			</table>
		";
	}
	function mod ( ) { }
	
	function del ( ) { }

	function addform ( ) {
                ob_start();
                include_once ('dosing_wizard.php');
                $GLOBALS['display_buffer'] .= ob_get_contents();
                ob_end_clean();
		return true;

		//---------------------------------------------- old is below ----------------


//		$q = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT id FROM doseplan WHERE doseplanpatient='".addslashes($_REQUEST['patient'])."' AND doseplanactive=1 ORDER BY id DESC LIMIT 1"));
//		print "SELECT id FROM doseplan WHERE doseplanpatient='".addslashes($_REQUEST['patient'])."' AND doseplanactive=1 AND doseplaneffectivedate <= NOW() ORDER BY doseplanstartdate LIMIT 1";
		$q = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT id FROM doseplan WHERE doseplanpatient='".addslashes($_REQUEST['patient'])."' AND doseplanactive=1 AND doseplanstartdate <= NOW() ORDER BY doseplanstartdate  DESC LIMIT 1"));
		syslog(LOG_INFO, "dose last doseplan = ".$q['id']);
		$_REQUEST['doseplanid'] = $GLOBALS['doseplanid'] = $doseplanid = $q['id'];
		$_REQUEST['doseassigneddate'] = $GLOBALS['doseassigneddate'] = date ('Y-m-d');
/*
		var_dump(module_function( $doseplanid  )."<input type=\"hidden\" id=\"doseplanid\" value=\"".$doseplanid."\" />");
		exit;
*/

		// Figure out dosing station based on idf
		$st = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query( "SELECT id FROM dosingstation WHERE dsurl LIKE '%".addslashes( $_SERVER['REMOTE_ADDR'] )."%' LIMIT 1" ));
		if ($st['id'] > 0) { $_REQUEST['dosestation'] = $GLOBALS['dosestation'] = $st['id']; }

		include_once(freemed::template_file('ajax.php'));

		$hold_status = module_function( 'dosehold', 'GetCurrentHoldStatusByPatient', array ( $_REQUEST['patient'] ) );
		switch ($hold_status) {
			case 1:
			$hold_type = "SOFT HOLD";
			break;

			case 2:
			$GLOBALS['display_buffer'] .= "
			<div align=\"center\">
			<b>There is a hard hold on this patient.</b>
			<br/>
			<a onClick=\"window.location(-1); return true;\" class=\"button\">Go Back</a>
			</div>
			";
			return false;
			break;

			case 0: default:
			$hold_type = "No holds";
			break; // 0 = no hold
		}

		// Load saved session values, if they exist
		global $dosestation; $dosestation = $_SESSION[ 'dosing' ][ 'station'  ];
		global $txtLotNo;    $txtLotNo    = $_SESSION[ 'dosing' ][ 'txtLotNo' ];

		$GLOBALS['display_buffer'] .= 
			"<center><table border=\"0\"><tr><td valign=\"top\">\n".
			html_form::form_table(array (
			__("Hold Status (by patient)") => $hold_text,
			__("Dosing Plan") => module_function( 'doseplan', 'to_text', array ( $doseplanid ) )." <input type=\"hidden\" id=\"doseplanid\" value=\"".$doseplanid."\" />",
			__("Dosing Station") => module_function ('dosingstation', 'widget', array ( 'dosestation' ) ),
			__("Assigned Date") => fm_date_entry ( 'doseassigneddate' ),
			__("Select Lot No") => module_function( 'LotReceipt', 'getLotNos', array ( "txtLotNo" ) ),
			__("Select Bottle No") => "<div id='idBtlNo'> </div>" ,			
		))."<td>".
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
			</td></tr></table>
			<script>
			function getbtlno(){
				x_module_html('lotreceipt', 'getAjxBottleNos', document.getElementById('txtLotNo').value, test);
			}
			function test( value ){
				document.getElementById('idBtlNo').innerHTML = value;
			}
			</script>
			</center>\n";
			
//		$blRet=	$GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT doseplantakehomesched FROM doseplan WHERE id = ".$doseplanid." AND doseplanactive=1 ORDER BY doseplanstartdate LIMIT 1"));
		$blRet=	$GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT doseplantakehomecountgiven FROM doseplan WHERE id = ".$doseplanid." AND doseplanactive=1 ORDER BY doseplanstartdate LIMIT 1"));
	 	
		$GLOBALS['display_buffer'] .= "
			<script language=\"javascript\">

			// Force initial load
			x_module_html('doseplan', 'ajax_display_dose_plan', document.getElementById('doseplanid').value, x_dosePlanDiv_expand_div);

			function updateDoseAmount ( ) {
				var station = document.getElementById('dosestation').value;
				var plan = document.getElementById('doseplanid').value;
				var dt = document.getElementById('doseassigneddate_cal').value;
				if ( station < 1 ) {
					alert('You must select a dosing station to continue.');
					return false;
				}
				x_module_html('doseplan', 'ajax_doseForDate', plan + ',' + dt, updateDoseAmountPopulate);
				x_module_html('".get_class($this)."', 'ajax_alreadyDosed', '".addslashes($_REQUEST['patient'])."' + ',' + dt, alreadyDosedProcess);
			}
			function alreadyDosedProcess ( value ) {
				if (value.indexOf('ALREADY') != -1) {
					// Already dosed, don't allow dispensing.
					document.getElementById('dispenseButton').disabled = true;
				} else {
					document.getElementById('dispenseButton').disabled = false;
				}
				document.getElementById('dosedalready').innerHTML = value;
			}
			function updateDoseAmountPopulate ( value ) {
				if ( value > 0 ) {
					document.getElementById('doseunits').value = value;
					document.getElementById('stageTwo').style.display = 'block';
				} else {
					alert('No dose is scheduled for the date selected.');
					document.getElementById('doseunits').value = value;
					document.getElementById('stageTwo').style.display = 'none';
				}
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

		$GLOBALS['display_buffer'] .= "
			<div align=\"center\">
			<input type=\"button\" value=\"Dispense\" id=\"dispenseButton\" onClick=\"dispenseDose(); return true;\" />
			</div>
			<input type=\"hidden\" id=\"id\" name=\"id\" value=\"0\" />
			<center><div id=\"message\" style=\"background-color: #cccccc; font-weight: bold;\"></div></center>
			<script language=\"javascript\">
			function dispenseDose ( ) {
				// Disable button so it can't be pressed again
				document.getElementById('dispenseButton').disabled = true;

				// Save us some room
				x_dosePlanDiv_contract_div();

				// Get values to submit
				var plan = document.getElementById('doseplanid').value;
				var dt = document.getElementById('doseassigneddate_cal').value;
				var units = document.getElementById('doseunits').value;
				var station = document.getElementById('dosestation').value;
				var btlid = document.getElementById('btlno').value;
				x_module_html('".get_class($this)."', 'dispenseDose', '".addslashes($_REQUEST['patient'])."' + ',' + dt + ',' + plan + ',' + units + ',' + station + ',$txtLotNo,' + btlid, dispenseDoseAction);
			}
			function dispenseDoseAction ( value ) {
				
				if ( value == -99 ) {
					document.getElementById('dispenseButton').disabled = false;
					document.getElementById('message').innerHTML = 'Split dosing has been attempted with a non-valid value.';
					document.getElementById('message').style.backgroundColor = '#ff0000';
					document.getElementById('stageThree').style.display = 'none';
					return true;
				}
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
			<table border=\"0\">
			<tr>
				<td>Bottles Given / Returned</td>
				<td>
					<input type=\"text\" name=\"doseplantakehomecountgiven\" id=\"doseplantakehomecountgiven\" value=\" ".$blRet[0]." \" /> <b>/</b>
					<input type=\"text\" name=\"doseplantakehomecountreturned\" id=\"doseplantakehomecountreturned\" value=\"0\" />
					<input type=\"button\" id=\"bottlesButton\" value=\"Record\" onClick=\"recordBottles();\" />
				</td>
			</tr>
			<tr>
				<td colspan=\"2\" align=\"center\">Dosing Mistake Entry</td>
			</tr>
			<tr>
				<td>Poured Units</td>
				<td><input type=\"text\" name=\"dosepouredunits\" id=\"dosepouredunits\" value=\"0\" /></td>
			</tr>
			<tr>
				<td>Prepared Units</td>
				<td><input type=\"text\" name=\"dosepreparedunits\" id=\"dosepreparedunits\" value=\"0\" /></td>
			</tr>
			<tr>
				<td>Reason / Comment</td>
				<td><input type=\"text\" name=\"dosecomment\" id=\"dosecomment\" /></td>
			</tr>
			<tr>
				<td><label for=\"pouragain\">Pour Again?</label></td>
				<td><input type=\"checkbox\" name=\"pouragain\" id=\"pouragain\" value=\"1\" /></td>
			</tr>
			<tr>
				<td colspan=\"2\" align=\"center\"><input type=\"button\" id=\"mistakeButton\" value=\"Record Mistake\" onClick=\"alert(123);recordMistake(); return true;\" />
				<input type=\"button\" id=\"doseAnotherButton\" value=\"Another Dose\" onClick=\"doseAnother(); return true;\" />
				<input type=\"button\" id=\"noMistakeButton\" value=\"Dosed Correctly\" onClick=\"window.location = '".( $_REQUEST['return'] == 'manage' ? "manage.php?id=".$_REQUEST['patient'] : "module_loader.php?module=".get_class($this)."&patient=".$_REQUEST['patient'] )."'; return true;\" /></td>
			</tr>
			</table>
			</div> <!-- stageThree -->
			</center>
			<script language=\"javascript\">
			function doseAnother ( ) {
				document.getElementById('dispenseButton').disabled = false;
				document.getElementById('stageTwo').style.display = 'none';
				document.getElementById('stageThree').style.display = 'none';
				document.getElementById('message').innerHTML = '';
				document.getElementById('message').style.backgroundColor = '#cccccc';
				document.getElementById('id').value = 0;

				// Reload the schedule, forced
				x_module_html('doseplan', 'ajax_display_dose_plan', document.getElementById('doseplanid').value, x_dosePlanDiv_expand_div);
			}
			function recordBottles ( ) {
				var doseplan = document.getElementById('doseplanid').value;
				var given = document.getElementById('doseplantakehomecountgiven').value;
				var returned = document.getElementById('doseplantakehomecountreturned').value;
				document.getElementById('bottlesButton').disabled = true;
				x_module_html('dose', 'ajax_recordBottles', doseplan + '##' + given + '##' + returned , updateRecordBottles);
			}
			function updateRecordBottles ( value ) {
				if ( value == 1 ) {
					alert('Updated bottle counts.');
				} else {
					alert('Failed to update bottle counts.');
				}
				document.getElementById('bottlesButton').disabled = false;
			}
			function recordMistake ( ) {
				var id = document.getElementById('id').value;
				var comment = document.getElementById('dosecomment').value;
				var poured = document.getElementById('dosepouredunits').value;
				var prepared = document.getElementById('dosepreparedunits').value;
				var btlid = document.getElementById('btlno').value;

				// A sanity clause?
				if ( comment.length < 3 ) {
					alert('You must specify a reason for the dose failing.');
					return false;
				}

				// Avoid duplicate clicks
				document.getElementById('mistakeButton').disabled = true;
				// XmlHttpRequest send
				x_module_html('dose', 'ajax_recordMistake', id + '##' + poured + '##' + prepared + '##' + comment + '##' + btlid, updateRecordMistake);
			}
			
			function updateRecordMistake ( value ) {
				var pouragain = document.getElementById('pouragain').checked;
				
				if ( pouragain ) {
					// Enable dispense button, etc
					document.getElementById('dispenseButton').disabled = false;
					document.getElementById('dosedalready').innerHTML = 'Mistake Dosing';
					document.getElementById('stageThree').style.display = 'none';
					document.getElementById('message').innerHTML = '';
					document.getElementById('message').style.backgroundColor = '#cccccc';
					document.getElementById('mistakeButton').disabled = false;					
				} else {
					// Kick to another window
					//window.location = '".( $_REQUEST['return'] == 'manage' ? "manage.php?id=".$_REQUEST['patient'] : "module_loader.php?module=".get_class($this)."&patient=".$_REQUEST['patient'] )."';
				}
			}
			</script>
		";
	} // end method addform

	function view ( ) {
		global $sql; global $display_buffer; global $patient;
		$display_buffer .= freemed_display_itemlist (
			$sql->query("SELECT *,CASE dosegiven WHEN 1 THEN 'dosed' WHEN 2 THEN 'mistake' ELSE 'not dosed' END AS _dosestatus FROM ".$this->table_name." ".
				"WHERE ".$this->patient_field."='".addslashes($patient)."' ".
				freemed::itemlist_conditions(false)." ".
				"ORDER BY ".$this->order_by),
			$this->page_name,
			array(
				__("Date") =>		"doseassigneddate",
				__("When") =>		"dosegivenstamp",
				__("Given By") =>	"doseplangivenuser",
				__("Status") =>		"_dosestatus",
				__("Amount") =>		"doseunits"
			), NULL, NULL, NULL, NULL,
                        ITEMLIST_VIEW
		);
	} // end method view

	//function dispenseDose ( $patient, $doseplan, $units, $station, $txtLotNo, $btlid ) {
	function dispenseDose ( $blob ) {
		list ( $patient, $date, $doseplan, $units, $station, $txtLotNo, $btlid ) = explode ( ',', $blob );
		syslog(LOG_INFO, "dispenseDose| blob = $blob");
		syslog(LOG_INFO, "dispenseDose| called with $patient, $date, $doseplan, $units, $station");

		// Store session data
		$_SESSION[ 'dosing' ][ 'station'  ] = $station;
		$_SESSION[ 'dosing' ][ 'bottleId' ] = $btlid;
		$_SESSION[ 'dosing' ][ 'txtLotNo' ] = $txtLotNo;

		// Check for invalid split dosing amounts
		$plan = freemed::get_link_rec($doseplan, 'doseplan');
		if ( $plan['doseplansplit'] == 1 ) {
			// get value for past dose for today.
			$lastq = $GLOBALS['sql']->query("SELECT * FROM doserecord WHERE dosegiven=1 AND doseassigneddate='".addslashes($date)."' AND dosepatient='".addslashes($patient)."'");
			$lastdosetotal = 0;
			while ($lastr = $GLOBALS['sql']->fetch_array($lastq)) {
				syslog(LOG_INFO, "dispenseDose | adding dose of ".$lastr['doseunits']);
				$lastdosetotal += $lastr['doseunits'];
			}
			if ( ($lastdosetotal + $units) > $plan['doseplandose'] ) {
				// Return specific error
				return -99;
			}
		}
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
			'dosegiven' => 1,
			'dosegivenstamp' => SQL__NOW,
			'dosegivenuser' => $GLOBALS['this_user']->user_number,
			'dosestation' => $station,
			'dosemedicationtype' => $dp['doseplantype'],
			'dosebottleid' => $btlid,
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
			$x = $this->sendCommandToPump( $station, str_pad($units, 3 , "0",STR_PAD_LEFT) );
			// print a label ...
			$patientObject = CreateObject('_FreeMED.Patient', $patient);
			$y = $this->printLabel( $station, array(
				'patient' => $patientObject->to_text()
			));
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
		$already = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT COUNT(*) AS already, dp.doseplansplit AS issplit FROM doserecord dr LEFT OUTER JOIN doseplan dp ON dp.id=dr.doseplanid WHERE dr.dosepatient='".addslashes($patient)."' AND dr.doseassigneddate='".addslashes($doseassigneddate)."' AND dr.dosegiven <> 2 GROUP BY dr.dosepatient"));
		syslog(LOG_INFO, "dosed already = ".$already['already'].", issplit = ".$already['issplit']);
		// If we're dealing with splits ...
		if ($already['issplit']+0 > 0) {
			switch ( $already['already'] ) {
				case 1:
				return "First dose already given (split dosing)";
				break;

				case 2:
				return "<span style=\"color: #ff0000;\">ALREADY DOSED</span>";
				break;

				case 0:
				return "<b>Ready for Dosing</b>";
				break;

				default:
				return "ERROR";
				break;
			}
		}
		return ( $already['already'] > 0 ? "<span style=\"color: #ff0000;\">ALREADY DOSED</span>" : "<b>Ready for Dosing</b>" );
	} // end ajax_alreadyDosed

	function printLabel( $pump, $args ) {
		extract( $args );
		$output = $this->sshWrapper( $pump, "/home/freemed/generate_label.pl -p ".escapeshellarg($patient)." -P ".escapeshellarg($provider)." -d ".escapeshellarg($dosage)." -e ".escapeshellarg($expires) );
		return $output;
	} // end printLabel

	function sendCommandToPump( $pump, $command ) {
		$output = $this->_sshWrapper( $pump, "/home/freemed/remote_test.pl ".$command );
		return $output;
	} // end sendCommandToPump

	function _sshWrapper ( $pump, $command ) {
		$station = freemed::get_link_rec( $pump, 'dosingstation' );

		// Write identity
		$identityFile = tempnam('/tmp', 'ssh-identity');
		file_put_contents( $identityFile, $station['sshkey'] );

		$cmd = "ssh -i " . escapeshellarg( $identityFile ) . " -o StrictHostKeyChecking=no -u freemed " . escapeshellarg( $station['dsurl']  ) . " " . $command;
		syslog( LOG_DEBUG, "_sshWrapper( $pump )[in] : ${cmd}" );
		$output = shell_exec( $cmd );
		syslog( LOG_DEBUG, "_sshWrapper( $pump )[out] : ${output}" );

		unlink( $identityFile );

		return $output;
	} // end _sshWrapper

	function ajax_recordBottles ( $blob ) {
		list ( $id, $given, $returned ) = explode ( '##', $blob );
		$q = $GLOBALS['sql']->update_query(
			'doseplan',
			array (
				'doseplantakehomecountgiven' => $given,
				'doseplantakehomecountreturned' => $returned
			), array ( 'id' => $id )
		);
		$res = $GLOBALS['sql']->query( $q );
		return $res ? '1' : '';
	} // end ajax_recordBottles

	function clearPump ( $dosingstation ) {
		$output = $this->sendCommandToPump( $dosingstation, 'v40002' );
		return true;
	}

	function primePump ( $dosingstation ) {
		$output = $this->sendCommandToPump( $dosingstation, 'v999' );
		return true;
	}

	function closePumpClear ( $dosingstation ) {
		$output = $this->sendCommandToPump( $dosingstation, 'v9992' );
		return true;
	}

	function closePumpFlush ( $dosingstation ) {
		$output = $this->sendCommandToPump( $dosingstation, 'v9993' );
		return true;
	}

	function ajax_recordMistake ( $blob ) {
		//print $blob;
		list ( $id, $poured, $prepared, $comment, $btlid ) = explode ( '##', $blob );
		$q = $GLOBALS['sql']->update_query(
			$this->table_name,
			array (
				'dosegiven' => 2,
				'dosepreparedunits' => $prepared,
				'dosepouredunits' => $poured,
				'dosecomment' => $comment,
				'dosebottleid' => $btlid
			), array ( 'id' => $id )
		);
		return $GLOBALS['sql']->query( $q );
	} // end ajax_recordMistake
	
	function ajax_bottle_qty($btlno){
		$q = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT sum(doseunits) QTY FROM ".$this->table_name." WHERE dosebottleid = $btlno"));
		return $q['QTY'];
		
	}

	// Method: SaveSession
	//
	//	Save dosing information into the session.
	//
	// Parameters:
	//
	//	$hash - CSV of dosing station, lot no, bottle idA
	//
	// Returns:
	//
	//	Boolean, success.
	//
	function SaveSession ( $hash ) {
		list ( $_SESSION['dosing']['dosingstation'], $_SESSION['dosing']['txtLotNo'], $_SESSION['dosing']['btlno'] ) = explode( ',', $hash );
		return true;
	}

} // end class Dose

register_module("Dose");
?>
