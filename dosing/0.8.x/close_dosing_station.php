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
			history.go(-1); // go back from where you came ...
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
		}
	};

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

	<div dojoType="WizardPane" id="dosingFinishedPane" label="Clean Pump (2/2)" canGoBack="false">
		<h1>Clearing Pump (2/2)</h1>

		<p><i>Please click "Done" to exit the wizard.</i></p>

	</div>

</div> <!-- container -->

