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

class LotReceipt extends MaintenanceModule {
	var $MODULE_NAME = "LotReceipt";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Lots Management';
	var $table_name = 'lotreceipt';		// need to change
	var $order_by = 'lotrecno DESC';
//	var $widget_hash = "##id## ##lotrecno## (##id## ##lotrecno##)";
	var $widget_hash = "##id## [##lotrecbottleno ##]";
	
	function LotReceipt ( ) {
	
		$this->table_definition = array (
			'lotrecdate' => SQL__DATE,
			'lotrecno' => SQL__VARCHAR(50),
			'lotrecsite' => SQL__INT_UNSIGNED(0),
			'lotrec20k' => SQL__INT_UNSIGNED(0),
			'lotrec40k' => SQL__INT_UNSIGNED(0),
			'lotrecuserid' => SQL__INT_UNSIGNED(0),
			'lotrecbottleno' => SQL__VARCHAR(50),
			'lotrecbottleqty' => SQL__INT_UNSIGNED(0),
			'lotrecbottleused' => SQL__INT_UNSIGNED(0),
			'lotrecmfgdate' => SQL__DATE,
			'lotrecexpdate' => SQL__DATE,
			'lotsupplrefno' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);

		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }
		
		$this->variables = array (
			'lotrecdate',
			'lotrecno',
			'lotrecsite',
			'lotrec20k',
			'lotrec40k',
			'lotrecuserid',
			'lotrecbottleno',
			'lotrecmfgdate' ,
			'lotsupplrefno' ,
			'lotrecexpdate',
			'lotrecbtlqty'
		);

		$this->summary_vars = array (
			__("User") => "doseplanuser:user",
			__("Date") =>	"lotrecdate",
			__("Lot Number") =>	"lotrecno",
			__("Total Bottles Manufactured") =>	"",
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
		$w = CreateObject( 'PHP.wizard', array ( 'been_here', 'action', 'module', 'return', 'patient' ) );
		$w->set_cancel_name(__("Cancel"));
	//	$w->set_finish_name(__("Save"));
		$w->set_finish_name(__("Finish"));
		$w->set_previous_name(__("Previous"));
		$w->set_next_name(__("Next"));
		$w->set_refresh_name(__("Refresh"));
		$w->set_revise_name(__("Revise"));
		$w->set_width('100%');

		$w->add_page ( 'Lot Management',
			array (
				'txtDate', 
				'txtLotNo',
				'txtSite',
				'txtlotno',
				'txt20k',
				'txt40k',
			),
				html_form::form_table(array(			
			__(" ") => $this->prtStep1($_POST))
			)			
		);
		// Process of next step, variables from previous fields
		
		$w->add_page ( 'Bottle Manufacture',
			array (
				'txtDate', 
				'txtLotNo',
				'txtSite',
				'txt20k',
				'txt40k',
				'lotrecbottleno',
				'lotrecmfgdate',
				'lotrecexpdate',
				'lotrecbottleqty'
			),
			html_form::form_table(array(			
			__(" ") => $this->prtStep2($_POST["txtTQty"]))
			)			
		);
		// Finally, display wizard
		if (! $w->is_done() and ! $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] = $w->display();
		}
		if ( $w->is_done() ) {
			// Final insertion of the
			//new lot entry to generate saperate lot id
			$this->variables = array(
				'lotrecdate' => $_POST['txtDate'],
				'lotrecno' => $_POST['txtLotNo'],
				'lotstatus' => 'open',
				'lotcreateddate' => date('Y-m-d')
			);
			$query = $GLOBALS['sql']->insert_query (
				'lotreg',
				$this->variables
			);
		
			$result = $GLOBALS['sql']->query($query);			
			$q = $GLOBALS['sql']->query("SELECT id FROM lotreg WHERE lotrecno = '".addslashes($_POST['txtLotNo'])."'");
			$q = $GLOBALS['sql']->fetch_array($q);
		
			// new lot entry completed
			for ($i = 1;$i <= $_POST["txthQty"];$i++){
				if ($_POST['txtbottleno1_'.$i] != "" || $_POST['txtmanfdate1_'.$i] != '' || $_POST['txtexpdate1_'.$i] != ''){
					$this->variables = array (
						'lotrecdate' => ($_POST['txtDate']),
						'lotrecno' => $q['id'],
						'lotrecsite' => $_POST['txtSite'],
						'lotrec20k' => $_POST['txt20k'],
						'lotrec40k' => $_POST['txt40k'],
						'lotrecuserid' => $GLOBALS['this_user']->user_number,
						'lotrecbottleno' => $_POST['txtbottleno1_'.$i],
						'lotrecmfgdate' => $_POST['txtmanfdate1_'.$i] ,
						'lotrecexpdate' => $_POST['txtexpdate1_'.$i],
						'lotrecbottleqty' => $_POST['txtbottleqty_'.$i],
						'lotrecbottleused' => 0,
					);  // change $_POST['txtid20kk'] to $_POST['txt20k'] same ro 40 k by raju
					$query = $GLOBALS['sql']->insert_query (
						$this->table_name,
						$this->variables
					);
//					print $query."\n";
					syslog( LOG_INFO, $query );
					$result = $GLOBALS['sql']->query($query);
					$GLOBALS['display_buffer'] = "Please <a href=\"dosing_functions.php?action=type&type=dosinginventory\">Click here</a> to go on Medication Inventory or <a href=\"module_loader.php?module=LotReceipt&action=addform\">Click here</a> to add new";
				} else
				{
					$error = "Please Enter all fields value.";
				}
			}
		}
				
