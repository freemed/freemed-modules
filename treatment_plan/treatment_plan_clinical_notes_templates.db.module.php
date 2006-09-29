<?php
  // $Id$
  //
  // Authors:
  //      Jeff Buchbinder <jeff@freemedsoftware.org>
  //
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

LoadObjectDependency('_FreeMED.MaintenanceModule');

class TreatmentPlanClinicalNotesTemplates extends MaintenanceModule {
	var $MODULE_NAME = "Treatment Plan Clinical Notes Templates";
	var $MODULE_VERSION = "0.1";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.3';

	// This module is hidden because it doesn't *behave* like other
	// generic modules. It is meant to be run in a popup window.
	var $MODULE_HIDDEN = true;

	var $record_name = "Treatment Plan Clinical Notes Templates";
	var $table_name = "tpcntemplate";

	function TreatmentPlanClinicalNotesTemplates () {
		// Check for, and if not there create, a user object
		global $this_user;
		if (!is_object($this_user)) {
			$this_user = CreateObject('_FreeMED.User');
		}

		// Create table definition
		$this->table_definition = array(
			'tpcntname' => SQL__VARCHAR(150),
			'tpcntphy' => SQL__INT_UNSIGNED(0),
			'tpcntS' => SQL__TEXT,
			'tpcntO' => SQL__TEXT,
			'tpcntA' => SQL__TEXT,
			'tpcntP' => SQL__TEXT,
			'tpcntcpt' => SQL__INT_UNSIGNED(0),
			'tpcntdiag1' => SQL__INT_UNSIGNED(0),
			'tpcntdiag2' => SQL__INT_UNSIGNED(0),
			'tpcntdiag3' => SQL__INT_UNSIGNED(0),
			'tpcntdiag4' => SQL__INT_UNSIGNED(0),
			'tpcntcharges' => SQL__REAL,
			'id' => SQL__SERIAL
		);

		$this->variables = array (
			'tpcntname',
			'tpcntphy' => $this_user->user_phy,
			'tpcntS',
			'tpcntO',
			'tpcntA',
			'tpcntP',
			'tpcntcpt',
			'tpcntdiag1',
			'tpcntdiag2',
			'tpcntdiag3',
			'tpcntdiag4',
			'tpcntcharges',
		);

		// Call parent constructor
		$this->MaintenanceModule();
	} // end constructor

	// function add
	function add () {
		// Set onLoad to reload parent template set
		$GLOBALS['__freemed']['on_load'] = 'process';
		$GLOBALS['__freemed']['no_template_display'] = true;

		// Set this phy to be 'tpcntphy'
		$result = $GLOBALS['sql']->query(
			$GLOBALS['sql']->insert_query(
				$this->table_name, 
				$this->variables
			)
		);

		// Put out proper JavaScript
		$GLOBALS['display_buffer'] .= "
			<script LANGUAGE=\"JavaScript\">
			function process() {
				opener.document.forms.".prepare($GLOBALS['formname']).".submit()
				window.self.close()
			}
			</script>
			";

		template_display();
	}
		
	// function mod
	function mod () {
		// Set onLoad to reload parent template set
		$GLOBALS['__freemed']['on_load'] = 'process';
		$GLOBALS['__freemed']['no_template_display'] = true;

		// Set this phy to be 'tpcntphy'
		$result = $GLOBALS['sql']->query(
			$GLOBALS['sql']->update_query(
				$this->table_name, 
				$this->variables,
				array('id' => $GLOBALS['id'])
			)
		);

		// Put out proper JavaScript
		$GLOBALS['display_buffer'] .= "
			<script LANGUAGE=\"JavaScript\">
			function process() {
				opener.document.forms.".prepare($GLOBALS['formname']).".submit()
				window.self.close()
			}
			</script>
			";

		template_display();
	}
		
