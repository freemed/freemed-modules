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

class TreatmentPlanType extends EMRModule {

	var $MODULE_NAME = "Treatment Plan Type";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_VERSION = "0.1";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.3';

	var $record_name = "Treatment Plan Type";
	var $table_name = 'treatmentplantype';
	var $patient_field = 'tptpatient';
	var $date_field = 'tptstamp';
	var $widget_hash = '##tpttype## (##tptcomment##) ##tptstamp##';

	function TreatmentPlanType () {
		global $this_user;
		$this_user = CreateObject('_FreeMED.User');

		$this->table_definition = array (
			'tptpatient' => SQL__INT_UNSIGNED(0),
			'tpteoc' => SQL__INT_UNSIGNED(0),
			'tptstamp' => SQL__TIMESTAMP(14),
			'tpttype' => SQL__VARCHAR(50),
			'tptcomment' => SQL__TEXT,
			'tptuser' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);

		$this->variables = array (
			'tpttype' => html_form::combo_assemble('tpttype'),
			'tptcomment' => html_form::combo_assemble('tptcomment'),
			'tptpatient' => $_REQUEST['patient'],
			'tptuser' => $this_user->user_number,
			'tptstamp' => SQL__NOW
		);

		$this->summary_vars = array (
			__("Date") => 'tptstamp',
			__("Type") => 'tpttype',
			__("Comment") => 'tptcomment'
		);
		$this->summary_options = SUMMARY_DELETE;
		$this->summary_order_by = 'tptstamp';

		// call parent constructor
		$this->EMRModule();
	} // end constructor TreatmentPlanType 

	function form_table ( ) {
		include_once(freemed::template_file('ajax.php'));
		return array (
			__("Type") =>
			ajax_distinct_widget( 'tpttype', $this->table_name, 'tpttype' ),

			__("Comment") =>
			ajax_distinct_widget( 'tptcomment', $this->table_name, 'tptcomment' )

		);
	} // end method form_table

	function view ( ) {
		global $display_buffer;
		$display_buffer .= freemed_display_itemlist (
			$GLOBALS['sql']->query("SELECT * FROM ".$this->table_name." ".
				"WHERE tptpatient='".addslashes($_REQUEST['patient'])."' ".
				freemed::itemlist_conditions(false)." ".
				"ORDER BY tptstamp"),
			$this->page_name,
			array(
				__("Date") => 'tptstamp',
				__("Type") => 'tpttype',
				__("Comment") => 'tptcomment'
			),
			array('', __("Not specified")) //blanks
		);
	} // end method view

} // end class TreatmentPlanType

register_module ("TreatmentPlanType");

?>
