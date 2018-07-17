#!/usr/bin/python

# Custom PacketLogic Execution Script for Ubiquiti CRM
# Creation Date: 06/22/2018
# Author: Shaun Wilkinson
# Company: AtLink Services, LLC
# URL: http://www.atlinkservices.com

# Import system and PacketLogic API
import packetlogic2
import sys
import os
import ucrmInfo
from globals import rs

# Function to add customers to Procera under authorized
def makeClients(client,plan,status,clientIP):
        
    # UCRM Client Variable
    pcl = str("UCRM_Client_%s" % client)
    
    # UCRM Service Plan
    psn = str("UCRM_%s" % plan)
    
    # UCRM Client Status
    pst = str(status)
    
    # UCRM Client IP
    if len(clientIP) < 2:
        for pcip in clientIP:
            cdip = pcip + "/32"
    elif len(clientIP) > 2:
        for pcip in clientIP:
            cdip = pcip + "/32"
    else:
        for pcip in clientIP:
            cdip = pcip
    
    # Create Authorized NetObject on active status
    if pst == "1":
        if rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl):
            rs.object_remove("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl)
        elif not rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl):
            o = rs.object_add("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl)
            o.add(pcip)

    # Create Unauthorized NetObject on suspended status
    if pst == "2":
        if rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl):
            rs.object_remove("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl)
        elif not rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl):
            o = rs.object_add("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl)
            o.add(pcip)
    
    # Remove NetObject on terminated status
    if pst == "3":
        if rs.object_find("/NetObjects/Ubiquiti_CRM/Services/%s" % pcl):
            if rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl):
                rs.object_remove("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl)
                rs.object_remove("/NetObjects/Ubiquiti_CRM/Services/%s/%s" % (psn, pcl))
            elif rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl):
                rs.object_remove("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl)
                rs.object_remove("/NetObjects/Ubiquiti_CRM/Services/%s/%s" % (psn, pcl))
        elif rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl):
            rs.object_remove("/NetObjects/Ubiquiti_CRM/UCRM_Authorized/%s" % pcl)
        elif rs.object_find("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl):
            rs.object_remove("/NetObjects/Ubiquiti_CRM/UCRM_Unauthorized/%s" % pcl)

    # Exit the function
    return

# Function to add the customers to their respective package group
def makeClientPlan(client,plan,devIP):
    
    # UCRM Client Variable
    pcl = str("UCRM_Client_%s" % client)
    
    # UCRM Service Plan
    psn = str("UCRM_%s" % plan)

    # Set pdip as the client's device IP
    if len(devIP) < 2:
        for pdip in devIP:
            cdip = pdip + "/32"
    elif len(devIP) > 2:
        for pdip in devIP:
            cdip = pdip + "/32"
    else:
        for pdip in devIP:
            cdip = pdip

    # Gather service plan data
    surl = ucrmInfo.ucrmURL + "/service-plans"
    splan = ucrmInfo.ucrmConnect(surl,ucrmInfo.appKey)

    # Check to see if the customer's plan matches the one they were on and remove the old one
    for sp in splan:
        oldplan = str("UCRM_" + sp["name"])

        if rs.object_find("/NetObjects/Ubiquiti_CRM/Services/%s/%s" % (oldplan,pcl)):
            if oldplan != psn:
                rs.object_remove("/NetObjects/Ubiquiti_CRM/Services/%s/%s" % (oldplan,pcl))

    # Check to see if the customer is existing under their plan and add them if not
    if not rs.object_find("/NetObjects/Ubiquiti_CRM/Services/%s/%s" % (psn, pcl)):
        o = rs.object_add("/NetObjects/Ubiquiti_CRM/Services/%s/%s" % (psn, pcl))
        o.add(pdip)

    # Exit the function
    return
