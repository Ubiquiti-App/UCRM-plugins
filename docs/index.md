# Developer documentation

## Plugins are PHP programs that extend functionality of UCRM.
Plugins can be used as: 
* backend scripts - reading/writing UCRM data (batch changes, batch exports/imports, etc.). These plugins can be executed automatically with a defined frequency.
* frontend pages - a completely new page with own features and UI can be shown to authenticated UCRM users or clients. This can be used for a custom features, for example user-defined reports, batch data changes with user input, etc.

## Plugin samples for an easy start of creating your own plugin
* [Plugin Template](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/examples/plugin-template) - Simplest UCRM plugin sample, can be used as a base for a new plugin development.
* [Invoice CSV Export](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/invoice-csv-export) - Better plugin example, that actually does something: a new menu item is shown, user can filter invoices by date and export them into a CSV file.  
* [Revenue Report](https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/revenue-report) - Revenue report grouped by products or services, shown under the Reporting main menu section.

## Tutorials
- [How to create your first UCRM plugin](tutorials/first-plugin.md)

## UCRM Plugin SDK
You can use [UCRM Plugin SDK](https://github.com/Ubiquiti-App/UCRM-Plugin-SDK) to help you with development of plugins.  
It contains classes for calling UCRM API, getting plugin's configuration and much more.

## Distribution
Plugins are distributed as ZIP archives. User uploads the archive into UCRM, it's checked and if valid, extracted to a folder based on plugin's name.  
You can use the [Pack script](https://github.com/Ubiquiti-App/UCRM-Plugin-SDK#pack-script) from UCRM Plugin SDK to easily prepare the ZIP archive.

## File structure
The minimum valid plugin consists of 2 files, [`manifest.json`](file-structure.md#manifestjson) and [`main.php`](file-structure.md#mainphp).
These files are required for successful installation in UCRM. Other than the required files, archives can contain anything the plugin needs (with some exceptions - see [reserved files](file-structure.md#reserved-files)).

Read more in the [File structure](file-structure.md) documentation.

## Security
The plugins can also add custom pages via `public.php`. You can put additional security restrictions on these pages if desired.

Read more in the [Security](security.md) documentation.
