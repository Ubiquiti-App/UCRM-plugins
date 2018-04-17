# File structure

## Required files

### manifest.json
This file contains all needed information about the plugin, like name, author and required configuration. See the [manifest documentation](manifest.md) for more information.

### main.php
Main file of the plugin. This is what will be executed when the plugin is run by UCRM.

## Optional files

### public.php
If this file is present, public URL will be generated for the plugin which will point to this file. When the URL is accessed, the file will be parsed as PHP script and executed.

## Reserved files
These files cannot be contained in the plugin archive as UCRM handles them and they would be overridden.

### ucrm.json
This is an auto-generated file, created after plugin is installed in UCRM. It contains prepared configuration, that the plugin can use right away.  
The configuration is automatically refreshed, when changes are made in UCRM settings.

The following options are available:
- `ucrmPublicUrl` - URL under which is UCRM publicly accessible, this will be `null` if the `Server domain name` or `Server IP` options are not configured in UCRM.
- `pluginPublicUrl` - URL under which the `public.php` file is publicly accessible, this will be `null` if the plugin does not have `public.php` file or if the `Server domain name` or `Server IP` options are not configured in UCRM.
- `pluginAppKey` - An App key automatically generated for the plugin (with write permissions), which can be used to access UCRM API.

Example of the `ucrm.json` file:
```json
{
    "ucrmPublicUrl": "http://ucrm.example.com/",
    "pluginPublicUrl": "http://ucrm.example.com/_plugin/dummy-plugin",
    "pluginAppKey": "5YbpCSto7ffl/P/veJ/GK3U7K7zH6ZoHil7j5dorerSN8o+rlJJq6X/uFGZQF2WL"
}
```

### "data" directory
This directory is protected in between updates of the plugin. Anything in this directory will not be touched. All other files will be deleted and new files will appear from the plugin archive.

### data/config.json
Plugin configuration (i.e. plugin's parameters and their values) will be saved to this file. When the UCRM plugin's paramteres are set by the user, this file is regenerated. Values can be modified by the plugin manually but any manual changes to the keys (e.g. removing, modifying or adding new keys) by the plugin will be discarded during any plugin configuration update. As a developer, you don't need to create this file unless you want to set default values for the parameters mentioned in the configuration section of manifest.json file.

### data/plugin.log
Anything this file contains will be displayed as text on plugin detail page in UCRM. You can use this file for logging the plugin's output, error messages, etc.

### .ucrm-plugin-running
This file is used to prevent multiple plugin executions if the previous instance is still running. This measure is used only for manual execution (using the "execute manually" button) and for automatic execution (using cron and execution period). The execution triggered by plugin's public URL is not affected, it can be accessed and run simultaneously by users or webhooks without any limitations.

> Please note, that regardless of execution period chosen by the user, the plugin is never executed if the previous instance did not finish. If the script does not finish in an hour, it will be automatically killed.

### .ucrm-plugin-execution-requested
This file is used to plan execution of the plugin from UCRM frontend, regardless of the chosen execution period.
