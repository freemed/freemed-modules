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

LoadObjectDependency('_FreeMED.EMRModule');

class TreatmentPlanClinicalNotes extends EMRModule {

	var $MODULE_NAME = "Treatment Plan Clinical Notes";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_VERSION = "0.1";
	var $MODULE_FILE = __FILE__;

	var $PACKAGE_MINIMUM_VERSION = '0.8.3';

	var $record_name   = "Treatment Plan Clinical Notes";
	var $table_name    = "tpcnotes";
	var $patient_field = "tpcnotespat";
	var $widget_hash   = "##tpcnotesdt## ##tpcnotesdescrip##";

	var $print_template = 'progress_notes';

	function TreatmentPlanClinicalNotes() {
		// Table description
		$this->table_definition = array (
			'tpcnotesdt' => SQL__DATE,
			'tpcnotesdtadd' => SQL__DATE,
			'tpcnotesdtmod' => SQL__DATE,
			'tpcnotespat' => SQL__INT_UNSIGNED(0),
			'tpcnotesdescrip' => SQL__VARCHAR(100),
			'tpcnotesdoc' => SQL__INT_UNSIGNED(0),
			'tpcnoteseoc' => SQL__INT_UNSIGNED(0),
			'tpcnotesproc' => SQL__INT_UNSIGNED(0),
			'tpcnotes_S' => SQL__TEXT,
			'tpcnotes_O' => SQL__TEXT,
			'tpcnotes_A' => SQL__TEXT,
			'tpcnotes_P' => SQL__TEXT,
			'locked' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);
		
		// Define variables for EMR summary
		$this->summary_vars = array (
			__("Date")        =>	"my_date",
			__("Provider")    =>	"tpcnotesdoc:physician",
			__("Description") =>	"tpcnotesdescrip"
		);
		$this->summary_options |= SUMMARY_VIEW | SUMMARY_LOCK | SUMMARY_PRINT | SUMMARY_DELETE;
		$this->summary_query = array("DATE_FORMAT(tpcnotesdt, '%m/%d/%Y') AS my_date");
		$this->summary_order_by = 'tpcnotesdt DESC,id';

		// Set associations
		$this->_SetAssociation('EpisodeOfCare');
		$this->_SetMetaInformation('EpisodeOfCareVar', 'tpcnoteseoc');

		// Call parent constructor
		$this->EMRModule();
	} // end constructor TreatmentPlanClinicalNotes

	function add () { $this->form(); }
	function mod () { $this->form(); }

	function form () {
		global $display_buffer, $sql, $tpcnoteseoc;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }

		$book = CreateObject('PHP.notebook',
				array ("id", "module", "patient", "action", "return"),
				NOTEBOOK_COMMON_BAR | NOTEBOOK_STRETCH, 5);

		switch ($action) {
			case "add": case "addform":
			$book->set_submit_name(__("Add"));
			break;

			case "mod": case "modform":
			$book->set_submit_name(__("Modify"));
			break;
		}
     
		if (!$book->been_here()) {
      switch ($action) { // internal switch
        case "addform":
 	// check if we are a physician
         if ($this->this_user->isPhysician()) {
		global $tpcnotesdoc;
 		$tpcnotesdoc = $this->this_user->getPhysician(); // if so, set as default
	}
	 global $tpcnotesdt;
         $tpcnotesdt     = date("Y-m-d");
         break; // end addform
        case "modform":
         //while(list($k,$v)=each($this->variables)) { global ${$v}; }

         if (($id<1) OR (strlen($id)<1)) {
           $page_title = _($this->record_name)." :: ".__("ERROR");
           $display_buffer .= "
             ".__("You must select a patient.")."
           ";
           template_display();
         }

         $r = freemed::get_link_rec ($id, $this->table_name);

	 if ($r['locked'] > 0) {
		$display_buffer .= "
		<div ALIGN=\"CENTER\">
		".__("This record is locked, and cannot be modified.")."
		</div>

		<p/>
		
		<div ALIGN=\"CENTER\">
		".
		(($return == "manage") ?
		"<a href=\"manage.php?id=$patient\">".__("Manage Patient")."</a>" :
		"<a href=\"module_loader.php?module=".get_class($this)."\">".
			__("back")."</a>" )
		."\n</div>\n";
		return false;
	 }
	 
         foreach ($r AS $k => $v) {
           global ${$k}; ${$k} = stripslashes($v);
         }
  	 	 extract ($r);
         break; // end modform
      } // end internal switch
     } // end checking if been here

