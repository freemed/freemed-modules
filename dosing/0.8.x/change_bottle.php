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

	function getBottleNumbers ( ) {
		dojo.io.bind({
			method: 'GET',
			url: 'json-relay-0.8.x.php?module=lotreceipt&method=getAjaxBottleNos&param[]=' + document.getElementById( 'txtLotNo' ).value,
			load: function( type, data, evt ) {
				document.getElementById( 'idBtlNo' ).innerHTML = data;
			},
			mimetype: 'text/json'
		});
	}

	var dw = {
		dosePlan: 0,
		onCancel: function ( ) {
			alert('Cancelling dose operation as requested.');
			history.go(-1); // go back from where you came ...
		},
		onFinished: function ( ) {
			history.go(-1); // go back from where you came ...
		},
		onClearPump: function ( ) {
			// Set up blocker
			dojo.widget.byId( 'clearDialog' ).show();

			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=clearPump&param[]=' + document.getElementById( 'dosingstation' ).value,
				load: function( type, data, evt ) {
					// Close blocker
					dojo.widget.byId( 'clearDialog' ).hide();
					if ( data ) {
						// all good	
					} else {
						alert('Failed to clear the pump.');
					}
				},
				mimetype: 'text/json'
			});
		},
		onCloseCleanPump: function ( ) {
			// Set up blocker
			dojo.widget.byId( 'clearDialog' ).show();

			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=closePumpClean&param[]=' + document.getElementById( 'dosingstation' ).value,
				load: function( type, data, evt ) {
					// Close blocker
					dojo.widget.byId( 'clearDialog' ).hide();
					if ( data ) {
						// all good	
					} else {
						alert('Failed to cycle the pump.');
					}
				},
				mimetype: 'text/json'
			});
		},
		onCloseFlushPump: function ( ) {
			// Set up blocker
			dojo.widget.byId( 'clearDialog' ).show();

			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=closePumpFluh&param[]=' + document.getElementById( 'dosingstation' ).value,
				load: function( type, data, evt ) {
					// Close blocker
					dojo.widget.byId( 'clearDialog' ).hide();
					if ( data ) {
						// all good	
					} else {
						alert('Failed to cycle the pump.');
					}
				},
				mimetype: 'text/json'
			});
		},
		onPrimePump: function ( ) {
			// Set up blocker
			dojo.widget.byId( 'primeDialog' ).show();

			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=primePump&param[]=' + document.getElementById( 'dosingstation' ).value,
				load: function( type, data, evt ) {
					// Close blocker
					dojo.widget.byId( 'primeDialog' ).hide();
					if ( data ) {
						// all good	
					} else {
						alert('Failed to prime the pump.');
					}
				},
				mimetype: 'text/json'
			});
		},
		onOpenStation: function ( ) {
			var station = document.getElementById( 'dosingstation' ).value;
			if ( station == '' ) {
				alert('You must select a dosing station.');
				return false;
			}
		},
		onSaveSession: function ( ) {
			var station = document.getElementById( 'dosingstation' ).value;
			var txtLotNo = document.getElementById( 'txtLotNo' ).value;
			var btlno = document.getElementById( 'btlno' ).value;
			var hash = station + ',' + txtLotNo + ',' + btlno;
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
		}
	};

	dojo.addOnLoad(function() {
		dojo.event.connect( dojo.widget.byId( 'openDosingStationContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.connect( dojo.widget.byId( 'dosingStationPane' ), 'passFunction', dw, 'onOpenStation' );
		dojo.event.connect( dojo.widget.byId( 'handleRemainingPane' ), 'passFunction', dw, 'onHandleRemaining' );
		dojo.event.connect( dojo.widget.byId( 'dosingStationBottleLotPane' ), 'passFunction', dw, 'onSaveSession' );
		dojo.event.connect( dojo.widget.byId( 'dosingClearPane' ), 'passFunction', dw, 'onClearPump' );
		dojo.event.connect( dojo.widget.byId( 'dosingPrimingPane' ), 'passFunction', dw, 'onPrimePump' );
		dojo.event.connect( dojo.widget.byId( 'dosingDosePane' ), 'passFunction', dw, 'onDispenseDose' );
		dojo.event.connect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

	dojo.addOnUnload(function() {
		dojo.event.disconnect( dojo.widget.byId( 'openDosingStationContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingStationPane' ), 'passFunction', dw, 'onOpenStation' );
		dojo.event.disconnect( dojo.widget.byId( 'handleRemainingPane' ), 'passFunction', dw, 'onHandleRemaining' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingStationBottleLotPane' ), 'passFunction', dw, 'onSaveSession' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingClearPane' ), 'passFunction', dw, 'onClearPump' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingPrimingPane' ), 'passFunction', dw, 'onPrimePump' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

</script>

<br/><br/><br/><br/>

<div dojoType="WizardContainer" id="openDosingStationContainer" 
 style="width: 95%; height: 80%;" hideDisabledButtons="false"
 nextButtonLabel="Next &gt; &gt;" previousButtonLabel="&lt; &lt; Previous"
 cancelButtonLabel="Cancel" doneButtonLabel="Done">

	<div dojoType="WizardPane" label="Handle Remaining Amount (1/6)" id="handleRemainingPane">

		<h1>Handle Remaining Amount (1/6)</h1>

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

	<div dojoType="WizardPane" label="Select Dosing Station (2/6)" id="dosingStationPane">
		<h1>Select Dosing Station (2/6)</h1>

		<p>
			<i>Please select a dosing station if the station presented is
			not the correct station.</i>
		</p>

		<table border="0" cellpadding="5">

			<tr>
				<td align="right">Dosing Station</td>
				<td align="left"><?php print module_function( 'DosingStation', 'widget', array ( 'dosingstation' ) ); ?></td>
			</tr>

		</table>

	</div>

	<div dojoType="WizardPane" label="Clear Pump (3/6)" id="dosingClearPane" canGoBack="false">

		<h1>Clear Pump (3/6)</h1>

		<p><i>
		Click "Next" to clear the selected pump.
		</i></p>

	</div>

	<div dojoType="WizardPane" label="Select Lot and Bottle (4/6)" canGoBack="false" id="dosingStationBottleLotPane">

		<h1>Select Lot and Bottle (4/6)</h1>

		<table border="0" cellpadding="5">

			<tr>
				<td align="right">Lot Number</td>
				<td align="left"><?php print module_function( 'LotReceipt', 'getLotNosForWizard', array ( "txtLotNo" ) ); ?></td>
			</tr>

			<tr>
				<td align="right">Bottle Number</td>
				<td align="left"><div id="idBtlNo"><?php
				if ( $_SESSION[ 'dosing' ][ 'btlno' ] ) {
					print module_function( 'LotReceipt', 'getAjxBottleNos', array ( $txtLotNo ) );
				}
				?></div></td>
			</tr>

		</table>

	</div>

	<div dojoType="WizardPane" label="Prime the Pump (5/6)" id="dosingPrimingPane" canGoBack="false">

		<h1>Prime the Pump (5/6)</h1>

		<p>
		Make sure that both the INLET and OUTLET tubes are inserted into the
		SOURCE BOTTLE of medication.
		</p>

		<p>
		Click "Next" to prime the pump.
		</p>

	</div>

	<div dojoType="WizardPane" id="dosingFinishedPane" label="Clean Pump (6/6)" canGoBack="false">
		<h1>Clean Pump (6/6)</h1>

		<p><i>Please click "Done" to exit the wizard.</i></p>

	</div>

</div> <!-- openDosingStationContainer -->

<div dojoType="Dialog" id="primeDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Priming / cycling pump ... </h1>
</div>

<div dojoType="Dialog" id="clearDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Clearing pump ... </h1>
</div>

