#!/bin/bash
# $Id$
# Provision dosing station package installation script
# Author:  JC Boursiquot, B-MAS, LLC

echo " "
echo "Installaing necessary packages...."
sudo apt-get -y install libnet-server-perl libdbi-perl libdevice-serialport-perl libdbd-mysql-perl mysql-client-5.0 libconfig-tiny-perl
echo "done"

echo " "
echo -n "What com port are you using? Enter 0 for com1 or 1 for com2"
read myport
sudo chmod 666 /dev/ttyS${myport}

echo " "
echo "Creating ssh directory for user freemed....."
sudo mkdir -p /home/freemed/.ssh

echo " "
echo "Testing label printer...."
/home/freemed/generate_label.pl -p 'Linux, Tux [ 1955-01-03 ] 004419' -P 'Administrator' -d '100' -e  -i '004419' -d '100' -D '2008-06-09' -l '20080606001' -f 'Community Health Services' -a '  Hartford, CT ' -t '8608081234'
echo "Please check label printer for output..."

echo " "
echo " "
echo "The setup completed successfully."
echo "now you need to type ./pump_manual.pl from the /home/freemed directory. Then type v50 when prompted for a command."

