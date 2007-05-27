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

class FoodAssistancePerson extends SupportModule {

	var $MODULE_NAME    = "Food Assistance Person Record";
	var $MODULE_VERSION = "0.1";
	var $MODULE_FILE    = __FILE__;
	var $MODULE_UID     = "c9015b3d-e755-47d2-82b7-87138c2923ca";

	var $PACKAGE_MINIMUM_VERSION = '0.8.0';

	var $record_name    = "Food Assistance Person";
	var $table_name     = "foodassistanceperson";
	var $variables      = array (
		'fa_patient',
		'fa_lastname',
		'fa_firstname',
		'fa_middlename',
		'fa_dob',
		'fa_address',
		'fa_city',
		'fa_state',
		'fa_age',
		'fa_household_size',
		'fa_household_elderly',
		'fa_household_disabled',
		'fa_household_children',
		'fa_programs'
	);
	var $order_field = 'fa_lastname, fa_firstname';
	var $widget_hash = '##fa_lastname##, ##fa_firstname## ##fa_middlename## (##fa_dob##)';

	public function __contruct () {
		// For i18n: __("Food Assistance Person Record")

		$this->list_view = array (
			__("Last Name") => "fa_lastname",
			__("First Name") => "fa_firstname",
			__("Date of Birth") => "fa_dob"
		);

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

} // end class FoodAssistancePerson

register_module ("FoodAssistancePerson");

?>
