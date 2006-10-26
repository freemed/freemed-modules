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

class TreatmentPlanModule extends EMRModule {

	var $MODULE_NAME = "Treatment Plan";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_VERSION = "0.1";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.0';

	var $record_name = "Treatment Plan";
	var $table_name = 'treatmentplan';
	var $patient_field = 'patient';
	var $date_field = 'dateofadmission';
	var $widget_hash = '##dateofadmission## (##periodcovered## days)';
	var $order_fields = "dateofadmission";
	var $print_template = 'treatment_plan';

	function TreatmentPlanModule () {
		$this->table_definition = array (
				// Date tracking information
			'creationdate' => SQL__DATE,
			'dateofadmission' => SQL__DATE,
			'periodcovered' => SQL__INT_UNSIGNED(0),
			'dateexpires' => SQL__DATE,
				// Axis I-VI codes
			'd1code' => SQL__INT_UNSIGNED(0),
			'd2code' => SQL__INT_UNSIGNED(0),
			'd3code' => SQL__INT_UNSIGNED(0),
			'd4code' => SQL__INT_UNSIGNED(0),
			'd5code' => SQL__INT_UNSIGNED(0),
			'd6code' => SQL__INT_UNSIGNED(0),
			'gafcurrent' => SQL__INT_UNSIGNED(0),
			'gafhighest' => SQL__INT_UNSIGNED(0),
				// Axis I-VI status
			'd1status' => SQL__INT_UNSIGNED(0),
			'd2status' => SQL__INT_UNSIGNED(0),
			'd3status' => SQL__INT_UNSIGNED(0),
			'd4status' => SQL__INT_UNSIGNED(0),
			'd5status' => SQL__INT_UNSIGNED(0),
			'd6status' => SQL__INT_UNSIGNED(0),
				// Axis I-VI notes
			'd1note' => SQL__TEXT,
			'd2note' => SQL__TEXT,
			'd3note' => SQL__TEXT,
			'd4note' => SQL__TEXT,
			'd5note' => SQL__TEXT,
			'd6note' => SQL__TEXT,
				// Level of care
			'indicatedlevelofcare' => SQL__VARCHAR(50),
			'highercareassessed' => SQL__VARCHAR(50),
				// Location information
			'facility' => SQL__INT_UNSIGNED(0),
				// Open/closed status
			'status' => SQL__INT_UNSIGNED(0),
				// Creation information
			'createdby' => SQL__INT_UNSIGNED(0),
			'createddate' => SQL__TIMESTAMP(14),
				// Approval information
			'approvedby' => SQL__INT(0),
			'approveddate' => SQL__TIMESTAMP(14),
				// Link to patient EMR
			'patient' => SQL__INT_UNSIGNED(0),
				// Lock, ID, etc
			'locked' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);
		$this->summary_query = array (
			"CASE approvedby WHEN -1 THEN 'denied' WHEN 0 THEN 'waiting' ELSE 'approved' END AS approvalstatus"
		);

		$this->variables = array (
		);

		// Form proper configuration information
		$this->_SetMetaInformation('global_config_vars', array(
			'tp_approval'
		));
		$this->_SetMetaInformation('global_config', array(
			__("Supervisor(s)") =>
			'freemed::multiple_choice ( '.
			'"SELECT CONCAT(username, \' (\', userdescrip, \')\') '.
			'AS descrip, id FROM user ORDER BY descrip", "descrip", '.
			'"tp_approval", fm_join_from_array($tp_approval))'
			)
		);

		$this->summary_vars = array (
			__("Created On") => 'creationdate',
			__("Period") => 'periodcovered',
			__("Approval") => 'approvalstatus'
		);
		$this->summary_options = SUMMARY_DELETE | SUMMARY_PRINT;
		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }
		if ($this->check_for_supervisor()) { $this->summary_options |= SUMMARY_LOCK; }

