# FIO CZ

* Imports payments from Fio bank (CZE) to UCRM and associate them with clients automatically.

## Installation guide
* Upload the plugin ZIP archive to UCRM
* Configure the plugin 
	* To enable this plugin to retrieve data from your bank account, you need to [enable the FIO API for your account](https://www.fio.cz/bankovni-sluzby/api-bankovnictvi).
	* When done, set the Fio token in the plugin configuration page.
	* Set other plugin attributes, such as the import frequency, date of the first payment you want to import and which client's attribute should be used to match the payment.
* Start the plugin automatic execution or run it manually. 
	 
## How does the payment import work?
* All imported payments are automatically associated with clients and their invoices.
* The payment amount is used for all client's invoices from the oldest one to the newest one.
* Any possible overpayment is converted into client's credit.

#### Unmatched payments
* Payments which couldn't be associated with any client are marked as Unattached and they are highlighted on the UCRM dashboard.
* You can associate these payments manually. 





