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

class LotMgt extends EMRModule {
	var $MODULE_NAME = "LotMgt";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Bulk Methadone Inventory';
	var $table_name = 'lotmgt';		// need to change
	var $order_by = 'lotmgtdate DESC';

	function LotMgt ( ) {
		$this->table_definition = array (
			'lotmgtdate' => SQL__DATE,
			'lotfacility_id' => SQL__INT_UNSIGNED(0),
			'lotsuppl_name' => SQL__VARCHAR(50),
			'lotsuppl_refno' => SQL__VARCHAR(50),
			'lot_rec_qty' => SQL__INT_UNSIGNED(0),
			'lot_bal_qty' => SQL__INT_UNSIGNED(0),			
			'lot_used_qty' => SQL__INT_UNSIGNED(0),			
			'lot_rec_by' => SQL__VARCHAR(50),
			'lot_user' => SQL__INT_UNSIGNED(0),			
			'id' => SQL__SERIAL
		);

		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }
		
		$this->variables = array (
			'lotmgtdate',
			'lotfacility_id',
			'lotsuppl_name',
			'lotsuppl_refno',
			'lot_rec_qty',
			'lot_no_qty',
			'lot_tot_qty',
			'lot_bal_qty',			
			'lot_rec_by',
			'lot_user',			
		);

