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

class DispenseRep extends EMRModule {
	var $MODULE_NAME = "DispenseRep";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Dispense';
	var $table_name = 'doseplan';		// need to change
	var $order_by = 'id';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function DispenseRep ( ) {
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }		

		// Set associations
		$this->EMRModule();
	} // end constructor DispenseRep
	

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
					document.getElementById('dispensereport').innerHTML = value;
				}
				function showreport(){
					document.getElementById('dispensereport').innerHTML = 'Loading...';
					x_module_html('DispenseRep', 'DisplayReport', document.getElementById('txtlot_id').value, showrep);
				}
			</script>
			<table width=100% cellspacing=0 cellpadding=0>
				<tr>
					<td align=\"left\" class=\"top-data\">Select Lot No :</td>
					<td>".module_function('LotReceipt', 'getLotNos', array('txtlot_id'))."</td>
				</tr>
				<tr>
					<td></td>
					<td><input type='button' value='Go' onclick='showreport();'></td>
				</tr>
			</table>
			
			<div id='dispensereport'></div>
			<div align='right'><input type='button' onclick='redirect();' value='Back'></div>";	
//		return $retval;
	}
	
	function DisplayReport($lotno)
	{
		$totaldip=0;
		$total=0;
		$sqlquerytemp="SELECT distinct ".
		"doserecord.doseplanid ".
		"from doserecord,lotreceipt ".
		"WHERE ".
		"dosebottleid=lotreceipt.id AND ".
		"lotreceipt.lotrecno=".addslashes($lotno);
		$resulttemp= $GLOBALS['sql']->query($sqlquerytemp);
		if($GLOBALS['sql']->num_rows($resulttemp)>0)
		{
			$sqlquerylot="SELECT lotrecno from lotreg where id=".addslashes($lotno);
			$resultlot= $GLOBALS['sql']->query($sqlquerylot);
			$rowlot=$GLOBALS['sql']->fetch_array($resultlot);
			$retval="
				<center>Lot # ".$rowlot['lotrecno']." of Methadone</center>
				<table BORDER=\"0\" align=\"center\" cellpadding=\"3\">
					<tr>
						<td></td>
						<td align=\"right\"><b>Dosage<br>(mgs)</b></td>
						<td align=\"right\"><b>Number<br>Dispensed</b></td>
						<td align=\"right\"><b>Total<br>(mgs)</b></td>
					</tr>";
			while($rowtemp=$GLOBALS['sql']->fetch_array($resulttemp))
			{
				$sqlquery="select count(*) as cnt from doserecord where doseplanid=".$rowtemp['doseplanid'];
				$sqlquery1="select doseunits from doserecord where doseplanid=".$rowtemp['doseplanid'];
				//$retval.=$sqlquery;
				$result= $GLOBALS['sql']->query($sqlquery);
				$result1= $GLOBALS['sql']->query($sqlquery1);
				if(mysql_num_rows($result)>0)
				{
					$row=$GLOBALS['sql']->fetch_array($result);
					$row1=$GLOBALS['sql']->fetch_array($result1);
					$retval.="
							<tr>
								<td></td>
								<td align=\"right\">". $row1['doseunits'] ."</td>
								<td align=\"right\">". $row['cnt'] ."</td>
								<td align=\"right\">". ($row1['doseunits']*$row['cnt']) ."</td>
							</tr>";
					$totaldip+=$row['cnt'];
					$total+=($row1['doseunits']*$row['cnt']);
				}				
			}
			//$retval.="</table>";
			//$retval.="<table>";
			$retval.=
					"<tr>
						<td colspan=\"4\"><br></td>
					</tr>";
			$retval.=
					"<!-- <tr>
						<td>Bottle Totals</td>
						<td></td>
						<td align=\"right\">".$totaldip."</td>
						<td align=\"right\">".$total."</td>
					</tr> -->
					<tr>
						<td>Lot Totals</td>
						<td></td>
						<td align=\"right\">".$totaldip."</td>
						<td align=\"right\">".$total."</td>
					</tr>
					<!-- <tr>
						<td>Totals Dispensed Today</td>
						<td></td>
						<td align=\"right\">".$totaldip."</td>
						<td align=\"right\">".$total."</td>
					</tr> -->";
			$retval.="</table>";
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
		get_class($this)."&action=view&return=reports\">Dispensing Log Recap by Dose</a>
		";
	} // end function summary_bar

} // end class DispenseRep

register_module("DispenseRep");

?>
