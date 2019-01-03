# Invoice CSV export

Configurable export of invoices into CSV. Using this plugin, user can define From and To date filters and export the given invoices into a CSV file, this can be used for a manual export to 3rd party accounting tools.

Also, this plugin can be used as an example to show some of the possibilities of what you can do with UCRM plugins. It can be used by developers as a template for creating a new plugin.

Read more about creating your own plugin in the [Developer documentation](../../master/docs/index.md).

## Useful classes

### `App\Service\TemplateRenderer`

Very simple class to load a PHP template. When writing a PHP template be careful to use correct escaping function: `echo htmlspecialchars($string, ENT_QUOTES);`.

### UCRM Plugin SDK
The [UCRM Plugin SDK](https://github.com/Ubiquiti-App/UCRM-Plugin-SDK) is used by this plugin. It contains classes able to help you with calling UCRM API, getting plugin's configuration and much more.

## Extending the plugin

Let's say you would want to improve this plugin to only export invoices that were not invoiced before. In that case you have two options:

- persist the IDs of already exported invoices to a file
- mark them as already exported with UCRM API using a [Custom Attribute](https://ucrm.docs.apiary.io/#reference/custom-attributes)
