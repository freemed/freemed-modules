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

class ReconcileBottle extends MaintenanceModule {
	var $MODULE_NAME = "ReconcileBottle";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;

	var $record_name = 'Reconcile Bottle';
	var $table_name = 'reconcilereport';
	var $order_by = 'rec_per_end';

	
	function ReconcileBottle ( ) {
		$this->table_definition = array (
			'rec_bottle_id' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)),
			'rec_user' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // ID of user performing reconcile
			// begin/end order is to get mysql's defaults right
			'rec_per_end' => SQL__NOT_NULL(SQL__TIMESTAMP),	// end of period (SQL__NOW)
			'rec_per_begin' => SQL__NOT_NULL(SQL__TIMESTAMP), // beginning of period (last reconcile or 0000-00-00)
			'rec_qty_initial' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // quantity at period-begin
			'rec_qty_tr_in' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // qty transferred in
			'rec_qty_tr_out' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // qty transferred out
			'rec_qty_disp' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // qty dispensed, non take-home
			'rec_qty_disp_takehome' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // qty dispensed, take-home
			'rec_qty_spill' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // qty spilled in mistakes
			'rec_qty_final_expected' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // qty expected at end (calculated from above)
			'rec_reason' => SQL__VARCHAR(255), // reason for discrepancy, if any
			'rec_qty_final_actual' => SQL__NOT_NULL(SQL__INT_UNSIGNED(0)), // qty measured at end
			'id' => SQL__SERIAL
		);

		if (!is_object($GLOBALS['this_user'])) { $GLOBALS['this_user'] = CreateObject('_FreeMED.User'); }
		
		$this->variables = array (
			'rec_bottle_id',
			'rec_user',
			'rec_per_begin',
			'rec_per_end' => SQL__NOW,
			'rec_qty_initial',
			'rec_qty_tr_in',
			'rec_qty_tr_out',
			'rec_qty_disp',
			'rec_qty_disp_takehome',
			'rec_qty_spill',
			'rec_qty_final_expected',
			'rec_reason',
			'rec_qty_final_actual'
		);

		// will need a correct version of this if we merge in the 'view' functionality
		/*
		$this->summary_vars = array (
			__("Date") => "lotmgtdate",
			__("Received Qty.") =>	"lot_rec_qty",
			__("Used Qty.") =>	"",
			__("Ref. No.") =>	"lotsuppl_refno",
			__("Balance Qty") => "lot_bal_qty",
			__("Posted By") => "lot_rec_by"
		);
		*/
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_PRINT;
		$this->summary_order_by = 'rec_per_end';

		$this->_SetHandler('DosingFunctions', 'addform');
		$this->_SetMetaInformation('DosingFunctionName', __("Reconcile Bottle"));
		$this->_SetMetaInformation('DosingFunctionDescription', __("Reconcile a bottle.") );

		// Set associations
		$this->MaintenanceModule();
	} // end constructor Lot

	function modform ( ) { }
	function mod ( ) { }
	function del ( ) { }

	function addform ( ) {
		ob_start();
		include_once ('reconcile_wizard.php');
		$GLOBALS['display_buffer'] .= ob_get_contents();
		ob_end_clean();
		return true;
	} // end method addform

	function view ( ) { }

	// AJAX FUNCTIONS HERE
	function ajax_reconcile ( $blob ) {
		$blobr = explode(",", $blob);
		$rec_bottle_id = array_shift($blobr);
		$rec_qty_final_actual = array_shift($blobr);
		// last element of $blob is a possibly-comma-laden comment
		$rec_reason = implode(",", $blobr);
		
		// period start: max(0000-00-00, date of last reconcile)
		$q = $GLOBALS['sql']->query("SELECT * FROM ".$this->table_name." WHERE rec_bottle_id='".addslashes($rec_bottle_id)."' ORDER BY rec_per_end DESC LIMIT 1");
		$r = $GLOBALS['sql']->fetch_array($q);
		if ($r['rec_per_end'] != null) {
			$rec_per_begin = $r['rec_per_end'];
			$rec_qty_initial = $r['rec_qty_final_actual'];
		} else {
			$rec_per_begin = "0000-00-00";
			$bottle = freemed::get_link_rec($rec_bottle_id, 'lotreceipt');
			$rec_qty_initial = $bottle['lotrecqtytotal'];
		}

		// quantities transferred, as described by transfer records
		$q = $GLOBALS['sql']->query("SELECT SUM(bt_quantity) AS total FROM bottletransfer WHERE bt_to_bottle='".addslashes($rec_bottle_id)."' AND bt_date >= '".addslashes($rec_per_begin)."'");
		$r = $GLOBALS['sql']->fetch_array($q);
		$rec_qty_tr_in = $r['total'];

		$q = $GLOBALS['sql']->query("SELECT SUM(bt_quantity) AS total FROM bottletransfer WHERE bt_from_bottle='".addslashes($rec_bottle_id)."' AND bt_date >= '".addslashes($rec_per_begin)."'");
		$r = $GLOBALS['sql']->fetch_array($q);
		$rec_qty_tr_out = $r['total'];

		// quantities dispensed, as described by dosing records
		$q = $GLOBALS['sql']->query("SELECT SUM(doseunits) AS total FROM doserecord WHERE dosebottleid='".addslashes($rec_bottle_id)."' AND doseassigneddate = CAST(dosegivenstamp AS DATE) AND dosegiven=1");
		$r = $GLOBALS['sql']->fetch_array($q);
		$rec_qty_disp = $r['total'];
		
		$q = $GLOBALS['sql']->query("SELECT SUM(doseunits) AS total FROM doserecord WHERE dosebottleid='".addslashes($rec_bottle_id)."' AND doseassigneddate > CAST(dosegivenstamp AS DATE) AND dosegiven=1");
		$r = $GLOBALS['sql']->fetch_array($q);
		$rec_qty_disp_takehome = $r['total'];

		/* TODO dosepreparedunits? dosepouredunits? asked JC 12/9 --adb
		$q = $GLOBALS['sql']->query("SELECT SUM(????) AS total FROM doserecord WHERE dosebottleid='".addslashes($rec_bottle_id)."' AND dosegiven=2");
		$r = $GLOBALS['sql']->fetch_array($q);
		$rec_qty_spill = $r['total'];
		*/
		$rec_qty_spill = 0; // placeholder
		
		// NOTE: We're calculating the amount that should be left in
		// the bottle, but we could just as easily select it from the
		// record in lotreceipt. This decision was arbitrary.
		$rec_qty_final_expected = $rec_qty_initial
			+ $rec_qty_tr_in
			- $rec_qty_tr_out
			- $rec_qty_disp
			- $rec_qty_disp_takehome
			- $rec_qty_spill;

		// TODO asked Jeff how to get current UID 12/9 --adb
		$rec_user = 0;

		// save the reconcile report
		$q = $GLOBALS['sql']->insert_query(
			$this->table_name,
			$this->variables
		);
		$GLOBALS['sql']->query($q);
		// update remaining quantity in the bottle
		$GLOBALS['sql']->query("UPDATE lotreceipt SET lotrecqtyremain = '".addslashes($rec_qty_final_actual)."' WHERE id = '".addslashes($rec_bottle_id)."'");
	}
} // end class ReconcileBottle 

register_module("ReconcileBottle");

?>
