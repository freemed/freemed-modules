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

LoadObjectDependency('_FreeMED.MaintenanceModule');

class DoseHoldRep extends MaintenanceModule {
	var $MODULE_NAME = "DoseHoldRep";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Dose Hold Report';
	var $table_name = 'dosehold';		// need to change
	var $order_by = 'id';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function DoseHoldRep ( ) {
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }		

		// Set associations
		$this->MaintenanceModule();
	} // end constructor DoseHoldRep

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
					x_module_html('".get_class($this)."', 'DisplayReport', document.getElementById('txtrptdate_cal').value, showrep);
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
		$sqlquery="Select dosehold.*,ptid,userdescrip
			FROM dosehold 
			LEFT JOIN patient ON dosehold.doseholdpatient = patient.id
			LEFT JOIN user ON dosehold.doseholduser = user.id
			WHERE Date(doseholdstamp) < '".addslashes($date)."'
			AND doseholdstatus = 1
			ORDER BY ptid
			";

		$result= $GLOBALS['sql']->query($sqlquery);
			$retval=" 
				<table cellspacing=0 cellpadding=3 width=100%>
					<tr>
						<td> ".date('Y-m-d')."</td>
						<td colspan=6 align=center> CODAC II <br> Dosing Holds Report For $date </td>
					</tr>
					<tr>
						<th align=\"left\">Client</th>
						<th align=\"left\">Type of Hold</th>
						<th align=\"left\">Placed By</th>
						<th align=\"left\">Placed On</th>
						<th align=\"left\">Description</th>
					</tr>	
					";
			while ($row=$GLOBALS['sql']->fetch_array($result)) {
				switch ($row['doseholdtype']){
					case 0:
						$type = "None";
						break;
					case 1:
						$type = "Soft Dose";
						break;
					case 2:
						$type = "Hard Dose";					
						break;
				}
				$retval .="
					<tr>
						<td align=\"left\">".$row['ptid']."</td>
						<td align=\"left\">".$type."</td>
						<td align=\"left\">".$row['userdescrip']."</td>
						<td align=\"left\">".date("Y-m-d", strtotime($row['doseholdstamp']))."</td>
						<td align=\"left\">".$row['doseholdcomment']."</td>
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
		get_class($this)."&action=view&return=reports\">Dosehold Report</a>
		";
	} // end function summary_bar

} // end class DoseHoldRep

register_module("DoseHoldRep");

?>
