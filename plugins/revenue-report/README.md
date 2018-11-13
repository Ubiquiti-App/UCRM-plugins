# [Example] Revenue Report Plugin

This plugin calculates the organization's revenue. The data can be grouped by products or service plans.
When this plugin is deployed no configuration is needed. It just creates a new item under the Reporting main menu section.

Also, this plugin can be used as an example to show what you can do with UCRM plugins. It can be used by developers as a template for creating a new plugin.

Read more about creating your own plugin in the [Developer documentation](../../docs/index.md).

## Useful classes

First you can find some classes here that you can use in your own plugins.

### `App\Service\OptionsManager`

OptionsManager can be used to load the current configuration from both [ucrm.json](../../docs/file-structure.md#ucrmjson) and [config.json](../../docs/file-structure.md#dataconfigjson). The data are returned in form of `App\Data\UcrmData` and `App\Data\PluginData` respectively.

### `App\Service\UcrmApi`

UcrmApi is used to simplify the calls to [UCRM API](https://ucrm.docs.apiary.io). There are 3 methods:

- `command` - to call a POST, PATCH or DELETE endpoint
- `query` - to call a GET endpoint
- `getUser` - to retrieve data about the current user to verify [security](../../docs/security.md)

### `App\Service\TemplateRenderer`

Very simple class to load a PHP template. When writing a PHP template be careful to use correct escaping function: `echo htmlspecialchars($string, ENT_QUOTES);`.

## Google Charts example

This plugin also renders the data as a very simple chart using the [Google Charts](https://developers.google.com/chart/) JavaScript library.
