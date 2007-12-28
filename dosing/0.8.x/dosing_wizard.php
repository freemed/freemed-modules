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
global $dosingstation, $txtLotNo, $btlno;
$dosingstation = $_SESSION['dosing']['dosingstation'];
$txtLotNo = $_SESSION['dosing']['txtLotNo'];
$btlno = $_SESSION['dosing']['btlno'];
if ( $_REQUEST['patient'] ) { global $dosepatient; $dosepatient = $_REQUEST['patient']; }

?>

<style type="text/css">

	#dosePlanDisplay, #dosePlanDisplay * {
		size: 8pt;
		}

</style>
<script language="javascript" src="lib/dojo/dojo.js"></script>
<script language="javascript">
	dojo.require("dojo.io.*");
	dojo.require("dojo.date");
	dojo.require("dojo.widget.Dialog");
	dojo.require("dojo.widget.DropdownDatePicker");
	dojo.require("dojo.widget.Wizard");
	dojo.require("dojo.widget.Tooltip");

	var dw = {
		dosePlan: 0,
		doseId: 0,
		onCancel: function ( ) {
			alert('Cancelling dose operation as requested.');
			history.go(-1); // go back from where you came ...
		},
		onFinished: function ( ) {
			// Deal with dose same patient again
			var differentPatient = document.getElementById( 'anotherDoseDifferentPatient' ).checked;
			if ( differentPatient ) {
				document.getElementById( 'anotherDoseDifferentPatient' ).checked = false;
				dw.updateSchedule( );
				dojo.widget.byId( 'dosingContainer' ).onSelected( dojo.widget.byId( 'dosingPatientPane' ) );
				dojo.widget.byId( 'dosingContainer' ).checkButtons( );
			} else {
				dw.updateSchedule( );
				dojo.widget.byId( 'dosingContainer' ).onSelected( dojo.widget.byId( 'dosingCalculatePane' ) );
				dojo.widget.byId( 'dosingContainer' ).checkButtons( );
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
			if ( pt == "" || parseInt( pt ) < 1 ) {
				return "You must select a patient!";
			}

			var exStatus = false;

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
						exStatus = false;
						break;

						case 2:
						exStatus = "There is a hard hold on this patient";
						break;

						case 0:
						document.getElementById( 'patientHoldStatus' ).innerHTML = 'No holds.';
						exStatus = false;
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
			if ( document.getElementById( 'doseassigneddate_cal' ).value.length < 8 )
				return "You must select a date.";
			var assigned_date = dojo.date.fromIso8601Date(document.getElementById( 'doseassigneddate_cal' ).value);

			// -1 for past, 0 for today, 1 for future
			var comp = dojo.date.compare(assigned_date, new Date(), dojo.date.compareTypes.DATE);
			// Can dose for up to today+30 days. Hard code for now.
			var takeHomeEnd = dojo.date.add(new Date(),"day",30);
			var comp2 = dojo.date.compare(assigned_date, takeHomeEnd, dojo.date.compareTypes.DATE);
			if (comp < 0)
				return "Cannot dose for a date in the past.";
			if (comp > 0 && comp2 <= 0) { // have to see if it's take-home
				var isTakeHome = false;
				var dosehash = dw.dosePlan + ',' + document.getElementById( 'doseassigneddate_cal' ).value;
				dojo.io.bind({
					method: 'GET',
					url: 'json-relay-0.8.x.php?module=doseplan&method=ajax_DoseTakeHome&param[]=' + dosehash,
					load: function( type, data, evt ) {
						if ( data ) {
							isTakeHome = true;
						} else {
							isTakeHome = false;
						}
					},
					sync: true,
					mimetype: 'text/json'
				});
				if (! isTakeHome)
					return "Can only dispense future doses for takehome doseplan.";
			}
			if (comp2 > 0)
				return "Can only dispense take-home doses within the next 30 days.";

			var dosehash = dw.dosePlan + ',' + document.getElementById( 'doseassigneddate_cal' ).value;
			var returnVal = false;
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=doseplan&method=ajax_DoseForDate&param[]=' + dosehash,
				load: function( type, data, evt ) {
					if ( data > 0 ) {
						// All good, continue
						dojo.byId( 'doseunits' ).innerHTML = data;
						dojo.byId( 'doseunits2' ).innerHTML = data;
					} else {
						returnVal = "No dose is scheduled for the date selected.";
					}
				},
				sync: true,
				mimetype: 'text/json'
			});
			if (returnVal)
				return returnVal;

			// And make sure we check to see if it dosed properly
			var iso_date = dojo.date.format(assigned_date, "%Y-%m-%d");
			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=dose&method=ajax_alreadyDosed&param[]=' + document.getElementById( 'dosepatient' ).value + ',' + iso_date,
				load: function( type, data, evt ) {
					if ( data.indexOf( 'ALREADY' ) != -1 ) {
						// clear so we can check it later
						dojo.byId( 'doseStatus' ).innerHTML = "";
						returnVal = "Already dispensed all doses for date "+assigned_date+".";
					} else {
						// All good
						dojo.byId( 'doseStatus' ).innerHTML = data;
					}
				},
				sync: true,
				mimetype: 'text/json'
			});
			return returnVal;
		},
		onDispenseDose: function ( ) {
			var plan = dw.dosePlan;
			var dt = document.getElementById('doseassigneddate_cal').value;
			var units = parseInt( document.getElementById('doseunits').innerHTML );
			var station = document.getElementById('dosingstation').value;
			var hash = document.getElementById('dosepatient').value + ',' + dt + ',' + plan + ',' + units + ',' + station;

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
			var units = parseInt( document.getElementById('doseunits').innerHTML );
			// A sanity clause?
			if ( comment.length < 3 ) {
				alert('You must specify a reason for the dose failing.');
				return false;
			}
			// Avoid duplicate clicks
			document.getElementById('mistakeButton').disabled = true;
			// XmlHttpRequest send
			var hash = id + '##' + units + '##' + comment;
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

	// These are here because a WizardPane's passFunction must be a
	// globally-accesible function, rather than something like "dw.foo".
	// Connecting the event via dojo.event.connect doesn't let it properly
	// return an error message.
	function pass_dosingPatientPane() {
		return dw.onLoadPatient();
	}
	function pass_dosingCalculatePane() {
		return dw.onCalculateDose();
	}
	function pass_dosingDosePane() {
		return dw.onDispenseDose();
	}

	dojo.addOnLoad(function() {
		dojo.event.connect( dojo.widget.byId( 'dosingContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.connect( dojo.widget.byId( 'doseassigneddate' ), 'onSetDate', dw, 'updateDate' );
		dojo.event.connect( dojo.widget.byId( 'mistakeButton' ), 'onClick', dw, 'onRecordMistake' );
		dojo.event.connect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

	dojo.addOnUnload(function() {
		dojo.event.disconnect( dojo.widget.byId( 'dosingContainer' ), 'cancelFunction', dw, 'onCancel' );
		dojo.event.disconnect( dojo.widget.byId( 'doseassigneddate' ), 'onSetDate', dw, 'updateDate' );
		dojo.event.disconnect( dojo.widget.byId( 'mistakeButton' ), 'onClick', dw, 'onRecordMistake' );
		dojo.event.disconnect( dojo.widget.byId( 'dosingFinishedPane' ), 'doneFunction', dw, 'onFinished' );
	});

</script>

<br/><br/><br/><br/>

<div dojoType="WizardContainer" id="dosingContainer" 
 style="width: 95%; height: 80%;" hideDisabledButtons="false"
 nextButtonLabel="Next &gt; &gt;" previousButtonLabel="&lt; &lt; Previous"
 cancelButtonLabel="Cancel" doneButtonLabel="Done">

	<div dojoType="WizardPane" label="Select Patient (1/5)" id="dosingPatientPane" canGoBack="false" passFunction="pass_dosingPatientPane">
		<h1>Select Patient (1/5)</h1>

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
				<td align="left"><?php print module_function( 'DosingStation', 'widget', array ( 'dosingstation', "dsenabled = 1 AND dsfacility='".addslashes($_SESSION['default_facility'])."' AND dsopen='open'" ) ); ?></td>
			</tr>

		</table>
	</div>

	<div dojoType="WizardPane" id="dosingCalculatePane" label="Schedule Dose (2/5)" canGoBack="true" passFunction="pass_dosingCalculatePane">

		<h1>Schedule Dose (2/5)</h1>

		<p>Patient Hold Status : <span id="patientHoldStatus"></span></p>

		<p>Dose Plan : <span id="dosePlan"></span></p>

		<p>Dose Assigned Date : <div dojoType="DropdownDatePicker" id="doseassigneddate" widgetId="doseassigneddate" displayFormat="yyyy-MM-dd" value="today"></div></p>
		<input type="hidden" id="doseassigneddate_cal" />

		<p align="center">
		<div id="dosePlanDisplay" style="border: 1px solid #000000;"></div>
		</p>

	</div>

	<div dojoType="WizardPane" id="dosingDosePane" label="Calculate Dose (3/5)" canGoBack="false" passFunction="pass_dosingDosePane">
		
		<h1>Calculate Dose (3/5)</h1>

		<p>Status : <span id="doseStatus"></span></p>

		<p>Dose Amount : <span id="doseunits"></span></p>
		<!-- <p>Dose Amount : <input type="text" id="doseunits" /></p> -->

		<p>Please press the "Next" button to complete this dosing procedure.</p>

	</div>

	<div dojoType="WizardPane" id="dosingMistakePane" label="Mistake (4/5)" canGoBack="false">
		<h1>Mistake (4/5)</h1>

		<p>
		<i>If a dosing mistake was made, please complete this form to record the
		mistake information.</i>
		</p>

		<table border="0" cellpadding="5" cellspacing="0">

                        <tr>
                                <td align="right">Lost Units</td>
				<td><span id="doseunits2"></span></td>
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

	<div dojoType="WizardPane" id="dosingFinishedPane" label="Continue Dosing (5/5)" canGoBack="false">
		<h1>Continue Dosing (5/5)</h1>

		<p>
		<input type="checkbox" id="anotherDoseDifferentPatient" value="1" /> <label for="anotherDoseDifferentPatient">Next Dose is for Different Patient</label>
		</p>

	</div>

</div> <!-- dosingContainer -->

<div dojoType="Dialog" id="primeDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Priming / cycling pump ... </h1>
</div>

<div dojoType="Dialog" id="dispenseDialog" bgOpacity="0.5" toggle="fade" toggleDuration="250" bgColor="blue" style="display: none;" closeNode="hider">
	<h1>Dispensing ... </h1>
</div>
