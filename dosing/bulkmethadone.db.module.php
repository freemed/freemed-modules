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

class BulkMethadone extends MaintenanceModule {
	var $MODULE_NAME = "BulkMethadone";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Bulk Methadone Inventory';
	var $table_name = 'bulkmethadone';	// need to change
	var $order_by = 'bulkdate DESC';

	function BulkMethadone ( ) {
		$this->table_definition = array (
			'bulkdate' => SQL__DATE,
			'bulkfacility' => SQL__INT_UNSIGNED(0),
			'bulksuppl_name' => SQL__VARCHAR(50),
			'bulksuppl_refno' => SQL__VARCHAR(50),
			'bulk_rec_qty' => SQL__INT_UNSIGNED(0), // grams
			'bulk_used_qty' => SQL__INT_UNSIGNED(0), // grams
			'bulkuser' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);

		$this->widget_hash = '##bulksuppl_refno##';

		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }
		
		$this->variables = array (
			'bulkdate',
			'bulkfacility',
			'bulksuppl_name',
			'bulksuppl_refno',
			'bulk_rec_qty',
			'bulk_used_qty',
			'bulkuser'
		);

		// TODO how to select a derived variable for the summary query? (rec'd - used qty)
		$this->summary_vars = array (
			__("Date") => "bulkdate",
			__("Received Qty.") => "bulk_rec_qty",
			__("Used Qty.") => "bulk_used_qty",
			__("Ref. No.") => "bulksuppl_refno",
		//	__("Balance Qty") => "lot_bal_qty",
			__("Posted By") => "user:bulkuser"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'id';

		// nothing to do with this class; we just need somewhere to put the handler
		$this->_SetHandler('DosingFunctions', 'openDosingStationWizard');
		$this->_SetMetaInformation('DosingFunctionName', __("Open Dosing Station"));
		$this->_SetMetaInformation('DosingFunctionDescription', __("Prepare dosing station.") );

		// Set associations
		$this->MaintenanceModule();
	} // end constructor Lot

	function openDosingStationWizard ( ) {
                ob_start();
                include_once ('open_dosing_station.php');
                $GLOBALS['display_buffer'] .= ob_get_contents();
                ob_end_clean();
		return true;
	}

	function modform ( ) { }
	function mod ( ) { }
	function del ( ) { }

	function addform ( ) {
		$w = CreateObject( 'PHP.wizard', array ( 'been_here', 'action', 'module', 'return', 'patient' ) );
		$w->set_cancel_name(__("Cancel"));
		$w->set_finish_name(__("Save"));
		$w->set_previous_name(__("Previous"));
		$w->set_next_name(__("Next"));
		$w->set_refresh_name(__("Refresh"));
		$w->set_revise_name(__("Revise"));
		$w->set_width('100%');
		
		$w->add_page ( 'Bulk Methadone Inventory',
			array (
				'txtbulkdate',
				'txtbulkfacility',
				'txtbulksuppl_name',
				'txtbulksuppl_refno',
				'txtbulk_rec_bottles',
				'txtbulk_rec_per_bottle',
				'txtbulkuser'
			),
					html_form::form_table(array(			
			__(" ") => $this->prtStep1($_POST))
			)			
		);
		// Process of next step, variables from previous fields
		
		// Finally, display wizard
		if (! $w->is_done() and ! $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] = $w->display()."<script type=\"text/javascript\">document.getElementById('txtbulkdate').value='".date("Y-m-d")."';</script>";
		}
		
