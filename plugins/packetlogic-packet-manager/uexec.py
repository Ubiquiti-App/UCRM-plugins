#!/usr/bin/python

# Custom PacketLogic Execution Script for Ubiquiti CRM
# Creation Date: 06/22/2018
# Author: Shaun Wilkinson
# Company: AtLink Services, LLC
# URL: http://www.atlinkservices.com

# Import sys and PacketLogic API built-ins
import sys
import packetlogic2
from src import ucrmInfo
from src import plConfigClients
from src import plConfigServices
from src import plConfigInfrastructure
from src import plInit
from src import globals

# Define synchronization function
def sync():
    plInit.InitializeAdd()
    
    surl = ucrmInfo.ucrmURL + "/service-plans"
    ss = ucrmInfo.ucrmConnect(surl,ucrmInfo.appKey)
    for svc in ss:
        psn = svc["name"]
        pdn = svc["downloadSpeed"]
        pup = svc["uploadSpeed"]
        
        plConfigServices.makeServicePlan(psn,pdn,pup)
    iurl = ucrmInfo.ucrmURL + "/devices"
    si = ucrmInfo.ucrmConnect(iurl,ucrmInfo.appKey)
    for did in si:
        devid = did["id"]
        
        dIntURL = ucrmInfo.ucrmURL + ("/devices/%d/device-interfaces" % devid)
        
        dc = ucrmInfo.ucrmConnect(dIntURL,ucrmInfo.appKey)
        
        for dip in dc:
            pdip = dip["ipRanges"]
            
            plConfigInfrastructure.makeOpenAccess(pdip)
    curl = ucrmInfo.ucrmURL + "/clients/services"
    sc = ucrmInfo.ucrmConnect(curl,ucrmInfo.appKey)
    for client in sc:
        
        pcl = client["clientId"]
        pst = client["status"]
        psn = client["servicePlanName"]
        pcip = client["ipRanges"]
        psid = client["id"]
        
        plConfigClients.makeClients(pcl,psn,pst,pcip)
        plConfigClients.makeClientPlan(pcl,psn,pcip)
    return

# Check to see what the option variable is, then call the function
if globals.popt == "InitRemove":
    plInit.InitializeRemove()

if globals.popt == "InitAdd":
    plInit.InitializeAdd()

if globals.popt == "Infrastructure":
    for did in globals.ud:
        devid = did["id"]
        
        dIntURL = ucrmInfo.ucrmURL + ("/devices/%d/device-interfaces" % devid)
    
        dc = ucrmInfo.ucrmConnect(dIntURL,ucrmInfo.appKey)
    
        for dip in dc:
            pdip = dip["ipRanges"]

            plConfigInfrastructure.makeOpenAccess(pdip)

if globals.popt == "Clients":
    for client in globals.ud:
        
        pcl = client["clientId"]
        pst = client["status"]
        psn = client["servicePlanName"]
        pcip = client["ipRanges"]
        psid = client["id"]
    
        plConfigClients.makeClients(pcl,psn,pst,pcip)
        plConfigClients.makeClientPlan(pcl,psn,pcip)

if globals.popt == "Services":
    for svc in globals.ud:
        psn = svc["name"]
        pdn = svc["downloadSpeed"]
        pup = svc["uploadSpeed"]
        
        plConfigServices.makeServicePlan(psn,pdn,pup)

if globals.popt == "Sync":
    sync()

# Submit changes to the Procera and close ruleset
globals.rs.commit()
