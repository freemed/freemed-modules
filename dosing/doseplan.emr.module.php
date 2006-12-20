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
	var $widget_hash = '##doseplandose## ##doseplanunits## (##doseplancomment##)';
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
			'doseplantakehomesched',
			'doseplansplit',
			'doseplansplit1',
			'doseplansplit2',
			'doseplantakehomecountgiven',
			'doseplanincrementationtype',
			'doseplanincrementationschedule' => join(',', $_REQUEST['doseplan']),
			'doseplanlength' => count( $_REQUEST['doseplan'] ),
			'doseplanmedicalorders',
			'doseplancomment',
			'doseplanpickupdate' => fm_date_assemble('doseplanpickupdate'),
			'doseplanreturndate' => fm_date_assemble('doseplanreturndate')
		);

		$this->summary_vars = array (
			__("User")    =>	"doseplanuser:user",
			__("Effective")    =>	"doseplaneffectivedate",
			__("Length")    =>	"doseplanlength",
			__("Comment") =>	"doseplancomment"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'id';

		// Set associations
		$this->EMRModule();
	} // end constructor DosePlan

	function modform ( ) { }
	function mod ( ) { }

	function addform ( ) {
		if (!$_REQUEST['been_here']) {
			$_REQUEST['doseplaneffectivedate'] = $GLOBALS['doseplaneffectivedate'] = date('Y-m-d');
			$_REQUEST['doseplanstartdate'] = $GLOBALS['doseplanstartdate'] = date('Y-m-d');
			$_REQUEST['been_here'] = 1;
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
				if ( document.getElementById('doseplantype').value == 'exception' ) {
					eV.style.display = 'block';
				} else {
					eV.style.display = 'none';
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
				"<input type=\"radio\" id=\"doseplansplity\" name=\"doseplansplit\" value=\"1\" onClick=\"splitDose(1); return true;\" ".( $_REQUEST['doseplansplit']==1 ? "SELECTED" : "" )."><label for=\"doseplansplity\">".__("Yes")."</label> ".
				"<input type=\"radio\" id=\"doseplansplitn\" name=\"doseplansplit\" value=\"0\" onClick=\"splitDose(0); return true;\" ".( $_REQUEST['doseplansplit']==0 ? "SELECTED" : "" )."><label for=\"doseplansplitn\">".__("No")."</label> ".
				"<script language=\"javascript\">
				function splitDose ( b ) {
					document.getElementById('splitDoseDiv').style.display = b ? 'block' : 'none';
				}
				</script>
				<div id=\"splitDoseDiv\" style=\"display: ".( $_REQUEST['doseplansplit']==1 ? 'block' : 'none' ).";\">
				HERE
				</div>
				",
			__("Incrementation Type") =>
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
				))."
			<div id=\"incrementationView\" style=\"display: ".( ($_REQUEST['doseplanincrementationtype']=='none' or $_REQUEST['doseplanincrementationtype']=='') ? 'none' : 'block' ).";\">
			<div id=\"dayCountView\" style=\"display: ".( ($_REQUEST['doseplanincrementationtype']=='titration-decrease' or $_REQUEST['doseplanincrementationtype']=='titration-increase') ? 'block' : 'none' ).";\">".__("Number of Days")." : ".html_form::text_widget("doseplandays")."</div>
			<div id=\"decView\" style=\"display: ".( ($_REQUEST['doseplanincrementationtype']=='behavioral' or $_REQUEST['doseplanincrementationtype']=='voluntary' or $_REQUEST['doseplanincrementationtype']=='adminstrative') ? 'block' : 'none' ).";\">".__("Daily Decrease Dose")." : ".html_form::text_widget("doseplandec")."</div>
			</div>	

			<script language=\"javascript\">
			function adjustIncrementationView ( ) {
				var iV = document.getElementById('doseplanincrementationtype').value;
				if (iV != 'none') {
					document.getElementById('incrementationView').style.display = 'block';
				} else {
					document.getElementById('incrementationView').style.display = 'none';
				}

				if ((iV == 'behavioral') || (iV == 'voluntary')) {
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
			__("Take Home Schedule") => '
			<input type="checkbox" id="takehomes" name="takehomes" onClick="toggletakehomes();" /><label for="takehomes">Enable take home doses</label>
			<br/>
			<div id="takehomecontainer" style="display: '.( $_REQUEST['takehomes'] ? 'block' : 'none' ).';">
			<input type="checkbox" id="sun" name="takehomesched[sun]" /><label for="sun">Sun</label>
			<input type="checkbox" id="mon" name="takehomesched[mon]" /><label for="mon">Mon</label>
			<input type="checkbox" id="tue" name="takehomesched[tue]" /><label for="tue">Tue</label>
			<input type="checkbox" id="wed" name="takehomesched[wed]" /><label for="wed">Wed</label>
			<input type="checkbox" id="thu" name="takehomesched[thu]" /><label for="thu">Thu</label>
			<input type="checkbox" id="fri" name="takehomesched[fri]" /><label for="fri">Fri</label>
			<input type="checkbox" id="sat" name="takehomesched[sat]" /><label for="sat">Sat</label>
			</div>
			<script language="javascript">
			function toggletakehomes ( ) {
				if ( document.getElementById(\'takehomes\').checked ) {
					document.getElementById(\'takehomecontainer\').style.display = \'block\';
				} else {
					document.getElementById(\'takehomecontainer\').style.display = \'none\';
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
				$amt = (int) ( $_REQUEST['doseplandose'] / $_REQUEST['doseplandec'] );
				for ($i = 0; $i <= $amt; $i++ ) {
					$dp[] = (int) $_REQUEST['doseplandec'];
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
				$date = $this->increment_date ( $date );
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
				__("Comment") => html_form::text_area('doseplancomment'),
				" " => 
				( $_REQUEST['doseplantype'] == 'exception' ? "
				<div>Medication Pickup Date ".fm_date_entry("doseplanpickupdate")."</div>
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
			// Calculate take home schedule
			$days = array (
				'sun' => 0,
				'mon' => 1,
				'tue' => 2,
				'wed' => 3,
				'thu' => 4,
				'fri' => 5,
				'sat' => 6
			);
			$takehomesched = ''; $count = 0;
			foreach ( $days AS $day => $pos ) {
				if ( $_REQUEST['takehomesched'][$day] ) {
					$takehomesched .= 'X';
				} else {
					$takehomesched .= ' ';
				}
			}
			$_REQUEST['doseplantakehomesched'] = $takehomesched;

			$query = $GLOBALS['sql']->insert_query (
				$this->table_name,
				$this->variables
			);
			$GLOBALS['sql']->query( $query );
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

	function figureInitialDosePlan ( $total, $increment, $stop_at = 0 ) {
		$my_total = $total;
		while ( $my_total > 0 and $my_total > $stop_at ) {
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

	function increment_date ( $old, $days = 1 ) {
		return date( 'Y-m-d', $this->dateToStamp($old) + (84600 * $days) );
	} // end method increment_date

	function dow ( $date ) {
		return date( 'D', $this->dateToStamp($old) );
	} // end method dow

	function dateToStamp ( $date ) {
		list ( $y, $m, $d ) = explode ( '-', $date );
		return mktime ( 0, 0, 0, $m, $d, $y );
	} // end method dateToStamp

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
		$doses = explode( ',', $plan['doseplanincrementationschedule'] );
		// Avoid divide by 0, give initial date.
		if ( $date == $plan['doseplanstartdate'] ) {
			return $doses[0];
		}

		// Magic
		$days = (int) ( $this->dateToStamp( $date ) - $this->dateToStamp( $plan['doseplanstartdate'] ) / 84600 );
		return ( $days < 1 or $days >= count( $doses ) ) ? 0 : $doses[$days];
	} // end method doseForDate

} // end class DosePlan

register_module("DosePlan");

?>
