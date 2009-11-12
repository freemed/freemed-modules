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

class PendingOrder extends EMRModule {
	var $MODULE_NAME = "PendingOrder";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Lots Management';
	var $table_name = 'doseplan';		// need to change
	var $order_by = 'id';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function PendingOrder ( ) {
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
				function showrep( value ){
					//alert(value);
					document.getElementById('recreport').innerHTML = value;
				}
				function showreport(){
					document.getElementById('recreport').innerHTML = 'Loading...';
					var arr = Array();
					arr[0] = document.getElementById('txtrptdate_cal').value;
					arr[1] = 0; // for every day after today
					x_module_html('DailyPostedOrder', 'DisplayReport', arr, showrep);
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
	
	// DisplayReport is performed by passing a parameter to DailyPostedOrder's DisplayReport function

	function viewrep_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=reports\">Pending Doctor's Order Register</a>
		";
	} // end function summary_bar

} // end class ReconcileRep

register_module("PendingOrder");
?>