		global $refresh;
		if ($GLOBALS['return'] == 'manage') {
			  $refresh = 'dosing_functions.php?action=type&type=dosinginventory';
		}
		
		if ( $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] .= "
			<p/>
			<div ALIGN=\"CENTER\"><b>".__("Cancelled")."</b></div>
			<p/>
			<div ALIGN=\"CENTER\">
			<a HREF=\"dosing_functions.php?action=type&type=dosinginventory\"
			>".__("Return to Medication Inventory")."</a>
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

		$display_buffer = freemed_display_itemlist (
			$sql->query("SELECT lotreg.lotrecno,lotreg.lotrecdate,sum(`lotrec20k`) + sum(`lotrec40k`) totbottles FROM lotreg, lotreceipt WHERE lotreceipt.lotrecno = lotreg.id GROUP BY lotreg.id"),
			$this->page_name,
			array(
				__("Date") =>	"lotrecdate",
				__("Lot Number") =>	"lotrecno",
				__("Total Bottles Manufactured") =>	"totbottles"
			), NULL, NULL, NULL, NULL,
                        ITEMLIST_VIEW
		);

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
					<td>".fm_date_entry("txtDate")."</td>
				  </tr>
				  <tr>
					<td align=\"left\" class=\"top-data\">Lot Number</td>
					<td><input type=\"text\" name=\"txtLotNo\" value=\"".$this->getNewLotNo()."\" readonly></td>
				  </tr>
				  <tr> 
					<td align=\"left\" class=\"top-data\">Site</td>
					<td>".module_function('facilitymodule', 'widget', array('txtSite'))."</td>
				  </tr>
				  <tr class=\"menuBar\">
				  	<td colspan=2 align=\"center\" class=\"top-data\">Bottle Preparation</td>
				  </tr>
				  <tr>
					<td align=\"left\" class=\"top-data\">Total number of 20000 mgs bottles in this lot:</td>
					<td><input type=\"text\" id='txtid20k' onchange='javascript:if(document.getElementById(\"txtid20k\").value==\"\"){document.getElementById(\"txtid20k\").value=0;}if(document.getElementById(\"txtid40k\").value==\"\"){document.getElementById(\"txtid40k\").value=0;}document.getElementById(\"txtIDTQty\").value=parseInt(document.getElementById(\"txtid20k\").value) + parseInt(document.getElementById(\"txtid40k\").value)' name=\"txt20k\" value=\"".$var["txt20k"]."\"></td>
				  </tr>
				  <tr> 
					<td align=\"left\" class=\"top-data\">Total number of 40000 mgs bottles in this lot:</td>
					<td><input type=\"text\" id='txtid40k' onchange='javascript:if(document.getElementById(\"txtid20k\").value==\"\"){document.getElementById(\"txtid20k\").value=0;}if(document.getElementById(\"txtid40k\").value==\"\"){document.getElementById(\"txtid40k\").value=0;}document.getElementById(\"txtIDTQty\").value=parseInt(document.getElementById(\"txtid20k\").value) + parseInt(document.getElementById(\"txtid40k\").value)' name=\"txt40k\" value=\"".$var["txt40k"]."\"></td>
				  </tr>
				   <tr>
					<td align=\"left\" class=\"top-data\">Total number of bottles:</td>
					<td><input type=\"text\" id=\"txtIDTQty\" name=\"txtTQty\" onchange='javascript:document.getElementById(\"txtIDTQty\").value=parseInt(document.getElementById(\"txtid20k\").value) + parseInt(document.getElementById(\"txtid40k\").value)' value=\"".$var["txtTQty"]."\"></td>
				  </tr>
				  <tr> 
					<td align=\"left\" class=\"top-data\">User</td>
					<td class=\"top-data\">".$GLOBALS['this_user']->user_name."</td>
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
					document.getElementById(\"txtDate_cal\").value = d.getFullYear() + '-' + (month) + '-' + d.getDate();
				</script>				
			</div>	
				";
		return $str;	
	}
	
