<?php
	// $Id$
	// $Author$

LoadObjectDependency('_FreeMED.MaintenanceModule');

class DrugSampleInventory extends MaintenanceModule {

	var $MODULE_NAME = "Drug Sample Inventory";
	var $MODULE_VERSION = "0.1.4";
	var $MODULE_AUTHOR = "jeff b (jeff@ourexchange.net)";
	var $MODULE_FILE = __FILE__;
	var $MODULE_HIDDEN = false;

	var $PACKAGE_MINIMUM_VERSION = "0.7.0";

	var $record_name = "Drug Sample Inventory";
	var $table_name = "drugsampleinv";
	var $order_fields = "logdate DESC";
	var $widget_hash = "##logdate## - ##drugformal## ##samplecountremain##/##samplecount## (##lot##)";

	function DrugSampleInventory ( ) {
		$this->table_definition = array (
			"drugformal" => SQL__VARCHAR(75),
			"druggeneric" => SQL__VARCHAR(75),
			"drugclass" => SQL__VARCHAR(150),
			"strength" => SQL__VARCHAR(75),
			"deliveryform" => SQL__VARCHAR(75),
			"packagecount" => SQL__INT_UNSIGNED(0),
			"location" => SQL__VARCHAR(150),
			"drugco" => SQL__VARCHAR(75),
			"drugrep" => SQL__VARCHAR(75),
			"invoice" => SQL__VARCHAR(20),
			"samplecount" => SQL__INT_UNSIGNED(0),
			"samplecountremain" => SQL__INT_UNSIGNED(0),
			"lot" => SQL__VARCHAR(16),
			"expiration" => SQL__DATE,
			"received" => SQL__DATE,
			"assignedto" => SQL__VARCHAR(75),
			"loguser" => SQL__INT_UNSIGNED(0),
			"logdate" => SQL__DATE,
			"disposeby" => SQL__VARCHAR(75),
			"disposedate" => SQL__DATE,
			"disposemethod" => SQL__VARCHAR(75),
			"disposereason" => SQL__VARCHAR(75),
			"witness" => SQL__VARCHAR(75),
			"id" => SQL__SERIAL
		);
	
		$this->variables = array (
			"drugformal" => html_form::combo_assemble("drugformal"),
			"druggeneric" => html_form::combo_assemble("druggeneric"),
			"drugclass" => html_form::combo_assemble("drugclass"),
			"strength",
			"deliveryform" => html_form::combo_assemble("deliveryform"),
			"packagecount",
			"location" => html_form::combo_assemble("location"),
			"drugco" => html_form::combo_assemble("drugco"),
			"drugrep" => html_form::combo_assemble("drugrep"),
			"invoice",
			"samplecount",
			"samplecountremain",
			"lot",
			"expiration" => fm_date_assemble("expiration"),
			"received" => fm_date_assemble("received"),
			"assignedto" => html_form::combo_assemble("assignedto"),
			"loguser",
			"logdate" => fm_date_assemble("logdate"),
			"disposeby",
			"disposedate" => fm_date_assemble("disposedate"),
			"disposemethod",
			"disposereason",
			"witness"
		);

		// XML-RPC field mappings
		$this->rpc_field_map = array (
			'brand_name' => "drugformal",
			'name' => "druggeneric",
			'strength' => "strength",
			'class' => "drugclass",
			'drug_company' => 'drugco',
			'manufacturer' => 'drugco',
			'delivery_form' => 'deliveryform',
			'remaining' => 'samplecountremain',
			'location' => 'location'
		);

		$this->distinct_fields = array (
			"drugformal",
			"drugclass",
			"location",
			"drugco",
			"drugrep",
			'deliveryform',
			"assignedto"
		);

		$this->order_field = "drugformal, logdate";

		// Call parent constructor
		$this->MaintenanceModule();
	} // end constructor

	function add ( ) {
		global $loguser, $display_buffer;

		// Get user number to log the user
		$u = CreateObject('_FreeMED.User');
		$loguser = $u->user_number;

		$this->_add();

		$display_buffer .= template::link_button(__("Add Another"), $this->page_name."?module=".get_class($this)."&action=addform");
	} // end method add

	function _preadd ( $_param = NULL ) {
		global $samplecountremain;
		// Start sample count remaining at what there is
		$samplecountremain = $_REQUEST['samplecount'];
		$_REQUEST['samplecountremain'] = $samplecountremain;
	}

	//function modform () { die ("Operation not allowed"); }
	//function mod () { die ("Operation not allowed"); }

