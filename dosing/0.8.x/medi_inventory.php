<?php
	// $Id$
	// $Author$
	// note: template for Medication Inventory
	// lic : GPL, v2

//----- Pull configuration for this user

if (!is_object($this_user)) $this_user = CreateObject('FreeMED.User');

//----- Make sure all module functions are loaded
LoadObjectDependency('PHP.module');
//----- Extract all configuration data
if (is_array($this_user->manage_config)) extract($this_user->manage_config);
//----- Check for a *reasonable* refresh time and summary items
if ($automatic_refresh_time > 14) {
	$GLOBALS['__freemed']['automatic_refresh'] = $automatic_refresh_time;
}
if ($num_summary_items < 1) $num_summary_items = 5;


//----- Display patient information box...
$display_buffer .= freemed::patient_box($this_patient);

//----- Create module list
if (!is_object($module_list)) { $module_list = freemed::module_cache(); }
//-- ... then modular

//----- Determine column requirements

//----- Display tables
function __sort_panels ($a, $b) {
	if ($a['order'] == $b['order']) {
	       	$c_a = isset($a['module']) ? $a['module'] : $a['static'];
	       	$c_b = isset($b['module']) ? $b['module'] : $b['static'];
		return ($c_a < $c_b) ? -1 : 1;
	}
	return ($a['order'] < $b['order']) ? -1 : 1;
}
$display_buffer ="
	<div class=\"section\">".__("Medication Inventory")."</div><br/>
	<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
			<td> ".module_function("BulkMethadone", "addform_link")."</td>
		</tr>
		<tr>
			<td>".module_function("LotReceipt", "addform_link")."</td>
		</tr><!--
		<tr>
			<td>".module_function("NewLotOpen", "addform_link") . "</td>
		</tr>
		<tr>
			<td>".module_function("ReconcileBottle", "addform_link") ."</td>
		</tr>
		<tr>
			<td>".module_function("RecordStock", "addform_link") ."</td>
		</tr>-->
	</table>";

?>
