# UCRM Notification Plugin

This is a simple plugin that allows end-users to configure more notifications than the UCRM system currently allows.

All entities and event types currently supported by UCRM Webhooks can now be used to trigger a notification to a 
designated list of recipients.

This plugin is designed specifically for notifications to the company and employees using the UCRM system and is not 
necessarily designed for notifications to the managed Clients.  I believe the UCRM system should handle those 
interactions directly.

## Installation

1. Download the [Plugin](https://github.com/mvqn/ucrm-plugins/raw/master/plugins/notifications/notifications.zip) and 
add it to the System/Plugins in your UCRM.
2. Configure the plugin as noted below and then "Save and Enable".
3. Add a Webhook pointing to your newly added public URL or use the "Add webhook" button on the plugin page in newer 
versions of UCRM.
4. In the Webhook, either leave "Any event" set to "Yes" for all event types, or set it to "No" and then multi-select 
only the events for which you want notifications sent.  See currently "Supported Events" below.
5. Your all set!

##### NOTES
- Notification links will be build using settings in the following order:
    - Settings: Server Domain Name
    - Settings: Server IP
    - "http://localhost"
- A static Google Maps image will be embedded in HTML notifications only when a Google Maps API Key is set in the UCRM.
- If SSL is not enabled on your UCRM system, make sure to set "Verify SSL Certificate" to "No" in the Webhook 
Settings.  My recommendation is to leave the setting to "No" always, as the plugin is designed communicating with the
 server using 'localhost' and should not pose a security risk. 

## Configuration

**Use HTML?**

If enabled, will attempt to send messages in HTML format, but will fall-back to TEXT when not supported.

Recommended: `Yes`

**Client Types**

Determines the type of Client events to handle, as the webhook system currently does not allow you us to receive 
events specific to Clients or Leads.

The options are as follows:

- "Clients & Leads": Client events set in the webhook will be received "as is" and notifications for both 
Clients and Leads will be sent.

- "Clients Only": Client events set in the webhook will be received, checked to exclude Leads and 
notifications for only Clients will be sent.

- "Leads Only": Client events set in the webhook will be received, checked to exclude Clients and 
notifications for only Leads will be sent.

Recommended: `Leads Only`

**Client Recipients**

A comma separated list of email addresses to which Client notifications should be sent.

Example: `rspaeth@mvqn.net`

**Invoice Recipients**

A comma separated list of email addresses to which Invoice notifications should be sent.

Example: `rspaeth@mvqn.net`

**Payment Recipients**

A comma separated list of email addresses to which Payment notifications should be sent.

Example: `rspaeth@mvqn.net`

**Quote Recipients**

A comma separated list of email addresses to which Quote notifications should be sent.

Example: `rspaeth@mvqn.net`

**Service Recipients**

A comma separated list of email addresses to which Service notifications should be sent.

Example: `rspaeth@mvqn.net`

**Ticket Recipients**

A comma separated list of email addresses to which Ticket notifications should be sent.

Example: `rspaeth@mvqn.net`

**User Recipients**

A comma separated list of email addresses to which User notifications should be sent.

Example: `rspaeth@mvqn.net`

**Webhook Recipients**

A comma separated list of email addresses to which Webhook notifications should be sent.

Example: `rspaeth@mvqn.net`

## Features

#### Supported Events
Automatically compose and send an email notification upon the occurrence of any of the following events:
- Client (add, archive, ~~delete~~, edit, invite) [html/text]
- Ticket (add, comment, ~~delete~~, edit, status_change) [html/text]

#### Customization
Allow for the customization of both HTML and TEXT templates to suit individual needs.

*NOTE: Currently requires manual editing of the Twig templates in the `twig/` folder.*

#### Upcoming
Add support for the remaining entities and event types:
- **Invoice** (add, add_draft, delete, edit, near_due, overdue)
- **Payment** (add, delete, edit, unmatch)
- **Quote** (add, delete, edit)
- **Service** (add, archive, edit, end, postpone, suspend, suspend_cancel)
- **User** (reset_password)
- **Webhook** (test)

## About

### Requirements
- This package will be maintained in step with the PHP version used by UCRM to ensure 100% compatibility.
- Any packages required that are not already enabled in the default UCRM installation are included with this Plugin 
in the accompanying `vendor/` folder and can be updated and maintained manually using
[composer](https://getcomposer.org/) if desired.

### Related Packages
[mvqn-ucrm/plugins](https://github.com/mvqn-ucrm/plugins)\
A plugin module that includes numerous helper class/methods for developing UCRM Plugins.

[mvqn-ucrm/rest](https://github.com/mvqn-ucrm/rest)\
A plugin module used to simplify access to the UCRM REST API.

[mvqn-ucrm/data](https://github.com/mvqn-ucrm/data)\
A plugin module used to simplify access to the UCRM Database.

[mvqn/localization](https://github.com/mvqn/localization)\
A plugin module for localization.

### Submitting bugs and feature requests
Bugs and feature request are tracked on [Github](https://github.com/mvqn/ucrm-plugins/issues)

### Author
Ryan Spaeth <[rspaeth@mvqn.net](mailto:rspaeth@mvqn.net)>

### License
This module is licensed under the MIT License - see the `LICENSE` file for details.

### Acknowledgements
Credit to the Ubiquiti Team for giving us the luxury of Plugins!