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
		doseId: 0,
		onCancel: function ( ) {
			alert('Cancelling dose operation as requested.');
			history.go(-1); // go back from where you came ...
		},
		onFinished: function ( ) {
			// Deal with dose same patient again
			var again = document.getElementById( 'anotherDoseSamePatient' ).checked;
			if ( again ) {
				document.getElementById( 'anotherDoseSamePatient' ).checked = false;
				dw.updateSchedule( );
				dojo.widget.byId( 'dosingContainer' ).onSelected( dojo.widget.byId( 'dosingCalculatePane' ) );
				dojo.widget.byId( 'dosingContainer' ).checkButtons( );
			} else {
				dojo.widget.byId( 'dosingContainer' ).onSelected( dojo.widget.byId( 'dosingPatientPane' ) );
				//history.go(-1); // go back from where you came ...
			}
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
		onLoadPatient: function ( ) {
			var pt = document.getElementById( 'dosepatient' ).value;
			if ( parseInt( pt ) < 1 ) {
				alert( 'You must select a patient!' );
				return false;
			}

			var exStatus = true;

			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=doseplan&method=getDosePlanForPatient&param[]=' + pt,
				load: function( type, data, evt ) {
					dw.dosePlan = data.id;
					document.getElementById( 'dosePlan' ).innerHTML = data.name;
				},
				sync: true,
				mimetype: 'text/json'
			});
			dw.updateSchedule( );
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dosehold&method=GetCurrentHoldStatusByPatient&param[]=' + pt,
				load: function( type, data, evt ) {
					switch ( data ) {
						case 1:
						document.getElementById( 'patientHoldStatus' ).innerHTML = 'SOFT HOLD';
						exStatus = true;
						break;

						case 2:
						alert('There is a hard hold on this patient');
						exStatus = false;
						break;

						case 0:
						document.getElementById( 'patientHoldStatus' ).innerHTML = 'No holds.';
						exStatus = true;
						break;
						
					}
				},
				sync: true,
				mimetype: 'text/json'
			});
			return exStatus;
		},
		updateSchedule: function ( ) {
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=doseplan&method=ajax_display_dose_plan&param[]=' + dw.dosePlan,
				load: function( type, data, evt ) {
					document.getElementById( 'dosePlanDisplay' ).innerHTML = data;
				},
				mimetype: 'text/json'
			});
		},
		updateDate: function (dt) {
			var val = dojo.widget.byId( 'doseassigneddate' ).inputNode.value;
			// Handle funky mm/dd/yyyy values
			if ( val.match('/') ) {
				var pieces = val.split('/');
				val = pieces[2] + '-' + pieces[0] + '-' + pieces[1];
			};
			document.getElementById( 'doseassigneddate_cal' ).value = val;
		},
		onCalculateDose: function ( ) {
			if ( document.getElementById( 'doseassigneddate_cal' ).value,length < 8 ) {
				// Skip back to calculate pane
				//dojo.widget.byId( 'dosingContainer' ).onSelected( dojo.widget.byId( 'dosingCalculatePane' ) );
				return false;
			}
			var dosehash = dw.dosePlan + ',' + document.getElementById( 'doseassigneddate_cal' ).value;
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=doseplan&method=ajax_DoseForDate&param[]=' + dosehash,
				load: function( type, data, evt ) {
					if ( data > 0 ) {
						// All good, continue
						dojo.byId( 'doseunits' ).value = data;
					} else {
						alert('No dose is scheduled for the date selected.');
						return false;
					}
				},
				sync: true,
				mimetype: 'text/json'
			});

			// And make sure we check to see if it dosed properly
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=ajax_alreadyDosed&param[]=' + document.getElementById( 'dosepatient' ).value + ',' + document.getElementById( 'doseassigneddate_cal' ).value,
				load: function( type, data, evt ) {
					if ( data.indexOf( 'ALREADY' ) != -1 ) {
						alert('Already dispensed for that day');
					} else {
						// All good
						dojo.byId( 'doseStatus' ).innerHTML = data;
					}
				},
				sync: true,
				mimetype: 'text/json'
			});
			return true;
		},
		onDispenseDose: function ( ) {
			var plan = dw.dosePlan;
			var dt = document.getElementById('doseassigneddate_cal').value;
			var units = document.getElementById('doseunits').value;
			var station = document.getElementById('dosingstation').value;
			var btlid = document.getElementById('btlno').value;
			var txtLotNo = document.getElementById( 'txtLotNo' ).value;
			var hash = document.getElementById('dosepatient').value + ',' + dt + ',' + plan + ',' + units + ',' + station + ',' + txtLotNo + ',' + btlid;

			dojo.widget.byId( 'primeDialog' ).show();

			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=dispenseDose&param[]=' + hash,
				load: function( type, data, evt ) {
					dojo.widget.byId( 'primeDialog' ).hide();
					var value = parseInt( data );
					if ( value == -99 ) {
						alert( 'Split dosing has been attempted with a non-valid value.' );
						return false;
					}
					if ( value < 0 ) {
						alert( 'A dosing error has occurred, but the machine has reported that it has dispensed something.' );
						return false;
					}
					if ( value == 0 ) {
						alert( 'A dosing error has occurred, but nothing was dispensed.' );
						return false;
					}
					dw.doseId = value;
					alert( 'Dose was dispensed successfully.' );
					return true;
				},
				sync: true,
				mimetype: 'text/json'
			});
		},
		onRecordMistake: function ( ) {
			var id = dw.doseId;
			var comment = document.getElementById('dosecomment').value;
			var poured = document.getElementById('dosepouredunits').value;
			var prepared = document.getElementById('dosepreparedunits').value;
			// A sanity clause?
			if ( comment.length < 3 ) {
				alert('You must specify a reason for the dose failing.');
				return false;
			}
			// Avoid duplicate clicks
			document.getElementById('mistakeButton').disabled = true;
			// XmlHttpRequest send
			var hash = id + '##' + poured + '##' + prepared + '##' + comment;
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=ajax_recordMistake&param[]=' + hash,
				load: function( type, data, evt ) {
					// Move to index # x to redose
					dojo.widget.byId( 'dosingContainer' ).onSelected( dojo.widget.byId( 'dosingCalculatePane' ) );
					dojo.widget.byId( 'dosingContainer' ).checkButtons( );
					// Reset mistake form
					document.getElementById('mistakeButton').disabled = false;
				},
				mimetype: 'text/json'
			});
		}
	};

	dojo.addOnLoad(function() {
		dojo.event.connect( dojo.widget.byId( 'dosingContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.connect( dojo.widget.byId( 'doseassigneddate' ), 'onValueChanged', dw, 'updateDate' );
		dojo.event.connect( dojo.widget.byId( 'dosingCalculatePane' ), 'passFunction', dw, 'onCalculateDose' );
		dojo.event.connect( dojo.widget.byId( 'dosingPatientPane' ), 'passFunction', dw, 'onLoadPatient' );
		dojo.event.connect( dojo.widget.byId( 'dosingDosePane' ), 'passFunction', dw, 'onDispenseDose' );
		dojo.event.connect( dojo.widget.byId( 'mistakeButton' ), 'onClick', dw, 'onRecordMistake' );
		dojo.event.connect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

	dojo.addOnUnload(function() {
		dojo.event.disconnect( dojo.widget.byId( 'dosingContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.disconnect( dojo.widget.byId( 'doseassigneddate' ), 'onValueChanged', dw, 'updateDate' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingCalculatePane' ), 'passFunction', dw, 'onCalculateDose' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingPatientPane' ), 'passFunction', dw, 'onLoadPatient' );
		dojo.event.disconnect( dojo.widget.byId( 'mistakeButton' ), 'onClick', dw, 'onRecordMistake' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

</script>

<br/><br/><br/><br/>

<div dojoType="WizardContainer" id="dosingContainer" 
 style="width: 95%; height: 80%;" hideDisabledButtons="false"
 nextButtonLabel="Next &gt; &gt;" previousButtonLabel="&lt; &lt; Previous"
 cancelButtonLabel="Cancel" doneButtonLabel="Done">

	<div dojoType="WizardPane" label="Select Patient (2/7)" id="dosingPatientPane" canGoBack="false">
		<h1>Select Patient (2/7)</h1>

		<p><i>
		Please select a patient.
		</i></p>

		<p>
		<?php print freemed::patient_widget( 'dosepatient' ); ?>
		</select>
		</p>

		<p> <i>Please confirm the dosing station.</i> </p>

		<table border="0" cellpadding="5">

			<tr>
				<td align="right">Dosing Station</td>
				<td align="left"><?php print module_function( 'DosingStation', 'widget', array ( 'dosingstation', "dsenabled = 1" ) ); ?></td>
			</tr>

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

	<div dojoType="WizardPane" id="dosingCalculatePane" label="Schedule Dose (5/7)" canGoBack="true">

		<h1>Schedule Dose (5/7)</h1>

		<p>Patient Hold Status : <span id="patientHoldStatus"></span></p>

		<p>Dose Plan : <span id="dosePlan"></span></p>

		<p>Dose Assigned Date : <div dojoType="DropdownDatePicker" id="doseassigneddate" widgetId="doseassigneddate" displayFormat="yyyy-MM-dd" value="today"></div></p>
		<input type="hidden" id="doseassigneddate_cal" />

		<p align="center">
		<div id="dosePlanDisplay" style="border: 1px solid #000000;"></div>
		</p>

	</div>

	<div dojoType="WizardPane" id="dosingDosePane" label="Calculate Dose (6/7)" canGoBack="false">
		
		<h1>Calculate Dose (6/7)</h1>

		<p>Status : <span id="doseStatus"></span></p>

		<p>Dose Amount : <input type="text" id="doseunits" /></p>

		<p>Please press the "Next" button to complete this dosing procedure.</p>

	</div>

	<div dojoType="WizardPane" id="dosingMistakePane" label="Mistake (7/7)" canGoBack="false">
		<h1>Mistake (7/7)</h1>

		<p>
		<i>If a dosing mistake was made, please complete this form to record the
		mistake information.</i>
		</p>

		<table border="0" cellpadding="5" cellspacing="0">

                        <tr>
                                <td align="right">Poured Units</td>
                                <td><input type="text" name=\dosepouredunits" id="dosepouredunits" value="0" /></td>
                        </tr>

                        <tr>
                                <td align="right">Prepared Units</td>
                                <td><input type="text" name="dosepreparedunits" id="dosepreparedunits" value="0" /></td>
                        </tr>

                        <tr>
                                <td align="right">Reason / Comment</td>
                                <td><input type="text" name="dosecomment" id="dosecomment" /></td>
                        </tr>
		</table>

		<p align="center">
		<button dojoType="Button" id="mistakeButton">
			<div>Record Mistake</div>
		</button>
		</p>

	</div>

	<div dojoType="WizardPane" id="dosingFinishedPane" label="Continue Dosing (7/7)" canGoBack="false">
		<h1>Continue Dosing (7/7)</h1>

		<p>
		<input type="checkbox" id="anotherDoseSamePatient" value="1" /> <label for="anotherDoseSamePatient">Dose Again, Same Patient</label>
		</p>

	</div>

</div> <!-- dosingContainer -->

<div dojoType="Dialog" id="primeDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Priming / cycling pump ... </h1>
</div>

<div dojoType="Dialog" id="dispenseDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Dispensing ... </h1>
</div>
