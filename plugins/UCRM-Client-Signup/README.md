# UCRM Client Signup
A [UCRM plugin](https://github.com/Ubiquiti-App/UCRM-plugins) that provides a frontend form for clients to signup.

_Developed by [Charuwts, LLC](https://charuwts.com)_

When installed onto UCRM the plugin public URL will display a form that anyone can enter valid info into to create a client.

#### *_Not ready for Production._*

_Primarily because there hasn't been many unit tests. So technically if you want to use it in production it will work. But as a best practice in coding and for the sake maintainability, "Programmatically Testing" the code is strongly recommended. So this is a disclaimer in light of that having not been done yet._


## Instructions

This plugin does not use "Execution Period" or "Execute Manually". It is designed to use the plugin public URL to post from a form and create a new Client.

There are optional params for a custom Logo, Title, Form Description and Completion Text.

- *Form Title:* Displayed below logo before form description
- *Logo URL:* Displayed above form. Max width 400px
- *Form Description:* Displayed below logo before the form. (Can contain HTML)
- *Completion Text:* Text shown upon signup completion. (Text Only) - Defaults To: Thank you for signing up! You will receive an invitation to access your account upon approval.

Further information can be found at the [Charuwts Wiki](https://github.com/charuwts/UCRM-Client-Signup/wiki).


## Simplified release

This is a simplified release of a more feature rich plugin planned for creating services for the client as well and automatically charging for invoices that are due using Stripe. More gateway integrations to come! Full Release can be found at https://www.charuwts.com/plugins

For more functionality, share your support by voting on these feature requests on the UCRM forum. And by making your interest known on the [discussion thread](https://community.ubnt.com/t5/UCRM/New-Plugin-Discussion-UCRM-Public-Client-Signup/m-p/2394250#M9593).

## Features in UCRM that will help make this plugin do more for you
- [Customizable Plugin Public Url](https://community.ubnt.com/t5/UCRM-Feature-Requests/Customizable-Plugin-Public-URL/idi-p/2388893)
- [Creating Subscriptions via API](https://community.ubnt.com/t5/UCRM-Feature-Requests/Creating-Subscriptions-via-API/idi-p/2342937)
- [API Linked Subscriptions](https://community.ubnt.com/t5/UCRM-Feature-Requests/API-Linked-Subscriptions/idc-p/2341614#M1150)

## Form is built with Ember.js
[UCRM Client Signup Form](https://github.com/charuwts/UCRM-Client-Signup-Form)