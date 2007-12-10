<?php
  // $Id$
  //
  // Authors:
  //      Adam Buchbinder <adam.buchbinder@gmail.com>
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
?>

<script language="javascript" src="lib/dojo/dojo.js"></script>
<script language="javascript">
	dojo.require("dojo.io.*");
	dojo.require("dojo.widget.Dialog");
	dojo.require("dojo.widget.Wizard");
	dojo.require("dojo.widget.Tooltip");

	function getBottleNumbers ( value, name ) {
		var hash = value + ',' + name;
		dojo.io.bind({
			method: 'GET',
			url: 'json-relay-0.8.x.php?module=lotreceipt&method=getAjaxBottleNos&param[]=' + hash,
			load: function( type, data, evt ) {
				document.getElementById( 'divBottle' ).innerHTML = data;
			},
			mimetype: 'text/json'
		});
	}

	var dw = {
		onMeasure: function ( ) {
			var id = document.getElementById( 'bottle' ).value;
			
			var recorded;
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=lotreceipt&method=getBottleRemain&param[]=' + id,
				load: function( type, data, evt ) {
					if (data)
						remaining = parseInt( data );
					else
						alert ("Could not get remaining quantity for bottle "+id);
				},
				mimetype: 'text/json',
				sync: true
			});
			var entered = parseInt( document.getElementById( 'quantity' ).value );
			
			document.getElementById( 'qty_recorded' ).innerHTML = remaining;
			document.getElementById( 'qty_entered' ).innerHTML = entered;
		},
		onCancel: function ( ) {
			alert('Cancelling dose operation as requested.');
			window.location = 'dosing_functions.php';
			//history.go(-1); // go back from where you came ...
		},
		onFinished: function ( ) {
			var bottle = document.getElementById( 'bottle' ).value;
			var measured = parseInt( document.getElementById( 'quantity' ).value );
			var reason = parseInt( document.getElementById( 'reason' ).value );
                       
			// Set up blocker
			dojo.widget.byId( 'reconcileDialog' ).show();
			// Execute the transfer
		       	var hash = bottle + ',' + measured + ',' + reason;
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=ReconcileBottle&method=ajax_reconcile&param[]=' + hash,
				load: function( type, data, evt ) {
					// Change blocker
					dojo.widget.byId( 'reconcileDialog' ).hide();
					if ( data ) {
						// all good
					} else {
						alert('Failed to reconcile.');
					}
				},
				mimetype: 'text/json',
				sync: true
			});

			window.location = 'dosing_functions.php';
			//history.go(-1); // go back from where you came ...
		},
	};

	function pass_measurePane() {
		return dw.onMeasure();
	}

	dojo.addOnLoad(function() {
		dojo.event.connect( dojo.widget.byId( 'reconcileFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

	dojo.addOnUnload(function() {
		dojo.event.disconnect( dojo.widget.byId( 'reconcileFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

</script>

<br/><br/><br/><br/>

<div dojoType="WizardContainer" id="reconcileContainer" 
 style="width: 95%; height: 80%;" hideDisabledButtons="false"
 nextButtonLabel="Next &gt; &gt;" previousButtonLabel="&lt; &lt; Previous"
 cancelButtonLabel="Cancel" doneButtonLabel="Done">

	<div dojoType="WizardPane" label="Select Bottle (1/4)" id="bottleSelectPane" passFunction="pass_bottleSelectPane">
		<h1>Select Bottle (1/4)</h1>

		<p>
			<i>Please select the bottle to reconcile.</i>
		</p>

		<table border="0" cellpadding="5">

			<tr>
				<td align="right">Lot Number</td>
				<td align="left"><?php print module_function( 'LotReceipt', 'getLotNosForWizard', array ( "lot", "bottle" ) ); ?></td>
			</tr>

			<tr>
				<td align="right">Bottle Number</td>
				<td align="left"><div id="divBottle"/></td>
			</tr>

		</table>

	</div>

	<div dojoType="WizardPane" label="Measure Bottle (2/4)" id="measure" passFunction="pass_measurePane">

		<h1>Measure Bottle (2/4)</h1>

		<p><i>Please measure the quantity remaining in the bottle.</i></p>

		<table border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td align="right">Quantity Remaining</td>
				<td align="left"><input type="text" id="quantity" name="quantity" value="0" /></td>
			</tr>
		</table>

	</div>

	<div dojoType="WizardPane" id="explain" label="Explain Discrepancy (3/4)">
		<h1>Explain Discrepancy (3/4)</h1>

		<p>The system reports that the bottle contains
		<span id="qty_recorded"><b>??</b></span>, but was measured at 
		<span id="qty_entered"><b>??</b></span>.
		Write an explanation for the discrepancy.</p>
		
		<table border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td align="right">Explanation</td>
				<td align="left"><input type="text" id="reason" name="reason" value="" /></td>
			</tr>
		</table>
	</div>

	<div dojoType="WizardPane" id="reconcileFinishedPane" label="Confirm (4/4)">
		<h1>Confirm (4/4)</h1>

		<p>Click "Done" to perform the reconciliation.</p>
<!--
		<p>You are transferring a quantity of
		<span id="finished_qty"><b>??</b></span> from bottle
		<span id="finished_src"><b>??</b></span> to bottle
		<span id="finished_dst"><b>??</b></span>. Click "Finish" to
		confirm this.</p>
-->
	</div>

</div> <!-- bottleTransferContainer -->

<div dojoType="Dialog" id="reconcileDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Reconciling ... </h1>
</div>
