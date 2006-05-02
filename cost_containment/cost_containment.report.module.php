<?php
	//
	// $Id$
	// jeff@freemedsoftware.org
	// Licensed under the GNU Public License
	//

LoadObjectDependency('_FreeMED.ReportsModule');

class CostContainmentReport extends ReportsModule {

	var $MODULE_NAME = "Cost Containment Report";
	var $MODULE_VERSION = "0.1.1";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.1';

	var $CRLF = "\r\n";

	function CostContainmentReport () {
		$this->ReportsModule();
	} // end constructor CostContainmentReport

	function view() {
		global $display_buffer;

		// Make sure we have parameters
		if (!$_REQUEST['year'] or !$_REQUEST['quarter']) {
			return $this->form();
		}

		// Based on quarter ...
		switch ($_REQUEST['quarter']) {
			case 1:
				$firstday = $_REQUEST['year'].'-01-01';
				$lastday  = $_REQUEST['year'].'-03-31';
				break;
			case 2:
				$firstday = $_REQUEST['year'].'-04-01';
				$lastday  = $_REQUEST['year'].'-06-30';
				break;
			case 3:
				$firstday = $_REQUEST['year'].'-07-01';
				$lastday  = $_REQUEST['year'].'-09-30';
				break;
			case 4:
				$firstday = $_REQUEST['year'].'-10-01';
				$lastday  = $_REQUEST['year'].'-12-31';
				break;
			default:
				trigger_error(__("A valid quarter must be specified."), E_USER_ERROR);
				break;
		}

		// Get facility information
		$facility = freemed::get_link_rec($_REQUEST['facility'], 'facility');

		// Create header row
		$header = array (
			array (
				// Data Source Identifier (Medicaid number/MPI)
				'pos' => 1,
				'length' => 25,
				'content' => $facility['psrein']
			),
			array (
				// Name = Position 26-50
				'pos' => 26,
				'length' => 25,
				'content' => strtoupper($facility['psrname'])
			),
			array (
				// Address 1
				'pos' => 51,
				'length' => 25,
				'content' => strtoupper($facility['psraddr1'])
			),
			array (
				// Address 2
				'pos' => 76,
				'length' => 25,
				'content' => strtoupper($facility['psraddr2'])
			),
			array (
				// City
				'pos' => 101,
				'length' => 15,
				'content' => strtoupper($facility['psrcity'])
			),
			array (
				// State
				'pos' => 115,
				'length' => 2,
				'content' => strtoupper($facility['psrstate'])
			),
			array (
				// Zip
				'pos' => 117,
				'length' => 9,
				'content' => $facility['psrzip']
			),
			array (
				// Period Covered First Day
				'pos' => 126,
				'length' => 6,
				'content' => $this->_FormatDate( $firstday )
			),
			array (
				// Period Covered Last Day
				'pos' => 132,
				'length' => 6,
				'content' => $this->_FormatDate( $lastday )
			),
			array (
				// Run Date 138
				'pos' => 138,
				'length' => 6,
				'content' => $this->_FormatDate( date('Y-m-d') )
			),
			array (
				// Inpatient/Outpatient Indicator
				'pos' => 144,
				'length' => 1,
				'content' => 'O'
			),
			array (
				// Submission Type 2299
				'pos' => 2299,
				'length' => 1,
				'content' => 'O'
			),
			array (
				// Record Type 2300
				'pos' => 2300,
				'length' => 1,
				'content' => 'H'
			)
		);
		$buffer .= $this->_ArrangeLine( $header, 2300 ) . $this->CRLF;

		// Get list of patients with procedures during this time period
		$p_query = "SELECT procpatient,procdt,patient.* FROM procrec LEFT OUTER JOIN patient ON patient.id = procrec.procpatient WHERE procdt >= '".addslashes($firstday)."' AND procdt <= '".addslashes($lastday)."' AND procpos = '".addslashes($_REQUEST['facility'])."' GROUP BY procpatient,procdt";
		$p_result = $GLOBALS['sql']->query( $p_query );
		while ( $p_r = $GLOBALS['sql']->fetch_array( $p_result ) ) {
			// Clean out what we already had
			unset( $record );
			unset( $procs );

			// Patient specific part of record
			$record[] = array (
				// SSN
				'pos' => 1,
				'length' => 9,
				'content' => str_replace('-', '', $p_r['ptssn'])
			);
			$record[] = array (
				// Date of Birth
				'pos' => 10,
				'length' => 8,
				'content' => $this->_FormatDate( $p_r['ptdob'], true )
			);
			$record[] = array (
				// Gender
				'pos' => 18,
				'length' => 1,
				'content' => strtoupper($p_r['ptsex'])
			);
			$record[] = array (
				// Patient Home Zipcode
				'pos' => 19,
				'length' => 9,
				'content' => $p_r['ptzip']
			);
			$record[] = array (
				// Date of Admission
				'pos' => 28,
				'length' => 8,
				'content' => $this->_FormatDate( $p_r['procdt'] )
			);
			$record[] = array (
				// Date of Discharge
				'pos' => 36,
				'length' => 8,
				'content' => $this->_FormatDate( $p_r['procdt'] )
			);

			// Get detail information, loop through these
			$d_query = "SELECT *,c.cptcode AS cptcode,d.icd9code AS icdcode,f.psrzip AS zipcode FROM procrec LEFT OUTER JOIN cpt c ON c.id=procrec.proccpt LEFT OUTER JOIN icd9 d ON d.id=procrec.procdiag1 LEFT OUTER JOIN facility f ON f.id=procrec.procpos WHERE procpatient='".addslashes($p_r['procpatient'])."' AND procdt='".addslashes($p_r['procdt'])."' AND procpos='".addslashes($_REQUEST['facility'])."'";
			$d_result = $GLOBALS['sql']->query( $d_query );
			unset ($icdcodes); unset ($cptcodes);
			while ($d_r = $GLOBALS['sql']->fetch_array( $d_result )) {
				$procs[] = array (
					'zipcode' => $d_r['zipcode'],
					'icdcode' => $d_r['icdcode'],
					'cptcode' => $d_r['cptcode'],
					'units'   => $d_r['procunits'],
					'amount'  => $d_r['proccharges']
				);
				$icdcodes[$d_r['icdcode']] = $d_r['icdcode'];
				if (!$cptcodeshash[$d_r['cptcode']]) {
					$cptcodeshash[$d_r['cptcode']] = $d_r['cptcode'];
					$cptcodes[] = $d_r['cptcode'];
				}
			} // end looping through details

			//print "<pre>"; print_r($procs[0]); print "</pre>\n";

			// Push primary diagnosis code
			$record[] = array (
				// Primary diagnosis
				'pos' => 48,
				'length' => 6,
				'content' => $procs[0]['icdcode']
			);
			// TODO: Loop through the *rest* of the diagnosis codes

			// Loop through procedure codes
			$count = 0;
			//print "<pre>"; print_r($cptcodes); print "</pre>";
			while ($count <= 6 && isset($cptcodes[$count])) {
				$record[] = array (
					// Procedure code
					'pos' => 114 + ( $count * 11 ),
					'length' => 7,
					'content' => $cptcodes[$count]
				);
				$record[] = array (
					// Date
					'pos' => 121 + ( $count * 11 ),
					'length' => 4,
					'content' => $this->_FormatDateMMDD($p_r['procdt'])
				);

				// Populate further ahead as well
				$reclength = 4 + 9 + 8 + 7 + 10 + 10;
				$record[] = array (
					// Revenue code
					'pos' => 249 + ( $reclength * $count ),
					'length' => 4,
					'content' => '' // TODO
				);
				$record[] = array (
					// HCPCS/Rate
					'pos' => 253 + ( $reclength * $count ),
					'length' => 9,
					'content' => '' // TODO
				);
				$record[] = array (
					// Service Date
					'pos' => 262 + ( $reclength * $count ),
					'length' => 8,
					'content' => $this->_FormatDate( $p_r['procdt'], true )
				);
				$record[] = array (
					// Units of Service
					'pos' => 270 + ( $reclength * $count ),
					'length' => 7,
					'content' => $this->_FormatZeroFill($procs[$count]['units'])
				);
				$record[] = array (
					// Total Charges
					'pos' => 277 + ( $reclength * $count ),
					'length' => 10,
					'content' => $this->_FormatAmount($procs[$count]['amount'])
				);
				$record[] = array (
					// Non-covered charges
					'pos' => 287 + ( $reclength * $count ),
					'length' => 10,
					'content' => $this->_FormatAmount(0, 10) // FIXME
				);

				$count++;
			} // end looping through procedure codes

			$record[] = array (
				// Estimated Amount Due
				'pos' => 1468,
				'length' => 10,
				'content' => $this->_FormatZeroFill(0, 10)
			);
			$record[] = array (
				// Estimated Amount Due
				'pos' => 1478,
				'length' => 10,
				'content' => $this->_FormatZeroFill(0, 10)
			);
			$record[] = array (
				// Estimated Amount Due
				'pos' => 1488,
				'length' => 10,
				'content' => $this->_FormatZeroFill(0, 10)
			);
			$record[] = array (
				// Estimated Amount Due
				'pos' => 1498,
				'length' => 10,
				'content' => $this->_FormatZeroFill(0, 10)
			);
			$record[] = array (
				// POS Zip Code
				'pos' => 1515,
				'length' => 5,
				'content' => $procs[0]['zipcode']
			);

			// Push into list of everything
			$buffer .= $this->_ArrangeLine( $record, 2300 ) . $this->CRLF;
		} // end while each patient

		// Show	
		Header("Content-type: text/plain");
		print $buffer;
		die();
	} // end method view

