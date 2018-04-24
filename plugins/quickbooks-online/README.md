Imports UCRM invoices to QuickBooks Online
=== 
With this basic plugin you can import your [UCRM](https://ucrm.ubnt.com/) customers, payments and invoices to 
[QuickBooks Online](https://quickbooks.intuit.com/).

Set up the connection with QuickBooks
---
#### QuickBook - Create App
- At [Intuit Developer](https://developer.intuit.com/) create developer account.
- After registration process create new app. Choose **Select APIs** and check **Accounting** and **Payments** option.

#### QuickBook - App setting
- At App Dashboard use **Keys** tab and fill **Redirect URI** with public URL of your plugin.
![Intuit redirect URI](docs/images/intuit-redirect-uri.png)

- At App Dashboard use **Keys** tab and copy **Client ID** and **Client Secret**.
![Intuit keys](docs/images/intuit-developer-keys.png)

#### Setting of plugin in plugin configuration page in UCRM
- Fill obtained **Client ID** and **Client Secret** .
- Fill **Account type** with ``Development`` if you only test and you have testing keys. Otherwise fill ``Production`` 
- Fill your **Income account number**.
![UCRM Plugin config](docs/images/ucrm-plugin-config.png)
 
#### Connect UCRM with your Intuit App
- On Plugin page is plugin log where will be displayed Authorization URL. 
![UCRM Authorization URL](docs/images/ucrm-authorization-url.png)

- Open it in your browser to confirm connection.
![UCRM Authorization APP](docs/images/authorize-APP.png)
- Than you can see in the plugin log ```Authorization Code obtained.``` and after next execution of plugin ```Exchange Authorization Code for Access Token succeeded.```. After that UCRM an QB are properly connected.


About UCRM data integration
---
- The UCRM data are the single source of truth, this plugin pushes data from UCRM to QB only. 
- During the plugin run, all clients, payments and invoices are pushed to QB.
- Any further plugin run pushes just the newly created entities (i.e new clients, new payments, new invoices with higher ID than the last pushed ID)  

To be done in future version
---
(Feel free to push your upgrades in this repo.)
- Configurable date of the first payment or invoice to be imported. 
- Remove entity from QB when the related entity is deleted in UCRM.