	// function form
	function form () {
		global $display_buffer, $module, $formname;

		// Get everything if modification
		if ($GLOBALS['action'] == 'modform') {
			$r = freemed::get_link_rec($GLOBALS['id'], $this->table_name);
			if (is_array($r)) {
				foreach ($r AS $k => $v) {
					global ${$k}; ${$k} = $v;
				}
			}
		}

		$GLOBALS['__freemed']['no_template_display'] = true;
		$display_buffer .= "
		<form ACTION=\"".$this->page_name."\" METHOD=\"POST\">
		<input TYPE=\"HIDDEN\" NAME=\"module\" VALUE=\"".prepare($module)."\"/>
		<input TYPE=\"HIDDEN\" NAME=\"id\" VALUE=\"".
			prepare($GLOBALS['id'])."\"/>
		<input TYPE=\"HIDDEN\" NAME=\"action\" VALUE=\"".
			( ($GLOBALS['action']=='addform') ? 'add' : 'mod' )."\"/>
		<input TYPE=\"HIDDEN\" NAME=\"formname\" VALUE=\"".prepare($formname)."\"/>
		".html_form::form_table(array(
		
			__("Template Name") =>
			html_form::text_widget('tpcntname', 25, 150),

			__("Problem") =>
			freemed::rich_text_area('tpcntS', 10, 40, true),
		
			__("Discussion") =>
			freemed::rich_text_area('tpcntO', 10, 40),
		
			__("Assessment") =>
			freemed::rich_text_area('tpcntA', 10, 40),
		
			__("Plan") =>
			freemed::rich_text_area('tpcntP', 10, 40),

			__("Procedure Code") =>
			module_function('cptmaintenance', 'widget', array('tpcntcpt')),
		
			__("Diagnosis Code").' 1' =>
			module_function('icdmaintenance', 'widget', array('tpcntdiag1')),
		
			__("Diagnosis Code").' 2' =>
			module_function('icdmaintenance', 'widget', array('tpcntdiag2')),
		
			__("Diagnosis Code").' 3' =>
			module_function('icdmaintenance', 'widget', array('tpcntdiag3')),
		
			__("Diagnosis Code").' 4' =>
			module_function('icdmaintenance', 'widget', array('tpcntdiag4')),
		
			__("Procedure Amount") =>
			html_form::text_widget('tpcntcharges', 25),
		))."
		</div>
		<p/>
		<div ALIGN=\"CENTER\">
			<input TYPE=\"SUBMIT\" VALUE=\"".(
				($action=="addform") ? __("Add") : __("Modify") )."\"/>
			<input TYPE=\"BUTTON\" VALUE=\"".__("Cancel")."\"
			 onClick=\"window.close(); return true;\"/>
		</div>
		</form>
		";
	}

	// function picklist
	// - generates a picklist widget of possible templates
	function picklist ($varname, $formname) {
		$query = "SELECT * FROM ".$this->table_name." ".
			//"WHERE tpcntphy='".$GLOBALS['this_user']->user_phy."' OR tpcntphy=0 ".
			"ORDER BY tpcntname";
		$result = $GLOBALS['sql']->query($query);
		
		$add = "<input type=\"BUTTON\" onClick=\"tpcntPopup=window.open(".
		"'".$this->page_name."?module=".get_class($this)."&varname=".
		urlencode($varname)."&action=addform&formname=".
		urlencode($formname)."', 'tpcntPopup'); ".
		"tpcntPopup.opener=self; return true\" VALUE=\"".__("Add")."\"/>\n";

		// Make sure there are templates already
		if (!$GLOBALS['sql']->results($result)) {
			return $add;
		}

		// Add the "edit" button
		$add .= "<input type=\"BUTTON\" onClick=\"tpcntPopup=window.open(".
		"'".$this->page_name."?module=".get_class($this)."&varname=".
		urlencode($varname)."&action=modform&formname=".
		urlencode($formname)."&id='+document.".$formname.".".$varname.
		".value, 'tpcntPopup'); ".
		"tpcntPopup.opener=self; return true\" VALUE=\"".__("Edit")."\"/>\n";

		// Loop them into "options"
		$options = array();
		while ($r = $GLOBALS['sql']->fetch_array($result)) {
			$options[prepare($r['tpcntname'])] = $r['id'];
		}
		
		return html_form::select_widget(
			$varname,
			$options
		)." ".
		"<input TYPE=\"SUBMIT\" VALUE=\"".__("Use")."\" ".
		"onClick=\"this.form.".$varname."_used.value = '1'; this.form.submit(); ".
		"return true;\"/> ".
		$add;
	} // end method picklist

	// function retrieve
	// - retrieves a template and inserts it locally into proper variables
	function retrieve ($varname) {
		global ${$varname}, ${$varname.'_used'};

		if (${$varname.'_used'} == 1) {
			// Get template
			$t = freemed::get_link_rec(${$varname}, $this->table_name);

			// Loop through values in record
			foreach ($t AS $k => $v) {
				// Check for 'tpcnt' prefix
				if (is_integer(strpos($k, 'tpcnt'))) {
					$_k = str_replace('tpcnt', 'tpcnotes_', $k);
					global ${$_k}; ${$_k} = $v;
					$_k = str_replace('tpcnt', 'tpcnotes', $k);
					global ${$_k}; ${$_k} = $v;
				}
			}

			// Reset
			${$varname.'_used'} = 0;
		}
	} // end method retrieve

} // end class TreatmentPlanClinicalNotesTemplates

register_module('TreatmentPlanClinicalNotesTemplates');

?>
