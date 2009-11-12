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

LoadObjectDependency('_FreeMED.MaintenanceModule');

class NewLotOpen extends MaintenanceModule {
	var $MODULE_NAME = "NewLotOpen";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'New Lot Receipt';
	var $table_name = 'newlotopen';		// need to change
	var $order_by = 'id DESC';

	function NewLotOpen ( ) {
		$this->table_definition = array (
			'lotrecdate' => SQL__DATE,
			'lot_id' => SQL__INT_UNSIGNED(0),
			'lotrecsite' => SQL__INT_UNSIGNED(0),
			'lotrecuserid' => SQL__INT_UNSIGNED(0),
			'lotrecbottleno' => SQL__VARCHAR(50),
			'lotrecbottleqty' => SQL__INT_UNSIGNED(0),
			'lotstatus' => SQL__VARCHAR(50),
			'id' => SQL__SERIAL
		);

		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }
		
		$this->variables = array (
			'lotrecdate',
			'lot_id',
			'lotrecsite',
			'lotrecuserid',
			'lotrecbottleno',
			'lotrecbottleqty',
			'lotstatus',
			'id'
		);

		$this->summary_vars = array (
			__("Lot Number ") => "doseplanuser:user",
			__("Date Opened") => "lotrecdate",
			__("Date Closed") => "lotrecno",
			__("Original Qty") =>	"",
			__("Used Qty") =>	"",
			__("Status") =>	"lotstatus",
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'id';

		// Set associations
		$this->MaintenanceModule();
	} // end constructor Lot

	function modform ( ) { }
	function mod ( ) { }
	function del ( ) { }

	function addform ( ) {
		include_once(freemed::template_file('ajax.php'));	
		$w = CreateObject( 'PHP.wizard', array ( 'been_here', 'action', 'module', 'return', 'patient' ) );
		$w->set_cancel_name(__("Cancel"));
		$w->set_finish_name(__("Finish"));
		$w->set_previous_name(__("Previous"));
		$w->set_next_name(__("Next"));
		$w->set_refresh_name(__("Refresh"));
		$w->set_revise_name(__("Revise"));
		$w->set_width('100%');

		$w->add_page ( 'New Lot Receipt',
			array (
				'txtlotrecdate',
				'txtlot_id',
				'txtlotrecsite',
				'txtlotrecbottleno',
				'txtlotrecbottleqty',
			),
				html_form::form_table(array(			
			__(" ") => $this->prtStep1($_POST))
			)			
		);

		if (! $w->is_done() and ! $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] = $w->display();
		}
		if ( $w->is_done() ) {
			// Final insertion of the
			$id=$_POST['chkbotrecno_'];
			//print_r($id);
			$count=0;
			foreach ($id as $key => $value )
			{
				$count++;
				$this->variables = array (
					'lotrecdate' => $_POST['txtlotrecdate'],
					'lot_id' => $_POST['txtlot_id'],
					'lotrecsite' => $_POST['txtlotrecsite'],
					'lotrecuserid' => $_POST['txtlotrecuserid'],
					'lotrecbottleno' => $_POST['lotrecbottleno'.$value],
					'lotrecbottleqty' => $_POST['txtlotrecbottleno'],
					'lotstatus' => "yes",
					'id' => $value
				);
				$query = $GLOBALS['sql']->insert_query (
					$this->table_name,
					$this->variables
				);
				//print $query;
				$result = $GLOBALS['sql']->query( $query );
			}
			
			$GLOBALS['display_buffer'] = "Please <a href=\"dosing_functions.php?action=type&type=dosinginventory\">Click here</a> to go on Medication Inventory or <a href=\"module_loader.php?module=NewLotOpen&action=addform\">Click here</a> to add new";
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
			>".__("Medication Inventory")."</a>
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

		/*
		$sql->query("SELECT *,CASE dosegiven WHEN 1 THEN 'dosed' WHEN 2 THEN 'mistake' ELSE 'not dosed' END AS _dosestatus FROM ".$this->table_name." ".
			"WHERE ".$this->patient_field."='".addslashes($patient)."' ".
			freemed::itemlist_conditions(false)." ".
			"ORDER BY ".$this->order_by),
		*/
		if($_GET['id']!="")
		{
			$result=$sql->query("SELECT lotreg.id, lotreg.lotrecno, newlotopen.lotrecdate  FROM ".$this->table_name.", lotreg where lotreg.id = newlotopen.lot_id and newlotopen.id=".$_GET['id']." GROUP BY lotreg.id");
			$row=$sql->fetch_array($result);
			$display_buffer = "
				<script>
				function redirect(){
					window.location='module_loader.php?module=NewLotOpen&action=view&return=manage';
				}
				</script>
				<table align='center'>
					<tr>
						<th colspan='2'>New Lot Receipt</th>
					</tr>
					<tr>
						<td>Lot Number :</td>
						<td>".$row['lotrecno']."</td>
					</tr>
					<tr>
						<td>Date Open :</td>
						<td>".$row['lotrecdate']."</td>
					</tr>
					<!--
					<tr>
						<td>Date Closed :</td>
						<td></td>
					</tr>
					<tr>
						<td>Orig. Qty :</td>
						<td>".$row['']."</td>
					</tr>
					<tr>
						<td>Used Amt :</td>
						<td>".$row['']."</td>
					</tr>
					-->
					<tr>
						<td>Status :</td>
						<td>".$row['lotstatus']."</td>
					</tr>
					<tr>
						<td valign='top'>Bottles :</td>
						<td>";
			$bottle_result=$sql->query("SELECT newlotopen.lotrecbottleno FROM newlotopen, lotreg where lotreg.id = newlotopen.lot_id AND lotreg.id='".$row['id']."'");
			while (	$bottle_row=$sql->fetch_array($bottle_result) ) {
				$display_buffer .= $bottle_row['lotrecbottleno']."<br/>";
			}
			$display_buffer .= "
						</td>
					</tr>
					<tr>
						<td colspan='2' align='center'><input type='button' value='Back' onclick='redirect();'></td>
					</tr>
				</table>";
		}
		else
		{
			global $_ref; $_ref = 'dosing_functions.php?action=type&type=dosinginventory';
			$display_buffer .= freemed_display_itemlist (
	
				$sql->query("SELECT newlotopen.id, lotreg.lotrecno, newlotopen.lotrecdate FROM ".$this->table_name.", lotreg WHERE lotreg.id = newlotopen.lot_id ".freemed::itemlist_conditions(false)." GROUP BY lotrecno ORDER BY newlotopen.lotrecdate DESC"),
				$this->page_name,
				array(
				__("Lot Number") =>	"lotrecno",
				__("Date Open") =>	"lotrecdate",
				//__("Date Closed") =>	"",
				//__("Orig. Qty") =>	"",			
				//__("Used Amt") =>	"",
				__("Status") =>	"lotstatus",
				), 
				NULL, NULL, NULL, NULL,
				ITEMLIST_VIEW
			);
		}
	} // end method view