	function prtStep2($tQty){
		global $_POST;
		if ((int) $tQty == 0){
			$str = " You have not entered any value for Total Qty.<br> Click on <strong><font color=red>previous</font></strong> to go back";
		} else {
			$str="<input type='hidden' value='".$tQty."' name='txthQty'>
			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
			<tr> 
			  <td><strong>Sr.</strong></td>
			  <td><strong>Bottle No.</strong></td>
			  <td><strong>Bottle Qty.</strong></td>			  
			  <td><strong>Bulk Ref. No</strong></td>			  			  
			  <td><strong>Manufacturer Date</strong></td>
			  <td><strong>Expiry Date</strong></td>
			</tr>
			<tr> 
			  <td colspan=\"3\">&nbsp;</td>
			</tr>";
			for ($i=1;$i<=$tQty;$i++){
				if ($_POST['txt20k'] >= $i){
					$qty = 20000;
				} else {
					$qty = 40000;
				}
				$str .=	"<tr> 
					  <td> $i. </td>
					  <td><input type=\"text\" name=\"txtbottleno1_$i\"></td>
					  <td><input type=\"text\" name=\"txtbottleqty_$i\" value=\"$qty\"></td>
					  <td>".module_function('lotmgt', 'getSupplNo', array('lstsupplno_'.$i))."</td>
					  <td>".fm_date_entry("txtmanfdate1_$i")."</td>
					  <td>".fm_date_entry("txtexpdate1_$i")."</td>
					</tr>";
			}
			$str .= "
				<tr>
					<td colspan=4> <strong>Note:</strong>  <font color='red'> If Bottleno or Manufacturing Date or Expiry date is blank then Lot will not be saved. </font> </td>
				</tr>
		  </table>";
		}
	  return $str;
	}
	
	function getBottleNos( $selectName) {
		global $btlno;
		if ( $_SESSION['dosing']['btlno'] ) {
			$btlno = $_SESSION['dosing']['btlno'];
		}
		$q = $GLOBALS['sql']->query("SELECT lotrecbottleno, id FROM lotreceipt order by lotrecno");
		while ($r = $GLOBALS['sql']->fetch_array($q)) {
			$ar[$r['id']] = $r['lotrecbottleno'];
		}
		return html_form::select_widget($selectName, $ar);
	} // end get lot numbers
	
