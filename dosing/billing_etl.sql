# $Id$
#
# Authors:
#      Jeff Buchbinder <jeff@freemedsoftware.org>
#
# FreeMED Electronic Medical Record and Practice Management System
# Copyright (C) 1999-2008 FreeMED Software Foundation
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

CREATE TABLE IF NOT EXISTS `dosingBillingEtlProcessed` (
	  bYear			INT UNSIGNED NOT NULL
	, bWeek			INT UNSIGNED NOT NULL
	, bDosePlanId		INT UNSIGNED NOT NULL
	, bPatientId		INT UNSIGNED NOT NULL
	, bProcessed		TIMESTAMP NOT NULL DEFAULT NOW()
	, id			SERIAL
);

DROP PROCEDURE IF EXISTS dosingBillingEtl;

DELIMITER //

CREATE PROCEDURE dosingBillingEtl( IN processDt DATE )
BEGIN
	DECLARE thisWeekOfYear INT UNSIGNED;

	DECLARE cptCode INT UNSIGNED;
	DECLARE icdCode INT UNSIGNED;

	#----- Resolve CPT and ICD codes
	SELECT id INTO icdCode FROM icd9 WHERE icd9code = '304.00';
	SELECT id INTO cptCode FROM cpt WHERE cptcode = 'H0020';
	
	SELECT WEEKOFYEAR( processDt ) INTO thisWeekOfYear;

	DROP TEMPORARY TABLE IF EXISTS tmpToBill;
	CREATE TEMPORARY TABLE tmpToBill (
		  patient_id		BIGINT UNSIGNED
		, starting_date		DATE
		, ending_date		DATE
		, pxcode		INT UNSIGNED
		, dxcode		INT UNSIGNED
		, doseplan_id		INT UNSIGNED
		, etl_id		INT UNSIGNED
	) ENGINE=MEMORY;

	INSERT INTO tmpToBill (
			  patient_id
			, starting_date
			, ending_date
			, pxcode
			, dxcode
			, doseplan_id
			, etl_id
		)
		SELECT
			  r.dosepatient
			, MIN( r.doseassigneddate )
			, MAX( r.doseassigneddate )
			, 52 #, cptCode
			, 167 #, icdCode
			, p.id
			, b.id
		FROM doserecord r
			LEFT OUTER JOIN patient pt ON pt.id = r.dosepatient
			LEFT OUTER JOIN doseplan p ON p.id = r.doseplanid
			LEFT OUTER JOIN treatmentplan t ON t.patient = r.dosepatient
			LEFT OUTER JOIN dosingBillingEtlProcessed b ON (
					    p.id = b.bDosePlanId
					AND b.bYear = YEAR( r.doseassigneddate )
					AND b.bWeek = WEEKOFYEAR( r.doseassigneddate )
				)
		WHERE
			    WEEKOFYEAR( r.doseassigneddate ) = thisWeekOfYear
			AND ABS( DATEDIFF( processDt, r.doseassigneddate ) ) < 8
			AND r.dosegiven = 1
		GROUP BY
			  r.doseplanid
			, r.dosepatient
			, b.id
		;
	SELECT COUNT(*) AS 'Total Entries' FROM tmpToBill;
	#DELETE FROM tmpToBill WHERE NOT ISNULL( etl_id );
	#SELECT COUNT(*) AS 'Total Entries After' FROM tmpToBill;

	#---- Fix coverages ...
	DROP TEMPORARY TABLE IF EXISTS tmpCoverages;
	CREATE TEMPORARY TABLE tmpCoverages (
		  patient_id	INT UNSIGNED NOT NULL
		, coverage_id	INT UNSIGNED
		, insco_id	INT UNSIGNED

		, PRIMARY KEY	( patient_id )
	) ENGINE=MEMORY;
	INSERT INTO tmpCoverages
		SELECT p.id, c.id, i.id
			FROM patient p
			LEFT OUTER JOIN coverage c ON ( c.covpatient = p.id  AND c.covstatus = 0 )
			LEFT OUTER JOIN insco i ON c.covinsco = i.id
			WHERE p.id > 0
			GROUP BY p.id HAVING NOT ISNULL( c.id )
			ORDER BY c.id DESC;

	# Debug, show
	SELECT * FROM tmpToBill;

	#---- Import all entries so they are not processed again
	INSERT INTO dosingBillingEtlProcessed (
			  bYear
			, bWeek
			, bDosePlanId
			, bPatientId
		) SELECT
			  YEAR( starting_date )
			, WEEKOFYEAR( starting_date )
			, doseplan_id
			, patient_id
		FROM tmpToBill;

	#----- Import into the procrec table to create actual procedures
	INSERT INTO procrec
		(
			  procpatient
			, procdt
			, procdtend
			, proccpt
			, procdiag1
			, procbillable
			, procbilled
			, proccurcovid
			, proccurcovtp
			, proccov1
			, procunits
			, procbalorig
			, procbalcurrent
			, proccharges
			, procphysician
			, procpos
		)
		SELECT
			  t.patient_id
			, t.starting_date
			, t.ending_date
			, t.pxcode
			, t.dxcode
			, 0
			, 0
			, cov.coverage_id
			, c.covtype
			, cov.coverage_id
			, 1.0
			, 80.00
			, 80.00
			, 80.00
			, 1
			, p.ptdefaultfacility
		FROM tmpToBill t
		LEFT OUTER JOIN tmpCoverages cov ON cov.patient_id = t.patient_id
		LEFT OUTER JOIN patient p ON t.patient_id = p.id
		LEFT OUTER JOIN coverage c ON cov.coverage_id = c.id;

	#----- Cleanup
	DROP TEMPORARY TABLE tmpToBill;
	DROP TEMPORARY TABLE tmpCoverage;
END;
//

DELIMITER ;

