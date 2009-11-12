<?php
  // $Id$
  //
  // Authors:
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

class UsageByDay extends MaintenanceModule {
	var $MODULE_NAME = "UsageByDay";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Lots Management';
	var $table_name = 'doseplan';		// need to change
	var $order_by = 'id';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function UsageByDay ( ) {
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }		

		// Set associations
		$this->MaintenanceModule();
	} // end constructor UsageByDay
	

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
					document.getElementById('report').innerHTML = value;
				}
				function showreport(){
					document.getElementById('report').innerHTML = 'Loading...';
					var date = document.getElementById('txtrptdate_cal').value;
					x_module_html('UsageByDay', 'DisplayReport', date, showrep);
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
			
			<div id='report'></div>
			<div align='right'><input type='button' onclick='redirect();' value='Back'></div>
		";	
//		return $retval;
	}
	
	function DisplayReport($date)
	{
		$loc = $_SESSION['default_facility'];
		$locstr = freemed::get_link_field($loc, "facility", "psrname");
		$d = getdate(strtotime($date));
		$monthstart = "$d[year]-$d[mon]-01";
		$dobj = new DateTime($monthstart);
		$dobj->modify("+1 month");
		$dobj->modify("-1 day");
		$monthend = $dobj->format("Y-m-d");
		$sqlquery="SELECT SUM(doseunits) as totalunits, DATE(dosegivenstamp) AS ddate FROM doserecord LEFT JOIN dosingstation ON doserecord.dosestation = dosingstation.id WHERE dosegivenstamp >= '$monthstart' AND dosegivenstamp <= '$monthend' AND dsfacility = '".addslashes($loc)."' GROUP BY ddate";
		$result= $GLOBALS['sql']->query($sqlquery);
		$monthtotal = 0;
		$weektotal = 0;
		if($GLOBALS['sql']->num_rows($result)>0)
		{
			$retval="
				<table BORDER=\"0\" align=\"center\" cellpadding=\"3\">
					<tr>
						<td colspan=2 align=\"center\">
						Methadone usage by day for $locstr<br/>
						$monthstart to $monthend
						</td>
					</tr>
					<tr>
						<td align=\"left\"><b>Date</b></td>
						<td align=\"right\"><b>Total Dispensed</b></td>
					</tr>";
			$been_here = false;
			while($row=$GLOBALS['sql']->fetch_array($result))
			{
				$d = getdate(strtotime($row["ddate"]));
syslog(LOG_INFO, "$row[ddate]: $d[mday] - $lastd[mday] + $lastd[wday] = ".($d[mday] - $lastd[mday] + $lastd[wday]));
				if ($been_here)
					if ($d[mday] - $lastd[mday] + $lastd[wday] > 6) {
						$retval .= "
				<tr><td/><td><hr/></td></tr>
				<tr>
					<td align=\"left\"><b>Week ".(int)($lastd[mday]/7 + 1)." Total</b></td>
					<td align=\"right\">".$weektotal."</td>
				</tr>
				<tr><td colspan=2>&nbsp;</td></tr>";
					$weektotal = 0;
					}
				$been_here = true;
				$retval.="
				<tr>
					<td align=\"left\">$d[weekday], $d[month] $d[mday]</td>
					<td align=\"right\">".$row["totalunits"]."</td>
				</tr>";
				$monthtotal += $row["totalunits"];
				$weektotal += $row["totalunits"];
				$lastd = $d;
			}
			$retval.="
			<tr><td/><td><hr/></td></tr>
			<tr>
				<td align=\"left\"><b>Week ".(int)($lastd[mday]/7 + 1)." Total</b></td>
				<td align=\"right\">".$weektotal."</td>
			</tr>
			<tr><td/><td><hr/></td></tr>
			<tr>
				<td align=\"left\"><b>Monthly Total</b></td>
				<td align=\"right\">".$monthtotal."</td>
			</tr>";
			$retval.="</table>";
		}
		else
			return "Sorry no record found";
		return $retval;
	}

	function viewrep_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=reports\">Methadone Usage By Day</a>
		";
	} // end function summary_bar

} // end class UsageByDay

register_module("UsageByDay");
?>
