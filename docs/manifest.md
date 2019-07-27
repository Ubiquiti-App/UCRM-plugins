# Plugin manifest
The `manifest.json` file contains all needed information about the plugin and is required by CRM.

## Example
```json
{
    "version": "1",
    "information": {
        "name": "ubnt__dummy-plugin",
        "displayName": "Dummy Plugin - documentation example",
        "description": "This plugin does nothing at all and is used only as a documentation example.",
        "url": "https://github.com/Ubiquiti-App/UCRM-plugins/docs/manifest.md",
        "version": "1.0.0",
        "ucrmVersionCompliancy": {
            "min": "2.13.0-beta1",
            "max": null
        },
        "unmsVersionCompliancy": {
            "min": "1.0.0-alpha.1",
            "max": null
        },
        "author": "Ubiquiti Networks, Inc."
    },
    "configuration": [
        {
            "key": "requiredConfigurationField",
            "label": "Configuration field required by the plugin",
            "description": "Please provide information required by this field.",
            "required": 1
        },
        {
            "key": "optionalConfigurationField",
            "label": "An optional and not important configuration field",
            "required": 0
        },
        {
            "key": "welcomeText",
            "label": "Welcome text",
            "description": "This can be very long text with new lines.",
            "required": 0,
            "type": "textarea"
        },
        {
            "key": "paymentMatchAttribute",
            "label": "Match by attribute",
            "description": "Choose by which attribute will the payments be matched.",
            "required": 1,
            "type": "choice",
            "choices": {
              "Invoice number": "invoiceNumber",
              "Client ID": "clientId",
              "Client User Ident": "clientUserIdent",
              "This is label": "thisIsValue"
            }
        },
        {
            "key": "startDate",
            "label": "Start date",
            "required": 0,
            "type": "date"
        },
        {
            "key": "startDateTime",
            "label": "Start date with time",
            "required": 0,
            "type": "datetime"
        },
        {
            "key": "isBoolean",
            "label": "Check this if you want it to be true.",
            "required": 0,
            "type": "checkbox"
        },
    ],
    "menu": [
        {
            "key": "Reports",
            "label": "Dummy Plugin",
            "type": "admin",
            "target": "iframe",
            "parameters": {
                "hook": "main"
            }
        }
    ],
    "widgets": [
        {
            "position": "dashboard",
            "iframeHeight": 300,
            "iframeUrlParameters": {
                "foo": "bar",
                "foo2": "bar2"
            }
        },
        {
            "position": "client/overview",
            "iframeHeight": 300,
            "iframeUrlParameters": {
                "hook": "client"
            }
        }
    ],
    "paymentButton": {
        "label": "My Gateway",
        "urlParameters": {
            "foo": "bar"
        }
    }
}
```

## Structure

### version _(required)_
Determines version of the configuration file, for now the only possible value is "1".

### information _(required)_
Contains information describing the plugin.
- `name` - lowercase name of the plugin (can contain alphanumeric characters, dashes `-` and underscores `_`), plugin folder name is determined by this
- `displayName` - name of the plugin as displayed on CRM frontend
- `description` - longer description of the plugin (e.g. what it does)
- `url` - link to the plugin page
- `version` - version of the plugin as displayed on CRM frontend
- `ucrmVersionCompliancy` - defines minimum and maximum version of UCRM this plugin supports, minimum must be always defined, maximum can be `null`
- `unmsVersionCompliancy` - works the same as `ucrmVersionCompliancy` and is required for plugins to work since UNMS 1.0 (integrated version)
    - If you want to support both UCRM 2.x and UNMS 1.x, you can include both `ucrmVersionCompliancy` and `unmsVersionCompliancy` in your manifest. If you want to support only UNMS 1.0 (integrated version), do **not** include `ucrmVersionCompliancy` in your manifest.
- `author` - author of the plugin as displayed on CRM frontend

