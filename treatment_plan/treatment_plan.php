<?php
  // $Id$
  //
  // Authors:
  //      Jeff Buchbinder <jeff@freemedsoftware.org>
  //
  // FreeMED Electronic Medical Record and Practice Management System
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

// Header stuff, remove when we can...
//include 'lib/freemed.php';

include_once(freemed::template_file('ajax.php'));
$_cache = freemed::module_cache();

//print $GLOBALS['__freemed']['header'];
//if ($_REQUEST['patient']) {
//	print "<pre>"; print_r($_REQUEST); print "</pre>"; die();
//}

// Simulate data gathering
$id = $_REQUEST['id'];
$clone = $_REQUEST['clone'];
if ($id > 0 or $clone > 0) {
	$rec = freemed::get_link_rec(($id ? $id : $clone), 'treatmentplan', true);
	foreach ($rec AS $k => $v) {
		if ($k != 'id') {
			// Convert all dates to appropriate format
			if (eregi('[0-9]{4}-[0-9]{2}-[0-9]{2}', $v)) {
				list ($y, $m, $d) = explode('-', $v);
				$rec[$k] = "${m}/${d}/${y}";
			}
			$GLOBALS[$k] = $_REQUEST[$k] = $rec[$k];
			syslog(LOG_INFO, "k = $k, v = ".$rec[$k]);
		}
	}

	// Get all problems
	$q = "SELECT * FROM treatmentplanproblem WHERE treatmentplan='".addslashes(($id ? $id : $clone))."'";
	$result = $GLOBALS['sql']->query( $q );
	while ($r = $GLOBALS['sql']->fetch_array( $result )) {
		foreach ($r AS $k=>$v) {
			// Convert all dates to appropriate format
			if (eregi('[0-9]{4}-[0-9]{2}-[0-9]{2}', $v)) {
				list ($y, $m, $d) = explode('-', $v);
				$r[$k] = "${m}/${d}/${y}";
			}
		}
		if ($clone) { $r['id'] = 0; }
		$dsm = $r['tpdsm'];
		$data['problems'][$dsm][] = $r;
	}
	// Get all O/I
	$q = "SELECT * FROM treatmentplanoi WHERE oitreatmentplan='".addslashes(($id ? $id : $clone))."'";
	$result = $GLOBALS['sql']->query( $q );
	while ($r = $GLOBALS['sql']->fetch_array( $result )) {
		foreach ($r AS $k=>$v) {
			// Convert all dates to appropriate format
			if (eregi('[0-9]{4}-[0-9]{2}-[0-9]{2}', $v)) {
				list ($y, $m, $d) = explode('-', $v);
				$r[$k] = "${m}/${d}/${y}";
			}
		}
		if ($clone) { $r['id'] = 0; }
		$dsm = $r['oidsm'];
		$data['oi'][$dsm][] = $r;
	}
}

