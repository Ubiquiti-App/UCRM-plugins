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
        fip = ip.split("/")[0]
        fobj = rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access").items
        fobjf = []
        for fo in fobj:
            stfo = str(fo)
            sfo = "".join(stfo)
            rfo = sfo.split("/")[0]
            fobjf.append(rfo)
        if not fip in fobjf:
            o = rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access")
            o.add(fip)

    # Exit the function after the FOR loop
    return