	function form () {
		$form = CreateObject('PEAR.HTML_QuickForm', 'form', 'post');
		freemed::quickform_i18n(&$form);

		// Get distinct years
		$query = $GLOBALS['sql']->query("SELECT DISTINCT(YEAR(procdt)) AS y FROM procrec ORDER BY procdt");
		while ($r = $GLOBALS['sql']->fetch_array($query)) {
			$years[$r['y']] = $r['y'];
		}

		$form->addElement('hidden', 'module', get_class($this));
		$form->setDefaults(array(
			'year' => date('Y')
		));

		$form->addElement('static', 'facility', __("Facility"),
			module_function('facilitymodule', 'widget', 'facility')
		);
		$form->addElement('select', 'year', __("Year"),
			$years
		);
		$form->addElement('select', 'quarter', __("Quarter"),
			array(
				1 => __("First Quarter"),
				2 => __("Second Quarter"),
				3 => __("Third Quarter"),
				4 => __("Fourth Quarter")
			)
		);

		$submit_group[] = &HTML_QuickForm::createElement(
			'submit', 'submit_action', __("Generate"));
		$submit_group[] = &HTML_QuickForm::createElement(
			'submit', 'submit_action', __("Cancel"));
                $form->addGroup($submit_group, null, null, '&nbsp;');

		$GLOBALS['display_buffer'] .= "<div align=\"center\">".$form->toHtml()."</div>\n";
	} // end method form