	// Check for progress notes templates addon module
	if (check_module("TreatmentPlanClinicalNotesTemplates") and ($action=='addform')) {
		// Create picklist widget
		$pnt_array = array (
			__("Notes Template") =>
			module_function(
				'TreatmentPlanClinicalNotesTemplates', 
				'picklist', 
				array('pnt', $book->formname)
			)
		);

		// Check for used status
		module_function(
			'TreatmentPlanClinicalNotesTemplates',
			'retrieve',
			array('pnt')
		);
	} else {
		$pnt_array = array ("" => "");
	}

	$book->add_page (
		__("Basic Information"),
		array ("tpcnotesdoc", "tpcnotesdescrip", "tpcnoteseoc", date_vars("tpcnotesdt")),
		"<input TYPE=\"HIDDEN\" NAME=\"pnt_used\" VALUE=\"\"/>\n".
		html_form::form_table (
			array_merge ( 
				$pnt_array, 
				array (
					__("Treatment Plan Type") =>
						module_function('treatmentplantype', 'widget', array ( 'tpcnoteseoc', $_REQUEST['patient'], false, 'tpteoc' ) ),
					__("Provider") =>
						freemed_display_selectbox (
						$sql->query ("SELECT * FROM physician ".
						"WHERE phyref != 'yes' AND phylname != '' ".
						"ORDER BY phylname,phyfname"),
						"#phylname#, #phyfname#",
						"tpcnotesdoc"
					),
					__("Description") =>
						html_form::text_widget("tpcnotesdescrip", 25, 100),
					__("Date") => fm_date_entry("tpcnotesdt") 
				)
			)
		)
	); 

	$book->add_page (
		__("Problem"),
		array ("tpcnotes_S"),
		html_form::form_table (
			array (
				__("Problem") =>
				freemed::rich_text_area('tpcnotes_S', 30, 60, true),
				" " => "<input type=\"submit\" class=\"button\" value=\"".__("Save")."\" />".
				"<input type=\"reset\" class=\"button\" value=\"".__("Revert to Saved")."\" />"
			)
		)
	);

	$book->add_page (
		__("Discussion"),
		array ("tpcnotes_O"),
		html_form::form_table (
			array (
				__("Discussion") =>
				freemed::rich_text_area('tpcnotes_O', 30, 60, true),
				" " => "<input type=\"submit\" class=\"button\" value=\"".__("Save")."\" />".
				"<input type=\"reset\" class=\"button\" value=\"".__("Revert to Saved")."\" />"
			)
		)
	);

	$book->add_page (
		__("Assessment"),
		array ("tpcnotes_A"),
		html_form::form_table (
			array (
				__("Assessment") =>
				freemed::rich_text_area('tpcnotes_A', 30, 60, true),
				" " => "<input type=\"submit\" class=\"button\" value=\"".__("Save")."\" />".
				"<input type=\"reset\" class=\"button\" value=\"".__("Revert to Saved")."\" />"
			)
		)
	);

	$book->add_page (
		__("Plan"),
		array ("tpcnotes_P"),
		html_form::form_table (
			array (
				__("Plan") =>
				freemed::rich_text_area('tpcnotes_P', 30, 60, true),
				//html_form::text_area('tpcnotes_P', 'VIRTUAL', 20, 75),
				" " => "<input type=\"submit\" class=\"button\" value=\"".__("Save")."\" />".
				"<input type=\"reset\" class=\"button\" value=\"".__("Revert to Saved")."\" />"
			)
		)
	);

