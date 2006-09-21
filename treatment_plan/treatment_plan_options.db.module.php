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

LoadObjectDependency('_FreeMED.MaintenanceModule');

class TreatmentPlanOptions extends MaintenanceModule {

	var $MODULE_NAME = 'Treatment Plan Options';
	var $MODULE_AUTHOR = 'jeff b (jeff@ourexchange.net)';
	var $MODULE_VERSION = '0.1';
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.2';

	var $table_name = "tpoptions";
	var $order_field = "tpdsm, tptype";
	var $widget_hash = "##tpoption##";

	function TreatmentPlanOptions () {
		// __("Treatment Plan Options")

		$this->table_definition = array (
			'tpdsm' => SQL__INT_UNSIGNED(0),
			'tptype' => SQL__VARCHAR(50),
			'tpoption' => SQL__VARCHAR(250),
			'id' => SQL__SERIAL
		);
		$this->table_keys = array ( 'tpdsm', 'tptype' );
		$this->variables = array ( 'tpdsm', 'tptype', 'tpoption' );

		// Call parent constructor
		$this->MaintenanceModule();
	} // end constructor TreatmentPlanOptions

	function generate_form ( ) {
		return array (
			"DSM" => html_form::select_widget(
					'tpdsm',
					array (
						'I' => 1,
						'II' => 2,
						'III' => 3,
						'IV' => 4,
						'V' => 5,
						'VI' => 6
					)
				),
			'Category' => html_form::select_widget(
					'tptype',
					array (
						'Problem' => 'problem',
						'Long Term Goal' => 'longtermgoal',
						'Short Term Goal' => 'shorttermgoal',
						'Objective' => 'objective',
						'Intervention' => 'intervention'
					)
				),
			'Value' => html_form::text_widget('tpoption', 50, 250)
		);
	} // end method form_table

	function view ( ) {
		$GLOBALS['display_buffer'] .= freemed_display_itemlist (
			$GLOBALS['sql']->query (
				"SELECT * FROM ".$this->table_name." ".
				freemed::itemlist_conditions ( )." ".
				"ORDER BY ".$this->order_field
			),
			$this->page_name,
			array (
				'Dimension' => 'tpdsm',
				'Category' => 'tptype',
				'Value' => 'tpoption'
			),
			array (
				'',
				'',
				''
			)
		);
	} // end method view

	function remote_picklist ( $dsm , $type, $search ) {
		$query = "SELECT tpoption FROM $this->table_name WHERE tpdsm='".addslashes($dsm)."' AND tptype='".addslashes($type)."' AND ( tpoption LIKE '%".addslashes($search)."%' )";
		$result = $GLOBALS['sql']->query( $query );
		while ( $r = $GLOBALS['sql']->fetch_array ( $result ) ) {
			$return[$r['tpoption']] = $r['tpoption'];
		}
		return $return;
	} // end method remote_picklist

} // end class TreatmentPlanOptions

register_module("TreatmentPlanOptions");

?>
