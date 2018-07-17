# UCRM-plugins

Plugins are open-source programs that extend the functionality of [UCRM](https://ucrm.ubnt.com/). These plugins will enable your UCRM to import payments, integrate with another accounting software, cooperate with 3rd party HW and tools, etc. [Read more](https://help.ubnt.com/hc/en-us/articles/360002433113-UCRM-Plugins).

UCRM plugins are compatible with UCRM 2.10.0+

## How does it work?
* [Find the plugin](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins) you need and download its ZIP archive.
* Upload the ZIP archive in UCRM (System > Plugins).
* Then, enable the plugin and configure its settings.
* That's it, let UCRM run the plugin repeatedly or click to execute it on demand.

## List of available plugins
| Name | Description |
| ----------- | ------------- |
| [Plugin template](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/plugin-template) | UCRM plugin sample. Can be used as a base for a new plugin development. |
| [QuickBooks&nbsp;Online](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/quickbooks-online) | Sync financial data from UCRM into QB Online |
| [MKT&nbsp;Queue&nbsp;Sync](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/mkt-queue-sync) | Sync UCRM Service Data rate with Mikrotik Simple Queue by service IP Address and client's service speed set in UCRM |
| [FIO CZ](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/fio_cz) | Automatic payments import and matching with clients - from WISP's FIO bank account |
| [Client&nbsp;Signup](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/UCRM-Client-Signup) | Enables any visitor to register as a new client through a public web form |
| [RouterOS&nbsp;packet&nbsp;manager](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/routeros-packet-manager) | Sync UCRM Service Data rate with Router-OS |
| [Packetlogic packet manager](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/packetlogic-packet-manager) | Sync UCRM Entities and Service Data Rate with Procera |

## How can I contribute?
* These plugins are under MIT license enabling anyone to contribute any upgrades to existing plugins or create whole new ones.
* Propose any Pull Request in this repository.
* See the documentation below.

## Developer documentation
Developer documentation for creating UCRM plugins can be found in [`docs/index.md`](docs/index.md)

## Disclaimer 
The software is provided "as is", without any warranty of any kind. Read more in the [licence](https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/LICENSE)
