<?php
  // $Id$
  //
  // Authors:
  //      Hardik
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

class DoseplanRep extends EMRModule {
	var $MODULE_NAME = "DoseplanRep";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Dose Plan';
	var $table_name = 'doseplan';		// need to change
	var $order_by = 'id';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function DoseplanRep ( ) {
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
				function test( value ){
					document.getElementById('idBtlNo').innerHTML = value;
				}
				function showrep( value ){
					//alert(value);
					document.getElementById('doseplanreport').innerHTML = value;
				}
				function showreport(){
					document.getElementById('doseplanreport').innerHTML = 'Loading...';
					x_module_html('DoseplanRep', 'DisplayReport', document.getElementById('txtdate').value, showrep);
				}
			</script>
			<table width=100% cellspacing=0 cellpadding=0>
				<tr>
					<td align=\"left\" class=\"top-data\">Select Date :</td>
					<td>".fm_date_entry("txtdate")."</td>
				</tr>
				<tr>
					<td></td>
					<td><input type='button' value='Go' onclick='showreport();'></td>
				</tr>
			</table>
			
			<div id='doseplanreport'></div>
			<div align='right'><input type='button' onclick='redirect();' value='Back'></div>		
		";	
//		return $retval;
	}
	
	function DisplayReport($date)
	{
		$userquery="select userdescrip from user where id=".$GLOBALS['this_user']->user_number;
		$userresult= $GLOBALS['sql']->query($userquery);
		$userrow=$GLOBALS['sql']->fetch_array($userresult);
		if ($userrow != "")
		{
			$pharmacist=$userrow['userdescrip'];
		}
		$retval="
				<center>Note: All Amounts are in Milligrams
				<table align='center'>
					<tr>
						<td>Pharmacist :</td>
						<td>". $pharmacist ."</td>
						<td>Nurse #1 :</td>
						<td></td>
					</tr>
					<tr>
						<td>License # :</td>
						<td></td>
						<td>Nurse #2 :</td>
						<td></td>
					</tr>
				</table>
				<table align='center' cellpadding='5'>
					<tr>
						<td align=\"left\"><b>Name</b></td>
						<td align=\"left\"><b>Cns</b></td>
						<td align=\"right\"><b>Mgs</b></td>
						<td align=\"right\"><b>Cli#</b></td>
						<td align=\"left\"><b>Remarks</b></td>
					</tr>";
		$sqlquery="select * from doseplan where doseplaneffectivedate='".$date."'";
		$result= $GLOBALS['sql']->query($sqlquery);
		if(mysql_num_rows($result)>0)
		{
			$total=0;
			while($row=$GLOBALS['sql']->fetch_array($result))
			{
				$patientquery="select ptlname from patient where id=".$row['doseplanpatient'];
				$patientresult= $GLOBALS['sql']->query($patientquery);
				$patientrow=$GLOBALS['sql']->fetch_array($patientresult);
				$retval.="
						<tr>
							<td align='left'>". $patientrow['ptlname'] ."</td>
							<td align='left'>". $row[''] ."</td>
							<td align='right'>". $row['doseplandose'] ."</td>
							<td align='right'>". $row['doseplanpatient'] ."</td>
							<td align='left'>". $row['doseplancomment'] ." ". $row['doseplantakehomecountgiven'] ." ". $row['doseplantype'] ."</td>
						</tr>";
				$total+=$row['doseplandose'];
			}
			$retval.="
					<tr>
						<td align='left'></td>
						<td align='left'></td>
						<td align='right'>". $total ."</td>
						<td align='right'></td>
						<td align='left'></td>
					</tr>";
			$retval.="</table></center>";
		}
		else
		{
			return "Sorry no record found";
		}
		return $retval;
	}

	function viewrep_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=reports\">Dispensary Attendance Checklist</a>
		";
	} // end function summary_bar

} // end class ReconcileRep

register_module("DoseplanRep");

?>
