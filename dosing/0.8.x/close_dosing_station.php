<?php
  // $Id$
  //
  // Authors:
  //      Jeff Buchbinder <jeff@freemedsoftware.org>
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

// Comment out when run from elsewhere
include_once('lib/freemed.php');

// Load FreeMED 0.8.x module cache
freemed::module_cache();
global $sql;

// Load values from session if they exist
global $dosingstation, $txtLotNo;
$dosingstation = $_SESSION['dosing']['dosingstation'];
$txtLotNo = $_SESSION['dosing']['txtLotNo'];
$btlno = $_SESSION['dosing']['btlno'];

?>

<style type="text/css">

	#dosePlanDisplay, #dosePlanDisplay * {
		size: 8pt;
		}

</style>
<script language="javascript" src="lib/dojo/dojo.js"></script>
<script language="javascript">
	dojo.require("dojo.io.*");
	dojo.require("dojo.widget.Dialog");
	dojo.require("dojo.widget.DropdownDatePicker");
	dojo.require("dojo.widget.Wizard");
	dojo.require("dojo.widget.Tooltip");

	var dw = {
		onCancel: function ( ) {
			alert('Cancelling operation as requested.');
			history.go(-1); // go back from where you came ...
		},
		onFinished: function ( ) {
			var station = parseInt( document.getElementById( 'dosingstation' ).value );
			var initial_qty = parseInt( document.getElementById( 'initial_qty' ).value );
			var amt_tr_from = parseInt( document.getElementById( 'amt_tr_from' ).value );
			var amt_tr_prior = parseInt( document.getElementById( 'amt_tr_prior' ).value );
			var amt_tr_to = parseInt( document.getElementById( 'amt_tr_to' ).value );
			var qty_dispensed = parseInt( document.getElementById( 'qty_dispensed' ).value );
			var qty_spill_dispensed = parseInt( document.getElementById( 'qty_spill_dispensed' ).value );
			var qty_spill_other = parseInt( document.getElementById( 'qty_spill_other' ).value );
			var qty_weight = parseInt( document.getElementById( 'qty_weight' ).value );
			var empty_bottle_wt = parseInt( document.getElementById( 'empty_bottle_wt' ).value );
			// Is there a way to encode this so that commas don't cause breakage?
			var reason = document.getElementById( 'reason' ).value;
                        
			var hash = station + ','
				+ initial_qty + ','
				+ amt_tr_from + ','
				+ amt_tr_prior + ','
				+ amt_tr_to + ','
				+ qty_dispensed + ','
				+ qty_spill_dispensed + ','
				+ qty_spill_other + ','
				+ qty_weight + ','
				+ empty_bottle_wt + ','
				+ reason + ',closed';
			// Set up blocker
			dojo.widget.byId( 'settingDialog' ).show();
			// Set pump to closed
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=ajax_closePump&param[]=' + hash,
				load: function( type, data, evt ) {
					// Change blocker
					dojo.widget.byId( 'settingDialog' ).hide();
					if ( data ) {
						// all good
					} else {
						alert('Failed to close out pump.');
					}
				},
				mimetype: 'text/json',
				sync: true
			});

			window.location = 'dosing_functions.php';
			//history.go(-1); // go back from where you came ...
		},
		onClearSession: function ( ) {
			var hash = '0,0,0';
			// Save everything
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=SaveSession&param[]=' + hash,
				load: function( type, data, evt ) {
					// Do nothing, just saved.
				},
				mimetype: 'text/json'
			});
			return true;
		},
		onHandleRemaining: function ( ) {
			var oldBottle = parseInt( document.getElementById( 'oldBottle' ).value );
			var newBottle = parseInt( document.getElementById( 'destBottleId' ).value );
			var remaining = parseInt( document.getElementById( 'oldRemaining' ).value );
			if ( remaining < 1 ) { return true; }
			var hash = oldBottle + ',' + newBottle + ',' + remaining;
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=TransferBottles&method=ajax_transfer&param[]=' + hash,
				load: function( type, data, evt ) {
					alert( 'Remaining amount of methadone transferred.' );
				},
				mimetype: 'text/json'
			});
			return true;
		},
		onSelectStation: function ( ) {
                        var station = document.getElementById( 'dosingstation' ).value;
			if ( station == "" ) {
				return "You must select a dosing station!";
			}
			return false;
		}
	};

	function pass_selectStationPane() {
		return dw.onSelectStation();
	}
	
	dojo.addOnLoad(function() {
		dojo.event.connect( dojo.widget.byId( 'closeDosingStationContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.connect( dojo.widget.byId( 'handleRemainingPane' ), 'passFunction', dw, 'onHandleRemaining' );
		dojo.event.connect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

	dojo.addOnUnload(function() {
		dojo.event.disconnect( dojo.widget.byId( 'closeDosingStationContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.disconnect( dojo.widget.byId( 'handleRemainingPane' ), 'passFunction', dw, 'onHandleRemaining' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

</script>

<br/><br/><br/><br/>

<div dojoType="WizardContainer" id="closeDosingStationContainer" 
 style="width: 95%; height: 80%;" hideDisabledButtons="false"
 nextButtonLabel="Next &gt; &gt;" previousButtonLabel="&lt; &lt; Previous"
 cancelButtonLabel="Cancel" doneButtonLabel="Done">

	<div dojoType="WizardPane" label="Select Dosing Station (1/?)" id="selectStationPane" passFunction="pass_selectStationPane">

		<h1>Select Dosing Station (1/?)</h1>

                <p>
                        <i>Please select a dosing station if the station presented is
                        not the correct station.</i>
                </p>

                <table border="0" cellpadding="5">
                        <tr>
				<td align="right">Dosing Station</td>
				<td align="left"><?php print module_function( 'DosingStation', 'widget', array ( 'dosingstation', "dsenabled = 1 AND dsfacility='".addslashes($_SESSION['default_facility'])."' AND dsopen='open'" ) ); ?></td>
			</tr>

		</table>

	</div>

	<div dojoType="WizardPane" label="Reconcile Remaining Amount (2/?)" id="reconcilePane">
	
		<h1>Reconcile Remaining Amount (2/?)</h1>

		<p><i>Please fill in the following fields for now. These will
		be derived from information in the database once the reporting
		system is in place.</i></p>

		<table border="0" cellpadding="5">
			<tr>
				<td align="right">Initial quantity</td>
				<td align="left"><td align="left"><input type="text" id="initial_qty" name="initial_qty" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Amount transferred to bottles</td>
				<td align="left"><td align="left"><input type="text" id="amt_tr_from" name="amt_tr_from" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Amount transferred prior</td>
				<td align="left"><td align="left"><input type="text" id="amt_tr_prior" name="amt_tr_prior" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Amount transferred to</td>
				<td align="left"><td align="left"><input type="text" id="amt_tr_to" name="amt_tr_to" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Amount dispensed today</td>
				<td align="left"><td align="left"><input type="text" id="qty_dispensed" name="qty_dispensed" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Dosing-related spillages</td>
				<td align="left"><td align="left"><input type="text" id="qty_spill_dispensed" name="qty_spill_dispensed" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Other spillages</td>
				<td align="left"><td align="left"><input type="text" id="qty_spill_other" name="qty_spill_other" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Bottle weight</td>
				<td align="left"><td align="left"><input type="text" id="qty_weight" name="qty_weight" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Empty bottle weight</td>
				<td align="left"><td align="left"><input type="text" id="empty_bottle_wt" name="empty_bottle_wt" value="0" /></td>
			</tr>
			<tr>
				<td align="right">Comments</td>
				<td align="left"><td align="left"><input type="text" id="reason" name="reason" value="" /></td>
			</tr>
		</table>
	</div>

<!--
	<div dojoType="WizardPane" label="Handle Remaining Amount (1/2)" id="handleRemainingPane">

		<h1>Handle Remaining Amount (1/2)</h1>

		<p><i>Please assign the remaining amount elsewhere</i></p>

		<input type="hidden" id="oldBottle" name="oldBottle" value="<?php print $btlno; ?>" />

		<table border="0" cellspacing="0" cellpadding="5">

			<tr>
				<td align="right">Destination Bottle Number</td>
				<td align="left"><div id="destBottle"><?php print module_function( 'LotReceipt', 'getAjxBottleNos', array ( $txtLotNo . ',destBottleId' ) ); ?></div></td>
			</tr>

			<tr>
				<td align="right">Remaining Amount</td>
				<td align="left"><input type="text" id="oldRemaining" name="oldRemaining" value="0" /></td>
			</tr>

		</table>

	</div>
-->

	<div dojoType="WizardPane" id="dosingFinishedPane" label="Clean Pump (?/?)" canGoBack="false">
		<h1>Clearing Pump (?/?)</h1>

		<p><i>Please click "Done" to exit the wizard.</i></p>

	</div>

</div> <!-- container -->

<div dojoType="Dialog" id="settingDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
        <h1>Setting dosing station to closed ... </h1>
</div>