	//----- Internal methods --------------------------------------------------------

	function _ArrangeLine ( $contents, $length ) {
		// Create empty buffer
		$buffer = '';
		for ($i = 0; $i <= $length ; $i++) {
			$buffer[$i] = ' ';
		}

		// Display contents
		foreach ($contents AS $v) {
			// Limit to $v[length]
			//$length = (strlen($v['content']) > $v['length']) ? $v['length'] : strlen($v['content']);
			$length = $v['length'];
			//print "length = $length<br/>\n";

			// Start at $v[pos] and push contents into buffer
			$cur = $v['pos']-1;
			for ( $pos = 0; $pos < $length; $pos++ ) {
				//print " --- $cur : ".$v['content']." (sub $pos) [ <tt>".join('', $buffer). "</tt> ] <br/>\n";
 				$ch = substr($v['content'], $pos, 1);
				$buffer[$cur] = strlen($ch)==1 ? $ch : ' ';
				$cur++;
			}
		} // end 

		return join('', $buffer);
	} // end method _ArrangeLine

	function _FormatDate ( $date, $long = false ) {
		list ( $y, $m, $d ) = explode( '-', $date );
		return $m . $d . ( $long ? $y : substr($y, 2, 2));
	} // end method _FormatDate

	function _FormatDateMMDD ( $date ) {
		list ( $y, $m, $d ) = explode( '-', $date );
		return $m . $d;
	} // end method _FormatDateMMDD

	function _FormatAmount ( $amount, $length ) {
		// Get decimal part
		$decimal = sprintf('%02d', ($amount * 100) % 100);
		$integer = (int) $amount;

		// Get sign
		$sign = ($amount < 0) ? '-' : '+';

		$buffer = $integer . $decimal;
		while (strlen($buffer) < ($length - 1)) { $buffer = '0' . $buffer; }
		//die( " ************ FormatAmount = ${sign}${buffer} **************\n" );
		return $sign . $buffer;
	} // end method _FormatAmount

	function _FormatZeroFill ( $number, $length ) {
		$buffer = ((int)($number + 0)) . '';
		while (strlen($buffer) < $length) {
			$buffer = '0' . $buffer;
		}
		return $buffer;
	} // end method _FormatZeroFill

} // end class CostContainmentReport

register_module ("CostContainmentReport");

?>
