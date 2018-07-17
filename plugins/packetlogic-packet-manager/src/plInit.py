#!/usr/bin/python

# Custom PacketLogic Execution Script for Ubiquiti CRM
# Creation Date: 06/22/2018
# Author: Shaun Wilkinson
# Company: AtLink Services, LLC
# URL: http://www.atlinkservices.com

import packetlogic2
import sys
import re
from fnmatch import fnmatch
from globals import rs

# Function for initial PacketLogic Sync, will erase all instances related to UCRM
def InitializeRemove():

    ui = rs.shapingrule_list()

    if ui:
        for iui in ui:
            rs.shapingrule_remove(iui)

    ui = rs.fwrule_list()

    if ui:
        for iui in ui:
            rs.fwrule_remove(iui)

    if rs.object_find("/PortObjects/Ubiquiti_CRM"):
        ui = rs.object_list("/PortObjects/Ubiquiti_CRM")
        
        if ui:
            for iui in ui:
                if iui.items:
                    for item in iui.items:
                        iui.remove(item)
                    rs.object_remove(iui)
            rs.object_remove("/PortObjects/Ubiquiti_CRM")
        
    if rs.object_find("/ProtocolObjects/Ubiquiti_CRM"):
        ui = rs.object_list("/ProtocolObjects/Ubiquiti_CRM")
        
        if ui:
            for iui in ui:
                if iui.items:
                    for item in iui.items:
                        iui.remove(item)
                    rs.object_remove(iui)
            rs.object_remove("/ProtocolObjects/Ubiquiti_CRM")

    if rs.object_find("/NetObjects/Ubiquiti_CRM"):
        ui = rs.object_list("/NetObjects/Ubiquiti_CRM")

        if ui:
            for iui in ui:
                if iui.items:
                    for item in iui.items:
                        iui.remove(item)
                    rs.object_remove(iui)
            rs.object_remove("/NetObjects/Ubiquiti_CRM")

    ui = rs.shapingobject_list()

    if ui:
        for iui in ui:
            rs.shapingobject_remove(iui)
    
    return

