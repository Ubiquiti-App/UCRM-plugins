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

# Function to create the UCRM packages
def makeServicePlan(plan,download,upload):
    
    psn = plan
    pdn = [(0, 0, ((download * 1000)/8))]
    pldn = ((download * 1000)/8)
    pup = [(0, 0, ((upload * 1000)/8))]
    plup = ((upload * 1000)/8)
    
    so = rs.shapingobject_find("UCRM_%s" % psn)
    
    # Check to see if package speeds are correct, if it exists
    if so:
        if so.limits.inbound.bps != pldn:
            if so.limits.outbound.bps != plup:
                so.limits.inbound.bps = pldn
                so.limits.outbound.bps = plup
            else:
                so.limits.inbound.bps = pldn
        elif so.limits.outbound.bps != plup:
            so.limits.outbound.bps = plup

    # Check for service package, then make the package
    if rs.object_find("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn):
        if rs.shapingobject_find("UCRM_%s" % psn):
            if not rs.shapingrule_find("UCRM_%s" % psn):
                sloc = rs.object_find("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
                sobj = rs.shapingobject_find("UCRM_%s" % psn)
                o = rs.shapingrule_add("UCRM_%s" % psn)
                o.cond_add(rs.CONDITION_NETOBJECT_LOCAL, rs.CONDITION_OP_EQ, [sloc.id])
                o.set_objects([sobj.id])
        elif rs.shapingrule_find("UCRM_%s" % psn):
            rs.shapingobject_add(("UCRM_%s" % psn), inbound = pdn, outbound = pup, flags = ["counter", "blue"])
        else:
            rs.shapingobject_add(("UCRM_%s" % psn), inbound = pdn, outbound = pup, flags = ["counter", "blue"])
            sloc = rs.object_find("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
            sobj = rs.shapingobject_find("UCRM_%s" % psn)
            o = rs.shapingrule_add("UCRM_%s" % psn)
            o.cond_add(rs.CONDITION_NETOBJECT_LOCAL, rs.CONDITION_OP_EQ, [sloc.id])
            o.set_objects([sobj.id])
    elif rs.shapingobject_find("UCRM_%s" % psn):
        if rs.shapingrule_find("UCRM_%s" % psn):
            rs.object_add("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
        else:
            rs.object_add("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
            sloc = rs.object_find("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
            sobj = rs.shapingobject_find("UCRM_%s" % psn)
            o = rs.shapingrule_add("UCRM_%s" % psn)
            o.cond_add(rs.CONDITION_NETOBJECT_LOCAL, rs.CONDITION_OP_EQ, [sloc.id])
            o.set_objects([sobj.id])
    elif rs.shapingrule_find("UCRM_%s" % psn):
        rs.object_add("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
    else:
        rs.object_add("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
        sloc = rs.object_find("/NetObjects/Ubiquiti_CRM/Services/UCRM_%s" % psn)
        rs.shapingobject_add(("UCRM_%s" % psn), inbound = pdn, outbound = pup, flags = ["counter", "blue"])
        sobj = rs.shapingobject_find("UCRM_%s" % psn)
        o = rs.shapingrule_add("UCRM_%s" % psn)
        o.cond_add(rs.CONDITION_NETOBJECT_LOCAL, rs.CONDITION_OP_EQ, [sloc.id])
        o.set_objects([sobj.id])

    # Exit the function
    return
