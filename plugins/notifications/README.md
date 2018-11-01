# UCRM Notification Plugin

This is a simple plugin that allows end-users to configure more notifications than the UCRM system currently allows.

All entities and event types currently supported by UCRM Webhooks can now be used to trigger a notification to a 
designated list of recipients.

This plugin is designed specifically for notifications to the company and employees using the UCRM system and is not 
necessarily designed for notifications to the managed Clients.  I believe the UCRM system should handle those 
interactions directly.

## Installation

1. Download the [Plugin](/plugin-notifier.zip) and add it to the System/Plugins in your UCRM.
2. Configure the plugin as noted below and then "Save and Enable".
3. Enable a Webhook pointing to your newly added public URL or use the "Add webhook" button.
4. In the Webhook, set "Any event" to "No" and then multi-select only the events for which you want notifications sent.
5. Your all set!


## Configuration

**Debug Enabled?**

Currently only used internally, but designed to be used to produce more verbose plugin logs.  Typically this can be 
left set to "No".

**Language**

Determines the language to use in the HTML and TEXT email templates when composing the notifications.

*NOTE: Currently only English and Spanish are completed, but anyone interested in contributing translations would be 
most welcome!*

**SMTP Server**

This is the SMTP server that the email notification system will use to send it's emails.

Example: `smtp.example.com`

**SMTP Username**

This is the username used to authenticate requests to the SMTP Server.

Example: `ucrm@example.com`

**SMTP Password**

This is the password used to authenticate requests to the SMTP Server.

Example: `password`

**SMTP Encryption**

This is the encryption mechanism to use when attempting to authentication with the SMTP Server.

Example: `None, TLS, SSL`

**SMTP Port**

The SMTP port to which messages should be sent.

Example: `25, 587, 465`

**Sender Name**

The name of the sender (will also be used as the 'Reply To' name).

Example: `Ryan Spaeth`

**Sender Email**

The email of the sender (will also be used as the 'Reply To' name).

Example: `rspaeth@mvqn.net`

**Use HTML?**

If enabled, will attempt to send messages in HTML format.

Recommended: `Yes`

**Client Leads Only?**

If enabled, will only respond to Client events that belong to a Lead and ignore regular Client events.

Recommended: `Yes`

*NOTE: This is an option, only because the Webhook system does not give finer control over the type of Client events 
to send.*

**Client Recipients**

A comma separated list of email addresses to which Client notifications should be sent.

Example: `rspaeth@mvqn.net`


## Features

#### Notifications
Automatically compose and send an email notification upon the occurrence of any of the following events:
- Client.add (html/text)

#### Customization
Allow for the customization of both HTML and TEXT templates to suit individual needs.

*NOTE: Currently requires manual editing of the Twig templates in the `twig/` folder.*

#### Upcoming
Add support for the remaining entities and event types:
- **Client** (archive, delete, edit invite)
- **Invoice** (add, add_draft, delete, edit, near_due, overdue)
- **Payment** (add, delete, edit, unmatch)
- **Quote** (add, delete, edit)
- **Service** (add, archive, edit, end, postpone, suspend, suspend_cancel)
- **Ticket** (add, comment, delete, edit, status_change)
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
Another plugin module that includes numerous helper class/methods for developing UCRM Plugins.

[mvqn/rest-ucrm](https://github.com/mvqn/rest-ucrm)\
Another plugin module used to simplify access to the UCRM REST API.

### Submitting bugs and feature requests
Bugs and feature request are tracked on [Github](https://github.com/mvqn-ucrm/plugin-notifier/issues)

### Author
Ryan Spaeth <[rspaeth@mvqn.net](mailto:rspaeth@mvqn.net)>

### License
This module is licensed under the MIT License - see the `LICENSE` file for details.

### Acknowledgements
Credit to the Ubiquiti Team for giving us the luxury of Plugins!