		// call parent constructor
		$this->EMRModule();
	} // end constructor TreatmentPlanModule

	function check_for_supervisor ( ) {
		$supervisors = freemed::config_value('tp_approval');
		if (!(strpos($supervisors, ',') === false)) {
			// Handle array
			$found = false;
			foreach (explode(',', $supervisors) AS $s) {
				if ($s == $GLOBALS['this_user']->user_number) { $found = true; }
			}
			if (!$found) { return false; }
		} else {
			if (($supervisors> 0) and ($supervisors != $GLOBALS['this_user']->user_number)) {
				return false;
			} else {
				return true;
			}
		}
	} // end method check_for_supervisor

	function form ( ) {
		ob_start();
		include_once ('treatment_plan.php');
		$GLOBALS['display_buffer'] .= ob_get_contents();
		ob_end_clean();
	} // end method form

	function date_assemble ( $date ) {
		$parts = explode('/', $date);
		return sprintf("%04d-%02d-%02d", $parts[2], $parts[0], $parts[1]);
	}

	function del ( $id ) {
		$_id = $id+0;
		if ($_id == 0) { $_id = $_REQUEST['id']+0; }
		$query = "DELETE FROM ".$this->table_name." WHERE id='".addslashes($_id)."'";
		$GLOBALS['sql']->query( $query );
		$query = "DELETE FROM treatmentplanproblem WHERE treatmentplan='".addslashes($_id)."'";
		$GLOBALS['sql']->query( $query );
		$query = "DELETE FROM treatmentplanoi WHERE oitreatmentplan='".addslashes($_id)."'";
		$GLOBALS['sql']->query( $query );
		return true;
	}

	function mod ( ) { $this->add( ); }

	function lock ( ) {
		// special behavior to handle approvals
		$q = $GLOBALS['sql']->update_query (
			$this->table_name,
			array (
				'approvedby' => $GLOBALS['this_user']->user_number,
				'approveddate' => SQL__NOW
			)
		);
		$GLOBALS['sql']->query($q);
		return $this->_lock();
	}

	function add ( $dieout = false ) {
		if ($_REQUEST['id'] > 0 and $this->locked($_REQUEST['id'])) {
			return false;
		}

		$this_user = CreateObject('_FreeMED.User');

		// Handle all "subdeletions"
		if (is_array($_REQUEST['problem'])) {
			//problem[${dsm}][${id}][delete]
			foreach ($_REQUEST['problem'] AS $x) {
				foreach ($x AS $y => $z) {
					if ($z['delete'] > 0) {
						$query = "DELETE FROM treatmentplanproblem WHERE id='".addslashes($y)."'";
						$GLOBALS['sql']->query( $query );
						$query = "DELETE FROM treatmentplanoi WHERE oiproblem='".addslashes($y)."'";
						$GLOBALS['sql']->query( $query );
					}
				}
			}
		}

	//	print "<pre>"; print_r($_REQUEST); print "</pre>"; die();

		// Master record
		$a = array (
				// Date tracking information
			'creationdate' => $this->date_assemble($_REQUEST['creationdate']),
			'dateofadmission' => $this->date_assemble($_REQUEST['dateofadmission']),
			'periodcovered',
			'dateexpires' => $this->date_assemble($_REQUEST['dateexpires']),
				// Axis I-VI codes
			'd1code',
			'd2code',
			'd3code',
			'd4code',
			'd5code',
			'd6code',
			'gafcurrent',
			'gafhighest',
			'indicatedlevelofcare',
			'highercareassessed',
				// Axis I-VI status
			'd1status',
			'd2status',
			'd3status',
			'd4status',
			'd5status',
			'd6status',
				// Axis I-VI notes
			'd1note',
			'd2note',
			'd3note',
			'd4note',
			'd5note',
			'd6note',
				// Location information
			'facility',
			'patient',
				// Set approval to pending
			'approvedby' => 0
		);
		if ($_REQUEST['id']+0 > 0) {
			$q = $GLOBALS['sql']->update_query(
				$this->table_name,
				$a,
				array ( 'id' => $_REQUEST['id'] )
			);
			$new_id = $_REQUEST['id'];
		} else {
			$a['createdby'] = $this_user->user_number;
			$q = $GLOBALS['sql']->insert_query(
				$this->table_name,
				$a
			);
		}
		syslog(LOG_INFO, "$q");
		$query = $GLOBALS['sql']->query( $q );
		$new_id = $_REQUEST['id']>0 ? $_REQUEST['id'] : $GLOBALS['sql']->last_record($query, $this->table_name);

		// Deal with problems
		if (is_array($_REQUEST['problem'])) {
			//problem[${dsm}][${id}][delete]
			foreach ($_REQUEST['problem'] AS $x) {
				foreach ($x AS $ky => $y) { if (!$y['delete']) {
					if ($y['id'] > 0) {
						$GLOBALS['sql']->query(
							$GLOBALS['sql']->update_query(
								'treatmentplanproblem',
								array(
									'problem' => $y['problem'],
									'goalslongterm' => $y['goalslongterm'],
									'goalsshortterm' => $y['goalsshortterm'],
									'dateeffectiveshort' => $this->date_assemble($y['dateeffectiveshort']),
									'datetargetshort' => $this->date_assemble($y['datetargetshort']),
									'dateeffectivelong' => $this->date_assemble($y['dateeffectivelong']),
									'datetargetlong' => $this->date_assemble($y['datetargetlong'])
								), array ('id' => $y['id'])
							)
						);
						$tpp[$ky] = $y['id'];
					} elseif ($y['id'] == 0) {
						$q = $GLOBALS['sql']->query(
							$GLOBALS['sql']->insert_query(
								'treatmentplanproblem',
								array(
									'tpuser' => $this_user->user_number,
									'tppatient' => $_REQUEST['patient'],
									'tpdsm' => $y['dsm'],
									'treatmentplan' => $new_id,
									'problem' => $y['problem'],
									'goalslongterm' => $y['goalslongterm'],
									'goalsshortterm' => $y['goalsshortterm'],
									'dateeffectiveshort' => $this->date_assemble($y['dateeffectiveshort']),
									'datetargetshort' => $this->date_assemble($y['datetargetshort']),
									'dateeffectivelong' => $this->date_assemble($y['dateeffectivelong']),
									'datetargetlong' => $this->date_assemble($y['datetargetlong'])
								)
							)
						);
						$tpp[$ky] = $GLOBALS['sql']->last_record( $q, 'treatmentplanproblem' );
					}
				} }
			}

			// Deal with observation/interventions
			$oi = $_REQUEST['oi'];
			foreach ($oi AS $v) {
				if ( $v['objective'] and $v['intervention'] ) {
					$a = array (
						'objective' => $v['objective'],
						'intervention' => $v['intervention'],
						'dateeffective' => $this->date_assemble($v['dateeffective']),
						'datetarget' => $this->date_assemble($v['datetarget']),

						'oiuser' => $this_user->user_number,
						'oidsm' => $v['dsm'],
						'oiproblem' => $tpp[$v['problem']],
						'oipatient' => $_REQUEST['patient'],
						'oitreatmentplan' => $new_id
					);
					if ($v['id'] > 0) {
						// Create array to pass to queries

						// Update
						$GLOBALS['sql']->query($GLOBALS['sql']->update_query(
							'treatmentplanoi',
							$a,
							array( 'id' => $v['id'] )
						));
					} else {
						// Add
						$GLOBALS['sql']->query($GLOBALS['sql']->insert_query(
							'treatmentplanoi',
							$a
						));
					} // end if v[id] > 0
				} // end if objective && intervention
			} // end foreach oi
		}

                if ($_REQUEST['return'] == 'manage') {
                        global $refresh, $patient;
                        $refresh = "manage.php?id=".urlencode($patient);
                        Header("Location: ".$refresh);
                        die();
                }
		$this->view();
	}

	function treatmentPlanCount( $patient, $id ) {
		$query = "SELECT * FROM ".$this->table_name." WHERE patient='".addslashes($patient)."' ORDER BY datecreated";
		$result = $GLOBALS['sql']->query( $query );
		$count = 0;
		while ( $r = $GLOBALS['sql']->fetch_array( $result ) ) {
			$count++;
			if ($r['id'] == $id) { return $count; }
		}
		return $count+1;
	}

	function to_html ( $id, $html = false ) {
		ob_start();
		$TeX = CreateObject('_FreeMED.TeX');
		$rec = freemed::get_link_rec($id, $this->table_name, true);
		$patient = freemed::get_link_rec($rec[$this->patient_field], 'patient', true);
		$facility = freemed::get_link_rec($rec['facility'], 'facility');
		$patient_object = CreateObject('_FreeMED.Patient', $patient['id']);
		$createduser = CreateObject('_FreeMED.User', $rec['createdby']);
		$approveduser = CreateObject('_FreeMED.User', $rec['approvedby']);
		$planid = $this->treatmentPlanCount($rec['patient'], $id);

		$a = array (
			array ("<b>Patient</b>: ".$patient_object->fullName()." (${patient['ptid']})", "<b>Treatment Facility</b>: ${facility['psrname']}", "<b>Date of Admission</b>: ${rec['dateofadmission']}"),
			array ("<b>Plan ID</b>: ${planid}", "<b>Created on</b> ${rec['createddate']} by ".$createduser->getName(), "<b>Period Covered</b>: ${rec['periodcovered']} days" ),
			array ("<b>Chart ID</b>: ${patient['ptid']}", ($rec['approvedby']>0 ? "<b>Approved On</b>: ${rec['dateapproved']} by ".$approveduser->getName() : "<i>Plan not approved</i>" ), "<b>Expires</b>: ${rec['dateexpires']}"),
		);
		print $html ? $this->arrayToHtml($a) : $this->arrayToTeX($a);

		for ($i=1; $i<=8; $i++) {
			$temp = freemed::get_link_rec($rec["d${i}code"], 'codes');
			$dsm[$i] = "${temp['codevalue']} - ${temp['codedescripinternal']}";
		}

		if (!$html) { print "\\medskip\n\\hrule\n"; } else { print "<hr/>\n"; }
		if (!$html) {
			print " \\\\\n\n\n\n\\flushleft{\\textbf{Diagnostic Impressions: DSM-IV}}\n\n";
		} else {
			print "<br/><br/><center><b>Diagnostic Impressions: DSM-IV</b></center>\n";
		}
		$a = array (
			array ( "Primary Axis I", $dsm[1] ),
			array ( "Secondary Axis I", $dsm[2] ),
			array ( "Tertiary Axis I", $dsm[3] ),
			array ( "Axis II", $dsm[4] ),
			array ( "Axis III", $dsm[5] ),
			array ( "Axis IV", $dsm[6] ),
			array ( "GAF - Current", $dsm[7] ),
			array ( "GAF - Highest", $dsm[8] )
		);
		print $html ? $this->arrayToHtml($a) : $this->arrayToTeX($a);

		if (!$html) { print "\\medskip\n\\hrule\n"; } else { print "<hr/>\n"; }

		$a = array (
			array ( "<b>Indicated Level of Care</b> : ", $rec['indicatedlevelofcare'] ),
			array ( "<b>If higher level care is assessed, but not accessed, explain</b> : ", $rec['highercareassessed'] )
		);
		print $html ? $this->arrayToHtml($a) : $this->arrayToTeX($a);

		if (!$html) {
			print " \\\\\n\n\n\n\\begin{center}\n{\\textbf{Interpretative Summary Update by Dimension}}\n\\end{center}\n\n";
		} else {
			print "<br/><br/><center><b>Interpretative Summary Update by Dimension</b></center>\n";
		}

		if ($this->treatmentPlanCount($rec['patient'], $id) == 1) {
			$status = array ( 0 => 'Low', 1 => 'Medium', 2 => 'High' );
			$a = array (
				array ( "Dimension D1 Status: ".$status[$rec['d1status']], "<b>Note</b>: ".str_replace("\n", "", $rec['d1note']) ),
				array ( "Dimension D2 Status: ".$status[$rec['d2status']], "<b>Note</b>: ".str_replace("\n", "", $rec['d2note']) ),
				array ( "Dimension D3 Status: ".$status[$rec['d3status']], "<b>Note</b>: ".str_replace("\n", "", $rec['d3note']) ),
				array ( "Dimension D4 Status: ".$status[$rec['d4status']], "<b>Note</b>: ".str_replace("\n", "", $rec['d4note']) ),
				array ( "Dimension D5 Status: ".$status[$rec['d5status']], "<b>Note</b>: ".str_replace("\n", "", $rec['d5note']) ),
				array ( "Dimension D6 Status: ".$status[$rec['d6status']], "<b>Note</b>: ".str_replace("\n", "", $rec['d6note']) )
			);
			print $html ? $this->arrayToHtml($a) : $this->arrayToTeX($a);
		}

		if (!$html) { print "\\hrule\n"; } else { print "<hr/>\n"; }

		$d = array (
			1 => 'Acute Intoxication and/or Withdrawal Potential',
			2 => 'Biomedical Conditions and Complications',
			3 => 'Emotional/Behavioral Conditions and Complications',
			4 => 'Treatment Acceptance/Resistance',
			5 => 'Relapse/Continued Usage Potential',
			6 => 'Recovery Environment'
		);

		for ($dim=1; $dim<=6; $dim++) {
			if (!$html) {
				print "\n\\flushleft\\underline{\\textbf{Dimension $dim: ".$d[$dim]."}}\n\n";
			}
			$problems = $this->getProblems($id, $dim);
			foreach ($problems AS $problem) {
				print $html ? "<b>Problem</b>: ${problem['problem']}" : "\\flushleft\\parbox{8in}{\\textbf{Problem}: ".$TeX->_HTMLToRichText($problem['problem'])."}\n";
				$a = array (
					//array ( "<b>Problem</b>:", $problem['problem'], "", "" ),
					array ( "<b>Goals</b>:", "", "" ),
					array ( "<b>Long Term</b>: ".$problem['goalslongterm'], "<b>Effective Date</b>: <br/> ${problem['dateeffectivelong']}", "<b>Target Date</b>: <br/> ${problem['datetargetlong']}" ),
					array ( '', '', '' ),
					array ( "<b>Short Term</b>: ".$problem['goalsshortterm'], "<b>Effective Date</b>: <br/> ${problem['dateeffectiveshort']}", "<b>Target Date</b>: <br/> ${problem['datetargetshort']}"  )
				);
				print $html ? $this->arrayToHtml($a) : $this->arrayToTeX($a);
				print $html ? "<br/>" : "\\medskip\n"; 
				$a = array (
					array ( "<b>Objective</b>", "<b>Intervention</b>", "<b>Effective Date</b>", "<b>Target Date</b>" )
				);
				$oi = $this->getOI($problem['id']);
				foreach ($oi AS $thisoi) {
					$a[] = array ( $thisoi['objective'], $thisoi['intervention'], $thisoi['dateeffective'], $thisoi['datetarget'] );
				}
				print $html ? $this->arrayToHtml($a) : $this->arrayToTeX($a);
			}
			if (!$html) { print "\n\\hrule\n"; } else { print "<hr/>\n"; }
		}

		// Return contents of buffer
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	} // end method to_html

	function arrayToTeX ( $a ) {
		static $TeX;

		if (!isset($TeX)) { $TeX = CreateObject('_FreeMED.TeX'); }
		$EOL = "\n";

		// Display header
		$buffer .= '\begin{tabular}{llll}'. $EOL;
		foreach ($a AS $v) {
			switch (count($v)) {
				case 2: $w = '3.0'; break;
				case 3: $w = '2.5'; break;
				case 4: $w = '1.75'; break;
				default: $w = '3.0'; break;
			}
			foreach ($v AS $_k => $_v) {
				$v[$_k] = '\\parbox{'.$w.'in}{'.$TeX->_HTMLToRichText( $_v ).'}';
			}
			$buffer .= join(' & ', $v).' \\\\ '. $EOL;
		}
		$buffer .= '\end{tabular}'. $EOL;
		$buffer .= ' \\\\ '. $EOL;
		return $buffer;
	}

	function arrayToHtml ( $a ) {
		$buffer .= "<table border=\"0\" cellpadding=\"7\" width=\"85%\">\n";
		foreach ($a AS $v) {
			$buffer .= "<tr><td>";
			$buffer .= join('</td><td>', $v);
			$buffer .= "</td></tr>\n";
		}
		$buffer .= "</table>\n";
		return $buffer;
	}

	function getOI ( $problem ) {
		$q = "SELECT * FROM treatmentplanoi WHERE oiproblem='".addslashes($problem)."'";
		$result = $GLOBALS['sql']->query( $q );
		while ($r = $GLOBALS['sql']->fetch_array( $result )) {
			$return[] = $r;
		}
		return $return;
	}

	function getProblems ( $treatmentplan, $dimension ) {
		$q = "SELECT * FROM treatmentplanproblem WHERE treatmentplan='".addslashes($treatmentplan)."' AND tpdsm='".addslashes($dimension)."'";
		$result = $GLOBALS['sql']->query( $q );
		while ($r = $GLOBALS['sql']->fetch_array( $result )) {
			$return[] = $r;
		}
		syslog(LOG_INFO, "getProblems ( $treatmentplan, $dimension ) = ".count($return));
		return $return;
	}

	// Method: totalTreatmentPlanCount
	//
	//	Get total number of treatment plans for current patient.
	//
	// Parameters:
	//
	//	$id - Id of treatment plan in question
	//
	// Returns:
	//
	//	Number of treatment plans in total associated with the
	//	patient for this current treatment plan.
	//
	function totalTreatmentPlanCount ( $id ) {
		$tp = freemed::get_link_rec( $id, $this->table_name );
		$patient = $tp['patient'];
		$res = $GLOBALS['sql']->query( "SELECT COUNT(*) AS my_count FROM ".$this->table_name." WHERE patient='".addslashes($patient)."'" );
		$r = $GLOBALS['sql']->fetch_array( $res );
		return $r['my_count'];
	} // end method totalTreatmentPlanCount

	// Method: treatmentPlanOrder
	//
	//	Get order of treatment plan in sequence.
	//
	// Parameters:
	//
	//	$id - Id of treatment plan in question
	//
	// Returns:
	//
	//	Order number of this treatment plan in the date organized
	//	list of treatment plans for this patient.
	//
	function treatmentPlanOrder ( $id ) {
		$tp = freemed::get_link_rec( $id, $this->table_name );
		$patient = $tp['patient'];
		$res = $GLOBALS['sql']->query( "SELECT COUNT(*) AS my_count FROM ".$this->table_name." WHERE patient='".addslashes($patient)."' AND dateexpires < '".addslashes($tp['dateexpires'])."'" );
		$r = $GLOBALS['sql']->fetch_array( $res );
		return $r['my_count'] + 1;
	} // end method treatmentPlanOrder

	function view ( ) {
		global $sql; global $display_buffer; global $patient;
		$display_buffer .= freemed_display_itemlist (
			$sql->query("SELECT * FROM ".$this->table_name." ".
				"WHERE ".$this->patient_field."='".addslashes($patient)."' ".
				freemed::itemlist_conditions(false)." ".
				"ORDER BY ".$this->order_fields),
			$this->page_name,
			array(
				__("Date of Admission") => 'dateofadmission',
				__("Period Covered") => 'periodcovered',
			),
			array('', __("Not specified")) //blanks
		);
	} // end method view

	function additional_summary_icons ( $patient, $id ) {
		return " <a href=\"module_loader.php?module=".urlencode(get_class($this))."&action=addform&clone=".urlencode($id)."\"><img src=\"img/ark.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"[Clone]\" /></a>";
	} // end method additional_summary_icons

	// Update
	function _update ( ) {
		global $sql;
		$version = freemed::module_version($this->MODULE_NAME);

		/*
		// Version 0.2.1
		//
		//	Add "reviewed" field
		//
		if (!version_check($version, '0.2.1')) {
			$sql->query('ALTER TABLE '.$this->table_name.' '.
				'ADD COLUMN reviewed TIMESTAMP(14) AFTER patient');
		}
		*/
	} // end method _update

} // end class TreatmentPlanModule 

register_module ("TreatmentPlanModule");

?>
