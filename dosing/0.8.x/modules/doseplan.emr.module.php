<?php
  // $Id$
  //
  // Authors:
  //      Jeff Buchbinder <jeff@freemedsoftware.org>
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

class DosePlan extends EMRModule {
	var $MODULE_NAME = "Dose Plan";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Dose Plan';
	var $table_name = 'doseplan';
	var $patient_field = 'doseplanpatient';
//	var $widget_hash = '##doseplandose## ##doseplanunits## (##doseplancomment##)';
	var $widget_hash = '##doseplandose## ##doseplanunits##';
	var $order_by = 'id';

	function DosePlan ( ) {
		$this->table_definition = array (
			'doseplaneffectivedate' => SQL__DATE,
			'doseplanstartdate' => SQL__DATE,
			'doseplanpatient' => SQL__INT_UNSIGNED(0),
			'doseplandose' => SQL__INT_UNSIGNED(0),
			'doseplanunits' => SQL__CHAR(10),
			'doseplantype' => SQL__VARCHAR(50),
			'doseplanexceptiontype' => SQL__VARCHAR(50),
			'doseplantakehomesched' => SQL__CHAR(7),
			'doseplanuser' => SQL__INT_UNSIGNED(0),
			'doseplansplit' => SQL__INT_UNSIGNED(0),
			'doseplansplit1' => SQL__INT_UNSIGNED(0),
			'doseplansplit2' => SQL__INT_UNSIGNED(0),
			'doseplantakehomecountgiven' => SQL__INT_UNSIGNED(0),
			'doseplantakehomecountreturned' => SQL__INT_UNSIGNED(0),
			'doseplanincrementationtype' => SQL__VARCHAR(50),
			'doseplanincrementationschedule' => SQL__VARCHAR(250),
			'doseplanlength' => SQL__INT_UNSIGNED(0),
			'doseplanmedicalorders' => SQL__TEXT,
			'doseplancomment' => SQL__TEXT,
			'doseplanpickupdate' => SQL__DATE,
			'doseplanreturndate' => SQL__DATE,
			'doseplanactive' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);

		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }

		$this->variables = array (
			'doseplaneffectivedate' => fm_date_assemble('doseplaneffectivedate'),
			'doseplanstartdate' => fm_date_assemble('doseplanstartdate'),
			'doseplanuser' => $GLOBALS['this_user']->user_number,
			'doseplanpatient' => $_REQUEST['patient'],
			'doseplandose',
			'doseplanunits',
			'doseplantype',
			'doseplanexceptiontype',
			'doseplantakehomesched' => ( is_array($_REQUEST["takehomesched"]) ? join(',', $_REQUEST['takehomesched']) : '' ),
			'doseplansplit',
			'doseplansplit1',
			'doseplansplit2',
			'doseplantakehomecountgiven' => $_POST["takehomegiven"],
			'doseplanincrementationtype',
			'doseplanincrementationschedule' => join(',', $_REQUEST['doseplan']),
			'doseplanlength' => count( $_REQUEST['doseplan'] ),
			'doseplanmedicalorders',
			'doseplancomment',
			'doseplanpickupdate' => fm_date_assemble('doseplanpickupdate'),
			'doseplanreturndate' => fm_date_assemble('doseplanreturndate'),
			'doseplanactive' => 1
		);

		$this->summary_vars = array (
			__("User")    =>	"doseplanuser:user",
			__("Effective")    =>	"doseplaneffectivedate",
			__("Start")    =>	"doseplanstartdate",
			__("Length")    =>	"doseplanlength",
			__("Comment") =>	"doseplancomment"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'doseplaneffectivedate';

		$this->order_by = 'doseplaneffectivedate DESC';

		$this->_SetHandler('DosingFunctions', 'transferForm');
		$this->_SetMetaInformation('DosingFunctionName', __("Change Bottle"));
		$this->_SetMetaInformation('DosingFunctionDescription', __("Change bottle for dosing station.") );

		// Set associations
		$this->EMRModule();
	} // end constructor DosePlan

	function transferForm ( ) {
                ob_start();
                include_once ('change_bottle.php');
                $GLOBALS['display_buffer'] .= ob_get_contents();
                ob_end_clean();
		return true;
	}

	function modform ( ) { }
	function mod ( ) { }
	function del ( ) { }

