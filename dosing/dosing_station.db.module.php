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
	var $MODULE_VERSION = '0.2';
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
			'dsfacility' => SQL__INT_UNSIGNED(0),
			'dsurl' => SQL__VARCHAR(150),
			'dsenabled' => SQL__INT_UNSIGNED(0),
			'dslast_close' => SQL__DATE,
			'dsopen' => SQL__ENUM( array ('open','closed') ),
			'dsbottle' => SQL__INT_UNSIGNED(0),
			'dslot' => SQL__INT_UNSIGNED(0),
			'sshkey' => SQL__TEXT,
			'id' => SQL__SERIAL
		);
		$this->variables = array (
			'dsname',
			'dslocation',
			'dsfacility',
			'dsurl',
			'dsenabled',
			'sshkey'
		);

		// Call parent constructor
		$this->MaintenanceModule();
	} // end constructor DosingStation

	function generate_form ( ) {
		return array (
			__("Station Name") => html_form::text_widget('dsname', 50),
			__("Location") => html_form::text_widget('dslocation', 150),
			__("Facility") => module_function( 'FacilityModule', 'widget', array( 'dsfacility' ) ),
			__("URL") => html_form::text_widget('dsurl', 150),
			__("Dosing Enabled") => html_form::select_widget(
				'dsenabled',
				array (
					'enabled' => 1,
					'disabled' => 0
				)
			),
			__("SSH Key") => html_form::text_area( 'sshkey' )
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

	function _update ( ) { 
		$version = freemed::module_version($this->MODULE_NAME);
                // Version 0.2
		//
		//      Add last-closed date and open status.
		//
		if (! version_check($version, '0.2')) {
			$GLOBALS['sql']->query ( "ALTER TABLE ".$this->table_name." ADD COLUMN dslast_close DATE" );
			$GLOBALS['sql']->query ( "UPDATE ".$this->table_name." SET dslast_close = date('0000-00-00')" );
			$GLOBALS['sql']->query ( "ALTER TABLE ".$this->table_name." ADD COLUMN dsopen ENUM( 'closed', 'open') NOT NULL" );
			// not-null enums default to the first item in the enumset.
			// $GLOBALS['sql']->query ( "UPDATE ".$this->table_name." SET dsopen = 'closed'" );
		}
	}


} // end class DosingStation

register_module("DosingStation");

?>
