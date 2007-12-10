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
				if (name == 'from_bottle')
					document.getElementById( 'fromBottle' ).innerHTML = data;
				else
					document.getElementById( 'toBottle' ).innerHTML = data;
			},
			mimetype: 'text/json'
		});
	}

	// fetches lotrecqtytotal field
	function getBottleTotal ( id ) {
		var ret;
		dojo.io.bind({
			method: 'GET',
			url: 'json-relay-0.8.x.php?module=lotreceipt&method=getBottleTotal&param[]=' + id,
			load: function( type, data, evt ) {
				if (data)
					ret = parseInt( data );
				else
					alert ("Could not get total quantity for bottle "+id);
			},
			mimetype: 'text/json',
			sync: true
		});
		return ret;
	}

	// fetches lotrecqtyremain field
	function getBottleRemain ( id ) {
		var ret;
		dojo.io.bind({
			method: 'GET',
			url: 'json-relay-0.8.x.php?module=lotreceipt&method=getBottleRemain&param[]=' + id,
			load: function( type, data, evt ) {
				if (data)
					ret = parseInt( data );
				else
					alert ("Could not get remaining quantity for bottle "+id);
			},
			mimetype: 'text/json',
			sync: true
		});
		return ret;
	}

	var dw = {
		onCancel: function ( ) {
			alert('Cancelling dose operation as requested.');
			history.go(-1); // go back from where you came ...
		},
		onBottleSelect: function ( ) {
			var from_bottle = parseInt( document.getElementById( 'from_bottle' ).value );
			var to_bottle = parseInt( document.getElementById( 'to_bottle' ).value );

			if (from_bottle == to_bottle)
				return "Source and destination bottles must be different.";

			var qty_from_remain = getBottleRemain(from_bottle);
			var qty_to_total = getBottleTotal(to_bottle);
			var qty_to_remain = getBottleRemain(to_bottle);

			dojo.byId('selectQty_src').innerHTML = qty_from_remain;
			dojo.byId('selectQty_dst').innerHTML = qty_to_total - qty_to_remain;
			if (qty_from_remain > qty_to_total - qty_to_remain)
				dojo.byId('selectQty_tot').innerHTML = qty_to_total - qty_to_remain;
			else
				dojo.byId('selectQty_tot').innerHTML = qty_from_remain;
		},
		onQuantitySelect: function ( ) {
			var quantity = parseInt( document.getElementById( 'quantity' ).value );
			if (!(quantity > 0))
				return "Quantity must be greater than zero.";
			// No point in querying again; just use already-loaded values
			var maxtransfer = dojo.byId('selectQty_tot').innerHTML;
			if (quantity > maxtransfer)
				return "Cannot transfer more than "+maxtransfer+".";
		},
		onFinished: function ( ) {
			var from_bottle = parseInt( document.getElementById( 'from_bottle' ).value );
			var to_bottle = parseInt( document.getElementById( 'to_bottle' ).value );
			var quantity = parseInt( document.getElementById( 'quantity' ).value );
                        
			var hash = from_bottle + ','
				+ to_bottle + ','
				+ quantity;
			// Set up blocker
			dojo.widget.byId( 'transferDialog' ).show();
			// Execute the transfer
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=bottletransfer&method=ajax_transfer&param[]=' + hash,
				load: function( type, data, evt ) {
					// Change blocker
					dojo.widget.byId( 'transferDialog' ).hide();
					if ( data ) {
						// all good
					} else {
						alert('Failed to execute transfer.');
					}
				},
				mimetype: 'text/json',
				sync: true
			});

			window.location = 'dosing_functions.php';
			history.go(-1); // go back from where you came ...
		},
	};

	function pass_bottleSelectPane() {
		return dw.onBottleSelect();
	}
	function pass_selectQuantityPane() {
		return dw.onQuantitySelect();
	}

	dojo.addOnLoad(function() {
		dojo.event.connect( dojo.widget.byId( 'transferFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

	dojo.addOnUnload(function() {
		dojo.event.disconnect( dojo.widget.byId( 'transferFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

</script>

<br/><br/><br/><br/>

<div dojoType="WizardContainer" id="bottleTransferContainer" 
 style="width: 95%; height: 80%;" hideDisabledButtons="false"
 nextButtonLabel="Next &gt; &gt;" previousButtonLabel="&lt; &lt; Previous"
 cancelButtonLabel="Cancel" doneButtonLabel="Done">

	<div dojoType="WizardPane" label="Select Bottles (1/3)" id="bottleSelectPane" passFunction="pass_bottleSelectPane">
		<h1>Select Bottles (1/3)</h1>

		<p>
			<i>Please select the source and destination bottles.</i>
		</p>

		<table border="0" cellpadding="5">

			<tr>
				<td align="right">Source Lot Number</td>
				<td align="left"><?php print module_function( 'LotReceipt', 'getLotNosForWizard', array ( "from_lot", "from_bottle" ) ); ?></td>

				<td align="right">Destination Lot Number</td>

				<td align="left"><?php print module_function( 'LotReceipt', 'getLotNosForWizard', array ( "to_lot", "to_bottle" ) ); ?></td>
			</tr>

			<tr>
				<td align="right">Source Bottle Number</td>
				<td align="left"><div id="fromBottle"/></td>

				<td align="right">Destination Bottle Number</td>
				<td align="left"><div id="toBottle"/></td>
			</tr>

		</table>

	</div>

	<div dojoType="WizardPane" label="Select Quantity (2/3)" id="selectQuantity" passFunction="pass_selectQuantityPane">

		<h1>Select Quantity (2/3)</h1>

		<p><i>Please assign the remaining amount elsewhere</i></p>

		<table border="0" cellspacing="0" cellpadding="5">

			<tr>
				<td colspan="2">The source bottle contains
				<span id="selectQty_src"><b>??</b></span>,
				and the destination bottle has room for
				<span id="selectQty_dst"><b>??</b></span>;
				therefore, you can move up to
				<span id="selectQty_tot"><b>??</b></span>.</td>
			</tr>

			<tr>
				<td align="right">Quantity to Transfer</td>
				<td align="left"><input type="text" id="quantity" name="quantity" value="0" /></td>
			</tr>

		</table>

	</div>

	<div dojoType="WizardPane" id="transferFinishedPane" label="Confirm (3/3)">
		<h1>Confirm (3/3)</h1>

		<p>Click "Done" to perform the transfer.</p>
<!--
		<p>You are transferring a quantity of
		<span id="finished_qty"><b>??</b></span> from bottle
		<span id="finished_src"><b>??</b></span> to bottle
		<span id="finished_dst"><b>??</b></span>. Click "Finish" to
		confirm this.</p>
-->
	</div>

</div> <!-- bottleTransferContainer -->

<div dojoType="Dialog" id="transferDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Transferring contents ... </h1>
</div>