### configuration
Determines configuration keys of the plugin. Frontend configuration form is generated from this and the values are then saved to [`data/config.json`](file-structure.md#dataconfigjson) file.

Contains an array of items. Each item is defined as follows:
- `key` - property key
- `label` - label of the property as displayed in CRM
- `description` (optional) - description of the property, displayed under the form input in CRM
- `required` (optional, default: `1`) - whether the property is required or optional, configuration cannot be saved without required properties
- `type` (optional, default: `text`) - type of the configuration item, will render appropriate form input in CRM
    - available as of UCRM 2.13.0-beta1
    - possible values are:
        - `text` - standard text input
        - `textarea` - multi-line text
        - `checkbox` - true/false values
        - `choice` - dropdown list with pre-defined options (needs `choices` definition, see below)
        - `date` - date input with calendar
        - `datetime` - date and time input with calendar
        - `file` - file upload input (the file will have name based on the `key` definition, the filename will be saved in [`data/config.json`](file-structure.md#dataconfigjson) and the file itself will be saved in [`data/files`](file-structure.md#datafiles-directory) directory)
- `choices` (optional) - defines possible options for `choice` type (see manifest example above)

### menu
*Note: This feature is available since UCRM 2.14.0-beta1*

Adds link(s) to the plugin into CRM menu.

Contains an array of items. Each item is defined as follows:
- `type` - required, can have these values:
  - `"admin"` - the link will show in admin zone
  - `"client"` - the link will show in client zone
- `target` - required, can have these values:
  - `"blank"` - The link will lead simply to the target page
  - `"iframe"` - The link will lead to special page within CRM which will show the target page in an iframe
- `key` - Menu category to insert the link into (optional) <sup>[1]</sup>
- `label` - label of the link (optional, default value is plugin name)
- `parameters` - array of parameters for the link (optional) <sup>[2]</sup>

<sup>1. If `type` is `"admin"` then `"Billing"`, `"Network"`, `"Reports"` or `"System"` can be used to add the link under these existing categories in CRM menu. In other cases a new item will be added to the menu.</sup>  
<sup>2. For example if `parameters` are `{"hook": "main"},` then link is `/_plugins/<plugin-name>/public.php?hook=main`.</sup>

*Note: The target pages should typically be protected to be available only to authorized clients or admins. Read [this](security.md)  for details.*

### widgets
*Note: This feature is available since UNMS 1.0.0-beta7*

Adds iframe widgets showing the plugin's public page to specified positions.

Contains an array of items. Each item is defined as follows:
- `position` - required, determines where to display the widget, can have these values:
  - `"dashboard"` - CRM dashboard
  - `"client/overview"` - client detail, adds `_clientId` parameter to iframe URL
  - `"client/service"` - client service detail, adds `_serviceId` parameter to iframe URL
  - `"client-zone/dashboard"` - Client Zone - dashboard, adds `_clientId` parameter to iframe URL
  - `"client-zone/service"` - Client Zone - service detail, adds `_serviceId` parameter to iframe URL
- `iframeHeight` - required, height of the widget iframe in px
- `iframeUrlParameters` - array of parameters for the link (optional) <sup>[1]</sup>

<sup>1. See `parameters` in the [`menu section`](manifest.md#menu)</sup>

*Note: The target pages should typically be protected to be available only to authorized clients or admins. Read [this](security.md)  for details.*

### paymentButton
*Note: This feature is available since UNMS 1.0.0-beta7*

Add payment button to one-time online payment page.
- `label` - required, label of the payment button <sup>[1]</sup>
- `urlParameters` - array of parameters for the link (optional) <sup>[2]</sup>
    - parameter `_token` is always present, you can use `GET /payment-tokens/{token}` API call to retrieve all needed information from the token

<sup>1. Visible only if there are multiple payment gateways and prefixed with "Pay with". For example for label `My Gateway`, the final label would be `Pay with My Gateway`. In case of single payment gateway, the label will be `Pay online`.</sup>  
<sup>2. See `parameters` in the [`menu section`](manifest.md#menu)</sup>
