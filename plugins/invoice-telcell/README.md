# SMS notifier - Twilio

* This plugin sends SMS notifications to clients.
* SMS is triggered by an event which happened in UCRM, e.g. new invoice has been issued, or client's sevice became suspended.
* It only sends SMS to clients having a phone number set in their contacts details.
* [Twilio](https://www.twilio.com/) account is required to access its API.

## Configuration

* Install the plugin into UCRM and enable it. I.e. download the plugin [zip file](https://github.com/Ubiquiti-App/UCRM-plugins/raw/master/plugins/sms-twilio/sms-twilio.zip) and upload it to UCRM in System > Plugins.
* Keep execution period at "don't execute automatically" - the plugin will react to webhook events.
* Set up with data which you obtain from [Twilio Console](https://twilio.com/console):
   * Account SID
   * Auth Token
   * SMS number to send from
    
Note: there are two sets of credentials available, the default ("LIVE credentials") for actual use and [test credentials](https://www.twilio.com/console/project/settings) for development.
   
* Customize the texts you wish to send to a client when an event happens
   * Each event has its own row
   * Empty row means "do not send SMS for this"
   * It is possible to replace predefined variables: `%%some.variable%%`, see full list below
   * If a variable is not set for a client, it is replaced with an empty string
* Save the configuration
* Enable the plugin
* Add webhook (button next to Public URL)
* Save webhook using defaults
  * Optionally select events about which to notify clients

## Usage
* In UCRM admin, go to System / Webhooks / Endpoints
* Click Test Endpoint
* Go to System / Plugins / SMS notifications via Twilio
* In the log output, you'll see `Webhook test successful.`

## Variables replaced

These are loaded from UCRM API, and reflect the structure returned.
Client variables are replaced always; payment invoice and service only with the applicable events.  


## Developers
* This plugin is MIT-licensed and can be used by developers as a template for integrating with a different messaging solution:
  * Create a new plugin based on this one
  * Replace the TwilioNotifierFacade and any references to it with a different class which extends AbstractMessageNotifierFacade
  * Update libraries in composer.json as needed
  * Communicate with the remote system in the sendMessage() function
  * Preferably also change the SmsNotifier namespace to some other (not strictly necessary).

Read more about creating your own plugin in the [Developer documentation](https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/docs/index.md).

