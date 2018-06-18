# UCRM Client Signup
A [UCRM plugin](https://github.com/Ubiquiti-App/UCRM-plugins) that provides a frontend form for clients to signup.

_Developed by [Charuwts, LLC](https://charuwts.com)_

When installed onto UCRM the plugin public URL will display a form that anyone can enter valid info into to create a client.

## Instructions

_*Not ready for Production.*_

This plugin does not use "Execution Period" or "Execute Manually". It is designed to use the plugin public URL to post from a form and create a new Client.

There are optional params for a custom Logo, Title, Form Description and Completion Text.

*Form Title:* Displayed below logo before form description
*Logo URL:* Displayed above form. Max width 400px
*Form Description:* Displayed below logo before the form. (Can contain HTML)
*Completion Text:* Text shown upon signup completion. (Text Only) - Defaults To: Thank you for signing up! You will receive an invitation to access your account upon approval.


## Simplified release

This is a simplified release of a more feature rich plugin planned for subscribing clients using the signup process via Stripe. Which will include charging the client immediately and creating their service in UCRM. Or even having an installation fee and delay creation of the service via a Stripe Subscription trial period, that once ended, creates the UCRM service.

For more functionality, share your support by voting on these feature requests on the UCRM forum. And by making your interest known on the discussion thread.

## Features in UCRM that will help make this plugin do more for you
- [Creating Subscriptions via API](https://community.ubnt.com/t5/UCRM-Feature-Requests/Creating-Subscriptions-via-API/idi-p/2342937)
- [API Linked Subscriptions](https://community.ubnt.com/t5/UCRM-Feature-Requests/API-Linked-Subscriptions/idc-p/2341614#M1150)
- [Customizable Plugin Public Url](https://community.ubnt.com/t5/UCRM-Feature-Requests/Customizable-Plugin-Public-URL/idi-p/2388893)

## Form is built with Ember.js
[UCRM Client Signup Form](https://github.com/charuwts/UCRM-Client-Signup-Form)