# Function to add all objects to PacketLogic (does not include clients, open access devices, or plans)
def InitializeAdd():
    
    if not rs.object_find("/PortObjects/Ubiquiti_CRM"):
        rs.object_add("/PortObjects/Ubiquiti_CRM")

    if not rs.object_find("/PortObjects/Ubiquiti_CRM/UCRM_DHCP"):
        o = rs.object_add("/PortObjects/Ubiquiti_CRM/UCRM_DHCP")
        o.add("67-68")

    if not rs.object_find("/PortObjects/Ubiquiti_CRM/UCRM_DNS"):
        o = rs.object_add("/PortObjects/Ubiquiti_CRM/UCRM_DNS")
        o.add("53")

    if not rs.object_find("/PortObjects/Ubiquiti_CRM/UCRM_HTTP"):
        o = rs.object_add("/PortObjects/Ubiquiti_CRM/UCRM_HTTP")
        o.add("81")

    if not rs.object_find("/ProtocolObjects/Ubiquiti_CRM"):
        rs.object_add("/ProtocolObjects/Ubiquiti_CRM")

    if not rs.object_find("/ProtocolObjects/Ubiquiti_CRM/UCRM_UDP"):
        o = rs.object_add("/ProtocolObjects/Ubiquiti_CRM/UCRM_UDP")
        o.add("UDP")

    if not rs.object_find("/ProtocolObjects/Ubiquiti_CRM/UCRM_ICMP"):
        o = rs.object_add("/ProtocolObjects/Ubiquiti_CRM/UCRM_ICMP")
        o.add("ICMP")

    if not rs.object_find("/NetObjects/Ubiquiti_CRM"):
        o = rs.object_add("/NetObjects/Ubiquiti_CRM")
        o.set_visible(True)

    if not rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Authorized"):
        rs.object_add("/NetObjects/Ubiquiti_CRM/UCRM_Authorized")

    if not rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized"):
        rs.object_add("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized")

    if not rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access"):
        rs.object_add("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access")

    if not rs.object_find("/NetObjects/Ubiquiti_CRM/Services"):
        rs.object_add("/NetObjects/Ubiquiti_CRM/Services")

    if not rs.fwrule_find("UCRM Authorized Users"):
        hn = rs.object_get("/NetObjects/Ubiquiti_CRM/UCRM_Authorized").id
        o = rs.fwrule_add("UCRM Authorized Users", rs.FWRULE_ACTION_ACCEPT, quick=True)
        o.cond_add(rs.CONDITION_NETOBJECT_HOST, rs.CONDITION_OP_EQ, [hn])

    if not rs.fwrule_find("UCRM Open Access"):
        hn = rs.object_get("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access").id
        o = rs.fwrule_add("UCRM Open Access", rs.FWRULE_ACTION_ACCEPT, quick=True)
        o.cond_add(rs.CONDITION_NETOBJECT_HOST, rs.CONDITION_OP_EQ, [hn])

    if not rs.fwrule_find("UCRM Delinquent Redirect"):
        hn1 = rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized").id
        hn2 = rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Open_Access").id
        hn3 = rs.object_find("/ServiceObjects/Procera Networks Categorization/Categories/Web Browsing").id
        o = rs.fwrule_add("UCRM Delinquent Redirect", rs.FWRULE_ACTION_INJECT, quick=True, inject_data='HTTP/1.1 307 Temporary Redirect\nLocation: http://ucrm.atlinkservices.com:81\nConnection: close')
        o.cond_add(rs.CONDITION_NETOBJECT_CLIENT, rs.CONDITION_OP_EQ, [hn1])
        o.cond_add(rs.CONDITION_NETOBJECT_SERVER, rs.CONDITION_OP_NE, [hn2])
        o.cond_add(rs.CONDITION_SERVICEOBJECT, rs.CONDITION_OP_EQ, [hn3])

    if not rs.fwrule_find("UCRM Delinquent Drop"):
        hn1 = rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized").id
        hn = rs.object_find("/PortObjects/Ubiquiti_CRM/UCRM_HTTP").id
        o = rs.fwrule_add("UCRM Delinquent Drop", rs.FWRULE_ACTION_DROP, quick=True)
        o.cond_add(rs.CONDITION_NETOBJECT_HOST, rs.CONDITION_OP_EQ, [hn1])
        o.cond_add(rs.CONDITION_PORTOBJECT_SERVER, rs.CONDITION_OP_NE, [hn])

    if not rs.fwrule_find("UCRM DNS Accept"):
        hn = rs.object_find("/PortObjects/Ubiquiti_CRM/UCRM_DNS").id
        hn1 = rs.object_find("/ProtocolObjects/Ubiquiti_CRM/UCRM_UDP").id
        o = rs.fwrule_add("UCRM DNS Accept", rs.FWRULE_ACTION_ACCEPT, quick=True)
        o.cond_add(rs.CONDITION_PORTOBJECT_SERVER, rs.CONDITION_OP_EQ, [hn])
        o.cond_add(rs.CONDITION_PROTOCOLOBJECT, rs.CONDITION_OP_EQ, [hn1])

    if not rs.fwrule_find("UCRM DHCP Accept"):
        hn1 = rs.object_find("/ProtocolObjects/Ubiquiti_CRM/UCRM_UDP").id
        hn = rs.object_find("/PortObjects/Ubiquiti_CRM/UCRM_DHCP").id
        o = rs.fwrule_add("UCRM DHCP Accept", rs.FWRULE_ACTION_ACCEPT, quick=True)
        o.cond_add(rs.CONDITION_PROTOCOLOBJECT, rs.CONDITION_OP_EQ, [hn1])
        o.cond_add(rs.CONDITION_PORTOBJECT_SERVER, rs.CONDITION_OP_EQ, [hn])

    if not rs.fwrule_find("UCRM ICMP Accept"):
        hn = rs.object_find("/ProtocolObjects/Ubiquiti_CRM/UCRM_ICMP").id
        o = rs.fwrule_add("UCRM ICMP Accept", rs.FWRULE_ACTION_ACCEPT, quick=True)
        o.cond_add(rs.CONDITION_PROTOCOLOBJECT, rs.CONDITION_OP_EQ, [hn])

    return