	if (substr($_REQUEST['action'], 0, 3) == 'add') {
	$book->add_page(
		__("Billing Information"),
		array(
			'tpcnotescpt',
			'tpcnotesdiag1',
			'tpcnotesdiag2',
			'tpcnotesdiag3',
			'tpcnotesdiag4',
			'tpcnotescharges',
			'auth',
			'cov1',
			'cov2',
			'pos'
		), html_form::form_table(array(
			__("Procedure Code") => module_function ( 'cptmaintenance', 'widget', array ( 'tpcnotescpt' )),
			__("Diagnosis 1") => module_function ( 'icdmaintenance', 'widget', array ( 'tpcnotesdiag1' )),
			__("Diagnosis 2") => module_function ( 'icdmaintenance', 'widget', array ( 'tpcnotesdiag2' )),
			__("Diagnosis 3") => module_function ( 'icdmaintenance', 'widget', array ( 'tpcnotesdiag3' )),
			__("Diagnosis 4") => module_function ( 'icdmaintenance', 'widget', array ( 'tpcnotesdiag4' )),
			__("Place of Service") => module_function ( 'facilitymodule', 'widge', array ( 'pos' )),
			__("Authorization") => module_function ( 'authorizationsmodule', 'widget', array ( 'auth', $_REQUEST['patient'] )),
			__("Primary Coverage") => module_function ( 'patientcoveragesmodule', 'widget', array ( 'cov1', $_REQUEST['patient'] )),
			__("Secondary Coverage") => module_function ( 'patientcoveragesmodule', 'widget', array ( 'cov2', $_REQUEST['patient'] )),
			__("Procedural Units") => html_form::text_widget('tpcnotescharges', 25)
		))
	);
	} // end checking for addform

	// Handle cancel action
	if ($book->is_cancelled()) {
		// Unlock record, if it is locked
		$__lock = CreateObject('_FreeMED.RecordLock', $this->table_name);
		$__lock->UnlockRow ( $_REQUEST['id'] );

		if ($return=='manage') {
			Header("Location: manage.php?id=".urlencode($patient));
		} else {
			Header("Location: ".$this->page_name."?".
				"module=".$this->MODULE_CLASS."&".
				"patient=".$patient);
		}
		die("");
	}