?>
	<style type="text/css">
	.button {
		-moz-border-radius: 10px;
		background-color: #ffffff;
		border: 1px solid #000000;
		text-decoration: none;
		}
	div { font-family: sans-serif; }
	form { margin: 0; padding: 0; }
	</style>

	<script language="javascript">
		var djConfig = {
			isDebug: true
		};
	</script>
	<script language="javascript" src="lib/dojo/dojo.js"></script>
	<script language="javascript">
		// Define dojo widgets
		dojo.require('dojo.io.*');
		dojo.require('dojo.string');
		dojo.require('dojo.widget.TabContainer');
		dojo.require('dojo.widget.Button');
		dojo.require('dojo.widget.Select');
		dojo.require('dojo.widget.ContentPane');
		dojo.require('dojo.widget.DropdownDatePicker');

		// Define global variables / counters
		var treatmentPlanProblemCounter = 1;
		var treatmentPlanOICounter = 1;

		//----- Define form submission handlers -----

		function treatmentPlanCancel ( ) {
			history.go(-1);
			return true;
		}

		function treatmentPlanSubmit ( ) {
			if (!confirm('Are you sure that you are finished and want to commit this treatment plan?')) {
				return false;
			}
			document.getElementById('treatmentPlanForm').submit();
			return true;
		} // end function treatmentPlanSubmit

		function treatmentPlanDestroy ( ) {
			if (!confirm('Are you sure that you want to permanently destroy this treatment plan?')) {
				return false;
			}

			if (document.getElementById('id').value <= 0) {
				alert ('no id element');
				return true;
			}

			dojo.io.bind({
				method: 'GET',
				url: 'json-relay-0.8.x.php?module=treatmentplanmodule&method=del&param[]=' + document.getElementById('id').value,
				error: function(type, data, evt) {
					alert('Server error, please try again.')
					return false;
				},
				load: function(type, data, evt) {
					if (data) {
						alert('Treatment plan deleted.');
						history.go(-1);
					} else {
						alert('Failed to remove treatment plan.');
						return false;
					}
				}
			});

			return false;
		} // end function treatmentPlanDestroy

		//----- Define internal handlers for generating divs -----

		function treatmentPlanAddProblem ( dimension, data ) {
			dojo.debug('treatmentPlanAddProblem('+dimension+', '+data+')');
			// Get dimension container so we can add elements
			var container = document.getElementById('treatmentPlanPaneDimension' + dimension);

			// Create dojo objects
			var divHolder = document.createElement('div');
			divHolder.id = 'problem' + treatmentPlanProblemCounter;
			divHolder.style.background = '#dddddd';
			divHolder.style.width = '95%';
			divHolder.style.border = '1px solid #000000';
			divHolder.style.margin = '.5em';
			divHolder.style.padding = '.5em';
			divHolder.innerHTML += "" +
				"<input type=\"hidden\" name=\"problem["+dimension+"]["+treatmentPlanProblemCounter+"][id]\" value=\"" + ( data['id'] ? data['id'] : 0 ) + "\" />\n" +
				"<input type=\"hidden\" name=\"problem["+dimension+"]["+treatmentPlanProblemCounter+"][dsm]\" value=\"" + dimension + "\" />\n" +
				"<button onClick=\"if (confirm('Are you sure you want to remove this problem?')) { treatmentPlanRemoveProblem(" + dimension + "," + treatmentPlanProblemCounter + "); } return false;\" class=\"button\">Remove Problem</button>" +
				"<button onClick=\"treatmentPlanAddOI(" + dimension + "," + treatmentPlanProblemCounter + ", {}); return false;\" class=\"button\">Add Objective/Intervention</button>" +
				"<br/>\n";

			var tT = document.createElement('table');
			var tTr = new Array ( );
			var tTd = new Array ( );

			// Row 0: Problem
			tTr[13] = document.createElement('tr');
			tTd[13] = document.createElement('td');
			tTd[14] = document.createElement('td');
			tTd[13].innerHTML = '<b>Problem</b>';
			tempDiv = document.createElement('input');
			tempDiv.style.width = '400px';
			tTd[14].appendChild(tempDiv);
			dojo.widget.createWidget('Select', {
				name: 'problem['+dimension+']['+treatmentPlanProblemCounter+'][problem]',
				id: 'problem_'+treatmentPlanProblemCounter,
				autocomplete: 'false',
				dataUrl: "json-relay-0.8.x.php?module=treatmentplanoptions&method=remote_picklist&param[]="+dimension+"&param[]=problem&param[]=%{searchString}",
				mode: "remote",
				value: "This should be replaced.",
				style: "width: 400px;"
			}, tempDiv);
			if (data['problem']) { dojo.widget.byId('problem_'+treatmentPlanProblemCounter).setValue(data['problem']); }
			if (data['problem']) { dojo.widget.byId('problem_'+treatmentPlanProblemCounter).setLabel(data['problem']); }
			tTd[15] = document.createElement('td');
			tTd[15].innerHTML += '<a href="module_loader.php?action=addform&module=treatmentplanoptions&tpdsm='+dimension+'&tptype=problem&return=close" class="button" target="_entry">Add Entry</a>';
			tTr[13].appendChild( tTd[13] );
			tTr[13].appendChild( tTd[14] );
			tTr[13].appendChild( tTd[15] );
			tT.appendChild( tTr[13] );

			// Row 1: Long Term Goals (text)
			tTr[1] = document.createElement('tr');
			tTd[1] = document.createElement('td');
			tTd[2] = document.createElement('td');
			tTd[1].innerHTML = '<b>Long Term Goals</b>';
			tempDiv = document.createElement('input');
			tempDiv.style.width = '400px';
			tTd[2].appendChild(tempDiv);
			dojo.widget.createWidget('Select', {
				name: 'problem['+dimension+']['+treatmentPlanProblemCounter+'][goalslongterm]',
				id: 'longtermgoals_'+treatmentPlanProblemCounter,
				autocomplete: 'false',
				dataUrl: "json-relay-0.8.x.php?module=treatmentplanoptions&method=remote_picklist&param[]="+dimension+"&param[]=longtermgoal&param[]=%{searchString}",
				mode: "remote",
				value: "This should be replaced.",
				style: "width: 400px;"
			}, tempDiv);
			if (data['goalslongterm']) { dojo.widget.byId('longtermgoals_'+treatmentPlanProblemCounter).setValue(data['goalslongterm']); }
			if (data['goalslongterm']) { dojo.widget.byId('longtermgoals_'+treatmentPlanProblemCounter).setLabel(data['goalslongterm']); }
			tTd[102] = document.createElement('td');
			tTd[102].innerHTML += '<a href="module_loader.php?action=addform&module=treatmentplanoptions&tpdsm='+dimension+'&tptype=longtermgoal&return=close" class="button" target="_entry">Add Entry</a>';
			tTr[1].appendChild( tTd[1] );
			tTr[1].appendChild( tTd[2] );
			tTr[1].appendChild( tTd[102] );
			tT.appendChild( tTr[1] );

			// Row 2: Long Term Goals (date effective)
			tTr[2] = document.createElement('tr');
			tTd[3] = document.createElement('td');
			tTd[4] = document.createElement('td');
			tTd[3].innerHTML = 'Date Effective';
			tempDiv = document.createElement('input');
			tTd[4].appendChild(tempDiv);
			dojo.widget.createWidget('DropdownDatePicker', {
				name: 'problem['+dimension+']['+treatmentPlanProblemCounter+'][dateeffectivelong]',
				id: 'dateeffectivelong_'+treatmentPlanProblemCounter,
				date: ( data['dateeffectivelong'] ? data['dateeffectivelong'] : dojo.widget.byId('dateofadmission').inputNode.value ),
			}, tempDiv);
			dojo.widget.byId('dateeffectivelong_'+treatmentPlanProblemCounter).inputNode.name = 'problem['+dimension+']['+treatmentPlanProblemCounter+'][dateeffectivelong]';
			dojo.widget.byId('dateeffectivelong_'+treatmentPlanProblemCounter).inputNode.value =  ( data['dateeffectivelong'] ? data['dateeffectivelong'] : dojo.widget.byId('dateofadmission').inputNode.value );
			tTr[2].appendChild( tTd[3] );
			tTr[2].appendChild( tTd[4] );
			tT.appendChild( tTr[2] );

			// Row 3: Long Term Goals (date target)
			tTr[3] = document.createElement('tr');
			tTd[5] = document.createElement('td');
			tTd[6] = document.createElement('td');
			tTd[5].innerHTML = 'Date Target';
			tempDiv = document.createElement('input');
			tTd[6].appendChild(tempDiv);
			dojo.debug("datetargetlong = "+data['datetargetlong']);
			dojo.widget.createWidget('DropdownDatePicker', {
				name: 'problem['+dimension+']['+treatmentPlanProblemCounter+'][datetargetlong]',
				id: 'datetargetlong_'+treatmentPlanProblemCounter,
				date: ( data['datetargetlong'] ? data['datetargetlong'] : dojo.widget.byId('dateexpires').inputNode.value )
			}, tempDiv);
			dojo.widget.byId('datetargetlong_'+treatmentPlanProblemCounter).inputNode.name = 'problem['+dimension+']['+treatmentPlanProblemCounter+'][datetargetlong]';
			dojo.widget.byId('datetargetlong_'+treatmentPlanProblemCounter).inputNode.value =  ( data['datetargetlong'] ? data['datetargetlong'] : dojo.widget.byId('dateexpires').inputNode.value );
			tTr[3].appendChild( tTd[5] );
			tTr[3].appendChild( tTd[6] );
			tT.appendChild( tTr[3] );

			// Row 4: Short Term Goals (text)
			tTr[4] = document.createElement('tr');
			tTd[7] = document.createElement('td');
			tTd[8] = document.createElement('td');
			tTd[7].innerHTML = '<b>Short Term Goals</b>';
			tempDiv = document.createElement('input');
			tempDiv.style.width = '400px';
			tTd[8].appendChild(tempDiv);
			dojo.widget.createWidget('Select', {
				name: 'problem['+dimension+']['+treatmentPlanProblemCounter+'][goalsshortterm]',
				id: 'shorttermgoals_'+treatmentPlanProblemCounter,
				autocomplete: 'false',
				dataUrl: "json-relay-0.8.x.php?module=treatmentplanoptions&method=remote_picklist&param[]="+dimension+"&param[]=shorttermgoal&param[]=%{searchString}",
				mode: "remote",
				value: "This should be replaced.",
				style: "width: 400px;",
			}, tempDiv);
			if (data['goalsshortterm']) { dojo.widget.byId('shorttermgoals_'+treatmentPlanProblemCounter).setValue(data['goalsshortterm']); }
			if (data['goalsshortterm']) { dojo.widget.byId('shorttermgoals_'+treatmentPlanProblemCounter).setLabel(data['goalsshortterm']); }
			tTd[108] = document.createElement('td');
			tTd[108].innerHTML += '<a href="module_loader.php?action=addform&module=treatmentplanoptions&tpdsm='+dimension+'&tptype=shorttermgoal&return=close" class="button" target="_entry">Add Entry</a>';
			tTr[4].appendChild( tTd[7] );
			tTr[4].appendChild( tTd[8] );
			tTr[4].appendChild( tTd[108] );
			tT.appendChild( tTr[4] );

			// Row 5: Short Term Goals (date effective)
			tTr[5] = document.createElement('tr');
			tTd[9] = document.createElement('td');
			tTd[10] = document.createElement('td');
			tTd[9].innerHTML = 'Date Effective';
			tempDiv = document.createElement('input');
			tTd[10].appendChild(tempDiv);
			dojo.widget.createWidget('DropdownDatePicker', {
				name: 'problem['+dimension+']['+treatmentPlanProblemCounter+'][dateeffectiveshort]',
				id: 'dateeffectiveshort_'+treatmentPlanProblemCounter,
				date: ( data['dateeffectiveshort'] ? data['dateeffectiveshort'] : dojo.widget.byId('dateofadmission').inputNode.value )
			}, tempDiv);
			dojo.widget.byId('dateeffectiveshort_'+treatmentPlanProblemCounter).inputNode.name = 'problem['+dimension+']['+treatmentPlanProblemCounter+'][dateeffectiveshort]';
			dojo.widget.byId('dateeffectiveshort_'+treatmentPlanProblemCounter).inputNode.value =  ( data['dateeffectiveshort'] ? data['dateeffectiveshort'] : dojo.widget.byId('dateofadmission').inputNode.value );
			tTr[5].appendChild( tTd[9] );
			tTr[5].appendChild( tTd[10] );
			tT.appendChild( tTr[5] );

			// Row 6: Short Term Goals (date target)
			tTr[6] = document.createElement('tr');
			tTd[11] = document.createElement('td');
			tTd[12] = document.createElement('td');
			tTd[11].innerHTML = 'Date Target';
			tempDiv = document.createElement('input');
			tTd[12].appendChild(tempDiv);
			dojo.widget.createWidget('DropdownDatePicker', {
				name: 'problem['+dimension+']['+treatmentPlanProblemCounter+'][datetargetshort]',
				id: 'datetargetshort_'+treatmentPlanProblemCounter,
				date: ( data['datetargetshort'] ? data['datetargetshort'] : dojo.widget.byId('dateexpires').inputNode.value )
			}, tempDiv);
			dojo.widget.byId('datetargetshort_'+treatmentPlanProblemCounter).inputNode.name = 'problem['+dimension+']['+treatmentPlanProblemCounter+'][datetargetshort]';
			dojo.widget.byId('datetargetshort_'+treatmentPlanProblemCounter).inputNode.value =  ( data['datetargetshort'] ? data['datetargetshort'] : dojo.widget.byId('dateexpires').inputNode.value );
			tTr[6].appendChild( tTd[11] );
			tTr[6].appendChild( tTd[12] );
			tT.appendChild( tTr[6] );

			// Place table in holder
			divHolder.appendChild( tT );

			// Assign this to be the newest child of the container
			container.appendChild( divHolder );
			treatmentPlanProblemCounter += 1;

			// Keep the event system happy, return true
			return false;
		} // end function treatmentPlanAddProblem

		function treatmentPlanRemoveProblem ( dimension, problem ) {
			var myContainer = document.getElementById( 'treatmentPlanPaneDimension' + dimension );
			var myProblem = document.getElementById( 'problem' + problem );
			myProblem.style.display = 'none';

			// Set "remove" property so the submit removes the temporary record which isn't used
			myContainer.innerHTML += '<input type="hidden" name="problem[' + dimension + '][' + problem + '][delete]" value="1" />';

			return false;
		} // end function treatmentPlanRemoveProblem

		function treatmentPlanAddOI ( dimension, problem, data ) {
			var container = document.getElementById( 'problem' + problem );

			// Get id
			var myId = data['id'] ? data['id'] : '0';
			
			// Create dojo objects
			var divHolder = document.createElement('div');
			divHolder.id = 'oi' + treatmentPlanOICounter;
			divHolder.style.background = '#bbbbbb';
			divHolder.style.width = '95%';
			divHolder.style.border = '1px solid #000000';
			divHolder.style.margin = '.5em';
			divHolder.style.padding = '.5em';
			divHolder.innerHTML += "<button onClick=\"return treatmentPlanRemoveOI(" + treatmentPlanOICounter + ", " + problem + ");\" class=\"button\">Remove</button>";
			divHolder.innerHTML += "<br/>\n";

			// Hidden text
			divHolder.innerHTML += '<input type="hidden" name="oi['+treatmentPlanOICounter+'][id]" value="'+dojo.string.escape('html', myId)+'" />';
			divHolder.innerHTML += '<input type="hidden" name="oi['+treatmentPlanOICounter+'][dsm]" value="'+dimension+'" />';
			divHolder.innerHTML += '<input type="hidden" name="oi['+treatmentPlanOICounter+'][problem]" value="'+problem+'" />';

			//divHolder.innerHTML += "This is O/I # "+treatmentPlanOICounter+"<br/>\n";
			// Create items, pairs
			var tT = document.createElement('table');
			var tTr = new Array ( );
			var tTd = new Array ( );

			// Row 1: Long Term Goals (text)
			tTr[1] = document.createElement('tr');
			tTd[1] = document.createElement('td');
			tTd[2] = document.createElement('td');
			tTd[3] = document.createElement('td');
			tTd[4] = document.createElement('td');
			tTd[1].innerHTML = '<b>Objective</b>';
			tempDiv = document.createElement('input');
			tempDiv.style.width = '400px';
			tTd[2].appendChild( tempDiv );
			dojo.widget.createWidget('Select', {
				name: 'oi['+treatmentPlanOICounter+'][objective]',
				id: 'objective_'+treatmentPlanOICounter,
				autocomplete: 'false',
				dataUrl: "json-relay-0.8.x.php?module=treatmentplanoptions&method=remote_picklist&param[]="+dimension+"&param[]=objective&param[]=%{searchString}",
				mode: "remote",
				value: "This should be replaced.",
				style: "width: 400px;"
			}, tempDiv);
			if (data['objective']) { dojo.widget.byId('objective_'+treatmentPlanOICounter).setLabel(data['objective']); }
			if (data['objective']) { dojo.widget.byId('objective_'+treatmentPlanOICounter).setValue(data['objective']); }
			tTd[102] = document.createElement('td');
			tTd[102].innerHTML += '<a href="module_loader.php?action=addform&module=treatmentplanoptions&tpdsm='+dimension+'&tptype=objective&return=close" class="button" target="_entry">Add Entry</a>';
			tTd[3].innerHTML = 'Date Effective';
			tempDiv = document.createElement('input');
			tTd[4].appendChild(tempDiv);
			dojo.widget.createWidget('DropdownDatePicker', {
				name: 'oi['+treatmentPlanOICounter+'][dateeffective]',
				id: 'oidateeffective_'+treatmentPlanOICounter,
				date: ( data['dateeffective'] ? data['dateeffective'] : dojo.widget.byId('dateofadmission').inputNode.value )
			}, tempDiv);
			dojo.widget.byId('oidateeffective_'+treatmentPlanOICounter).inputNode.name = 'oi['+treatmentPlanOICounter+'][dateeffective]';
			dojo.widget.byId('oidateeffective_'+treatmentPlanOICounter).inputNode.value = ( data['dateeffective'] ? data['dateeffective'] : dojo.widget.byId('dateofadmission').inputNode.value );
			tTr[1].appendChild( tTd[1] );
			tTr[1].appendChild( tTd[2] );
			tTr[1].appendChild( tTd[102] );
			tTr[1].appendChild( tTd[3] );
			tTr[1].appendChild( tTd[4] );
			tT.appendChild( tTr[1] );

			// Row 2: Intervention
			tTr[2] = document.createElement('tr');
			tTd[5] = document.createElement('td');
			tTd[6] = document.createElement('td');
			tTd[7] = document.createElement('td');
			tTd[8] = document.createElement('td');
			tTd[5].innerHTML = '<b>Intervention</b>';
			tempDiv = document.createElement('input');
			tempDiv.style.width = '400px';
			tTd[6].appendChild( tempDiv );
			dojo.widget.createWidget('Select', {
				name: 'oi['+treatmentPlanOICounter+'][intervention]',
				id: 'intervention_'+treatmentPlanOICounter,
				autocomplete: 'false',
				dataUrl: "json-relay-0.8.x.php?module=treatmentplanoptions&method=remote_picklist&param[]="+dimension+"&param[]=intervention&param[]=%{searchString}",
				mode: "remote",
				value: "This should be replaced.",
				style: "width: 400px;"
			}, tempDiv);
			if (data['objective']) { dojo.widget.byId('intervention_'+treatmentPlanOICounter).setLabel(data['intervention']); }
			if (data['objective']) { dojo.widget.byId('intervention_'+treatmentPlanOICounter).setValue(data['intervention']); }
			tTd[106] = document.createElement('td');
			tTd[106].innerHTML += '<a href="module_loader.php?action=addform&module=treatmentplanoptions&tpdsm='+dimension+'&tptype=intervention&return=close" class="button" target="_entry">Add Entry</a>';
			tTd[7].innerHTML = 'Date Target';
			tempDiv = document.createElement('input');
			tTd[8].appendChild(tempDiv);
			dojo.widget.createWidget('DropdownDatePicker', {
				name: 'oi['+treatmentPlanOICounter+'][datetarget]',
				id: 'oidatetarget_'+treatmentPlanOICounter,
				date: ( data['datetarget'] ? data['datetarget'] : dojo.widget.byId('dateexpires').inputNode.value )
			}, tempDiv);
			dojo.widget.byId('oidatetarget_'+treatmentPlanOICounter).inputNode.name = 'oi['+treatmentPlanOICounter+'][datetarget]';
			dojo.widget.byId('oidatetarget_'+treatmentPlanOICounter).inputNode.value =  ( data['datetarget'] ? data['datetarget'] : dojo.widget.byId('dateexpires').inputNode.value );
			tTr[2].appendChild( tTd[5] );
			tTr[2].appendChild( tTd[6] );
			tTr[2].appendChild( tTd[106] );
			tTr[2].appendChild( tTd[7] );
			tTr[2].appendChild( tTd[8] );
			tT.appendChild( tTr[2] );

			// Place table in holder
			divHolder.appendChild( tT );

			// Assign this to be the newest child of the container
			container.appendChild( divHolder );
			treatmentPlanOICounter += 1;

			return false;
		} // end function treatmentPlanAddOI

		function treatmentPlanRemoveOI ( oi, problem ) {
			var myOI = document.getElementById( 'oi' + oi );
			myOI.style.display = 'none';

			// Set "remove" property so the entry is deleted as well
			var myContainer = document.getElementById('problem' + problem);
			myContainer.innerHTML += '<input type="hidden" name="oi[' + dimension + '][' + problem + '][delete]" value="1" />';

			return false;
		} // end function treatmentPlanRemoveOI

		function treatmentPlanUpdateDate ( ) {
			if (dojo.widget.byId('dateofadmission').inputNode.value.split("/").length <= 2) {
				return false;
			}
			var dateParts = dojo.widget.byId('dateofadmission').inputNode.value.split("/");
			var thisDate = new Date();
			thisDate.month = dateParts[1];
			thisDate.date = dateParts[2];
			thisDate.year = dateParts[3];

			var interval = document.getElementById('periodcovered').value;
			var newTime = thisDate.getTime() + ( interval * ( 1000 * 60 * 60 * 24 ) );
			thisDate.setTime(newTime);
			dojo.widget.byId('dateexpires').inputNode.value = dojo.date.format(thisDate, "%m/%d/%Y");
			document.getElementById('dateexpires_date').value = dojo.date.format(thisDate, "%m/%d/%Y");
			return true;
		} // end function treatmentPlanUpdateDate

