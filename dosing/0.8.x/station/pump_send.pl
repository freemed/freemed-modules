#!/usr/bin/perl -w

use strict;
use Device::SerialPort;
open (LOG, "> /home/freemed/pump.log");

my $port = new Device::SerialPort("/dev/ttyS0") || die "ERROR: $!\n";
print "Port open\n";
print LOG "Port open\n";


my $bool=$port->can_ioctl();
print "ioctl: ",($bool ? "Yes" : "No"),"\n";
if (!$bool) {
        print LOG "ERROR: ioctl failed\n";
        die "ERROR: ioctl failed.\n";
}

my $handshake = $port->handshake("none");
my $baudrate  = $port->baudrate("9600");
my $databits  = $port->databits(8);
my $parity    = $port->parity("none");
my $stopbits  = $port->stopbits(1);

# Print serial comm params

print LOG "Handshake = $handshake\n";
print LOG "Baud Rate = $baudrate\n";
print LOG "Data Bits = $databits\n";
print LOG "Parity    = $parity\n";
print LOG "Stop Bits = $stopbits\n";

my ($count, $str, $cnt, $got) = '' x 4;
    my $command = uc $ARGV[0];
    return() if ($command =~ /x/i);
    ($count)=$port->write("$command\r");
     main::read_stdout();

main::close_port();
close LOG;


sub read_stdout {

sleep 1;
($count,$str)=$port->read(1);
$cnt=$count;
while ($count>0) {
	($count,$got)=$port->read(1);
	$str.=$got;
	$cnt+=$count;
}
print "read: $cnt\n";
print "$str\n";
print LOG "read: $cnt\n";
print LOG "$str\n";

}

sub close_port {

undef $port;
print "Port closed\n";
print LOG "Port closed\n";

}

