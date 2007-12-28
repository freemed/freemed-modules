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
			
			var arr;
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=ReconcileBottle&method=ajax_getFields&param[]=' + id,
				load: function( type, data, evt ) {
					if (data)
						arr = data;
					else
						alert ("Could not get fields for bottle "+id);
				},
				mimetype: 'text/json',
				sync: true
			});
			var entered = parseInt( document.getElementById( 'quantity' ).value );
			
			document.getElementById( 'rec_qty_initial' ).innerHTML = parseInt(arr['rec_qty_initial']);
			document.getElementById( 'rec_qty_tr_out' ).innerHTML = parseInt(arr['rec_qty_tr_out']);
			document.getElementById( 'rec_qty_tr_in' ).innerHTML = parseInt(arr['rec_qty_tr_in']);
			document.getElementById( '_rec_net_available' ).innerHTML = parseInt(arr['rec_qty_initial']) + parseInt(arr['rec_qty_tr_out']) - parseInt(arr['rec_qty_tr_in']);
			document.getElementById( 'rec_qty_disp' ).innerHTML = parseInt(arr['rec_qty_disp']);
			document.getElementById( 'rec_qty_disp_takehome' ).innerHTML = parseInt(arr['rec_qty_disp_takehome']);
			document.getElementById( 'rec_qty_spill' ).innerHTML = parseInt(arr['rec_qty_spill']);
			document.getElementById( 'rec_qty_final_expected' ).innerHTML = parseInt(arr['rec_qty_final_expected']);
			document.getElementById( 'rec_qty_final_actual' ).innerHTML = entered;
			document.getElementById( '_rec_difference' ).innerHTML = parseInt(arr['rec_qty_final_expected']) - entered;
		},
		onCancel: function ( ) {
			alert('Cancelling dose operation as requested.');
			window.location = 'dosing_functions.php';
			//history.go(-1); // go back from where you came ...
		},
		onFinished: function ( ) {
			var bottle = document.getElementById( 'bottle' ).value;
			var measured = parseInt( document.getElementById( 'quantity' ).value );
			var reason = document.getElementById( 'reason' ).value;
                       
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
<?php
	if (isset($_GET['bottle']))
		print '
		<input type="hidden" name="bottle" id="bottle" value="'.$_GET['bottle'].'"/>
		<p>Bottle is already selected; please click next to continue.</p>
		';
	else
		print '
		<p>
			<i>Please select the bottle to reconcile.</i>
		</p>

		<table border="0" cellpadding="5">

			<tr>
				<td align="right">Lot Number</td>
				<td align="left">'.module_function( 'LotReceipt', 'getLotNosForWizard', array ( "lot", "bottle" ) ).'</td>
			</tr>

			<tr>
				<td align="right">Bottle Number</td>
				<td align="left"><div id="divBottle"/></td>
			</tr>

		</table>
		';
?>
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

	<div dojoType="WizardPane" id="explain" label="Reconcile (3/4)">
		<h1>Reconcile (3/4)</h1>
		
		<center>Note: All Amounts are in Milligrams</center>
			<table align='center'>
				<tr>
					<td colspan='2'><b>Computer Amounts :</b></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Initial Bottle Contents :</td>
					<td align='right'><span id="rec_qty_initial">??</span></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Transferred to Other Bottles :</td>
					<td align='right'><span id="rec_qty_tr_out">??</span></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Transferred from Other Bottles :</td>
					<td align='right'><span id="rec_qty_tr_in">??</span></td>
				</tr>
				<tr>
					<td></td>
					<td><hr/></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Net Amount Available for Use :</td>
					<td align='right'><span id="_rec_net_available">??</span></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Dispensed Today :</td>
					<td align='right'><span id="rec_qty_disp">??</span></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount Dispensed For Take-Home Doses :</td>
					<td align='right'><span id="rec_qty_disp_takehome">??</span></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Waste (spillage) :</td>
					<td align='right'><span id="rec_qty_spill">??</span></td>
				</tr>
				<tr>
					<td></td>
					<td><hr/></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Net Amount Remaining in Bottle :</td>
					<td align='right'><span id="rec_qty_final_expected">??</span></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td></td>
				</tr>
				<tr>
					<td>Actual Amount Measured :</td>
					<td align='right'><span id="rec_qty_final_actual">??</span></td>
				</tr>
				<tr>
					<td>Difference :</td>
					<td align='right'><span id="_rec_difference">??</span></td>
				</tr>
				<tr>
					<td>Adjustment Posted Reason :</td>
					<td align='right'><input type="text" id="reason" name="reason" value="" /></td>
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
