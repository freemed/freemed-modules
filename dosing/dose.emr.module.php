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

class Dose extends EMRModule {
	var $MODULE_NAME = "Dose";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Dose';
	var $table_name = 'doserecord';
	var $patient_field = 'dosepatient';
	var $order_by = 'id';

	function Dose ( ) {
		$this->table_definition = array (
			'dosepatient' => SQL__INT_UNSIGNED(0),
			'doseplanid' => SQL__INT_UNSIGNED(0),
			'doseassigneddate' => SQL__DATE,
			'dosegiven' => SQL__INT_UNSIGNED(0),
			'dosegivenstamp' => SQL__TIMESTAMP(14),
			'dosegivenuser' => SQL__INT_UNSIGNED(0),
			'dosestation' => SQL__INT_UNSIGNED(0),
			'dosemedicationtype' => SQL__VARCHAR(200),
			'dosemedicationdispensed' => SQL__INT_UNSIGNED(0),
			'dosepouredunits' => SQL__INT_UNSIGNED(0),
			'dosepreparedunits' => SQL__INT_UNSIGNED(0),
			'doseunits' => SQL__CHAR(10),
			'id' => SQL__SERIAL
		);

		$this->variables = array (
			'dosepatient' => $_REQUEST['patient'],
			'doseplanid',
			'doseassigneddate',
			'dosegivenstamp' => SQL__NOW,
			'dosegivenuser' => $GLOBALS['this_user']->user_number,
			'dosestation',
			'dosemedicationtype',
			'dosemedicationdispensed',
			'dosepouredunits',
			'dosepreparedunits',
			'doseunits'
		);

		$this->summary_vars = array (
			__("Date")    =>	"doseassigneddate",
			__("Given By") =>	"doseplangivenuser:user"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'id DESC';

		$this->EMRModule();
	} // end constructor Dose

	function form_table ( ) {
		if (!isset($_REQUEST['doseplan'])) {
			$q = $GLOBALS['sql']->fetch_array($GLOBALS['sql']->query("SELECT id FROM doseplan WHERE doseplanpatient='".addslashes($_REQUEST['patient'])."' ORDER BY id DESC LIMIT 1"));
			$_REQUEST['doseplan'] = $doseplan = $q['id'];
		}
		return array (
			__("Hold Status (by patient)") => ( module_function( 'dosehold', 'GetCurrentHoldStatusByPatient', array ( $_REQUEST['patient'] ) ) ? "ACTIVE HOLD" : "No holds" ),
			__("Dosing Plan") => module_function ('doseplan', 'widget', array ( 'doseplan', $_REQUEST['patient'] ) ),
			__("Dosing Station") => module_function ('dosingstation', 'widget', array ( 'dosestation' ) ),
			__("Assigned Date") => fm_date_entry ( 'doseassigneddate' ),
			__("Medication Type") => html_form::text_widget( 'dosemedicationtype' ),
			__("Medication Dispensed") => html_form::text_widget( 'dosemedicationdispensed' ),
			__("Prepared / Poured") => html_form::text_widget( 'dosepreparedunits' ).' / '.html_form::text_widget( 'dosepouredunits' ),
			__("Units") => html_form::text_widget( 'doseunits' )
		);
	} // end method form_table

} // end class Dose

register_module("Dose");

?>
