Import UCRM invoices to QuickBooks Online
=== 
With these basic plugin you can import your [UCRM](https://ucrm.ubnt.com/) customers, payments and invoices to 
[QuickBooks Online](https://quickbooks.intuit.com/).


Connection with QuickBooks
---
###1. QuickBook - Create App
- At [Intuit Developer](https://developer.intuit.com/) create developer account.
- After registration process create new app. Choose **Select APIs** and check **Accounting** and **Payments** option.

###2. QuickBook - App setting
- At App Dashboard use **Keys** tab and fill **Redirect URI**.
- At App Dashboard use **Keys** tab and copy **Client ID** and **Client Secret**.

###3. Setting of plugin in plugin configuration page in UCRM
- Fill obtained **Client ID** and **Client Secret** .
- Fill **baseUrl** with ``Development`` if you only test and you have testing keys. Otherwise fill ``Production`` 
- Fill your **Income account number**. 
