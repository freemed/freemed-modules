<?php
  // $Id$
  //
  // Authors:
  //      Hardik
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

class DispensingRep extends EMRModule {
	var $MODULE_NAME = "DispensingRep";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Daily Dispensing Report';
	var $table_name = 'doserecord';		// need to change
	var $order_by = 'id';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function DispensingRep ( ) {
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }		

		// Set associations
		$this->EMRModule();
	} // end constructor Lot
	

	function view ( ) {
		global $sql; global $display_buffer; global $patient;
		include_once(freemed::template_file('ajax.php'));
		$host  = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');		 
		$extra = "dosing_functions.php?action=type&type=inventoryreports";
		$display_buffer = "
			<script>
				function redirect(){
					window.location='http://$host$uri/$extra';
				}
				function showreport(){
					document.getElementById('recreport').innerHTML = 'Loading...';
					x_module_html('DispensingRep', 'DisplayReport', document.getElementById('txtrptdate_cal').value, showrep);
				}
				function showrep( value ){
					//alert(value);
					document.getElementById('recreport').innerHTML = value;
				}
			</script>
			<table width=100% cellspacing=0 cellpadding=0>
				<tr>
					<td align=\"left\" class=\"top-data\">Select Report Date</td>
					<td>".fm_date_entry ( 'txtrptdate' )."</td>
				</tr>
				<tr>
					<td></td>
					<td><input type='button' value='Show Report' onclick='showreport();'></td>
				</tr>
			</table>
			
			<div id='recreport'></div>
			<div align='right'><input type='button' onclick='redirect();' value='Back'></div>		
		";	
//		return $retval;
	}
	
	function DisplayReport($date)
	{
		$sqlquery="SELECT doserecord.*, ptlname, ptfname, userdescrip
			FROM doserecord
				LEFT JOIN patient ON doserecord.dosepatient = patient.id
				LEFT JOIN user ON user.id = doserecord.dosegivenuser 
			WHERE date(doserecord.dosegivenstamp) = '".date('Y-m-d',strtotime($date))."'
				AND dosegiven='1'
			ORDER BY dosegivenstamp";
		$result= $GLOBALS['sql']->query($sqlquery);
			$retval="
				<table cellspacing=0 cellpadding=3 width=100%>
					<tr>
						<td> ".date('Y-m-d')."</td>
						<td colspan=6 align=center> CODAC II <br> Final Dispensing Log for Main Dispensary </td>
					</tr>
					<tr>
						<th align=\"left\">Dispensed</th>
						<th align=\"left\">Client Name</th>
						<th align=\"left\">Dose Date</th>
						<th align=\"left\">Type</th>
						<th align=\"left\">Units</th>
						<th align=\"left\">Med Type</th>
						<th align=\"left\">Nurse</th>
					</tr>	
					";
			while ($row=$GLOBALS['sql']->fetch_array($result)) {
				if (date('Y-m-d',strtotime($row['dosegivenstamp'])) != date('Y-m-d',strtotime($row['doseassigneddate'])))
					$takehome = "Take-home";
				$retval .="
					<tr>
						<td>".date('Y-m-d',strtotime($row['dosegivenstamp']))."</td>
						<td>".$row['ptlname']." ".$row['ptfname']."</td>
						<td>".date('Y-m-d',strtotime($row['doseassigneddate']))."</td>
						<td>".$takehome."</td>
						<td>".$row['doseunits']."</td>
						<td>".$row['dosemedicationtype']."</td>
						<td>".$row['userdescrip']."</td>
					</tr>	
					";
			}
									
			$retval .= "
				</table>
					";
				return $retval;
	}

	function viewrep_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=reports\">Daily Dispensing Report</a>
		";
	} // end function summary_bar

} // end class ReconcileRep

register_module("DispensingRep");

?>