	function prtStep1($var=""){
		$str = "<div>
				<table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" align=center>
				  <tr> 
					<td width=\"50%\">&nbsp;</td>
					<td width=\"50%\">&nbsp;</td>
				  </tr>
				  <tr> 
					<td align=\"left\" class=\"top-data\">Date </td>
					<td>".fm_date_entry("txtlotrecdate")."</td>
				  </tr>
				  <tr>
					<td align=\"left\" class=\"top-data\">Lot Number</td>
					<td>".module_function('LotReceipt', 'getLotNos', array('txtlot_id'))."</td>
				  </tr>
				  <tr> 
					<td align=\"left\" class=\"top-data\">Site</td>
					<td>".module_function('facilitymodule', 'widget', array('txtlotrecsite'))."</td>
				  </tr>
				  <tr>
					<td align=\"left\" class=\"top-data\">Number of Bottles Received:</td>
					<td><input type=\"text\" id='txtidlotrecbottleno' name=\"txtlotrecbottleno\" value=\"".$var["txtlotrecbottleno"]."\"></td>
				  </tr>
				  <tr> 
					<td align=\"left\" class=\"top-data\">Received By:</td>
					<td class=\"top-data\">".$GLOBALS['this_user']->user_name."</td>
				  </tr>
				  <tr>
				  	<td class=\"top-data\">Bottle numbers received:
					<td class=\"top-data\"> 
						<div id='idBtlNo'></div>
					</td>
				  </tr>
		  		</table>
				<script>
					function getbtlno(){
						document.getElementById('idBtlNo').innerHTML = 'Loading...';
						x_module_html('lotreceipt', 'getAjxBottleTable', document.getElementById('txtlot_id').value, test);
						x_module_html('lotreceipt', 'getAjxSiteID', document.getElementById('txtlot_id').value, test1);
					}
					function test( value ){
						document.getElementById('idBtlNo').innerHTML = value;
					}
					
				
					function test1( value){
						var obj = document.getElementById('txtlotrecsite');
						var i ;
						for (i = 0;i <= obj.length;i++){
							if (obj.options[i].value == value){
								obj.options[i].selected = true;
								return true;
							}
						}
					}
					
					var d = new Date();
					var month =0;
					if (d.getMonth() < 10){
						month = \"0\"+ (d.getMonth() + 1)
					} else {
						month = d.getMonth() + 1;
					}

					document.form_0.txtlotrecdate.value= d.getFullYear() + '-' + (month) + '-' + d.getDate();
					//document.getElementById(\"txtlotrecdate\").value = d.getYear() + '-' + d.getMonth() + '-' + d.getDate();
					function updateval(obj){
						//return true;
						var val = document.getElementById('txtidlotrecbottleno').value;
						if (obj.checked) {
							//alert(val);
							if(val=='')
							{
								val=0;
							}
							val = parseInt(val) + 1;
						} else {
							val = val - 1;
						}
						document.getElementById('txtidlotrecbottleno').value = val;
					}
				</script>
			</div>	
				";
		return $str;	
	}

	function addform_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=manage\">New Lot Receipt</a>
		";
	} // end function summary_bar

} // end class LotReceipt
register_module("NewLotOpen");
?>
