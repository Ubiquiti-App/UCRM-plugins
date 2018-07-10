packetlogic-packet-manager
Python-based PacketLogic Packet Manager for UCRM API for PacketLogic Firmware 15.1.5.12 and greater

This is a PacketLogic packet manager for the Ubiquiti CRM that I have customer coded using PacketLogic's Python API that can be downloaded from their website http://download.proceranetworks.com/python-api.html. Feel free to edit this plugin as you see fit, but please reference me!

NOTE: This .egg file must be installed into your Python in order for this to work.
Files Included
main.php
This file calls uexec.py, which will execute the rest of the code for the plugin.

manifest.json
The basic manifest for a Ubiquiti CRM Plugin. Includes optional fields for having a redundant packet manager in your network.

src/__init__.py
Placeholder file to specify a sub-directory.

src/ucrmInfo.py
This is what contains all of the variables and connection information for the UCRM, including the config options set for the plugin. Also has the function ucrmConnect() which connects to the desired URL with the desired headers.

src/globals.py
This contains some variables called by a lot of the functions and is included in order to function properly.

src/plConfigClients.py
This contains some functions that are used to add, verify, and remove clients from the PacketLogic.

src/plConfigServices.py
This contains some functions that are used to add, verify, and remove service plans from the PacketLogic.

src/plConfigInfrastructure.py
This contains some functions that are used to add and verify open access devices to the PacketLogic.

src/plInit.py
This contains some functions that are used to Initialize the Procera.

###########

Usage Instructions
1. Download the corresponding API file for your PacketLogic firmware and install it.
2. Upload the zip file for the plugin.
3. Configure the plugin. There are necessary options for telling the PacketLogic what you want it to do.
  - InitRemove - This will remove all prior Ubiquiti-related objects and items from your PacketLogic.
  - InitAdd - This will add all of the vital parent objects to your PacketLogic.
  - Services - This will add all of your service plans to the PacketLogic.
  - Clients - This will add all of your clients to the PacketLogic and add them to their corresponding plans.
  - Infrastructure - This will add all of your open access devices to the PacketLogic.
  - Sync - This will force synchronize the PacketLogic, which will perform all of the above options (except InitRemove).
4. Enjoy.

###########

Thank you for using my plugin!
