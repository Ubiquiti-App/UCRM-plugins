# FIO CZ

* Imports payments from Fio bank (CZE) to UCRM and associate them with clients automatically.

## Installation guide
* Upload the plugin ZIP archive to UCRM
* Configure the plugin 
	* This plugin retrieves data from your bank account, you need to [enable the FIO API for your account](https://www.fio.cz/bankovni-sluzby/api-bankovnictvi) and request a token in Internetbanking / Settings / API (it only needs read-only permissions).
	* When done, set the Fio token in the plugin configuration page.
	* Set other plugin attributes, such as the import frequency, date of the first payment you want to import and which client's attribute should be used to match the payment.
	* Decide whether you want to import unattached payments (see below), or skip them; set the "Import all payments" attribute.
* Set the plugin automatic execution period or run it manually. 
	 
## How does the payment import work?
* All imported payments are automatically associated with clients and their invoices.
* The payment amount is used for all client's invoices from the oldest one to the newest one.
* Any possible overpayment is converted into client's credit.

#### Unmatched payments
* If you set "Import all payments" to 1, then payments which couldn't be associated with any client are marked as Unattached and they are highlighted on the UCRM dashboard.
* You can associate these payments manually.
* If you set "Import all payments" to 0, these unattached payments will only be logged and skipped, without importing them. 
