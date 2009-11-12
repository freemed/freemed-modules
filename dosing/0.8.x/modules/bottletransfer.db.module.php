<?php
  // $Id$
  //
  // Authors:
  //      Adam Buchbinder <adam.buchbinder@gmail.com>
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

class BottleTransfer extends MaintenanceModule {
	var $MODULE_NAME = "";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Bottle Transfer';
	var $table_name = 'bottletransfer';
	var $order_by = 'bt_date DESC';
	var $widget_hash = "##id## [##bt_date##]";
	
	function BottleTransfer( ) {
	
		$this->table_definition = array (
			'bt_from_bottle' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)),
			'bt_to_bottle' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)),
			'bt_quantity' => SQL__INT_UNSIGNED(0),
			'bt_date' => SQL__TIMESTAMP(16),
			'id' => SQL__SERIAL
		);

		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }
		
		$this->variables = array (
			'bt_from_bottle',
			'bt_to_bottle',
			'bt_quantity',
			'bt_date' => SQL__NOW,
		);

		$this->summary_vars = array (
			__("From") => "bt_from_bottle:lotreceipt:lotrecbottleno",
			__("To") => "bt_to_bottle:lotreceipt:lotrecbottleno",
			__("Quantity") => "bt_quantity",
			__("Date") => "bt_date",
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'bt_date';

		// Set associations
		$this->_SetHandler('DosingFunctions', 'addform');
		$this->_SetMetaInformation('DosingFunctionName', __("Bottle Transfer"));
		$this->_SetMetaInformation('DosingFunctionDescription', __("Transfer contents of one bottle to another.") );

		$this->MaintenanceModule();
	} // end constructor BottleTransfer

	function modform ( ) { }
	function mod ( ) { }
	function del ( ) { }
	
	function addform ( ) {
		ob_start();
		include_once ('bottle_transfer.php');
		$GLOBALS['display_buffer'] .= ob_get_contents();
		ob_end_clean();
		return true;
	} // end method addform

	function view ( ) {
		global $sql; 
		global $display_buffer;

		$display_buffer = freemed_display_itemlist (
			$sql->query("SELECT lr_from.lotrecbottleno AS from_bottle,lr_to.lotrecbottleno AS to_bottle,bt_quantity,bt_date FROM bottletransfer,lotreceipt AS lr_from,lotreceipt AS lr_to WHERE bt_from_bottle = lr_from.id AND bt_to_bottle = lr_to.id"),
			$this->page_name,
			array(
				__("Date") =>	"bt_date",
				__("From") =>	"from_bottle",
				__("To") =>	"to_bottle",
				__("Qty") =>	"bt_quantity"
			), NULL, NULL, NULL, NULL,
                        ITEMLIST_VIEW
		);

	} // end method view

	// Perform an actual transfer of meds from one bottle to another.
	function ajax_transfer ( $params ) {
		list ($from, $to, $qty) = explode(",", $params);
		if (($from == null || $to == null) || $qty == null) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Called with at least one null parameter: [$params]");
			return false;
		}
		syslog(LOG_INFO, "bottletransfer/ajax_transfer: Called with [$params]");
		if ($qty + 0 <= 0) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Non-positive quantity [$qty]");
			return false;
		}
		if ($from == $to) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Cannot transfer bottle ($from) to itself");
			return false;
		}
		$r = $GLOBALS['sql']->query("SELECT COUNT(*) AS count FROM dosingstation WHERE dsopen='open' AND dsenabled=1 AND (dsbottle='".addslashes($from)."' OR dsbottle='".addslashes($to)."')");
		$row = $GLOBALS['sql']->fetch_array($r);
		if ($row['count'] != 0) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Bottle [$from] or [$to] is attached to an open dosing station");
		}
		// records exist
		$from_rec = freemed::get_link_rec($from, 'lotreceipt');
		if ($from_rec['id'] == null) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Source bottle [$from] not found in table lotreceipt");
			return false;
		}
		$to_rec = freemed::get_link_rec($to, 'lotreceipt');
		if ($to_rec['id'] == null) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Destination bottle [$to] not found in table lotreceipt");
			return false;
		}
		// there's enough left in the source
		if ($qty > $from_rec['lotrecqtyremain']) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Tried to transfer $qty from source $from, but only ".$from_rec['lotrecqtyremain']." remain");
			return false;
		}
		// there's enough room in the destination
		if ($qty > $to_rec['lotrecqtytotal'] - $to_rec['lotrecqtyremain']) {
			syslog(LOG_INFO, "bottletransfer/ajax_transfer: Tried to transfer $qty to destination $to, but there's only room for ".($to_rec['lotrecqtytotal'] - $to_rec['lotrecqtyremain']));
			return false;
		}
		syslog(LOG_INFO, "bottletransfer/ajax_transfer: all server-side sanity checks passed");
		// perform the actual transfer
		$q = "INSERT INTO ".$this->table_name." SET ".
			"bt_from_bottle = '".addslashes($from)."', ".
			"bt_to_bottle = '".addslashes($to)."', ".
			"bt_quantity = '".addslashes($qty)."'";
		$GLOBALS['sql']->query($q);
		syslog(LOG_INFO, "bottletransfer/ajax_transfer: returning true");
		return true;
	}

        function _update ( ) {
		global $sql;
		$version = freemed::module_version($this->MODULE_NAME);
		// Version 0.1
		//
		//      No provision for triggers in table definition; add
		//      them here as an 'upgrade' from version not-there-at-all.
		//
		/* Adding triggers via PHP requires mysqli's multi_query function.
		 * do this manually for now.
		if (!version_check($version, '0.1')) {
			"DELIMITER |
			CREATE TRIGGER bottletransfer_ai AFTER INSERT ON bottletransfer
			 FOR EACH ROW BEGIN
			   UPDATE lotreceipt SET lotreceipt.lotrecqtyremain = lotreceipt.lotrecqtyremain - NEW.bt_quantity WHERE lotreceipt.id = NEW.bt_from_bottle;
			   UPDATE lotreceipt SET lotreceipt.lotrecqtyremain = lotreceipt.lotrecqtyremain + NEW.bt_quantity WHERE lotreceipt.id = NEW.bt_to_bottle;
			  END;
			|";
		}
		*/
	} // end function _update
} // end class BottleTransfer

register_module("BottleTransfer");

?>
