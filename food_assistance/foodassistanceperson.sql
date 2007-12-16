# $Id$
#
# Authors:
#      Jeff Buchbinder <jeff@freemedsoftware.org>
#
# FreeMED Electronic Medical Record and Practice Management System
# Copyright (C) 1999-2007 FreeMED Software Foundation
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

CREATE TABLE IF NOT EXISTS `foodassistanceperson` (
	fa_dateofentry		TIMESTAMP(14) NOT NULL DEFAULT NOW(),
	fa_patient		BIGINT UNSIGNED NOT NULL DEFAULT 0,
	fa_lastname		VARCHAR(50) NOT NULL,
	fa_firstname		VARCHAR(50) NOT NULL,
	fa_middlename		VARCHAR(50),
	fa_dob			DATE,
	fa_address		VARCHAR(150),
	fa_city			VARCHAR(50) NOT NULL,
	fa_state		CHAR(3) NOT NULL,
	fa_age			INT UNSIGNED NOT NULL,
	fa_household_size	INT UNSIGNED NOT NULL DEFAULT 1,
	fa_household_elderly	INT UNSIGNED NOT NULL DEFAULT 0,
	fa_household_disabled	INT UNSIGNED NOT NULL DEFAULT 0,
	fa_household_children	INT UNSIGNED NOT NULL DEFAULT 0,
	fa_programs		TEXT,
	id			SERIAL
);

DROP PROCEDURE IF EXISTS foodassistanceperson_Upgrade;
DELIMITER //
CREATE PROCEDURE foodassistanceperson_Upgrade ( )
BEGIN
	DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;

	#----- Remove triggers
	DROP TRIGGER foodassistanceperson_Insert;

	#----- Upgrades
END
//
DELIMITER ;
CALL foodassistanceperson_Upgrade( );

#----- Triggers

DELIMITER //

CREATE TRIGGER foodassistanceperson_Insert
	AFTER INSERT ON foodassistanceperson
	FOR EACH ROW BEGIN
		IF ISNULL( NEW.fa_age ) THEN
			UPDATE foodassistanceperson SET fa_age = FLOOR( DATEDIFF( NOW(), NEW.fa_dob ) / 365 );
		END IF;
	END;
//

DELIMITER ;

