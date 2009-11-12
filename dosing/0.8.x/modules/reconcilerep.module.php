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

class ReconcileRep extends EMRModule {
	var $MODULE_NAME = "ReconcileRep";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Lots Management';
	var $table_name = 'lotreceipt';		// need to change
	var $order_by = 'id';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function ReconcileRep ( ) {
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }		

		// Set associations
		$this->EMRModule();

		$this->_SetHandler('DosingFunctions', 'closeDosingStationWizard');
		$this->_SetMetaInformation('DosingFunctionName', __("Close Dosing Station"));
		$this->_SetMetaInformation('DosingFunctionDescription', __("Close dosing station.") );
	} // end constructor ReconcileRep

	function closeDosingStationWizard() {
		ob_start();
		include_once ('close_dosing_station.php');
		$GLOBALS['display_buffer'] .= ob_get_contents();
		ob_end_clean();
		return true;
	}

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
				function getbtlno(){
					document.getElementById('idBtlNo').innerHTML = 'Loading...';
					x_module_html('lotreceipt', 'getAjxBottleNos', document.getElementById('txtlot_id').value, test);
				}
				function test( value ){
					document.getElementById('idBtlNo').innerHTML = value;
				}
				function showrep( value ){
					//alert(value);
					document.getElementById('recreport').innerHTML = value;
				}
				function showreport(){
					document.getElementById('recreport').innerHTML = 'Loading...';
					x_module_html('ReconcileRep', 'DisplayReport', document.getElementById('btlno').value, showrep);
				}
			</script>
			<table width=100% cellspacing=0 cellpadding=0>
				<tr>
					<td align=\"left\" class=\"top-data\">Lot Number</td>
					<td>".module_function('LotReceipt', 'getLotNos', array('txtlot_id'))."</td>
				</tr>
				<tr>
							<td class=\"top-data\">Bottle numbers received:
							<td class=\"top-data\"> 
								<div id='idBtlNo'></div>
							</td>
				</tr>
				<tr>
					<td></td>
					<td><input type='button' value='Go' onclick='showreport();'></td>
				</tr>
			</table>
			
			<div id='recreport'></div>
			<div align='right'><input type='button' onclick='redirect();' value='Back'></div>
		
		";	
//		return $retval;
	}
	
	function DisplayReport($id)
	{
		$sqlquery="SELECT * FROM reconcilebottle WHERE rec_bottle_id='".addslashes($id)."' ORDER BY rec_per_end DESC";
		$result= $GLOBALS['sql']->query($sqlquery);
		$row=$GLOBALS['sql']->fetch_array($result);
		if ($row != ""){
			$retval="
					<center>Note: All Amounts are in Milligrams</center>
					<table align='center'>
						<tr>
							<td colspan='2'><b>Computer Amounts :</b></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Initial Bottle Contents :</td>
							<td align='right'>". $row['rec_qty_initial'] ."</td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Transferred to Other Bottles :</td>
							<td align='right'>". $row['rec_qty_tr_out'] ."</td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Transferred from Other Bottles :</td>
							<td align='right'>". $row['rec_qty_tr_in'] ."</td>
						</tr>
						<tr>
							<td></td>
							<td><hr/></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Net Amount Available for Use :</td>
							<td align='right'>". ($row['rec_qty_initial'] + $row['rec_qty_tr_out'] - $row['rec_qty_tr_in']) ."</td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Dispensed Today :</td>
							<td align='right'>". $row['rec_qty_disp'] ."</td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Dispensed For Take-Home Doses :</td>
							<td align='right'>". $row['rec_qty_disp_takehome'] ."</td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Waste (spillage) :</td>
							<td align='right'>". $row['rec_qty_spill'] ."</td>
						</tr>
						<tr>
							<td></td>
							<td><hr/></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Net Amount Remaining in Bottle :</td>
							<td align='right'>". $row['rec_qty_final_expected'] ."</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td></td>
						</tr>
						<tr>
							<td>Actual Amount Measured :</td>
							<td align='right'>". $row['rec_qty_final_actual'] ."</td>
						</tr>
						<tr>
							<td>Difference :</td>
							<td align='right'>". ($row['rec_qty_final_expected'] - $row['rec_qty_final_actual']) ."</td>
						</tr>
						<tr>
							<td>Adjustment Posted Reason :</td>
							<td align='right'>". $row['rec_reason'] ."</td>
						</tr>
					</table>";
				return $retval;
			}
			else
			{
				return "Sorry no record found";
			}
		}

	function viewrep_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=reports\">Reconciliation Bottles</a>
		";
	} // end function summary_bar

} // end class ReconcileRep

register_module("ReconcileRep");
?>
