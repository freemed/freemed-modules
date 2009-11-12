<?php
  // $Id$
  //
  // Authors:
  //      Jeff Buchbinder <jeff@freemedsoftware.org>
  //      Adam Buchbinder <adam.buchbinder@gmail.com>
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
	var $MODULE_VERSION = "0.2";

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
				var units= document.getElementById('mistakeunits').value;
				// A sanity clause?
				if ( comment.length < 3 ) {
					alert('You must specify a reason for the dose failing.');
					return false;
				}
				// Avoid duplicate clicks
				alert(id + '##' + units + '##' + comment);				
				document.getElementById('mistakeButton').disabled = true;
				// XmlHttpRequest send

				x_module_html('dose', 'ajax_recordMistake', id + '##' + units + '##' + comment, updateRecordMistake);
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
				<td>Lost Units</td>
				<td><input type=\"text\" name=\"mistakeunits\" id=\"mistakeunits\" value=\"0\" /></td>
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

	function setPumpStatus( $blob ) {
		list ( $station, $lot, $bottle, $status ) = explode ( ',', $blob );
		syslog(LOG_INFO, "setPumpStatus | called with parameters [$blob]");
		// make sure we have a number for the station ID
		if (preg_match("/^\d+$/", $station) < 1) {
			syslog(LOG_INFO, "setPumpStatus | invalid station $station selected");
			return false;
		}
		// if we're opening a pump, make sure it hasn't already been closed for the day (also check for this in the UI)
		// set the appropriate flag on the selected pump; return success or otherwise.

		// Possible race condition here. If we have transactions, this should be encapsulated in one.
		$stationq = $GLOBALS['sql']->query("SELECT * FROM dosingstation WHERE id='".addslashes($station)."'");
		$stationr = $GLOBALS['sql']->fetch_array($stationq);

		if ($status == 'open') {
			if ($stationr['dsopen'] == 'open') {
				syslog(LOG_INFO, "setPumpStatus | attempted to open already-open pump $station");
				return false;
			}
			$last = $stationr['dslast_close'];
			$current_date = date("Y-m-d");
			if (strtotime($current_date) <= strtotime($last)) {
				syslog(LOG_INFO, "setPumpStatus | trying to open pump closed on $last, but it's only $current_date");
				return false;
			}
			$GLOBALS['sql']->query("UPDATE dosingstation SET dsopen='open',dsbottle='".addslashes($bottle)."',dslot='".addslashes($lot)."' WHERE id='".addslashes($station)."'");
			return true;
		} else if ($status == 'closed') {
			if ($stationr['dsopen'] == 'closed') {
				syslog(LOG_INFO, "setPumpStatus | attempted to close already-closed pump $station");
				return false;
			}
			$GLOBALS['sql']->query("UPDATE dosingstation SET dsopen='closed', dslast_close='".date("Y-m-d", time())."' WHERE id='".addslashes($station)."'");
			return true;
		} else {
			syslog(LOG_INFO, "setPumpStatus | attempted to set invalid status '$status' on pump $station");
			return false;
		}
	}

	function ajax_closePump ( $station ) {
		// fill in values for the other fields
		return $this->setPumpStatus( $station.',0,0,closed' );
	}

	function checkSplitDosingValid ( $patient, $doseplan, $date, $units ) {
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
				return false;
			}
		} else {
			// If not split dosing at all, return false
			return false;
		}
		return true;
	} // end method checkSplitDosingValid

	//function dispenseDose ( $patient, $doseplan, $units, $station ) {
	function dispenseDose ( $blob ) {
		list ( $patient, $date, $doseplan, $units, $station ) = explode ( ',', $blob );
		syslog(LOG_INFO, "dispenseDose| blob = $blob");
		syslog(LOG_INFO, "dispenseDose| called with $patient, $date, $doseplan, $units, $station");

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

		$dosingstation = freemed::get_link_rec($station, 'dosingstation');
		$dosinglocation = $dosingstation['dsfacility'];
		// refers to lotrecno in lotreg table
		$lotno = $dosingstation['dslot'];
		$botno = $dosingstation['dsbottle'];
		if (empty($dosingstation['id'])) {
			syslog(LOG_INFO, "dispenseDose| Could not find dosing station $station; not dispensing");
			// Return nonspecific error (no dose dispensed)
			return 0;
		}
	
		// Ensure we can only dispense meds from a lot from the facility where the lot is located.
		$lotlocationq = $GLOBALS['sql']->query("SELECT lotrecsite FROM lotreg JOIN lotreceipt ON lotreceipt.lotrecno=lotreg.id WHERE lotreg.id='".addslashes($lotno)."'");
		if ($GLOBALS['sql']->num_rows($lotlocationq) < 1) {
			syslog(LOG_INFO, "dispenseDose| Could not find lot $lotno; not dispensing");
			// Return nonspecific error (no dose dispensed)
			return 0;
		}
		$lotlocationr = $GLOBALS['sql']->fetch_array($lotlocationq);
		$lotlocation = $lotlocationr['lotrecsite'];
		if ($lotlocation != $dosinglocation) {
			syslog(LOG_INFO, "dispenseDose| Location of dosing station different from location of lot; not dispensing");
			// Return nonspecific error (no dose dispensed)
			return 0;
		}

		// Sanity check: can't dispense more units than remain in the bottle.
		$bottle = freemed::get_link_rec($botno, 'lotreceipt');
		$balance = $bottle['lotrecqtyremain'];
		if ($balance < $units) {
			syslog(LOG_INFO, "dispenseDose| Attempting to dispense $units units; only $balance remain in bottle; not dispensing");
			// Return nonspecific error (no dose dispensed)
			return 0;
		}

		$pwd = PHYSICAL_LOCATION;
		$cmd = $pwd.'/scripts/dosing_frontend '.escapeshellarg($patient).' '.escapeshellarg($units).' '.escapeshellarg($station);
		syslog(LOG_INFO, "dispenseDose| cmd = $cmd");
		$output = `$cmd`;
		list ( $code, $returned ) = explode ( ':', $output );
		syslog(LOG_INFO, "dispenseDose| returned $code, text = '$returned'");
		$dp = freemed::get_link_rec($doseplan, 'doseplan');
		$station_rec = freemed::get_link_rec($station, 'dosingstation');
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
			'dosebottleid' => $station_rec['dsbottle'],
			//'dosemedicationdispensed',
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
			// update the "used" quantity
			$usedq = $GLOBALS['sql']->query("UPDATE lotreceipt SET lotrecqtyremain=lotrecqtyremain-'".addslashes($units)."' WHERE id = '".addslashes($botno)."'");
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

			// update the "used" quantity
			$usedq = $GLOBALS['sql']->query("UPDATE lotreceipt SET lotrecbottleused=lotrecbottleused+'".addslashes($units)."' WHERE id = '".addslashes($botno)."'");

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

	function ajax_changeBottle ( $blob ) {
		list ( $station, $lotid, $btlid ) = explode ( ',', $blob );
		$changeq = "UPDATE dosingstation SET dsbottle='".addslashes($btlid)."', dslot='".addslashes($lotid)."' WHERE id='".addslashes($station)."'";
		$GLOBALS['sql']->query($changeq);
		return true;
	} // end ajax_changeBottle

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
		list ( $id, $units, $comment, $btlid ) = explode ( '##', $blob );
		$q = $GLOBALS['sql']->update_query(
			$this->table_name,
			array (
				'dosegiven' => 2,
				'doseunits' => $units,
				'dosecomment' => $comment,
				'dosebottleid' => $btlid
			), array ( 'id' => $id )
		);
		// update the "used" quantity
		$usedq = $GLOBALS['sql']->query("UPDATE lotreceipt SET lotrecbottleused=lotrecbottleused+'".addslashes($units)."' WHERE id = '".addslashes($btlid)."'");
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

	function _update ( ) { 
		$version = freemed::module_version($this->MODULE_NAME);
                // Version 0.2
		//
		//      Removed dose{poured,prepared}units.
		//
		if (! version_check($version, '0.2')) {
			$GLOBALS['sql']->query ( "ALTER TABLE ".$this->table_name." DROP COLUMN dosepreparedunits" );
			$GLOBALS['sql']->query ( "ALTER TABLE ".$this->table_name." DROP COLUMN dosepouredunits" );
		}
	}

} // end class Dose

register_module("Dose");
?>
