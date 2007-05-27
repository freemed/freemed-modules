<?php
 // $Id$
 //
 // Authors:
 // 	Jeff Buchbinder <jeff@freemedsoftware.org>
 //
 // FreeMED Electronic Medical Record and Practice Management System
 // Copyright (C) 1999-2007 FreeMED Software Foundation
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

LoadObjectDependency('org.freemedsoftware.core.SupportModule');

class FoodAssistanceInventory extends SupportModule {

	var $MODULE_NAME    = "Food Assistance Inventory";
	var $MODULE_VERSION = "0.1";
	var $MODULE_FILE    = __FILE__;
	var $MODULE_UID     = "63533499-ac8a-4130-9587-68334295a631";
	var $MODULE_HIDDEN  = true;

	var $PACKAGE_MINIMUM_VERSION = '0.8.0';

	var $record_name    = "Food Assistance Inventory";
	var $table_name     = "foodassistanceinventory";
	var $variables      = array (
		'enteredby',
		'dateof',
		'contents',
		'comment',
		'totalcount',
		'currentcount'
	);
	var $order_field = 'dateof';
	var $widget_hash = '##dateof##';

	public function __contruct () {
		// For i18n: __("Food Assistance Inventory")

		// Run parent constructor
		parent::__construct();
	} // end constructor

	protected function add_pre ( &$data ) {
		$s = CreateObject( 'org.freemedsoftware.api.Scheduler' );
		$data['fa_dob'] = $s->ImportDate( $data['fa_dob'] );
	}

	protected function mod_pre ( &$data ) {
		$s = CreateObject( 'org.freemedsoftware.api.Scheduler' );
		$data['fa_dob'] = $s->ImportDate( $data['fa_dob'] );
	}

} // end class FoodAssistanceInventory

register_module ("FoodAssistanceInventory");

?>
