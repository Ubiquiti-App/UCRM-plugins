# [Example] Invoice CSV export

This plugin serves as an example to show some of the possibilities of what you can do with UCRM plugins. It can be used by developers as a template for creating a new plugin.

Read more about creating your own plugin in the [Developer documentation](../../master/docs/index.md).

## Useful classes

First you can find some classes here that you can use in your own plugins.

### `App\Service\OptionsManager`

OptionsManager can be used to load the current configuration from both [ucrm.json](../../docs/file-structure.md#ucrmjson) and [config.json](../../docs/file-structure.md#dataconfigjson). 

### `App\Service\UcrmApi`

UcrmApi is used to simplify the calls to [UCRM API](https://ucrm.docs.apiary.io). There are 3 methods:

- `command` - to call a POST, PATCH or DELETE endpoint
- `query` - to call a GET endpoint
- `getUser` - to retrieve data about the current user to verify [security](../../docs/security.md)

### `App\Service\TemplateRenderer`

Very simple class to load a PHP template. When writing a PHP template be careful to use correct escaping function: `echo htmlspecialchars($string, ENT_QUOTES);`.

## Extending the plugin

Let's say you would want to improve this plugin to only export invoices that were not invoiced before. In that case you have two options:

- persist the IDs of already exported invoices to a file
- mark them as already exported with UCRM API using a [Custom Attribute](https://ucrm.docs.apiary.io/#reference/custom-attributes)
