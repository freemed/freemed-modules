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

class TreatmentPlanProblemModule extends EMRModule {
	var $MODULE_NAME = "Treatment Plan Problem";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;
	var $MODULE_HIDDEN = true;

	var $record_name = 'Treatment Plan Problem';
	var $table_name = 'treatmentplanproblem';
	var $patient_field = 'oipatient';
	var $order_by = 'id';

	function TreatmentPlanProblemModule ( ) {
		$this->table_definition = array (
			'problem' => SQL__TEXT,
			'goalslongterm' => SQL__TEXT,
			'dateeffectivelong' => SQL__DATE,
			'datetargetlong' => SQL__DATE,
			'goalsshortterm' => SQL__TEXT,
			'dateeffectiveshort' => SQL__DATE,
			'datetargetshort' => SQL__DATE,
				// Linking information
			'tpuser' => SQL__INT_UNSIGNED(0),
			'treatmentplan' => SQL__INT_UNSIGNED(0),
			'tpdsm' => SQL__INT_UNSIGNED(0),
			'tppatient' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);
		$this->EMRModule();
	} // end constructor TreatmentPlanProblemModule

	function get_all ( $treatmentplan, $dsm ) {
		$q = "SELECT * FROM ".$this->table_name." WHERE treatmentplan='".addslashes($treatmentplan)."' AND tpdsm='".addslashes($dsm)."'";
		$result = $GLOBALS['sql']->query( $q );
		if (!$GLOBALS['sql']->results( $result )) {
			return array ( );
		}
		while ($r = $GLOBALS['sql']->fetch_array( $result )) {
			$set[$r['id']] = $r;
		}
		return $set;
	} // end method get_all

} // end class TreatmentPlanProblemModule

register_module("TreatmentPlanProblemModule");

?>
