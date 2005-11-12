<?php
	// $Id$
	// $Author$

LoadObjectDependency('_FreeMED.EMRModule');

class DrugSamples extends EMRModule {

	var $MODULE_NAME = "Drug Samples";
	var $MODULE_VERSION = "0.1.1";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";

	var $MODULE_FILE = __FILE__;
	var $PACKAGE_MINIMUM_VERSION = '0.7.0';

	var $record_name = "Drug Samples";
	var $table_name = "drugsamples";
	var $patient_field = "patientid";
	var $order_field = "drugsampleid";

	function DrugSamples ( ) {
		$this->summary_vars = array (
			__("Lot") => 'drugsampleid:drugsampleinv:lot',
			__("Drug") => 'drugsampleid:drugsampleinv:drugformal',
			__("Amount") => 'amount'
		);

		$this->table_definition = array (
			"drugsampleid" => SQL__INT_UNSIGNED(0),
			"patientid" => SQL__INT_UNSIGNED(0),
			"prescriber" => SQL__INT_UNSIGNED(0),
			"deliveryform" => SQL__VARCHAR(50),
			"amount" => SQL__INT_UNSIGNED(0),
			"instructions" => SQL__TEXT,
			"id" => SQL__SERIAL
		);

		$this->variables = array (
			'drugsampleid',
			'patientid' => $_REQUEST['patient'],
			'prescriber',
			'deliveryform' =>  html_form::combo_assemble('deliveryform'),
			'amount',
			'instructions'
		);

		$this->EMRModule(); // won't work without this line
	} // end constructor

	function add ( $_param = NULL ) {
		$this->_add($_param);
	} // end method add

	function _preadd ( $_param = NULL ) {
		// Deduct appropriate amount from sample inventory
		module_function (
			'DrugSampleInventory',
			'deduct',
			array(
				$_REQUEST['drugsampleid'], $_REQUEST['amount']
			)
		);
	} // end method _preadd

	function form_table ( ) {
		return array (
			__("Drug Sample Lot") => module_function(
					'DrugSampleInventory',
					'widget',
					array ('drugsampleid')
				),
			__("Prescriber") => module_function(
					'ProviderModule',
					'widget',
					array ('prescriber')
				),
			__("Delivery Form") => html_form::combo_widget(
					'deliveryform',
					$GLOBALS['sql']->distinct_values(
						$this->table_name,
						'deliveryform'
					)
				),
			__("Amount (numeric)") => html_form::text_widget(
					'amount'
				),
			__("Instructions") => html_form::text_area('instructions')
		);
	} // end method form

	function view () {
		global $display_buffer;
                global $patient, $action;
                foreach ($GLOBALS AS $k => $v) { global ${$k}; }

                // Check for "view" action (actually display)
                if ($action=="view") {
                        $this->display();
                        return NULL;
                }

                $display_buffer .= freemed_display_itemlist(
                        $sql->query(
                                "SELECT * FROM ".$this->table_name." ".
                                "WHERE (".$this->patient_field."='".addslashes($patient)."') ".
                                freemed::itemlist_conditions(false)." ".
                                ( $condition ? 'AND '.$condition : '' )." ".
                                "ORDER BY ".$this->order_field
                        ),
                        $this->page_name,
                        array (
                                __("Lot") => 'drugsampleid',
				__("Drug") => 'drugsampleid',
				__("Amount") => 'amount'
			),
			array (
				"",
				"",
				""
			),
			array (
				"drugsampleinv" => 'lot',
				"drugsampleinv " => 'drugformal',
				""
			),
			NULL,
			ITEMLIST_MOD | ITEMLIST_DEL
		);
	} // end method view

} // end class DrugSamples

register_module("DrugSamples");

?>