	function generate_form ( ) {
		return array (
			__("Drug (formal name)") => html_form::combo_widget("drugformal",
				$GLOBALS['sql']->distinct_values($this->table_name, "drugformal")
			),
			__("Drug (generic name)") => html_form::combo_widget("druggeneric",
				$GLOBALS['sql']->distinct_values($this->table_name, "druggeneric")
			),
			__("Drug Class") => html_form::combo_widget("drugclass",
				$GLOBALS['sql']->distinct_values($this->table_name, "drugclass")
			),
			__("Strength") => html_form::text_widget("strength"),
			__("Delivery Form") => html_form::combo_widget("deliveryform",
				$GLOBALS['sql']->distinct_values($this->table_name, "deliveryform")
			),
			__("Package Count") => html_form::text_widget("packagecount"),
			__("Drug Company") => html_form::combo_widget("drugco",
				$GLOBALS['sql']->distinct_values($this->table_name, "drugco")
			),
			__("Drug Co Representative") => html_form::combo_widget("drugrep",
				$GLOBALS['sql']->distinct_values($this->table_name, "drugrep")
			),
			__("Invoice") => html_form::text_widget("invoice"),
			__("Samples Count") => html_form::text_widget("samplecount"),
			__("Storage Location") => html_form::combo_widget("location",
				$GLOBALS['sql']->distinct_values($this->table_name, "location")
			),
			__("Lot Number") => html_form::text_widget("lot"),
			__("Expiration Date") => fm_date_entry("expiration"),
			__("Date Received") => fm_date_entry("received"),
			__("Assigned To") => html_form::combo_widget("assignedto",
				$GLOBALS['sql']->distinct_values($this->table_name, "assignedto")
			),
			__("Date Logged") => fm_date_entry("logdate"),
			__("Disposed By") => html_form::combo_widget("disposeby",
				$GLOBALS['sql']->distinct_values($this->table_name, "disposeby")
			),
			__("Date Disposed") => fm_date_entry("disposedate"),
			__("Disposal Method") => html_form::combo_widget("disposemethod",
				$GLOBALS['sql']->distinct_values($this->table_name, "disposemethod")
			),
			__("Reason for Disposal") => html_form::combo_widget("disposereason",
				$GLOBALS['sql']->distinct_values($this->table_name, "disposereason")
			),
			__("Witness") => html_form::text_widget("witness")
		);
	} // end method generate_form

	function view () {
		global $display_buffer;
		global $sql;

		$result = $sql->query ($query);
		$display_buffer .= freemed_display_itemlist (
			$sql->query (
				"SELECT lot,logdate,drugformal,samplecountremain,id ".
				"FROM ".$this->table_name." ".
				freemed::itemlist_conditions()." ".
				"ORDER BY ".$this->order_fields
			),
			$this->page_name,
			array (
				__("Lot")	=>	"lot",
				__("Drug")	=>	"drugformal",
				__("Remaining") =>	"samplecountremain"
			),
			array ("", "", 0)
		);
	} // end method view

	// Wrap widget with this to make sure we only get certain things.
	function widget ( $varname ) {
		$conditions = "samplecountremain > 0";
		return parent::widget( $varname, $conditions );
	}

	function deduct ( $id, $amount ) {
		syslog(LOG_INFO, "deduct $amount from record $id");
		syslog(LOG_INFO, "UPDATE ".$this->table_name." SET ".
			"amount = amount - ".($amount + 0)." ".
			"WHERE id = '".addslashes($id)."'");
		$result = $GLOBALS['sql']->query(
			"UPDATE ".$this->table_name." SET ".
			"samplecountremain = samplecountremain - ".($amount + 0)." ".
			"WHERE id = '".addslashes($id)."'"
		);
		return $result;
	} // end method deduct

	function _update ( ) {
		$version = freemed::module_version($this->MODULE_NAME);

		// Version 0.1.2
		//
		//	Add drug classes (drugclass)
		//	Add location of samples (location)
		//
		if (!version_check($version, '0.1.2')) {
			$GLOBALS['sql']->query('ALTER TABLE '.$this->table_name.
				' ADD COLUMN drugclass VARCHAR(150) AFTER druggeneric');
			$GLOBALS['sql']->query('UPDATE '.$this->table_name.' '.
				'SET drugclass=\'\'');
			$GLOBALS['sql']->query('ALTER TABLE '.$this->table_name.
				' ADD COLUMN location VARCHAR(150) AFTER packagecount');
			$GLOBALS['sql']->query('UPDATE '.$this->table_name.' '.
				'SET location=\'\'');
		}

		// Version 0.1.3
		//
		//	Consolodate strength+strengthtype
		//
		if (!version_check($version, '0.1.3')) {
			$GLOBALS['sql']->query('ALTER TABLE '.$this->table_name.
				' CHANGE COLUMN strength strength VARCHAR(75)');
			$GLOBALS['sql']->query('UPDATE '.$this->table_name.' '.
				'SET strength=CONCAT(strength,\' \', strengthtype)');
			$GLOBALS['sql']->query('ALTER TABLE '.$this->table_name.
				' DROP COLUMN strengthtype');
		}

		// Version 0.1.4
		//
		//	Add delivery form (deliveryform)
		//
		if (!version_check($version, '0.1.4')) {
			$GLOBALS['sql']->query('ALTER TABLE '.$this->table_name.
				' ADD COLUMN deliveryform VARCHAR(75) AFTER strength');
			$GLOBALS['sql']->query('UPDATE '.$this->table_name.' '.
				'SET deliveryform=\'\'');
		}
	} // end method _update

} // end class DrugSampleInventory

register_module("DrugSampleInventory");

?>
