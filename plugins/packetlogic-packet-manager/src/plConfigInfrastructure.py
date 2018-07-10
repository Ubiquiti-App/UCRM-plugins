#!/usr/bin/python

# Custom PacketLogic Execution Script for Ubiquiti CRM
# Creation Date: 06/22/2018
# Author: Shaun Wilkinson
# Company: AtLink Services, LLC
# URL: http://www.atlinkservices.com

# Import system, JSON, and PacketLogic API
import packetlogic2
import sys
from globals import rs

# Function to add devices to the open access list
def makeOpenAccess(ips):
    for ip in ips:
        if not ip in rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access").items:
            o = rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access")
            o.add(ip)

    # Exit the function after the FOR loop
    return