		$this->summary_vars = array (
			__("Date") => "lotmgtdate",
			__("Received Qty.") =>	"lot_rec_qty",
			__("Used Qty.") =>	"lot_used_qty",
			__("Ref. No.") =>	"lotsuppl_refno",
			__("Balance Qty") => "lot_bal_qty",
			__("Posted By") => "lot_rec_by"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'id';

		$this->_SetHandler('DosingFunctions', 'openDosingStationWizard');
		$this->_SetMetaInformation('DosingFunctionName', __("Open Dosing Station"));
		$this->_SetMetaInformation('DosingFunctionDescription', __("Prepare dosing station.") );

		// Set associations
		$this->EMRModule();
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
				'txtlotmgtdate',
				'txtlotfacility_id',
				'txtlotsuppl_name',
				'txtlotsuppl_refno',
				'txtlot_rec_qty',
				'txtlot_tot_qty',
				'txtlot_no_qty',
				'txtlot_bal_qty',			
				'txtlot_rec_by',
				'txtlot_user',			
			),
					html_form::form_table(array(			
			__(" ") => $this->prtStep1($_POST))
			)			
		);
		// Process of next step, variables from previous fields
		
		// Finally, display wizard
		if (! $w->is_done() and ! $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] = $w->display()."<script type=\"text/javascript\">document.getElementById('txtlotmgtdate').value='".date("Y-m-d")."';</script>";
		}
		
		if ( $w->is_done() ) {
			/*	FINAL SAVING OF ENTRY     */
			$this->variables = array (
				'lotmgtdate' => $_POST['txtlotmgtdate'] ,
				'lotfacility_id' => $_POST['txtlotfacility_id'] ,
				'lotsuppl_name' => $_POST['txtlotsuppl_name'] ,
				'lotsuppl_refno' => $_POST['txtlotsuppl_refno'] ,
				'lot_rec_qty' => $_POST['txtlot_rec_qty'] ,
				'lot_bal_qty' => $_POST['txtlot_bal_qty'] ,			
				'lot_rec_by' => $_POST['txtlot_rec_by'] ,
				'lot_no_qty' => $_POST['txtlot_no_qty'],
				'lot_used_qty' => $_POST['txt_tot_qty'],
				'lot_user' => $GLOBALS['this_user']->user_number,			
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
			$extra = "module_loader.php?module=LotMgt&action=addform";
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
			$result=$sql->query("SELECT  lotmgtdate,lotsuppl_name,lotsuppl_refno,lot_rec_qty,lot_bal_qty,lot_rec_by,lot_user FROM ".$this->table_name." where id=".addslashes($_REQUEST['id'])." order by lotmgtdate DESC");
			$row=$sql->fetch_array($result);
			$host  = $_SERVER['HTTP_HOST'];
			$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');		 
			$extra = "module_loader.php?module=LotMgt&action=view&return=manage";
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
						<td>".$row['lotmgtdate']."</td>
					</tr>
					<tr>
						<td>Received Qty. :</td>
						<td>".$row['lot_rec_qty']."</td>
					</tr>
					<tr>
						<td>Used Qty. :</td>
						<td>".$row['lot_used_qty']."</td>
					</tr>
					<tr>
						<td>Ref. No. :</td>
						<td>".$row['lotsuppl_refno']."</td>
					</tr>
					<tr>
						<td>Balance Qty :</td>
						<td>".$row['lot_bal_qty']."</td>
					</tr>
					<tr>
						<td>Posted By :</td>
						<td>".$row['lot_rec_by']."</td>
					</tr>
					<tr>
						<td colspan='2' align='center'><input type='button' value='Back' onclick='redirect();'></td>
					</tr>
				</table>";
		}
		else
		{
			global $_ref; $_ref = 'dosing_functions.php?action=type&type=dosinginventory';
			syslog(LOG_INFO, "SELECT  lotmgtdate,lotsuppl_name,lotsuppl_refno,lot_rec_qty,lot_bal_qty,lot_rec_by,lot_user,id,lot_used_qty FROM ".$this->table_name." ORDER BY lotmgtdate DESC");
			$display_buffer = freemed_display_itemlist (
				$sql->query("SELECT  lotmgtdate,lotsuppl_name,lotsuppl_refno,lot_rec_qty,lot_bal_qty,lot_rec_by,lot_user,id,lot_used_qty FROM ".$this->table_name." ORDER BY lotmgtdate DESC"),
				$this->page_name,
				array(
				__("Date") => "lotmgtdate",
				__("Received Qty.") =>	"lot_rec_qty",
				__("Used Qty.") =>	"lot_used_qty",
				__("Ref. No.") =>	"lotsuppl_refno",
				__("Balance Qty") => "lot_bal_qty",
				__("Posted By") => "lot_rec_by"
				), NULL, NULL, NULL, NULL,
							ITEMLIST_VIEW
			);
		}

	} // end method view

	function prtStep1($var=""){

		$str = "<table width=\"80%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=center>
			  <tr> 
				<td class=\"top-data\">".__("Date")."</td>
				<td>".fm_date_entry("txtlotmgtdate")."</td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Site Name")."</td>
				<td>".module_function('facilitymodule', 'widget', array('txtlotfacility_id'))."</td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Supplier Name")."</td>
				<td><input type=\"text\" name=\"txtlotsuppl_name\" value='".$var[txtlotsuppl_name]."'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Supplier Ref No")."</td>
				<td><input type=\"text\" name=\"txtlotsuppl_refno\" value='".$var[txtlotsuppl_refno]."'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Qty (grams)")."</td>
				<td><input type=\"text\" id='txtidlot_rec_qty' name=\"txtlot_rec_qty\" value='".$var[txtlot_rec_qty]."' onblur='javascript:document.getElementById(\"txtlot_tot_qty\").value=document.getElementById(\"txtidlot_no_qty\").value*document.getElementById(\"txtidlot_rec_qty\").value'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("No of Grams per Qty")."</td>
				<td><input type=\"text\" id='txtidlot_no_qty' name=\"txtlot_no_qty\" value='".$var[txtlot_no_qty]."' onblur='javascript:document.getElementById(\"txtlot_tot_qty\").value=document.getElementById(\"txtidlot_no_qty\").value*document.getElementById(\"txtidlot_rec_qty\").value'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Total Grams Received by ")."</td>
				<td><input type=\"text\" id='txtlot_tot_qty'  name=\"txtlot_tot_qty\" value='".$var[txtlot_tot_qty]."' onblur='javascript:document.getElementById(\"txtlot_tot_qty\").value=document.getElementById(\"txtidlot_no_qty\").value*document.getElementById(\"txtidlot_rec_qty\").value'></td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Balance Qty (grms)")."</td>
				<td> &nbsp; </td>
			  </tr>
			  <tr> 
				<td class=\"top-data\">".__("Received By")."</td>
				<td><input type=\"text\" name=\"txtlot_rec_by\" value='".$var[txtlot_rec_by]."'></td>
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
			document.form_0.txtlotmgtdate.value= d.getFullYear() + '-' + (month) + '-' + d.getDate();
			//document.getElementById(\"txtlotmgtdate\").value = d.getYear() + '-' + (month) + '-' + d.getDate();
			
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
		$q = $GLOBALS['sql']->query("SELECT  distinct lotsuppl_refno refno FROM lotmgt order by lotsuppl_refno");
		while ($lastr = $GLOBALS['sql']->fetch_array($q)) {
			$ar[$lastr["refno"]] = $lastr["refno"];
		}
		$var = html_form::select_widget($selectName, $ar);
		return $var;
	} // end get lot numbers

	// function for updating balance creted by raju
	function updateBalance($lotno,$lotbal){
		$query = $GLOBALS['sql']->query("SELECT * "." FROM ".$this->table_name." ".
							"WHERE lotrecno='".addslashes($_POST['txtLotNo'])."' order by id desc");
		$rec = $GLOBALS['sql']->fetch_array($query);
		$balance = $rec['lot_act_balance']-$lotbal;
		$q = $GLOBALS['sql']->update_query(
		'lotmgt',
		array (
			'lot_act_balance' => $balance,
		), array ( 'id' => $rec['id'] )
		);
		$res = $GLOBALS['sql']->query( $q );
	}// end update balance function
	
} // end class LotManagement
register_module("LotMgt");
?>
