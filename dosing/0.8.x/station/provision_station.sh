#!/bin/bash
# $Id$
# Provision dosing station script
#
# Authors:
#      Jeff Buchbinder <jeff@freemedsoftware.org>
#
# FreeMED Electronic Medical Record and Practice Management System
# Copyright (C) 1999-2009 FreeMED Software Foundation
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

echo "Provision Dosing Station Script"
echo "(c) FreeMED Software Foundation"
echo " "

echo -n " - Please choose the active database [freemed] : "
read ACTIVEDB

if [ "${ACTIVEDB}" == "" ]; then
	ACTIVEDB="freemed"
fi

echo " "
echo -n " - Dosing station name : "
read DSNAME

echo " "
echo -n " - Dosing station location : "
read DSLOCATION

echo " "
echo "LIST OF FACILITIES"
mysql -uroot $ACTIVEDB --skip-column-names -Be "select id, '-', concat(psrname, ', ', psrcity, ', ', psrstate) from facility;"

echo -n " - Facility [1] : "
read DSFACILITY

if [ "${DSFACILITY}" == "" ]; then
	DSFACILITY="1"
fi

echo " "
echo -n " - IP address of new station : "
read DSURL

echo " "
TEMPKEY=/tmp/provision-key-$$
echo "Generating ssh key ( $TEMPKEY ) ... "
ssh-keygen -t dsa -N '' -f $TEMPKEY

echo " "
echo -n "Inserting station into database ... "
cat<<EOF > $TEMPKEY.sql
INSERT INTO dosingstation (
	  dsname
	, dslocation
	, dsfacility
	, dsurl
	, dsenabled
	, dsopen
	, sshkey
) VALUES (
	  '$DSNAME'
	, '$DSLOCATION'
	, '$DSFACILITY'
	, '$DSURL'
	, 1
	, 'closed'
	, '`cat $TEMPKEY`'
);"
EOF

mysql -uroot $ACTIVEDB -Be "SOURCE $TEMPKEY.sql"
rm -f $TEMPKEY.sql
echo "done."

echo " "
echo "Propagating key to server, please type in password for $DSURL when prompted."
ssh-copy-id -i $TEMPKEY.pub freemed@${DSURL}

echo " "
echo "Pushing files out... "
rsync -e "ssh -i $TEMPKEY" -rvapP $(dirname "$0")/dist/* freemed@${DSURL}:

echo " "
echo -n "Cleaning up old files... "
rm -f $TEMPKEY $TEMPKEY.pub
echo "done"

echo " "
echo "Dosing station provisioning completed."