     if (!$book->is_done()) {
      $display_buffer .= $book->display();
     } else {
       switch ($action) {
        case "addform": case "add":
         $display_buffer .= "
           <div ALIGN=\"CENTER\"><b>".__("Adding")." ... </b>
         ";
           // preparation of values
         $tpcnotesdtadd = $cur_date;
         $tpcnotesdtmod = $cur_date;

           // actual addition
	global $locked, $this_user;
	$query = $sql->insert_query (
		$this->table_name,
		array (
			"tpcnotespat"      => $_REQUEST['patient'],
			"tpcnoteseoc",
			"tpcnotesdoc",
			"tpcnotesdt"       => fm_date_assemble("tpcnotesdt"),
			"tpcnotesdescrip",
			"tpcnotesdtadd"    => date("Y-m-d"),
			"tpcnotesdtmod"    => date("Y-m-d"),
			"tpcnotes_S",
			"tpcnotes_O",
			"tpcnotes_A",
			"tpcnotes_P",
			"locked"         => $this_user->user_number // $locked
		)
	);
         break;

			case "modform": case "mod":
			$display_buffer .= "<div ALIGN=\"CENTER\"><b>".__("Modifying")." ... </b>\n";
			global $locked;
			$query = $sql->update_query (
				$this->table_name,
				array (
					"tpcnotespat"      => $_REQUEST['patient'],
					"tpcnoteseoc",
					"tpcnotesdoc",
					"tpcnotesdt"       => fm_date_assemble("tpcnotesdt"),
					"tpcnotesdescrip",
					"tpcnotesdtmod"    => date("Y-m-d"),
					"tpcnotes_S",
					"tpcnotes_O",
					"tpcnotes_A",
					"tpcnotes_P",
					"locked"         => $locked
				),
				array ( "id" => $_REQUEST['id'] )
			);
			break;
		} // end inner switch

		// now actually send the query
		$result = $GLOBALS['sql']->query ($query);
		$this_record = $GLOBALS['sql']->last_record( $result );
		if ($result) {
			$display_buffer .= " <b> ".__("done").". </b>\n";
		} else {
			$display_buffer .= " <b> <font COLOR=\"#ff0000\">".__("ERROR")."</font> </b>\n";
		}

		// Handle procedure record addition if applicable
		if (substr($_REQUEST['action'], 0, 3) == 'add') {
			// Calculate charges
			$charges = module_function(
				'proceduremodule',
				'CalculateCharge',
				array (
					$_REQUEST['cov1'],
					abs( ( $_REQUEST['tpcnotescharges'] ? $_REQUEST['tpcnotescharges'] : 1 ) ),
					$_REQUEST['tpcnotescpt'],
					$_REQUEST['tpcnotesdoc'],
					$_REQUEST['patient']
				)
			);

			// Query
			$proc_query = $GLOBALS['sql']->insert_query (
				'procrec',
				array (
					'proccurcovtp' => ( $_REQUEST['cov1'] ? 1 : 0 ),
					'proccurcovid' => $_REQUEST['cov1']+0,
					'proccov1' => $_REQUEST['cov1']+0,
					'proccov2' => $_REQUEST['cov2']+0,
					'proccov3' => $_REQUEST['cov3']+0,
					'proccov4' => $_REQUEST['cov4']+0,
					'procauth' => $_REQUEST['auth']+0,
					'procpos' => $_REQUEST['pos']+0,
					'procpatient' => $_REQUEST['patient'],
					'proceoc' => $_REQUEST['tpcnoteseoc'],
					'procphysician' => $_REQUEST['tpcnotesdoc'],
					'procdt' => fm_date_assemble("tpcnotesdt"),
					'proccpt' => $_REQUEST['tpcnotescpt'],
					'procdiag1' => $_REQUEST['tpcnotesdiag1'],
					'procdiag2' => $_REQUEST['tpcnotesdiag2'],
					'procdiag3' => $_REQUEST['tpcnotesdiag3'],
					'procdiag4' => $_REQUEST['tpcnotesdiag4'],
					'procbalorig' => $charges,
					'procbalcurrent' => $charges,
					'procbillable' => 0,
					'procbilled' => 0,
					'procamtpaid' => 0
				)
			);
			$proc_result = $GLOBALS['sql']->query ( $proc_query );
			$this_procedure = $GLOBALS['sql']->last_record( $proc_result );
			$pay_query = $GLOBALS['sql']->insert_query(
				'payrec',
				array(
					'payrecdtadd' => date('Y-m-d'),
					'payrecdtmod' => '0000-00-00',
					'payrecpatient' => $_REQUEST['patient'],
					'payrecdt' => fm_date_assemble("tpcnotesdt"),
					'payreccat' => PROCEDURE,
					'payrecproc' => $this_procedure,
					'payrecsource' => ( $_REQUEST['cov1'] ? 1 : 0 ),
					'payreclink' => $_REQUEST['cov1'],
					'payrectype' => '0',
					'payrecnum' => '',
					'payrecamt' => $charges,
					'payrecdescrip' => '',
					'payreclock' => 'unlocked'
				)
			);
			$pay_result = $GLOBALS['sql']->query ( $pay_query );

			// Update the master record with the procrec table key
			$upd_query = $GLOBALS['sql']->update_query (
				$this->table_name,
				array ( 'tpcnotesproc' => $this_procedure ),
				array ( 'id' => $this_record )
			);
			$upd_result = $GLOBALS['sql']->query ( $upd_query );
		}

		$display_buffer .= "
		</div>
		<p/>
		<div ALIGN=\"CENTER\"><a HREF=\"manage.php?id=$patient\"
		>".__("Manage Patient")."</a>
		<b>|</b>
		<a HREF=\"$this->page_name?module=$module&patient=$patient\"
		>".__($this->record_name)."</a>
		";

		if ($action=="mod" OR $action=="modform") {
			$display_buffer .= "
			<b>|</b>
			<a HREF=\"$this->page_name?module=$module&patient=$patient&action=view&id=$id\"
			>".__("View $this->record_name")."</a>
			";
		}
		$display_buffer .= "
		</div>
		<p/>
		";

		// Handle returning to patient management screen after add
		global $refresh;
		if ($_REQUEST['return'] == 'manage') {
			$refresh = 'manage.php?id='.urlencode($patient).'&ts='.urlencode(mktime());
			}
		} // end if is done
	} // end method form

	function display () {
		global $display_buffer;

		// Tell FreeMED not to display a template
		$GLOBALS['__freemed']['no_template_display'] = true;
		
		foreach ($GLOBALS AS $k => $v) global $$k;
     if (($id<1) OR (strlen($id)<1)) {
       $display_buffer .= "
         ".__("Specify Notes to Display")."
         <p/>
         <div ALIGN=\"CENTER\">
	 <a HREF=\"$this->page_name?module=$module&patient=$patient\"
          >".__("back")."</a> |
          <a HREF=\"manage.php?id=$patient\"
          >".__("Manage Patient")."</a>
         </div>
       ";
       template_display();
     }
      // if it is legit, grab the data
     $r = freemed::get_link_rec ($id, "tpcnotes");
     if (is_array($r)) extract ($r);
     $tpcnotesdt_formatted = substr ($tpcnotesdt, 0, 4). "-".
                           substr ($tpcnotesdt, 5, 2). "-".
                           substr ($tpcnotesdt, 8, 2);
     $tpcnotespat = $r ["tpcnotespat"];
     $tpcnoteseoc = sql_expand ($r["tpcnoteseoc"]);

     $this->this_patient = CreateObject('FreeMED.Patient', $tpcnotespat);

     $display_buffer .= "
       <p/>
       ".template::link_bar(array(
        __("Treatment Plan Clinical Notes") =>
       $this->page_name."?module=$module&patient=$tpcnotespat",
        __("Manage Patient") =>
       "manage.php?id=$tpcnotespat",
	__("Select Patient") =>
        "patient.php",
	( freemed::acl_patient('emr', 'modify', $patient) ? __("Modify") : "" ) =>
        $this->page_name."?module=$module&patient=$patient&id=$id&action=modform",
	__("Print") =>
        "module_loader.php?module=".get_class($this)."&patient=$patient&".
        "action=print&id=".$r['id']
       ))."
       <p/>

       <CENTER>
        <B>Relevant Date : </B>
         $tpcnotesdt_formatted
       </CENTER>
       <P>
     ";
     // Check for EOC stuff
     if (count($tpcnoteseoc)>0 and is_array($tpcnoteseoc) and check_module("episodeOfCare")) {
      $display_buffer .= "
       <CENTER>
        <B>".__("Related Episode(s)")."</B>
        <BR>
      ";
      for ($i=0;$i<count($tpcnoteseoc);$i++) {
        if ($tpcnoteseoc[$i] != -1) {
          $e_r     = freemed::get_link_rec ($tpcnoteseoc[$i]+0, "eoc"); 
          $e_id    = $e_r["id"];
          $e_desc  = $e_r["eocdescrip"];
          $e_first = $e_r["eocstartdate"];
          $e_last  = $e_r["eocdtlastsimilar"];
          $display_buffer .= "
           <A HREF=\"module_loader.php?module=episodeOfCare&patient=$patient&".
  	   "action=manage&id=$e_id\"
           >$e_desc / $e_first to $e_last</A><BR>
          ";
	} else {
	  $episodes = $sql->query (
	    "SELECT * FROM eoc WHERE eocpatient='".addslashes($patient)."'" );
	  while ($epi = $sql->fetch_array ($episodes)) {
            $e_id    = $epi["id"];
            $e_desc  = $epi["eocdescrip"];
            $e_first = $epi["eocstartdate"];
            $e_last  = $epi["eocdtlastsimilar"];
            $display_buffer .= "
           <A HREF=\"module_loader.php?module=episodeOfCare&patient=$patient&".
  	     "action=manage&id=$e_id\"
             >$e_desc / $e_first to $e_last</A><BR>
            ";
	  } // end fetching
	} // check if not "ALL"
      } // end looping for all EOCs
      $display_buffer .= "
       </CENTER>
      ";
     } // end checking for EOC stuff
     $display_buffer .= "<CENTER>\n";

     // Crappy hack to get around not detecting <br />'s
     $tpcnotes_S = str_replace (' />', '/>', $tpcnotes_S);
     $tpcnotes_O = str_replace (' />', '/>', $tpcnotes_O);
     $tpcnotes_A = str_replace (' />', '/>', $tpcnotes_A);
     $tpcnotes_P = str_replace (' />', '/>', $tpcnotes_P);

      if (strlen($tpcnotes_S) > 7) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1 WIDTH=\"100%\"><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=\"CENTER\"><B>".__("Problem")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
		".( eregi("<[A-Z/]*>", $tpcnotes_S) ?
		prepare($tpcnotes_S) :
		stripslashes(str_replace("\n", "<br/>", htmlentities($tpcnotes_S))) )."
       </TD></TR></TABLE>
       ";
      if (strlen($tpcnotes_O) > 7) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1 WIDTH=\"100%\"><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><B>".__("Discussion")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
		".( eregi("<[A-Z/]*>", $tpcnotes_O) ?
		prepare($tpcnotes_O) :
		stripslashes(str_replace("\n", "<br/>", htmlentities($tpcnotes_O))) )."
       </TD></TR></TABLE>
       ";
      if (strlen($tpcnotes_A) > 7) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1 WIDTH=\"100%\"><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><B>".__("Assessment")."</B></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
		".( eregi("<[A-Z/]*>", $tpcnotes_A) ?
		prepare($tpcnotes_A) :
		stripslashes(str_replace("\n", "<br/>", htmlentities($tpcnotes_A))) )."
       </TD></TR></TABLE>
       ";
      if (strlen($tpcnotes_P) > 7) $display_buffer .= "
       <TABLE BGCOLOR=#ffffff BORDER=1 WIDTH=\"100%\"><TR BGCOLOR=$darker_bgcolor>
       <TD ALIGN=CENTER><CENTER><FONT COLOR=#ffffff>
        <B>".__("Plan")."</B></FONT></CENTER></TD></TR>
       <TR BGCOLOR=#ffffff><TD>
		".( eregi("<[A-Z/]*>", $tpcnotes_P) ?
		prepare($tpcnotes_P) :
		stripslashes(str_replace("\n", "<br/>", htmlentities($tpcnotes_P))) )."
       </TD></TR></TABLE>
       ";
        // back to your regularly sceduled program...
      $display_buffer .= "
       <p/>
       ".template::link_bar(array(
        __("Treatment Plan Clinical Notes") =>
       $this->page_name."?module=$module&patient=$tpcnotespat",
        __("Manage Patient") =>
       "manage.php?id=$tpcnotespat",
	__("Select Patient") =>
        "patient.php",
	( freemed::acl_patient('emr', 'modify', $patient) ? __("Modify") : "" ) =>
        $this->page_name."?module=$module&patient=$patient&id=$id&action=modform"
       ))."
       <p/>
     ";
	} // end of case display

	function view ($condition = false) {
		global $display_buffer;
		global $patient, $action;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }

		// Check for "view" action (actually display)
		if ($action=="view") {
			$this->display();
			return NULL;
		}

		$query = "SELECT * FROM ".$this->table_name." ".
			"WHERE (tpcnotespat='".addslashes($patient)."') ".
			freemed::itemlist_conditions(false)." ".
			( $condition ? 'AND '.$condition : '' )." ".
			"ORDER BY tpcnotesdt";
		$result = $sql->query ($query);

		$display_buffer .= freemed_display_itemlist(
			$result,
			$this->page_name,
			array (
				__("Date")        => "tpcnotesdt",
				__("Description") => "tpcnotesdescrip"
			), // array
			array (
				"",
				__("NO DESCRIPTION")
			),
			NULL, NULL, NULL,
			ITEMLIST_MOD | ITEMLIST_VIEW | ITEMLIST_DEL | ITEMLIST_LOCK
		);
		$display_buffer .= "\n<p/>\n";
	} // end method view

	// Method: noteForDate
	//
	//	Determines if a progress note was entered for a particular
	//	appointment.
	//
	// Parameters:
	//
	//	$patient - ID for patient record
	//
	//	$date - Date to be queried
	//
	// Returns:
	//
	//	Boolean, whether or not a note exists.
	//
	function noteForDate ( $patient, $date ) {
		$q = "SELECT COUNT(id) AS my_count ".
			"FROM ".$this->table_name." WHERE ".
			"tpcnotespat = '".addslashes($patient)."' AND ".
			"tpcnotesdt = '".addslashes($date)."'";
		$res = $GLOBALS['sql']->query($q);
		$r = $GLOBALS['sql']->fetch_array($res);
		if ($r['my_count'] > 0) {
			return true;
		} else {
			return false;
		}
	} // end method noteForDate

} // end class TreatmentPlanClinicalNotes

register_module ("TreatmentPlanClinicalNotes");

?>
