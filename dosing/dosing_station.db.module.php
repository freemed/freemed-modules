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

class DosingStation extends MaintenanceModule {

	var $MODULE_NAME = 'Dosing Station';
	var $MODULE_AUTHOR = 'jeff b (jeff@ourexchange.net)';
	var $MODULE_VERSION = '0.1';
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.2';

	var $table_name = "dosingstation";
	var $order_field = "dsname, dslocation";
	var $widget_hash = "##dsname## [##dslocation##]";

	function DosingStation () {
		// __("Dosing Station")

		$this->table_definition = array (
			'dsname' => SQL__VARCHAR(50),
			'dslocation' => SQL__VARCHAR(150),
			'dsurl' => SQL__VARCHAR(150),
			'dsenabled' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);
		$this->variables = array (
			'dsname',
			'dslocation',
			'dsurl',
			'dsenabled'
		);

		// Call parent constructor
		$this->MaintenanceModule();
	} // end constructor DosingStation

	function generate_form ( ) {
		return array (
			__("Station Name") => html_form::text_widget('dsname', 50),
			__("Location") => html_form::text_widget('dslocation', 150),
			__("URL") => html_form::text_widget('dsurl', 150),
			__("Dosing Enabled") => html_form::select_widget(
				'dsenabled',
				array (
					'enabled' => 1,
					'disabled' => 0
				)
			)
		);
	} // end method generate_form 

	function view ( ) {
		$GLOBALS['display_buffer'] .= freemed_display_itemlist (
			$GLOBALS['sql']->query (
				"SELECT * FROM ".$this->table_name." ".
				freemed::itemlist_conditions ( )." ".
				"ORDER BY ".$this->order_field
			),
			$this->page_name,
			array (
				__("Name") => 'dsname',
				__("Location") => 'dslocation'
			),
			array (
				'',
				''
			)
		);
	} // end method view

} // end class DosingStation

register_module("DosingStation");

?>
