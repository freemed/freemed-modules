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

class DoseHold extends EMRModule {
	var $MODULE_NAME = "Dose Hold";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Dose Hold';
	var $table_name = 'dosehold';
	var $patient_field = 'doseholdpatient';
	var $order_by = 'id';

	function DoseHold ( ) {
		$this->table_definition = array (
			'doseholdpatient' => SQL__INT_UNSIGNED(0),
			'doseholddoseplan' => SQL__INT_UNSIGNED(0),
			'doseholdtype' => SQL__INT_UNSIGNED(0),
			'doseholdstamp' => SQL__TIMESTAMP(14),
			'doseholdstatus' => SQL__INT_UNSIGNED(0),
			'doseholdrole' => SQL__INT_UNSIGNED(0),
			'doseholduser' => SQL__INT_UNSIGNED(0),
			'doseholdcoment' => SQL__TEXT,
			'id' => SQL__SERIAL
		);

		$this->variables = array (
			'doseholduser' => $GLOBALS['this_user']->user_number,
			'doseholdpatient' => $_REQUEST['patient'],
			'doseholddoseplan',
			'doseholdstamp' => SQL__NOW,
			'doseholdstatus',
			'doseholdrole',
			'doseholdcomment'
		);

		$this->summary_view = array (
			__("Date") => 'ts',
			__("Type") => 'hold_type',
			__("Status") => 'status'
		);
		$this->summary_query = array (
			"CASE doseholdtype WHEN 1 THEN 'soft' WHEN 2 THEN 'hard' ELSE 'none' END AS hold_type",
			"CASE doseholdstatus WHEN 1 THEN 'active' ELSE 'inactive' END AS status",
			"DATE_FORMAT(doseholdstamp, '%d %M %Y %H:%i') AS ts"
		);

		$this->EMRModule();
	} // end constructor DoseHold

	function form_table ( ) {
		return array (
			__("Hold Type") =>
				html_form::select_widget( 'doseholdtype',
					array (
						'none' => 0,
						'soft' => 1,
						'hard' => 2
					)
				),
			__("Status") =>
				html_form::select_widget( 'doseholdtype',
					array (
						__("Inactive") => 0,
						__("Active") => 1,
					)
				),
			__("Hold Role") =>
				html_form::select_widget( 'doseholdrole',
					array (
						__("None/System") => 0,
						__("Counselor") => 1,
						__("Director") => 2,
						__("Custom") => 3
					)
				),
			__("Comment") =>
				html_form::text_area("doseholdcomment")
		);
	}

	// Method: GetCurrentHoldStatusByPlan
	//
	//	Determine if there are active holds on a particular dose
	//	plan by id.
	//
	// Parameters:
	//
	//	$plan - Dose plan id
	//
	// Returns:
	//
	//	Boolean.
	//
	function GetCurrentHoldStatusByPlan ( $plan ) {
		$query = "SELECT COUNT(*) AS my_count FROM doserecord dr LEFT OUTER JOIN dosehold dh ON dh.doseholdplan = dr.doseplan WHERE dh.doseholdplan='".addslashes($plan)."' AND dh.doseholdstatus = 1 AND dh.doseholdtype > 0 AND dh.doseholdstamp <= NOW() ORDER BY dh.doseholdstamp";
		$result = $GLOBALS['sql']->query( $query );
		$r = $GLOBALS['sql']->fetch_array( $result );
		return $r['my_count'] > 0;
	} // end method GetCurrentHoldStatusByPlan

	// Method: GetCurrentHoldStatusByPatient
	//
	//	Determine if there are active holds on a particular patient.
	//
	// Parameters:
	//
	//	$patient - Patient id
	//
	// Returns:
	//
	//	Boolean.
	//
	function GetCurrentHoldStatusByPatient ( $patient ) {
		$query = "SELECT COUNT(*) AS my_count FROM doserecord dr LEFT OUTER JOIN dosehold dh ON dh.doseholdplan = dr.doseplan WHERE dh.doseholdpatient='".addslashes($patient)."' AND dh.doseholdstatus = 1 AND dh.doseholdtype > 0 AND dh.doseholdstamp <= NOW() ORDER BY dh.doseholdstamp";
		$result = $GLOBALS['sql']->query( $query );
		$r = $GLOBALS['sql']->fetch_array( $result );
		return $r['my_count'] > 0;
	} // end method GetCurrentHoldStatusByPatient

} // end class DoseHold

register_module("DoseHold");

?>