<?php if ($id or $clone) { ?> 
		// Import stock data
		var treatmentPlanStockData = <?php print json_encode($rec); ?>;
		var associatedStockData = <?php print json_encode($data); ?>;
		dojo.debug('defining treatmentPlanLoadStockData');
		function treatmentPlanLoadStockData( ) {
			dojo.debug('treatmentPlanLoadStockData() entered');
			// Push out stock data
			if (treatmentPlanStockData['creationdate']) { dojo.widget.byId('creationdate').inputNode.value = treatmentPlanStockData['creationdate']; }
			if (treatmentPlanStockData['dateofadmission']) { dojo.widget.byId('dateofadmission').inputNode.value = treatmentPlanStockData['dateofadmission']; }
			if (treatmentPlanStockData['dateexpires']) { dojo.widget.byId('dateexpires').inputNode.value = treatmentPlanStockData['dateexpires']; }
			if (treatmentPlanStockData['indicatedlevelofcare']) {
				dojo.widget.byId('indicatedlevelofcare').setLabel(treatmentPlanStockData['indicatedlevelofcare']);
				dojo.widget.byId('indicatedlevelofcare').setValue(treatmentPlanStockData['indicatedlevelofcare']);
			}
			if (treatmentPlanStockData['highercareassessed']) {
				dojo.widget.byId('highercareassessed').setLabel(treatmentPlanStockData['highercareassessed']);
				dojo.widget.byId('highercareassessed').setValue(treatmentPlanStockData['highercareassessed']);
			}

			// DSM codes handled by PHP ; TODO: FIXME for 0.9.0 

			dojo.debug('deciding if we have stock data');
			if (!associatedStockData) { return false; }
			dojo.debug('we have stock data');

			// Handle problems and O/I pairs
			var refTable = new Array ( );
			for (var i=1; i <= 6; i++ ) {
				// Loop through problems
				if (associatedStockData['problems'][i]) {
					dojo.debug('stock data problems '+i+' = array');
					try {
						for (var j=0; j < associatedStockData['problems'][i].length; j++) {
							dojo.debug("Dimension "+i+" id "+j+" / "+associatedStockData['problems'][i][j]['id']);
							treatmentPlanAddProblem( i, associatedStockData['problems'][i][j] );
							refTable[ associatedStockData['problems'][i][j]['id'] ] = j+1;
						}
					} catch (err) {}

					// Loop through O/I pairs
					try {
						for (var j=0; j < associatedStockData['oi'][i].length; j++) {
							treatmentPlanAddOI( i, refTable[associatedStockData['problems'][i][j]['id']], associatedStockData['oi'][i][j] );
						}
					} catch (err) {}
				}
			} // end 1..6
		}
		dojo.addOnLoad( treatmentPlanLoadStockData );
<?php } ?>

		// Have to load this after stock data properly
		dojo.addOnLoad( treatmentPlanUpdateDate );
	</script>

