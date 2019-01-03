# Revenue Report Plugin

This plugin calculates the organization's revenue. The data can be grouped by service plans.
When this plugin is deployed no configuration is needed. It just creates a new item under the Reporting main menu section.

Also, this plugin can be used as an example to show what you can do with UCRM plugins. It can be used by developers as a template for creating a new plugin.

Read more about creating your own plugin in the [Developer documentation](../../docs/index.md).

## Useful classes

### `App\Service\TemplateRenderer`

Very simple class to load a PHP template. When writing a PHP template be careful to use correct escaping function: `echo htmlspecialchars($string, ENT_QUOTES);`.

### UCRM Plugin SDK
The [UCRM Plugin SDK](https://github.com/Ubiquiti-App/UCRM-Plugin-SDK) is used by this plugin. It contains classes able to help you with calling UCRM API, getting plugin's configuration and much more.

## Google Charts example

This plugin also renders the data as a very simple chart using the [Google Charts](https://developers.google.com/chart/) JavaScript library.

## Further improvements
It is possible to improve this plugin to caluclate invoice products or any other custom items. Additionally, the plugin could use some kind of data cashing (caching of last calculated report) because the data processing can take a long time in case there are many invoices in the system.
