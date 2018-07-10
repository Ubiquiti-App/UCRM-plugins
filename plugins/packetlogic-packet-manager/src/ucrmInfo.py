#!/usr/bin/python

# Custom PacketLogic Execution Script for Ubiquiti CRM
# Creation Date: 06/22/2018
# Author: Shaun Wilkinson
# Company: AtLink Services, LLC
# URL: http://www.atlinkservices.com

import sys
import json
import requests
from os import path
from urllib2 import Request, urlopen

basepath = path.dirname(__file__)
filepath = path.abspath(path.join(basepath, "..", "data", "config.json"))

appKey = "K5lbrKaIXTav78JCt+QEE0jcmddXUZFkRRBBQglmwGkq8dUD4fb8JYvalGN39pA1"

# Open UCRM settings
ufile = path.abspath(path.join(basepath, "..", "ucrm.json"))
with open(ufile) as ucrm:
    uData = json.load(ucrm)

# Open UCRM Config data
with open(filepath) as config:
    cData = json.load(config)

# Set option variable
opt = cData["options"]

# Specify the public URL for the UCRM
ucrmURL = uData["ucrmPublicUrl"]

if opt == "Clients":
    configset = "/clients/services"
if opt == "Services":
    configset = "/service-plans"
if opt == "Infrastructure":
    configset = "/devices"
if opt == "InitAdd":
    configset = "/clients/services"
if opt == "InitRemove":
    configset = "/clients/services"
if opt == "Sync":
    configset = "/clients/services"
    
# Define the UCRM Connection function
def ucrmConnect(url,key):
    
    headers = {
    "Content-Type": "application/json",
        "X-Auth-App-Key": key
    }
    
    cURL = Request(url)
    cURL.add_header("Content-Type","application/json")
    cURL.add_header("X-Auth-App-Key",key)
    request = urlopen(cURL)
    response = request.read()
    data = json.loads(response)

    return data
