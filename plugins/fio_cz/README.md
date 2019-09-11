# FIO CZ

([English version follows](#fio-cz-english))
* Importuje platby z Fio banky do UCRM, automaticky je spáruje s klienty a použije je k úhradě nezaplacených faktur.

## Instalace
* Nainstalujte plugin v aplikaci UCRM, nebo nahrajte archiv ZIP do UCRM
* Nastavte plugin
	* Tento plugin čte data o vašem bankovním účtu: je proto potřeba [zapnout pro něj FIO API](https://www.fio.cz/bankovni-sluzby/api-bankovnictvi) a vyžádat si token v Internetbanking / Nastavení / API (stačí oprávnění "Pouze sledovat účet").
	* Vygenerovaný token zadejte v nastavení pluginu.
	* Nastavte další chování pluginu: datum, od nějž se mají importovat platby, a který klientský atribut se má používat ke spárování plateb.
	* Rozhodněte se, zda chcete importovat nespárované platby (viz níže), nebo je přeskakovat. Podle toho nastavte atribut "Importovat všechny platby" na 1 (i nespárované) nebo 0 (jen spárované).
* Nastavte frekvenci spouštění pluginu, nebo ho spusťte automaticky 

## Jak probíhá import plateb? 
* Všechny importované platby se automaticky párují ke klientům a jejich fakturám.
* Platba se použije postupně na všechny klientovy faktury, počínaje od nejstarší, dokud se nevyčerpá celá její částka.
* Případný přeplatek se uloží v systému jako kredit.

### Nespárované platby
* Pokud nastavíte "Importovat všechny platby" na 1, pak platby, které se nepodaří spárovat s klientem, se naimportují jako "nespárované" a takto se zobrazí na přehledové obrazovce UCRM.
    * Tyto platby lze spárovat ručně.
* Pokud nastavíte "Importovat všechny platby" na 0, tyto nespárované platby budou pouze zaznamenány v logu a přeskočeny, aniž by byly importovány.

# <a name="fio-cz-english"></a>FIO CZ (English)
* Imports payments from Fio bank (CZE) to UCRM, associates them with clients automatically, and marks their outstanding invoices as paid.

## Installation guide
* Install the plugin from UCRM, or upload the plugin ZIP archive to UCRM
* Configure the plugin 
	* This plugin retrieves data from your bank account, you need to [enable the FIO API for your account](https://www.fio.cz/bankovni-sluzby/api-bankovnictvi) and request a token in Internetbanking / Settings / API (it only needs read-only permissions).
	* When done, set the Fio token in the plugin configuration page.
	* Set other plugin attributes, such as the date of the first payment you want to import, and the client's attribute which should be used to match the payment.
	* Decide whether you want to import unattached payments (see below), or skip them; set the "Import all payments" attribute.
* Set the plugin automatic execution period or run it manually. 
	 
## How does the payment import work?
* All imported payments are automatically associated with clients and their invoices.
* The payment amount is used for all client's invoices from the oldest one to the newest one.
* Any possible overpayment is converted into client's credit.

### Unmatched payments
* If you set "Import all payments" to 1, then payments which couldn't be associated with any client are marked as Unattached and they are highlighted on the UCRM dashboard.
    * You can associate these payments manually.
* If you set "Import all payments" to 0, these unattached payments will only be logged and skipped, without importing them. 
