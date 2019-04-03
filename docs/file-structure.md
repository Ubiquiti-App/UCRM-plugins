# File structure

## Required files

### manifest.json
This file contains all needed information about the plugin, like name, author and required configuration. See the [manifest documentation](manifest.md) for more information.

### main.php
Main file of the plugin. This is what will be executed when the plugin is run by UCRM.

## Optional files

### public.php
If this file is present, public URL will be generated for the plugin which will point to this file. When the URL is accessed, the file will be parsed as PHP script and executed.

### "public" directory
*Available since UCRM 2.14.0-beta4.*
All files placed in this directory will be publicly accessible without any authentication. It should be used for static assets (e.g. CSS, javascript, images) as the files will be served without any processing.  
> Please note, that the directory will only work if the plugin has `public.php` file (see above).

This directory should be placed in the plugin's root, next to the `public.php` file. Take a look at the following plugins to see example usage:
- Revenue Report
    - https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/revenue-report/src/public
    - https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/plugins/revenue-report/src/templates/form.php#L10
- Invoice CSV export
    - https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/invoice-csv-export/src/public
    - https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/plugins/invoice-csv-export/src/templates/form.php#L10

### hook_*.php files
*Available since UCRM 2.16.0-beta1.*
When the plugin is installed, updated, configured, enabled, disabled or removed, UCRM will automatically call the respective hook file which you can use to execute any custom commands, e.g. run initial config queries, run migrations or setup database etc.

These files should be placed in the plugin's root, next to the `public.php` file.
- `hook_install.php` - called right after the plugin is installed
- `hook_update.php` - called after plugin's version changes
- `hook_configure.php` - called after plugin's configuration is saved
- `hook_enable.php` - called after plugin is enabled
- `hook_disable.php` - called after plugin is disabled
- `hook_remove.php` - called *after* the plugin's data are deleted from UCRM database, but *before* plugin's files are deleted

## Reserved files
These files cannot be contained in the plugin archive as UCRM handles them and they would be overridden.

### ucrm.json
This is an auto-generated file, created after plugin is installed in UCRM. It contains prepared configuration, that the plugin can use right away.  
The configuration is automatically refreshed, when changes are made in UCRM settings.

The following options are available:
- `ucrmPublicUrl` - URL under which UCRM is publicly accessible, this will be `null` if the `Server domain name` or `Server IP` options are not configured in UCRM.
- `ucrmLocalUrl` - URL under which UCRM is locally accessible. This should be used to call UCRM API to prevent issues with self-signed certificates. Available since UCRM 2.14.0-beta3.
- `pluginPublicUrl` - URL under which the `public.php` file is publicly accessible, this will be `null` if the plugin does not have `public.php` file or if the `Server domain name` or `Server IP` options are not configured in UCRM.
- `pluginAppKey` - An App key automatically generated for the plugin (with write permissions), which can be used to access UCRM API.
- `pluginId` - ID of the plugin in UCRM. Available since UCRM 2.15.0-beta6.

Example of the `ucrm.json` file:
```json
{
    "ucrmPublicUrl": "http://ucrm.example.com/",
    "ucrmLocalUrl":"http://localhost/",
    "pluginPublicUrl": "http://ucrm.example.com/_plugin/dummy-plugin",
    "pluginAppKey": "5YbpCSto7ffl/P/veJ/GK3U7K7zH6ZoHil7j5dorerSN8o+rlJJq6X/uFGZQF2WL",
    "pluginId": 1
}
```

### "data" directory
This directory is protected in between updates of the plugin. Anything in this directory will not be touched. All other files will be deleted and new files will appear from the plugin archive.

### data/config.json
Plugin configuration (i.e. plugin's parameters and their values) will be saved to this file. When the UCRM plugin's paramteres are set by the user, this file is regenerated. Values can be modified by the plugin manually but any manual changes to the keys (e.g. removing, modifying or adding new keys) by the plugin will be discarded during any plugin configuration update. As a developer, you don't need to create this file unless you want to set default values for the parameters mentioned in the configuration section of manifest.json file.

### data/plugin.log
Anything this file contains will be displayed as text on plugin detail page in UCRM. You can use this file for logging the plugin's output, error messages, etc.

### data/files directory
Files uploaded with `file` type configuration will be here.

### .ucrm-plugin-running
This file is used to prevent multiple plugin executions if the previous instance is still running. This measure is used only for manual execution (using the "execute manually" button) and for automatic execution (using cron and execution period). The execution triggered by plugin's public URL is not affected, it can be accessed and run simultaneously by users or webhooks without any limitations.

> Please note, that regardless of execution period chosen by the user, the plugin is never executed if the previous instance did not finish. If the script does not finish in an hour, it will be automatically killed.

### .ucrm-plugin-execution-requested
This file is used to plan execution of the plugin from UCRM frontend, regardless of the chosen execution period.