<form name="treatmentPlanForm" id="treatmentPlanForm" method="POST">

<!-- "hidden" data that has to be passed back to the module -->
<input type="hidden" name="id" id="id" value="<?php print $_REQUEST['id']+0; ?>" />
<input type="hidden" name="module" id="module" value="<?php print $_REQUEST['module']; ?>" />
<input type="hidden" name="action" id="action" value="add" />
<input type="hidden" name="return" id="return" value="<?php print $_REQUEST['return']; ?>" />

<input type="hidden" id="creationdate_date" name="creationdate" value="<?php print date('m/d/Y'); ?>" />
<input type="hidden" id="dateofadmission_date" name="dateofadmission" value="<?php print date('m/d/Y'); ?>" />
<input type="hidden" id="dateexpires_date" name="dateexpires" value="<?php print date('m/d/Y'); ?>" />

<div dojoType="TabContainer" id="treatmentPlanTabContainer" style="width: 90%; height: 90%;">
	<div dojoType="ContentPane" id="treatmentPlanPaneMain" label="General">
		<table border="0" cellpadding="10">
			<tr>
				<td>Patient</td>
				<td><?php print freemed::patient_widget('patient'); ?></td>
			</tr>
			<tr>
				<td>Treatment Facility</td>
				<td><?php print module_function('facilitymodule', 'widget', array ( 'facility' ) ); ?></td>
			</tr>
			<tr>
				<td>Treatment Plan Creation Date</td>
				<td><div dojoType="DropdownDatePicker" id="creationdate" date="<?php print date('m/d/Y'); ?>" containerToggle="wipe" onSetDate="document.getElementById('creationdate_date').value=dojo.widget.byId('creationdate').inputNode.value;"></div></td>
			</tr>
			<tr>
				<td>Date of Admission</td>
				<td><div dojoType="DropdownDatePicker" id="dateofadmission" date="<?php print date('m/d/Y'); ?>" containerToggle="wipe" onSetDate="treatmentPlanUpdateDate(); document.getElementById('dateofadmission_date').value=dojo.widget.byId('dateofadmission').inputNode.value;"></div></td>
			</tr>
			<tr>
				<td>Period Covered</td>
				<td>
					<select id="periodcovered" name="periodcovered" onChange="treatmentPlanUpdateDate(); return true;">
						<option value="30" <?php if ($rec['periodcovered']==30) { print "SELECTED"; } ?>>30 days</option>
						<option value="90" <?php if ($rec['periodcovered']==90) { print "SELECTED"; } ?>>90 days</option>
						<option value="180" <?php if ($rec['periodcovered']==180) { print "SELECTED"; } ?>>180 days</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Date of Expiration</td>
				<td><div dojoType="DropdownDatePicker" id="dateexpires" date="<?php print date('m/d/Y'); ?>" containerToggle="wipe" onSetDate="document.getElementById('dateexpires_date').value=dojo.widget.byId('dateexpires').inputNode.value;"></div></td>
			</tr>
		</table>
	</div>
	<div dojoType="ContentPane" id="treatmentPlanPaneDSM" label="Diagnostic Impressions: DSM-IV" style="overflow-x: none; overflow-y: auto;">
		<table border="0" padding=".5em">
			<tr>
				<td>Primary Axis I</td>
				<td><?php print module_function('codesmodule', 'widget', array ('d1code', "codedictionary='dsm'")); ?></td>
			</tr>
			<tr>
				<td>Secondary Axis I</td>
				<td><?php print module_function('codesmodule', 'widget', array ('d2code', "codedictionary='dsm'")); ?></td>
			</tr>
			<tr>
				<td>Tertiary Axis I</td>
				<td><?php print module_function('codesmodule', 'widget', array ('d3code', "codedictionary='dsm'")); ?></td>
			</tr>
			<tr>
				<td>Axis II</td>
				<td><?php print module_function('codesmodule', 'widget', array ('d4code', "codedictionary='dsm'")); ?></td>
			</tr>
			<tr>
				<td>Axis III</td>
				<td><?php print module_function('codesmodule', 'widget', array ('d5code', "codedictionary='dsm'")); ?></td>
			</tr>
			<tr>
				<td>Axis IV</td>
				<td><?php print module_function('codesmodule', 'widget', array ('d6code', "codedictionary='dsm'")); ?></td>
			</tr>
			<tr>
				<td>GAF - Current</td>
				<td><?php print html_form::text_widget('gafcurrent'); ?></td>
			</tr>
			<tr>
				<td>GAF - Highest</td>
				<td><?php print html_form::text_widget('gafhighest'); ?></td>
			</tr>
			<tr>
				<td>Indicated Level of Care</td>
				<td>
					<select dojoType="select" name="indicatedlevelofcare" id="indicatedlevelofcare" style="width: 300px;" autocomplete="false">
						<option>0.5 Early Intervention</option>
						<option>1.0 OP Treatment</option>
						<option>2.0 Intensive OP (Social)</option>
						<option>2.5 Partial Hosp/Supportive Living</option>
						<option>3.0 Med. Mon Intense IP</option>
						<option>4.0 Med Mngd Intense IP</option>
						<option>1D Amb w/o Extend On site</option>
						<option>2D Amb - Extend Monitor</option>
						<option>3.2D Clin Mngd Resident'l</option>
						<option>3.7 Med Monitored IP</option>
						<option>4D Med Managed IP</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Higher Care Assessed</td>
				<td>
					<select dojoType="select" name="highercareassessed" id="highercareassessed" style="width: 300px;" autocomplete="false">
						<option>None</option>
						<option>Lack of Funding</option>
						<option>Lack of Insurance</option>
						<option>Child Care</option>
						<option>No Treatment Resource</option>
						<option>Patient Refusal</option>
						<option>NTP Stabilization Required</option>
					</select>
				</td>
			</tr>
		</table>
	</div>
	<div dojoType="ContentPane" id="treatmentPlanPaneDimension1" label="Dimension 1" style="overflow-x: none; overflow-y: auto;">
		<div style="padding: .5em;"><b>Dimension 1: Acute Intoxication and/or Withdrawal Potential</b></div>
		<div style="padding: .5em;"><button onClick="treatmentPlanAddProblem(1, {}); return false;" class="button">Add Problem</button></div>
		<table border="0" padding=".5em">
			<tr>
				<td>Status</td>
				<td>
					<select name="d1status">
						<option value="0">Low</option>
						<option value="1">Medium</option>
						<option value="2">High</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Note</td>
				<td><textarea name="d1note" wrap="virtual" rows="4" cols="80"><?php print prepare($rec['d1note']); ?></textarea></td>
			</tr>
		</table>
	</div>
	<div dojoType="ContentPane" id="treatmentPlanPaneDimension2" label="Dimension 2" style="overflow-x: none; overflow-y: auto;">
		<div style="padding: .5em;"><b>Dimension 2: Biomedical Conditions and Complications</b></div>
		<div style="padding: .5em;"><button onClick="treatmentPlanAddProblem(2, {}); return false;" class="button">Add Problem</button></div>
		<table border="0" padding=".5em">
			<tr>
				<td>Status</td>
				<td>
					<select name="d2status">
						<option value="0">Low</option>
						<option value="1">Medium</option>
						<option value="2">High</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Note</td>
				<td><textarea name="d2note" wrap="virtual" rows="4" cols="80"><?php print prepare($rec['d2note']); ?></textarea></td>
			</tr>
		</table>
	</div>
	<div dojoType="ContentPane" id="treatmentPlanPaneDimension3" label="Dimension 3" style="overflow-x: none; overflow-y: auto;">
		<div style="padding: .5em;"><b>Dimension 3: Emotional/Behavioral Conditions and Complications</b></div>
		<div style="padding: .5em;"><button onClick="treatmentPlanAddProblem(3, {}); return false;" class="button">Add Problem</button></div>
		<table border="0" padding=".5em">
			<tr>
				<td>Status</td>
				<td>
					<select name="d3status">
						<option value="0">Low</option>
						<option value="1">Medium</option>
						<option value="2">High</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Note</td>
				<td><textarea name="d3note" wrap="virtual" rows="4" cols="80"><?php print prepare($rec['d3note']); ?></textarea></td>
			</tr>
		</table>
	</div>
	<div dojoType="ContentPane" id="treatmentPlanPaneDimension4" label="Dimension 4" style="overflow-x: none; overflow-y: auto;">
		<div style="padding: .5em;"><b>Dimension 4: Treatment Acceptance/Resistance</b></div>
		<div style="padding: .5em;"><button onClick="treatmentPlanAddProblem(4, {}); return false;" class="button">Add Problem</button></div>
		<table border="0" padding=".5em">
			<tr>
				<td>Status</td>
				<td>
					<select name="d4status">
						<option value="0">Low</option>
						<option value="1">Medium</option>
						<option value="2">High</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Note</td>
				<td><textarea name="d4note" wrap="virtual" rows="4" cols="80"><?php print prepare($rec['d4note']); ?></textarea></td>
			</tr>
		</table>
	</div>
	<div dojoType="ContentPane" id="treatmentPlanPaneDimension5" label="Dimension 5" style="overflow-x: none; overflow-y: auto;">
		<div style="padding: .5em;"><b>Dimension 5: Relapse/Continued Use Potential</b></div>
		<div style="padding: .5em;"><button onClick="treatmentPlanAddProblem(5, {}); return false;" class="button">Add Problem</button></div>
		<table border="0" padding=".5em">
			<tr>
				<td>Status</td>
				<td>
					<select name="d5status">
						<option value="0">Low</option>
						<option value="1">Medium</option>
						<option value="2">High</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Note</td>
				<td><textarea name="d5note" wrap="virtual" rows="4" cols="80"><?php print prepare($rec['d5note']); ?></textarea></td>
			</tr>
		</table>
	</div>
	<div dojoType="ContentPane" id="treatmentPlanPaneDimension6" label="Dimension 6" style="overflow-x: none; overflow-y: auto;">
		<div style="padding: .5em;"><b>Dimension 6: Recovery Environment</b></div>
		<div style="padding: .5em;"><button onClick="treatmentPlanAddProblem(6, {}); return false;" class="button">Add Problem</button></div>
		<table border="0" padding=".5em">
			<tr>
				<td>Status</td>
				<td>
					<select name="d6status">
						<option value="0">Low</option>
						<option value="1">Medium</option>
						<option value="2">High</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Note</td>
				<td><textarea name="d6note" wrap="virtual" rows="4" cols="80"><?php print prepare($rec['d6note']); ?></textarea></td>
			</tr>
		</table>
	</div>
</div>

<div align="center">
	<table border="0">
		<tr>
			<td><button dojoType="button" onClick="return treatmentPlanSubmit();" class="button">Commit Treatment Plan</button></td>
			<td><button dojoType="button" onClick="return treatmentPlanDestroy();" class="button">Destroy Treatment Plan</button></td>
			<td><button dojoType="button" onClick="return treatmentPlanCancel();" class="button">Cancel</button></td>
		</tr>
		</tr>
	</table>
</div>

</form>

</body>
</html>