	function getAjaxBottleNos( $selectName) {
		global $btlno;
		if ( $_SESSION['dosing']['btlno'] ) {
			$btlno = $_SESSION['dosing']['btlno'];
		}
		$q = $GLOBALS['sql']->query("SELECT  lotrecbottleno , lotreceipt.id FROM lotreceipt, lotreg WHERE  lotreceipt.lotrecno  = lotreg.id AND lotreg.id = '".addslashes($selectName)."' order by lotreg.lotrecno");
		$ar[0] = "Select Bottle";
		while ($r = $GLOBALS['sql']->fetch_array($q)) {
			$ar[$r['lotrecbottleno']] = $r["id"];
		}
		return html_form::select_widget("btlno", $ar );
	} // end get lot numbers

	function getAjxBottleNos( $sName ) {
		if ( strpos( $sName, ',' ) !== false ) {
			list ( $selectName, $widgetName ) = explode(',', $sName);
		} else {
			$selectName = $sName;
			$widgetName = 'btlno';
		}
		global $btlno;
		if ( $_SESSION['dosing']['btlno'] ) {
			$btlno = $_SESSION['dosing']['btlno'];
		}
		$q = $GLOBALS['sql']->query("SELECT  lotrecbottleno , lotreceipt.id FROM lotreceipt, lotreg WHERE  lotreceipt.lotrecno  = lotreg.id AND lotreg.id = '".addslashes($selectName)."' order by lotreg.lotrecno");
		$ar[0] = "Select Bottle";
		while ($r = $GLOBALS['sql']->fetch_array($q)) {
			$ar[$r['lotrecbottleno']] = $r["id"];
		}
		return html_form::select_widget($widgetName, $ar );//, array('array_index' => -1));
	} // end get lot numbers


	function getAjxBottleRec( $selectName) {		// FUNCTION FOR GENERATING BOTTLE DROPDOWNLIST
		$q = $GLOBALS['sql']->query("SELECT  lotrecbottleno , lotreceipt.id FROM lotreceipt, lotreg WHERE  lotreceipt.lotrecno  = lotreg.id AND lotreg.id = '".$selectName."' order by lotreg.lotrecno");
		//echo "SELECT  lotrecbottleno , lotreceipt.id FROM lotreceipt, lotreg WHERE  lotreceipt.lotrecno  = lotreg.id AND lotreg.lotrecno = '".$selectName."' order by lotreg.lotrecno";
		//print "SELECT  lotrecbottleno , id FROM lotreceipt WHERE lotrecno = '".$selectName."' order by lotrecno";
		//return "SELECT  lotrecbottleno , id FROM lotreceipt WHERE lotrecno = '".$selectName."' by lotrecno";
		$ar[0] = "Select Bottle";
		while ($lastr = $GLOBALS['sql']->fetch_array($q)) {
			$key = $lastr["lotrecbottleno"];
			$ar[$key] = $lastr["id"];
		}
		return html_form::select_widget("btlno", $ar, array('array_index' => -1,'on_change' => "javascript:getBtlInfo(123);"));
	} // end get lot numbers


	function getLotNosForWizard( $selectName ) {
		$loc = $_SESSION['default_facility'];
		syslog(LOG_DEBUG, "SELECT  lotreg.lotrecno, lotreg.id FROM lotreg JOIN lotreceipt ON lotreceipt.lotrecno=lotreg.id WHERE lotrecsite='".addslashes($loc)."'");
		$q = $GLOBALS['sql']->query("SELECT  lotreg.lotrecno, lotreg.id FROM lotreg JOIN lotreceipt ON lotreceipt.lotrecno=lotreg.id WHERE lotrecsite='".addslashes($loc)."'");
		$ar[0] = "Please Select";
		while ($lastr = $GLOBALS['sql']->fetch_array($q)) {
			$key = $lastr["lotrecno"];
			$ar[$key] = $lastr["id"];
		}
		$var = html_form::select_widget($selectName, $ar,array('on_change' => "getBottleNumbers(this.value,this.name);"));
		return $var;
	} // end get lot numbers