	function addform ( ) {
		if (!$_REQUEST['been_here'] and !$GLOBALS['been_here']) {
			$_REQUEST['doseplaneffectivedate'] = $GLOBALS['doseplaneffectivedate'] = date('Y-m-d');
			$_REQUEST['doseplanstartdate'] = $GLOBALS['doseplanstartdate'] = date('Y-m-d');
			$_REQUEST['been_here'] = $GLOBALS['been_here'] = 1;
		}

		$w = CreateObject( 'PHP.wizard', array ( 'been_here', 'action', 'module', 'return', 'patient' ) );
		$w->set_cancel_name(__("Cancel"));
		$w->set_finish_name(__("Finish"));
		$w->set_previous_name(__("Previous"));
		$w->set_next_name(__("Next"));
		$w->set_refresh_name(__("Refresh"));
		$w->set_revise_name(__("Revise"));
		$w->set_width('100%');

		$w->add_page ( 'Step One',
			array (
				'doseplaneffectivedate',
				'doseplanstartdate',
				'doseplandose',
				'doseplanunits',
				'doseplantype',
				'doseplanexceptiontype',
				'doseplansplit',
				'doseplansplit1',
				'doseplansplit2',
				'doseplanincrementationtype',
				'doseplandec',
				'doseplandays',
				'takehomes',
				'takehomesched',
			),
			html_form::form_table(array(
			__("Effective Date") => fm_date_entry('doseplaneffectivedate'),
			__("Starting Date") => fm_date_entry('doseplanstartdate'),
			__("Dosing Plan") =>
				html_form::select_widget('doseplantype', array(
					'Regular Methadone Dosing' => 'regular-methadone',
					'Incremental Methadone Dosing' => 'incremental-methadone',
					'Exception Dosing' => 'exception'
				),array(
					'on_change' => 'adjustExceptionView()'
				))."
			<div id=\"exceptionView\" style=\"display: ".( $_REQUEST['doseplantype']=='exception' ? 'block' : 'none' ).";\">
			<div>Exception Type ".html_form::select_widget('doseplanexceptiontype', array(
				'Courtesy Leave',
				'Hardship Leave',
				'Sick Leave',
				'Vacation',
				'Jail',
				'Other'
			))."</div>
			</div>	

			<script language=\"javascript\">
			function adjustExceptionView() {
				var eV = document.getElementById('exceptionView');
				var iV = document.getElementById('incrementationViewWidget');
				var iVl = document.getElementById('incrementationViewLabel');
				if ( document.getElementById('doseplantype').value == 'exception' ) {
					eV.style.display = 'block';
				} else {
					eV.style.display = 'none';
				}
				if ( document.getElementById('doseplantype').value == 'incremental-methadone' ) {
					iV.style.display = 'block';
					iVl.style.display = 'block';
				} else {
					iV.style.display = 'none';
					iVl.style.display = 'none';
				}
			}
			</script>
			",			"<span id=\"incrementationViewLabel\" style=\"display:none;\">".__("Incrementation Type")."</span>" =>
				"<div id=\"incrementationViewWidget\" style=\"display:none;\" >".
				html_form::select_widget('doseplanincrementationtype', array(
					'NONE' => 'none',
					'Administrative' => 'administrative',
					'Behavioral' => 'behavioral',
					'Titration Increase' => 'titration-increase',
					'Titration Decrease' => 'titration-decrease',
					'Voluntary' => 'voluntary',
					'Financial' => 'financial'
				), array(
					'on_change' => 'adjustIncrementationView()'
				))."</div>
			<div id=\"incrementationView\" style=\"display: ".( ($_REQUEST['doseplanincrementationtype']=='none' or $_REQUEST['doseplanincrementationtype']=='') ? 'none' : 'block' ).";\">
			<div id=\"dayCountView\" style=\"display: ".( ($_REQUEST['doseplanincrementationtype']=='titration-decrease' or $_REQUEST['doseplanincrementationtype']=='titration-increase') ? 'block' : 'none' ).";\">".__("Number of Days")." : ".html_form::text_widget("doseplandays")."</div>
			<div id=\"decView\" style=\"display: ".( ($_REQUEST['doseplanincrementationtype']=='behavioral' or $_REQUEST['doseplanincrementationtype']=='voluntary' or $_REQUEST['doseplanincrementationtype']=='administrative') ? 'block' : 'none' ).";\">".__("Daily Decrease Dose")." : ".html_form::text_widget("doseplandec")."</div>
			</div>	

			<script language=\"javascript\">
			function adjustIncrementationView ( ) {
				var iV = document.getElementById('doseplanincrementationtype').value;
				if (iV != 'none') {
					document.getElementById('incrementationView').style.display = 'block';
				} else {
					document.getElementById('incrementationView').style.display = 'none';
				}

				if ((iV == 'behavioral') || (iV == 'voluntary') || (iV == 'administrative')) {
					document.getElementById('decView').style.display = 'block';

				} else {
					document.getElementById('decView').style.display = 'none';
				}

				if ((iV == 'titration-increase') || (iV == 'titration-decrease')) {
					document.getElementById('dayCountView').style.display = 'block';
				} else {
					document.getElementById('dayCountView').style.display = 'none';
				}
			}
			</script>
			",
			__("Dosage") =>
				"<input type=\"text\" id=\"doseplandose\" name=\"doseplandose\" value=\"".htmlentities($_REQUEST['doseplandose'])."\" /> ".
				html_form::select_widget("doseplanunits",
					array (
						"mg" => "mg"
					)
				),
			__("Split Dose?") =>
				"<input type=\"radio\" id=\"doseplansplity\" name=\"doseplansplit\" value=\"1\" onClick=\"splitDose(1); return true;\" ".( $_REQUEST['doseplansplit']==1 ? "CHECKED" : "" )."><label for=\"doseplansplity\">".__("Yes")."</label> ".
				"<input type=\"radio\" id=\"doseplansplitn\" name=\"doseplansplit\" value=\"0\" onClick=\"splitDose(0); return true;\" ".( $_REQUEST['doseplansplit']!=1 ? "CHECKED" : "" )."><label for=\"doseplansplitn\">".__("No")."</label> ".
				"<script language=\"javascript\">
				function checkSplitDose ( ) {
				
					if (document.getElementById('splitDoseDiv').style.display == 'block') {						if ( ( parseInt(document.getElementById('doseplansplit1').value) + parseInt(document.getElementById('doseplansplit2').value) ) != parseInt(document.getElementById('doseplandose').value) ) {
							alert('Split dose values must add up to the total value of the dose!');
							document.getElementById('doseplansplit1').value = '0';
							document.getElementById('doseplansplit2').value = '0';
							document.getElementById('doseplansplit1').focus();
							return false;
						}
					}
					// Assume true if there's no split dose
					return true;
				}
				</script>
				<div id=\"splitDoseDiv\" style=\"display: ".( $_REQUEST['doseplansplit']==1 ? 'block' : 'none' ).";\">
					<input type=\"text\" id=\"doseplansplit1\" name=\"doseplansplit1\" />
					<input type=\"text\" id=\"doseplansplit2\" name=\"doseplansplit2\" onBlur=\"return checkSplitDose();\" />
				</div>
				",
			__("Take Home Schedule") => '
			<input type="checkbox" id="takehomes" name="takehomes" onClick="toggletakehomes();" /><label for="takehomes">Enable take home doses</label>
			<br/>
			<div id="takehomecontainer" style="display: '.( $_REQUEST['takehomes'] ? 'block' : 'none' ).';">
			<input type="checkbox" id="sun" name="takehomesched[]" value="0" /><label for="sun">Sun</label>
			<input type="checkbox" id="mon" name="takehomesched[]" value="1" /><label for="mon">Mon</label>
			<input type="checkbox" id="tue" name="takehomesched[]" value="2" /><label for="tue">Tue</label>
			<input type="checkbox" id="wed" name="takehomesched[]" value="3" /><label for="wed">Wed</label>
			<input type="checkbox" id="thu" name="takehomesched[]" value="4" /><label for="thu">Thu</label>
			<input type="checkbox" id="fri" name="takehomesched[]" value="5" /><label for="fri">Fri</label>
			<input type="checkbox" id="sat" name="takehomesched[]" value="6" /><label for="sat">Sat</label>
			</div>
			<script language="javascript">
			function toggletakehomes ( ) {
				if ( document.getElementById(\'takehomes\').checked ) {
					document.getElementById(\'takehomecontainer\').style.display = \'block\';
				} else {
					document.getElementById(\'takehomecontainer\').style.display = \'none\';
				}
			}
			function splitDose ( b ) {
				document.getElementById("splitDoseDiv").style.display = b ? "block" : "none";
				if (b == 1){
				document.getElementById("takehomes").disabled = true;
				} else {
				  document.getElementById("takehomes").disabled = false;
				}
			}
			</script>
			',
			))
		);
		
		if (!count($_REQUEST['doseplan']))
		switch ($_REQUEST['doseplanincrementationtype']) {
			case 'administrative':
			if ( $_REQUEST['doseplandose'] > 0 ) {
				$amt = (int)(( $_REQUEST['doseplandose'] / $_REQUEST['doseplandec'] ));
				for ($i = 1; $i <= $amt; $i++ ) {
					$dp[] = $_REQUEST['doseplandec'];
					$tamt += $_REQUEST['doseplandec'];
				}
				if ($_REQUEST['doseplandose']  - $tamt > 0 ){
					$dp[] =	$_REQUEST['doseplandose']  - $tamt;
				}
			}
			break; // end administrative

			case 'financial':
			if ($_REQUEST['doseplandose'] >= 50 and $_REQUEST['doseplandose'] <= 100) {
				// Between 50-100mg
				$dp = $this->figureInitialDosePlan( abs($_REQUEST['doseplandose']), -3 );
			} elseif ($_REQUEST['doseplandose'] < 50) {
				// Under 50mg
				$dp = $this->figureInitialDosePlan( abs($_REQUEST['doseplandose']), -2 );
			} elseif ($_REQUEST['doseplandose'] > 100) {
				// Over 100mg
				$dp = $this->figureInitialDosePlan( abs($_REQUEST['doseplandose']), -5, 100 );
				$dp2 = $this->figureInitialDosePlan( abs($dp[count($dp)-1]), -3 );
				unset($dp2[0]); // duplicate
				$dp = array_merge ( $dp, $dp2 );
			}
			break; // financial 

			case 'titration-increase':
			case 'titration-decrease':
			for ($i = 0; $i <= abs($_REQUEST['doseplandays']); $i++ ) {
				$dp[] = '0';
			}
			break; // titration-*

			case 'voluntary':
			case 'behavioral':
			$dp = $this->figureInitialDosePlan( abs($_REQUEST['doseplandose']), -(abs($_REQUEST['doseplandec'])) );
			break; // voluntary || behavioral

			case 'none': default:
			break;
		} // end inctype
		switch ($_REQUEST['doseplanincrementationtype']) {
			case 'administrative':
			case 'behavioral':
			case 'financial':
			case 'titration-increase':
			case 'titration-decrease':
			case 'voluntary':
				// display dates
			$date = fm_date_assemble('doseplanstartdate');
			$count = 0;
			foreach ($dp AS $dose) {
				$dpout .= "<tr><td>".$this->dow($date)." ".$date."</td><td><input type=\"text\" name=\"doseplan[]\" value=\"".( $_REQUEST['doseplan'][$count] ? $_REQUEST['doseplan'][$count] : $dose )."\" /></td></tr>\n";
				$date = $this->increment_date ( $date, 1 );
				$count++;
			}
			$w->add_page(
				__("Incrementation"),
				array ( 'doseplan' ),
				"<div><b><u>Type : ".$_REQUEST['doseplanincrementationtype']."</u></b></div>
				<table border=\"0\">
				<tr><th>Date</th><th>Dose</th></tr>".
				$dpout.
				"</table>"
			);
			break; // no fall through

			case 'none': default:
			break;
		}
		/*
		if ($tkhome!= "") { $tkhome="" } ;
		foreach ($_POST['takehomesched'] as $key => $val){
			$tkhome .= $key . "#";
		
		}*/
		if (is_array($_POST['takehomesched'])){
			$takegiven = count($_POST['takehomesched']);
		} else {
			$takegiven = count(split("#",$_POST['takehomesched']));
			$tkhome = $_POST["takehomesched"];
		}
		
		$w->add_page (
			__("Comments"),
			array (
				'doseplanpickupdate',
				'doseplanreturndate',
				'doseplantakehomecountgiven',
				'doseplanmedicalorders',
				'doseplancomment'
			),
			html_form::form_table(array(
				__("Medical Orders") => html_form::text_area('doseplanmedicalorders'),
				__("Comment") => html_form::text_area('doseplancomment')." <!-- <input type='hidden' value='".$tkhome."' name='takehomesched'><input type='hidden' value='".count($_POST['takehomesched'])."' name='takehomegiven'> -->",
				" " => 
				( $_REQUEST['doseplantype'] == 'exception' ? "
				<div>Medication Pickup Date ".fm_date_entry("doseplanpickupdate")."</div>
				<!-- <input type='hidden' value='".$tkhome."' name='takehomesched'>  -->
				<div>Return Date ".fm_date_entry("doseplanpickupdate")."</div>
				<div>Number of Doses Given ".html_form::text_widget("doseplantakehomecountgiven")."</div>
				" : "" )
			))
		);

		// Finally, display wizard
		if (! $w->is_done() and ! $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] .= $w->display();
		}
		if ( $w->is_done() ) {
			$takehomesched = '       '; // 7 spaces
			// Array is zero-based; Sunday, Monday, etc.
			foreach ( $_REQUEST['takehomesched'] as $day ) {
				$takehomesched[$day] = 'X';
			}
			$_REQUEST['takehomesched'] = $takehomesched;

			$query = $GLOBALS['sql']->insert_query (
				$this->table_name,
				$this->variables
			);
			$result = $GLOBALS['sql']->query( $query );
			$id = $GLOBALS['sql']->last_record( $result, $this->table_name );
			$query = $GLOBALS['sql']->query("SELECT distinct id FROM doseplan WHERE doseplanpatient = '".addslashes($_REQUEST['patient'] )."'");	
			$row = 0;
			while ($lastr = $GLOBALS['sql']->fetch_array($query)) {
				$row++;
				if ($id == $lastr["id"])
					continue;
				$qry = "UPDATE doseplan SET doseplanactive = 0 WHERE doseplanpatient='".addslashes($_REQUEST['patient'])."' AND id = ".$lastr["id"];
				$GLOBALS['sql']->query( $qry );				
			}
			if ($row <= 0){
				// if not any record found then it will display this thing
				$qry = "UPDATE doseplan SET doseplanactive = 1 WHERE doseplanpatient='".addslashes($_REQUEST['patient'])."' ";
				$GLOBALS['sql']->query( $qry );				
			}
			
//			var_dump($query);
//			$query = "UPDATE doseplan SET doseplanactive=0 WHERE doseplanpatient='".addslashes($_REQUEST['patient'] )."' AND id<>'".addslashes( $id )."'";
/*			$query = "UPDATE doserecord,doseplan SET doseplanactive= 0 
				WHERE doserecord.dosepatient = doseplan.doseplanpatient and 
				doserecord.dosepatient = '".addslashes($_REQUEST['patient'] )."' and doseplanid = doseplan.id";
			$GLOBALS['sql']->query( $query );
			print $query; */
//	exit;
			global $refresh;
			if ($GLOBALS['return'] == 'manage') {
			      $refresh = 'manage.php?id='.urlencode($_REQUEST['patient']);
			}
		}
		if ( $w->is_cancelled() ) {
			$GLOBALS['display_buffer'] .= "
			<p/>
			<div ALIGN=\"CENTER\"><b>".__("Cancelled")."</b></div>
			<p/>
			<div ALIGN=\"CENTER\">
			<a HREF=\"manage.php?id=$patient\"
			>".__("Manage Patient")."</a>
			</div>
			";
	
			global $refresh;
			if ($GLOBALS['return'] == 'manage') {
				$refresh = 'manage.php?id='.urlencode($_REQUEST['patient']);
			}
		}
	} // end method addform

	function view ( ) {
		global $sql; 
		global $display_buffer;
		global $patient;
		$display_buffer .= freemed_display_itemlist (
			$sql->query("SELECT ".$this->table_name.".*,userdescrip FROM ".
				$this->table_name." LEFT JOIN user ON ".
				"doseplanuser=user.id WHERE ".$this->patient_field."='".
				addslashes($patient)."' ".freemed::itemlist_conditions(false)." ".
				"ORDER BY ".$this->order_by),
			$this->page_name,
			array(
				__("User")    =>	"userdescrip",
				__("Effective")    =>	"doseplaneffectivedate",
				__("Start")    =>	"doseplanstartdate",
				__("Length")    =>	"doseplanlength",
				__("Comment") =>	"doseplancomment"
			), NULL, NULL, NULL, NULL, ITEMLIST_VIEW
		);
	} // end method view

	function figureInitialDosePlan ( $total, $increment, $stop_at = 0 ) {
		$my_total = $total;
		while ( $my_total >= 0 and $my_total >= $stop_at ) {
			if ( $my_total - abs ( $increment ) > 0 ) {
				// Record dose
				$dose[] = abs( $increment );
			} else {
				// In case the last one is a remainder
				$dose[] = $my_total;
			}
			// Take away from total (absolutely)
			$my_total -= abs( $increment );
		}
		return $dose;
	} // end method figureInitialDosePlan

	function getDosePlanForPatient ( $patient ) {
		$q = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT id, CONCAT(doseplandose, doseplanunits) AS planName FROM doseplan WHERE doseplanpatient='".addslashes($patient)."' AND doseplanactive=1 AND doseplanstartdate <= NOW() ORDER BY doseplanstartdate  DESC LIMIT 1"));
		return array( 'id' => $q['id'], 'name' => $q['planName'] );
	} // end getDosePlanForPatient

	function increment_date ( $old, $days = 1 ) {
		return date( 'Y-m-d', $this->dateToStamp($old) + (60 * 60 * 24 * $days) );
	} // end method increment_date

	function dow ( $date ) {
		return date( 'D', $this->dateToStamp($date) );
	} // end method dow

	function dateToStamp ( $date ) {
		list ( $y, $m, $d ) = explode ( '-', $date );
		return mktime ( 0, 0, 0, $m, $d, $y );
	} // end method dateToStamp

	// Method: ajax_display_dose_plan
	//
	//	Display dose plan.
	//
	// Parameters:
	//
	//	$doseplanid - Dose plan id
	//
	// Returns:
	//
	//	XHTML.
	//
	function ajax_display_dose_plan ( $doseplanid ) {
		if (!$doseplanid) { return 'NO DOSE PLAN SPECIFIED'; }
		$dp = freemed::get_link_rec( $doseplanid, $this->table_name );
		if ($dp['doseplantype'] == 'regular-methadone') {
			// Handle regular and/or split dosing
			$dt = $dp['doseplanstartdate'];
			// Don't display dose dates in the past.
			if (strtotime($dt) < strtotime(date("Y-m-d")))
				$dt = date("Y-m-d");
			$dose = $this->doseForDate( $doseplanid, $dt );
			$arTkh = split("#",$takehome);
			$buffer .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
			$buffer .= "<tr>\n\t<th>Date</th>\n\t<th>Dose</th>\n\t<th>Status</th>\n\t<th>Take Home</th>\n</tr>\n";
			// Per JC, display 31 days of dose plan.
			for ($i = 1;$i <=31;$i++){
				$d = strtotime($dt);
				$takehome = $this->doseTakeHome( $doseplanid, $dt );
				$dtmdy = date("m/d/Y",mktime(0,0,0,date("m",$d),date("d",$d),date("Y",$d)));
				$status = $this->doseStatus( $doseplanid, $dt );
				if ($status)
					$status_txt = "Dispensed";
				if ($dp['doseplansplit'] && ($status == 1))
					$status_txt = "Half-Dispensed";
				$day = $arTkh[array_search(strtolower(date("D",strtotime($dt))),$arTkh)];
				// Force no status if takehome + past
				if ( $takehome and ( strtotime($dt) < mktime()-86400 ) ) {
					$status = true;
				}
				// $day = ($day == strtolower(date("D",strtotime($dt)))?",$day":"");
				$buffer .= "<tr ".( !$status ? "onMouseOver=\"this.style.backgroundColor='#7777ff'; return true;\" onMouseOut=\"this.style.backgroundColor='transparent'; return true;\" onClick=\"try { document.getElementById('doseassigneddate_cal').value='${dt}'; } catch (err) { }; try { dojo.widget.byId( 'doseassigneddate' ).inputNode.value = '${dtmdy}'; dojo.widget.byId( 'doseassigneddate' ).onSetDate(); } catch (err) { } return true;\"" : "" )." >\n".
				"\t<td>${dt}</td>\n";
				if ($dp['doseplansplit']) 
					$buffer .= "\t<td>${dose} ${dp['doseplanunits']} (SPLIT)</td>\n";
				else
					$buffer .= "\t<td>${dose} ${dp['doseplanunits']}</td>\n";
				$buffer .= "\t<td>".( $status ? "<span style=\"color: #ff0000;\">$status_txt</span>" : "" )."</td>\n". 
				"\t<td>".( $takehome ? "<span style=\"color: #0000ff;\">Yes</span>" : "<span style=\"color: RED;\">No</span>" )."</td>\n".				
				// "\t<td>".( ( $takehome != "" && $day  != "" ) ? "<span style=\"color: #0000ff;\">Yes</span>" : "<span style=\"color: RED;\">No</span>" )." ".$day."</td>\n".				
				"</tr>\n";
				$d = strtotime($dt);
				$dt = date("Y-m-d",mktime(0,0,0,date("m",$d),date("d",$d)+1,date("Y",$d)));			
			}
			$buffer .= "</table>\n";			
			/*
			$buffer .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
			$buffer .= "<tr><td colspan=\"2\">Regular Dosing and Take Home</td></tr>\n";
			if ($dp['doseplansplit']) {
				$buffer .= "<tr><td>First Dose:</td><td>${dp['doseplansplit1']} ${dp['doseplanunits']}</td></tr>\n";
				$buffer .= "<tr><td>Second Dose:</td><td>${dp['doseplansplit2']} ${dp['doseplanunits']}</td><!-- /tr -->\n";
			} else {
				$buffer .= "<tr><td colspan=\"2\">${dp['doseplandose']} ${dp['doseplanunits']}</td>";
			}
			$buffer.= "<td>".( $takehome ? "<span style=\"color: #0000ff;\">TAKE HOME</span>" : "" )."</td></tr>\n";
			$buffer .= "</table>\n";
			*/

			return $buffer;
		}
		$buffer .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
		$buffer .= "<tr>\n\t<th>Date</th>\n\t<th>Dose</th>\n\t<th>Status</th>\n\t<th>Take Home</th>\n</tr>\n";
		$dt = $dp['doseplanstartdate'];
		$takehome = $this->doseTakeHome( $doseplanid, $dt );
		$arTkh = split("#",$takehome);
		if ($dp['doseplantype'] != 'incremental-methadone') { $dp['doseplanlength'] = 1; }
		if ($dp['doseplansplit'] == 1) { $dp['doseplanlength'] = 2; }
		for ($i=1; $i<=$dp['doseplanlength']; $i++) {
			// Use API to get the dose
			$dose = $this->doseForDate( $doseplanid, $dt );
			$status = $this->doseStatus( $doseplanid, $dt );
			
			$day = $arTkh[array_search(strtolower(date("D",strtotime($dt))),$arTkh)];
			$day = ($day == strtolower(date("D",strtotime($dt)))?",$day":"");
			$d = explode( '-', $dt );
			$dtmdy = date("m/d/Y",mktime(10,10,10,$d[1],$d[2],$d[0]));
		
			// Force no status if takehome + past
			if ( $takehome and ( strtotime($dt) < mktime()-86400 ) ) {
				$status = true;
			}

			// Force no dosing if not takehome and in the future
			if ( !$takehome and ( strtotime($dt) > mktime()+86300 ) ) {
				$status = true;
			}

			// Add this
			////$buffer .= "<tr ".( !$status ? "onMouseOver=\"this.style.backgroundColor='#7777ff'; return true;\" onMouseOut=\"this.style.backgroundColor='transparent'; return true;\" onClick=\"if (document.getElementById('doseassigneddate_cal')) { document.getElementById('doseassigneddate_cal').value='${dt}'; } return true;\"" : "" )." >\n".
			$buffer .= "<tr ".( !$status ? "onMouseOver=\"this.style.backgroundColor='#7777ff'; return true;\" onMouseOut=\"this.style.backgroundColor='transparent'; return true;\" onClick=\"try { dojo.widget.byId( 'doseassigneddate' ).inputNode.value = '${dtmdy}'; } catch (err) { } return true;\"" : "" )." >\n".
				"\t<td>${dt}</td>\n".
				"\t<td>${dose} ${dp['doseplanunits']}</td>\n".
				"\t<td>".( $status ? "<span style=\"color: #ff0000;\">Dispense</span>" : "" )."</td>\n".
				"\t<td>".( ( $takehome != "" && $day  != "" ) ? "<span style=\"color: #0000ff;\">Yes</span>" : "<span style=\"color: RED;\">No</span>" )." ".$day."</td>\n".
				"</tr>\n";
				
//original code				
//				"\t<td>".( $takehome ? "<span style=\"color: #0000ff;\">Yes</span>" : "<span style=\"color: RED;\">NO</span>" )."</td>\n".

			// Increment date at the end
			$dt = $this->increment_date( $dt, 1 );
		}
		$buffer .= "</table>\n";
		return $buffer;
	} // end method ajax_display_dose_plan

	function ajax_doseForDate ( $blob ) {
		list ( $doseplanid, $date ) = explode ( ',', $blob );
		return $this->doseForDate( $doseplanid, $date );
	}

	// Method: doseForDate
	//
	//	Determine dose for a particular date based on a doseplan.
	//
	// Parameters:
	//
	//	$doseplanid - Id for the doseplan in question
	//
	//	$date - Date to query
	//
	// Returns:
	//
	//	Dose amount for the specified date.
	//
	function doseForDate ( $doseplanid, $date ) {
		$plan = freemed::get_link_rec( $doseplanid, $this->table_name );
		if ($plan['doseplantype'] == 'regular-methadone') {
			// No dose if before start of plan
			if ( $this->dateToStamp( $date ) < $this->dateToStamp( $plan['doseplanstartdate'] ) ) {
				return 0;
			}

			// Handle regular and split dosing
			if ($plan['doseplansplit']) {
				// If there has been a dose given today, doseplansplit2, else doseplansplit1
				$c = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT COUNT(*) AS my_count FROM doserecord WHERE doseassigneddate='".addslashes($date)."' AND dosegiven=1 AND doseplanid='".addslashes($doseplanid)."'"));
				if ($c['my_count'] == 1) {
					return $plan['doseplansplit2'];
				} else {
					return $plan['doseplansplit1'];
				}
			} else {
				// Plain old, plain old
				return $plan['doseplandose'];
			}
		}
		if ($plan['doseplantype'] != 'incremental-methadone' && $plan['doseplanstartdate'] == $date && !$plan['doseplansplit']) {
			return $plan['doseplandose'];
		}	
		$doses = explode( ',', $plan['doseplanincrementationschedule'] );
		// Avoid divide by 0, give initial date.
		if ( $date == $plan['doseplanstartdate'] ) {
			return $doses[0];
		}

		// Magic
		$days = ceil( ( $this->dateToStamp( $date ) - $this->dateToStamp( $plan['doseplanstartdate'] ) ) / (60 * 60 * 24) );
		return ( $days < 1 or $days >= count( $doses ) ) ? 0 : $doses[$days];
	} // end method doseForDate

	// Method: doseStatus
	//
	//	Determine dose status based on a doseplan and date.
	//
	// Parameters:
	//
	//	$doseplanid - Id for the doseplan in question
	//
	//	$date - Date to query
	//
	// Returns:
	//
	//	Number of given doses for the specified date. May be 0 or 1 for a
	//	non-split doseplan; 0, 1 or 2 for a split doseplan.
	//
	function doseStatus ( $doseplanid, $date ) {
		$q = "SELECT COUNT(*) AS c FROM doserecord d LEFT OUTER JOIN doseplan dp ON d.doseplanid=dp.id WHERE d.doseassigneddate='".addslashes($date)."' AND dp.id='".addslashes($doseplanid)."' AND d.dosegiven=1";
		$a = $GLOBALS['sql']->fetch_array( $GLOBALS['sql']->query ( $q ) );
		return $a['c'];
	} // end method doseStatus

	function ajax_doseTakeHome ( $blob ) {
		list ( $doseplanid, $date ) = explode ( ',', $blob );
		return $this->doseTakeHome( $doseplanid, $date );
	}

	// Method: doseTakeHome
	//
	//	Determine dose takehome status based on a doseplan and date.
	//
	// Parameters:
	//
	//	$doseplanid - Id for the doseplan in question
	//
	//	$date - Date to query
	//
	// Returns:
	//
	//	Dose takehome status for the specified date, boolean.
	//
	function doseTakeHome ( $doseplanid, $date ) {
//		$q = "SELECT IF SUBSTR(dp.doseplantakehomesched FROM  DATE_FORMAT('".addslashes($date)."', '%w')+1 FOR 1) = 'X' THEN 1 ELSE 0 END IF AS takehome doseplan dp WHERE dp.id='".addslashes($doseplanid)."'";
//		$q = "SELECT FIND_IN_SET(DATE_FORMAT('".addslashes($date)."', '%w')+1, dp.doseplantakehomesched) AS takehome doseplan dp WHERE dp.id='".addslashes($doseplanid)."'";
//		$q = "SELECT sum(dp.doseplantakehomesched) AS takehome FROM doseplan dp WHERE dp.id='".addslashes($doseplanid)."'";
//		$q = "SELECT dp.doseplantakehomesched AS takehome FROM doseplan dp WHERE dp.id='".addslashes($doseplanid)."'";
		$q = "SELECT FIND_IN_SET(DATE_FORMAT('".addslashes($date)."', '%w'), doseplantakehomesched) AS takehome FROM doseplan WHERE id='".addslashes($doseplanid)."'";
		$a = $GLOBALS['sql']->fetch_array( $GLOBALS['sql']->query ( $q ) );
		return ( $a['takehome'] >= 1 );
		//return ( $a['takehome'] );
	} // end method doseTakeHome

} // end class DosePlan

register_module("DosePlan");

?>
