#!/usr/bin/python

# Custom PacketLogic Execution Script for Ubiquiti CRM
# Creation Date: 06/22/2018
# Author: Shaun Wilkinson
# Company: AtLink Services, LLC
# URL: http://www.atlinkservices.com

import packetlogic2
import ucrmInfo

# Create an address from the URL and options put into configuration
ucrmAdd = ucrmInfo.ucrmURL+ucrmInfo.configset

# Connect to UCRM and gather information
ud = ucrmInfo.ucrmConnect(ucrmAdd,ucrmInfo.appKey)

# Create array for Packet Manager IPs
ips = [ucrmInfo.cData["pmIP"],ucrmInfo.cData["pm2IP"]]

# Set pusr to the configured Admin user
pusr = ucrmInfo.cData["pmAdminUser"]

# Set ppass to the configured Admin password
ppass = ucrmInfo.cData["pmAdminPass"]

# Set config option to variable
popt = ucrmInfo.cData["options"]

# Convert ip to string
cip = ips[0]

# Establish PacketLogic Connection
pl = packetlogic2.connect(cip,pusr,ppass)

# Access Ruleset
rs = pl.Ruleset()