	function getLotNos( $selectName ) {
		$q = $GLOBALS['sql']->query("SELECT  lotrecno, id FROM lotreg");
		$ar[0] = "Please Select";
		while ($lastr = $GLOBALS['sql']->fetch_array($q)) {
			$key = $lastr["lotrecno"];
			$ar[$key] = $lastr["id"];
		}
		$var = html_form::select_widget($selectName, $ar,array('on_change' => "getbtlno(this.value,this.name);"));
		return $var;
	} // end get lot numbers
	
	function getNewLotNo() {
		$q1 = $GLOBALS['sql']->query("SELECT max(lotrecno) lotno FROM lotreg where lotcreateddate  = '".date('Y-m-d')."'");
		$q = $GLOBALS['sql']->fetch_array($q1);
		if ($q['lotno']=='NULL' || $q['lotno'] == ''){
			$q['lotno'] = date('Ymd')."001";
		}else {
			$q['lotno'] += 1;
		}
		return $q['lotno'];

	}
	
	function getBottleQty($id){
		$q1 = $GLOBALS['sql']->query("SELECT lotrecbottleno, id, lotrecbottleqty FROM lotreceipt Where id = $id ");
		$q = $GLOBALS['sql']->fetch_array($q1);
		return $q["lotrecbottleqty"];
	}
	
	function addform_link () {
		return "
		<a HREF=\"module_loader.php?module=".
		get_class($this)."&action=view&return=manage\">Lots Management</a>
		";
	} // end function summary_bar

	function getAjxSiteID($selectName){
		$q = $GLOBALS['sql']->query("SELECT  lotrecsite FROM lotreceipt, lotreg WHERE  lotreceipt.lotrecno  = lotreg.id AND lotreg.id = '".$selectName."' order by lotreg.lotrecno");
		$lastr = $GLOBALS['sql']->fetch_array($q);
		return $lastr['lotrecsite'];
	}

	function getAjxBottleTable( $selectName) {		
		//echo "SELECT Distinct lotreceipt.lotrecbottleno, lotreceipt.id FROM lotreceipt, lotreg, newlotopen WHERE lotreceipt.lotrecno = lotreg.id AND lotreg.id = '".$selectName."' AND lotreceipt.lotrecbottleno != newlotopen.lotrecbottleno ORDER BY lotreg.lotrecno";
		//$q = $GLOBALS['sql']->query("SELECT Distinct lotreceipt.lotrecbottleno, lotreceipt.id FROM lotreceipt, lotreg, newlotopen WHERE lotreceipt.lotrecno = lotreg.id AND lotreg.id = '".$selectName."' AND lotreceipt.lotrecbottleno != newlotopen.lotrecbottleno ORDER BY lotreg.lotrecno");
		
		$q = $GLOBALS['sql']->query("SELECT  lotrecbottleno , lotreceipt.id FROM lotreceipt, lotreg WHERE  lotreceipt.lotrecno  = lotreg.id AND lotreg.id = '".$selectName."' order by lotreg.lotrecno");
		$var = "<table cellspacing=1 celpadding=0 border=0 width=100% style='border:1px solid blue'>
					<tr>
						<th class=\"top-data\" bgcolor='blue'><font color='#ffffff'> Bottle No </font></th> 
						<th class=\"top-data\" bgcolor='blue'><font color='#ffffff'> Received  </font></th>
					</tr>
			";
		while ($lastr = $GLOBALS['sql']->fetch_array($q)) {
		
			$key = $lastr["lotrecbottleno"];
			$ar[$key] = $lastr["id"];
			$var .= "<tr>
						<td bgoclor='#ffffff'> ".$lastr["lotrecbottleno"]."<input type='hidden' name='lotrecbottleno".$lastr["id"]."' value='".$lastr["lotrecbottleno"]."'> </td>
						<td bgoclor='#ffffff' align='center'> <input type=checkbox value='".$lastr["id"]."' name='chkbotrecno_[]' onclick='javascript:updateval(this)'> <td>
					 </tr>
				";
		}
		
		$var .= "</table>";
		return $var;
	} // end get Bottle numbers table


} // end class LotReceipt


register_module("LotReceipt");

?>
