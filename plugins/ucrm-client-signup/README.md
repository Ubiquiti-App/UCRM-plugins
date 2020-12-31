# UCRM Client Signup
A [UCRM plugin](https://github.com/Ubiquiti-App/UCRM-plugins) that provides a frontend form for clients to signup.

_Developed by [Charuwts, LLC](https://charuwts.com)_

When installed onto UCRM the plugin public URL will display a form that anyone can enter valid info into to create a client or lead.

#### Note, only compatable with 2.14.0 and beyond

## Instructions

This plugin does not use "Execution Period" or "Execute Manually". It is designed to use the plugin public URL to post from a form and create a new Client.

There are optional params for a custom Logo, Title, Form Description and Completion Text.

- *Form Title:* Displayed below logo before form description
- *Logo URL:* Displayed above form
- *Form Description:* Displayed below logo before the form. (Can contain HTML)
- *Completion Text:* Text shown upon signup completion. (Text Only)
- *Create Lead:* Select "Yes" to create a lead instead of a client.

Further information can be found at the [Charuwts Wiki](https://github.com/charuwts/UCRM-Client-Signup/wiki).


## Simplified release

This is a simplified release of a more feature rich plugin planned for creating services for the client as well and automatically charging for invoices that are due using Stripe. More gateway integrations to come! Full Release can be found at https://www.charuwts.com/plugins

For more functionality, share your support by voting on these feature requests on the UCRM forum. And by making your interest known on the [discussion thread](https://community.ubnt.com/t5/UCRM/New-Plugin-Discussion-UCRM-Public-Client-Signup/m-p/2394250#M9593).

## Features in UCRM that will help make this plugin do more for you
- [Customizable Plugin Public Url](https://community.ubnt.com/t5/UCRM-Feature-Requests/Customizable-Plugin-Public-URL/idi-p/2388893)

## Form is built with Ember.js
[UCRM Client Signup Form](https://github.com/charuwts/UCRM-Client-Signup-Form)

## Tests

After running `composer update` tests can be run from within /src by running ./vendor/bin/phpunit New tests should be added for any new features and any bugs that get resolved.
luis
