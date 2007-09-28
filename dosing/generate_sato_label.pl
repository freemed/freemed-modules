#!/usr/bin/perl
# $Id$
# SATO Label printer generation script
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

#	Handle configuration
use Config::Tiny;
$config = Config::Tiny->read( 'label.ini' );

#	Get parameters
use Getopt::Std;
my %o;
getopt( 'pdPeDl', \%o );

if ( ! $o{p} ) {
	print "$0 (options .... ) \n";
	print "\t-p (patient name)\n";
	print "\t-P (doctor name)\n";
	print "\t-e (expires)\n";
	print "\t-d (dosage)\n";
	print "\t-D (dosage date)\n";
	print "\t-l (lot number)\n";
	exit 0;
}

#	Buffer
my $label;

#	Inches to dots = INCHES * ( 25.4 * 8 ) = DOTS PER INCHES = 203 DOTS
my $esc = "\e";

#	Length of non-warning label portion
my $l = 632;

#	Begin command
$label .= "${esc}A";
#	Set 4" x 1"
$label .= "${esc}A1" . "0101" . "0812";
#	Print CODAC header with phone number
#	5W x 9H proportional font = XU
$label .= "${esc}H0030${esc}V0010${esc}XS" .  $config->{installation}->{facility};
$label .= "${esc}H0400${esc}V0010${esc}XS" .  "Tel: " . $config->{installation}->{phone};
$label .= "${esc}H0030${esc}V0025${esc}XU" .  $config->{installation}->{address};
$label .= "${esc}H0000${esc}V0035${esc}FW02H0".${l};	# 2 width horizontal line

#	Patient and dosage
$label .= "${esc}H0030${esc}V0040${esc}XM" . $o{p};
$label .= "${esc}H0400${esc}V0070${esc}XM" . $o{d} . " mg";

#	Dosage date and lot information
$label .= "${esc}H0050${esc}V0070${esc}XS" . "Take on : " . $o{D};
$label .= "${esc}H0050${esc}V0088${esc}XS" . "Lot     : " . $o{l};

#	Doctor and expiration
$label .= "${esc}H0000${esc}V0115${esc}FW01H0".${l};	# 1 width horizontal line
$label .= "${esc}H0030${esc}V0120${esc}XS" . "Doctor: " . $o{P};
$label .= "${esc}H0400${esc}V0120${esc}XS" . "Expires: " . $o{e};

#	Warning at the bottom
$label .= "${esc}H0000${esc}V0140${esc}FW02H0".${l};	# 2 width horizontal line
$label .= "${esc}H0030${esc}V0145${esc}XS"."Controlled substance: DANGEROUS unless used as directed";

#	Print the warning at the end of the label ( ${l} - 790 horizontal )

$label .= "${esc}H0782${esc}V0000${esc}FW01V0170";	# 1 width vertical line
$label .= "${esc}H0780${esc}V0005${esc}".'%3'."${esc}XU" .  "  CAUTION: Federal Law";
$label .= "${esc}H0770${esc}V0005${esc}".'%3'."${esc}XU" .  "  PROHIBITS the transfer";
$label .= "${esc}H0760${esc}V0005${esc}".'%3'."${esc}XU" .  "  of this drug to any";
$label .= "${esc}H0750${esc}V0005${esc}".'%3'."${esc}XU" .  "  person other than the";
$label .= "${esc}H0740${esc}V0005${esc}".'%3'."${esc}XU" .  "  patient for whom it";
$label .= "${esc}H0730${esc}V0005${esc}".'%3'."${esc}XU" .  "  was prescribed.";
$label .= "${esc}".'%0';	# reset rotation
$label .= "${esc}H0720${esc}V0000${esc}FW01V0170";	# 1 width vertical line
$label .= "${esc}H0718${esc}V0005${esc}L0201${esc}".'%3'."${esc}XU" . " TAKE ENTIRE";
$label .= "${esc}H0708${esc}V0005${esc}L0201${esc}".'%3'."${esc}XU" . "   CONTENTS";
$label .= "${esc}L0101";	# reset expansion of characters
$label .= "${esc}".'%0';	# reset rotation
$label .= "${esc}H0698${esc}V0000${esc}FW01V0170";	# 1 width vertical line
$label .= "${esc}H0696${esc}V0005${esc}L0201${esc}".'%3'."${esc}XU" . "  METHADONE:";
$label .= "${esc}H0686${esc}V0005${esc}L0201${esc}".'%3'."${esc}XU" . " KEEP OUT OF";
$label .= "${esc}H0676${esc}V0005${esc}L0201${esc}".'%3'."${esc}XU" . "   REACH OF";
$label .= "${esc}H0666${esc}V0005${esc}L0201${esc}".'%3'."${esc}XU" . "   CHILDREN";
$label .= "${esc}L0101";	# reset expansion of characters
$label .= "${esc}".'%0';	# reset rotation
$label .= "${esc}H0656${esc}V0000${esc}FW01V0170";	# 1 width vertical line
$label .= "${esc}H0654${esc}V0005${esc}L0201${esc}".'%3'."${esc}XU" . "ORAL USE ONLY";
$label .= "${esc}L0101";	# reset expansion of characters
$label .= "${esc}".'%0';	# reset rotation
$label .= "${esc}H0644${esc}V0000${esc}FW01V0170";	# 1 width vertical line

#	End command
$label .= "${esc}Z\003";

open LP, ">".$config->{printer}->{port} or die ("Could not open port");
print LP $label;
close LP;