		if ( $w->is_done() ) {
			/*	FINAL SAVING OF ENTRY     */
			$this->variables = array (
				'bulkdate' => $_POST['txtbulkdate'],
				'bulkfacility' => $_POST['txtbulkfacility'],
				'bulksuppl_name' => $_POST['txtbulksuppl_name'],
				'bulksuppl_refno' => $_POST['txtbulksuppl_refno'],
				'bulk_rec_qty' => $_POST['txtbulk_rec_bottles'] * $_POST['txtbulk_rec_per_bottle'],
				'bulk_used_qty' => 0,
				'bulkuser' => $GLOBALS['this_user']->user_number,
			);
			
			$query .= $GLOBALS['sql']->insert_query (
				$this->table_name,
				$this->variables
			);
			syslog(LOG_INFO, $query);
			$result = $GLOBALS['sql']->query( $query );
			$id = $GLOBALS['sql']->last_record( $result, $this->table_name );
			$host  = $_SERVER['HTTP_HOST'];
			$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');		 
			$extra = "module_loader.php?module=BulkMethadone&action=addform";
			$GLOBALS['display_buffer'] = "Please <a href=\"dosing_functions.php?action=type&type=dosinginventory\">Click here</a> to go on Medication Inventory or <a href=\"http://$host$uri/$extra\">Click here</a> to add new";
			global $refresh;
			if ($GLOBALS['return'] == 'manage') {
			      $refresh = 'dosing_functions.php?action=type&type=dosinginventory';
			}
		}
		if ( $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] .= "
			<p/>
			<div ALIGN=\"CENTER\"><b>".__("Cancelled")."</b></div>
			<p/>
			<div ALIGN=\"CENTER\">
			<a HREF=\"dosing_functions.php?action=type&type=dosinginventory\"
			>".__("Return to Medication Inventory Menu")."</a>
			</div>
			";
			global $refresh;
			if ($GLOBALS['return'] == 'manage') {
			      $refresh = 'dosing_functions.php?action=type&type=dosinginventory';
			}
		}
	} // end method addform

	function view ( ) {
		global $sql; 
		global $display_buffer;
		global $patient;		

		if($_REQUEST['id']!="") {
			$result=$sql->query("SELECT ".$this->table_name.".*,userdescrip FROM ".$this->table_name." LEFT JOIN user ON bulkuser = user.id WHERE ".$this->table_name.".id=".addslashes($_REQUEST['id'])." order by bulkdate DESC");
			$row=$sql->fetch_array($result);
			$host  = $_SERVER['HTTP_HOST'];
			$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$extra = "module_loader.php?module=BulkMethadone&action=view&return=manage";
			$lotq = $sql->query("SELECT lotrecno,lotbulkgrams FROM lotreg WHERE lotbulk=".$row['id']);
			while ($lotrow = $sql->fetch_array($lotq))
				$lots .= $lotrow['lotrecno']." (".$lotrow['lotbulkgrams']."g)<br/>";
			$display_buffer = "
				<script>
				function redirect(){
					window.location='$extra';
				}
				</script>
				<table align='center'>
					<tr>
						<th colspan='2'>Bulk Methadone Inventory</th>
					</tr>
					<tr>
						<td>Date :</td>
						<td>".$row['bulkdate']."</td>
					</tr>
					<tr>
						<td>Received Qty. :</td>
						<td>".$row['bulk_rec_qty']."</td>
					</tr>
					<tr>
						<td>Used Qty. :</td>
						<td>".$row['bulk_used_qty']."</td>
					</tr>
					<tr>
						<td>Balance Qty :</td>
						<td>".($row['bulk_rec_qty'] - $row['bulk_used_qty'])."</td>
					</tr>
					<tr>
						<td>Ref. No. :</td>
						<td>".$row['bulksuppl_refno']."</td>
					</tr>
					<tr>
						<td valign=\"top\">Lots Created :</td>
						<td>".$lots."</td>
					</tr>
					<tr>
						<td>Posted By :</td>
						<td>".$row['userdescrip']."</td>
					</tr>
					<tr>
						<td colspan='2' align='center'><input type='button' value='Back' onclick='redirect();'></td>
					</tr>
				</table>";
		}
		else
		{
			global $_ref; $_ref = 'dosing_functions.php?action=type&type=dosinginventory';
			$display_buffer = freemed_display_itemlist (
				$sql->query("SELECT * FROM ".$this->table_name." ORDER BY bulkdate DESC"),
				$this->page_name,
				array(
				__("Date") => "bulkdate",
				__("Received Qty.") =>	"bulk_rec_qty",
				__("Used Qty.") =>	"bulk_used_qty",
				__("Ref. No.") =>	"bulksuppl_refno",
				//__("Balance Qty") => "lot_bal_qty",
				__("Posted By") => "user:bulkuser"
				), NULL, NULL, NULL, NULL,
							ITEMLIST_VIEW
			);
		}

	} // end method view

	function prtStep1($var=""){

		$str = "<table width=\"80%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=center>
			  <tr> 
				<td class=\"top-data\">".__("Date")."</td>
				<td>".fm_date_entry("txtbulkdate")."</td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Site Name")."</td>
				<td>".module_function('facilitymodule', 'widget', array('txtbulkfacility'))."</td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Supplier Name")."</td>
				<td><input type=\"text\" name=\"txtbulksuppl_name\" value='".$var[txtbulksuppl_name]."'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Supplier Ref No")."</td>
				<td><input type=\"text\" name=\"txtbulksuppl_refno\" value='".$var[txtbulksuppl_refno]."'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Qty (grams) per Bulk Bottle")."</td>
				<td><input type=\"text\" id='txtbulk_rec_per_bottle' name=\"txtbulk_rec_per_bottle\" value='".$var['txtbulk_rec_qty']."' onblur='javascript:document.getElementById(\"txtbulk_rec_qty\").value=document.getElementById(\"txtbulk_rec_per_bottle\").value*document.getElementById(\"txtbulk_rec_bottles\").value'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("No of Bulk Bottles")."</td>
				<td><input type=\"text\" id='txtbulk_rec_bottles' name=\"txtbulk_rec_bottles\" value='1' onblur='javascript:document.getElementById(\"txtbulk_rec_qty\").value=document.getElementById(\"txtbulk_rec_per_bottle\").value*document.getElementById(\"txtbulk_rec_bottles\").value'></td>
			  </tr>
		</table>
		  <script>
			var d = new Date();
			var month =0;
			if (d.getMonth() < 10){
				month = \"0\"+ (d.getMonth() + 1)
			} else {
				month = d.getMonth() + 1;
			}
			document.form_0.txtbulkdate.value= d.getFullYear() + '-' + (month) + '-' + d.getDate();
			//document.getElementById(\"txtbulkdate\").value = d.getYear() + '-' + (month) + '-' + d.getDate();
			
		  </script>
		";
		return $str;	
	}

	function addform_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=manage\">Bulk Methadone Inventory</a>
		";
	} // end function summary_bar

	function getSupplNo( $selectName) {
		$q = $GLOBALS['sql']->query("SELECT DISTINCT bulksuppl_refno refno FROM ".$this->table_name." ORDER BY refno");
		while ($lastr = $GLOBALS['sql']->fetch_array($q)) {
			$ar[$lastr["refno"]] = $lastr["refno"];
		}
		$var = html_form::select_widget($selectName, $ar);
		return $var;
	} // end get lot numbers
} // end class BulkMethadone
register_module("BulkMethadone");
?>
