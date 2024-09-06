# UCRM-plugins

Plugins are open-source programs that extend the functionality of [UCRM](https://ucrm.ubnt.com/). 
These plugins will enable your UCRM to import payments, integrate with another accounting software, cooperate with 3rd party HW and tools, view or create custom reports, modify all the UCRM data in a batch, etc. [Read more](https://help.ubnt.com/hc/en-us/articles/360002433113-UCRM-Plugins).

UCRM plugins are compatible with UCRM 2.10.0+

## How does it work?
* [Find the plugin](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins) you need and download its ZIP archive.
* Upload the ZIP archive in UCRM (System > Plugins).
* Then, enable the plugin and configure its settings.
* That's it, let UCRM run the plugin repeatedly or click to execute it on demand.

## List of available plugins
| Name                                                                                                                          | Description                                                                                                                                     |
|-------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| [SMS Gateway Integration](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/sms-twilio)                        | Integrates Twilio SMS gateway which enables UCRM to send SMS to clients triggered by custom defined events, e.g. client's service gets suspended. |
| [QuickBooks&nbsp;Online](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/quickbooks-online)                  | Sync financial data from UCRM into QB Online                                                                                                    |
| [FIO CZ](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/fio_cz)                                             | Automatic payments import and matching with clients - from WISP's FIO bank account                                                              |
| [Client&nbsp;Signup](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/ucrm-client-signup)                     | Enables any visitor to register as a new client through a public web form                                                                       |
| [KMZ Map](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/kmz-map)                                           | Provides a frontend Google Map for clients to view tower coverage.                                                                              |
| [Invoice CSV Export](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/invoice-csv-export)                     | Configurable export of invoices into CSV, can be used for a manual export to 3rd party accounting tools.                                        |
| [Revenue Report](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/revenue-report)                             | Revenue report grouped by products or services, shown under the Reporting main menu section.                                                    |
| [Facturas Argentina AFIP](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/argentina-afip-invoices)           | Plugin para obtener CAE (Facturacion electronica) en Argentina                                                                                  |
| [Barcode generator](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/barcode-generator)                       | Barcode generator                                                                                                                               |

## Plugins / API scripts from other sources
* [Client&nbsp;Signup Extended](https://www.charuwts.com/plugins/ucrm-signup) - Extended version of Client Signup Plugin
* [Notification Plugin](https://community.ubnt.com/t5/UCRM-Plugins/Notification-Plugin/td-p/2541572) - Extended UCRM's notification system. More event-triggered emails sent to UCRM administrators.  
* [UCRM FreeRadius](https://github.com/jhooper94/ucrm-freeradius-auth) - pulls the mac address and package from ucrm and push the information into free radius database

## How can I contribute?
* These plugins are under MIT license enabling anyone to contribute any upgrades to existing plugins or create whole new ones.
* Propose any Pull Request in this repository.
* See the documentation below.

## Developer documentation
Developer documentation for creating UCRM plugins can be found in [`docs/index.md`](docs/index.md)

## Disclaimer 
The software is provided "as is", without any warranty of any kind. Read more in the [licence](https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/LICENSE)
