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

class FoodAssistanceItem extends SupportModule {

	var $MODULE_NAME    = "Food Assistance Item";
	var $MODULE_VERSION = "0.1";
	var $MODULE_FILE    = __FILE__;
	var $MODULE_UID     = "06e0e91f-41ad-4f06-9457-2ab4dcd6264e";
	var $MODULE_HIDDEN  = true;

	var $PACKAGE_MINIMUM_VERSION = '0.8.0';

	var $record_name    = "Food Assistance Item";
	var $table_name     = "foodassistanceitem";
	var $variables      = array (
		'itemname',
		'description',
		'instock'
	);
	var $order_field = 'itemname';
	var $widget_hash = '##itemname## (##description##)';

	public function __contruct () {
		// For i18n: __("Food Assistance Item")

		// Run parent constructor
		parent::__construct();
	} // end constructor

} // end class FoodAssistanceItem

register_module ("FoodAssistanceItem");

?>
