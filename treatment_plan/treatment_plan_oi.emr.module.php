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

class TreatmentPlanOIModule extends EMRModule {
	var $MODULE_NAME = "Treatment Plan Objectives and Interventions";
	var $MODULE_VERSION = "0.1";

	var $MODULE_FILE = __FILE__;
	var $MODULE_HIDDEN = true;

	var $table_name = 'treatmentplanoi';
	var $patient_field = 'oipatient';
	var $order_by = 'id';

	function TreatmentPlanOIModule ( ) {
		$this->table_definition = array (
			'objective' => SQL__TEXT,
			'intervention' => SQL__TEXT,
			'dateeffective' => SQL__DATE,
			'datetarget' => SQL__DATE,
				// Linking information
			'oiuser' => SQL__INT_UNSIGNED(0),
			'oitreatmentplan' => SQL__INT_UNSIGNED(0),
			'oidsm' => SQL__INT_UNSIGNED(0),
			'oiproblem' => SQL__INT_UNSIGNED(0),
			'oipatient' => SQL__INT_UNSIGNED(0),
			'id' => SQL__SERIAL
		);
		$this->EMRModule();
	} // end constructor TreatmentPlanOIModule

} // end class TreatmentPlanOIModule

register_module("TreatmentPlanOIModule");

?>
