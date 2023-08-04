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
* Select events about which to notify clients and save the webhook endpoint

## Usage
* In UCRM admin, go to System / Webhooks / Endpoints
* Click Test Endpoint
* Go to System / Plugins / SMS notifications via Twilio
* In the log output, you'll see `Webhook test successful.`

## Variables replaced

These are loaded from UCRM API, and reflect the structure returned.
Client variables are replaced always; payment invoice and service only with the applicable events.  

### Client variables

* `%%client.id%%` => 20
* `%%client.userIdent%%` => '18'
* `%%client.previousIsp%%` => ''
* `%%client.isLead%%` => false
* `%%client.clientType%%` => 1
* `%%client.companyName%%` => ''
* `%%client.companyRegistrationNumber%%` => ''
* `%%client.companyTaxId%%` => ''
* `%%client.companyWebsite%%` => ''
* `%%client.street1%%` => '2544 Hillview Drive'
* `%%client.street2%%` => ''
* `%%client.city%%` => 'San Jose'
* `%%client.countryId%%` => 249
* `%%client.stateId%%` => 5
* `%%client.zipCode%%` => '95113'
* `%%client.invoiceStreet1%%` => ''
* `%%client.invoiceStreet2%%` => ''
* `%%client.invoiceCity%%` => ''
* `%%client.invoiceStateId%%` => ''
* `%%client.invoiceCountryId%%` => ''
* `%%client.invoiceZipCode%%` => ''
* `%%client.invoiceAddressSameAsContact%%` => true
* `%%client.note%%` => ''
* `%%client.sendInvoiceByPost%%` => false
* `%%client.invoiceMaturityDays%%` => 14
* `%%client.stopServiceDue%%` => true
* `%%client.stopServiceDueDays%%` => 7
* `%%client.organizationId%%` => 1
* `%%client.tax1Id%%` => 1
* `%%client.tax2Id%%` => ''
* `%%client.tax3Id%%` => ''
* `%%client.registrationDate%%` => '2016-04-26 00:00'
* `%%client.companyContactFirstName%%` => ''
* `%%client.companyContactLastName%%` => ''
* `%%client.isActive%%` => false
* `%%client.firstName%%` => 'Tyson'
* `%%client.lastName%%` => 'Doe'
* `%%client.username%%` => 'tyson.doe@example.com'
* `%%client.accountBalance%%` => 0
* `%%client.accountCredit%%` => 0
* `%%client.accountOutstanding%%` => 0
* `%%client.currencyCode%%` => 'USD'
* `%%client.organizationName%%` => 'UBNT ISP'
* `%%client.invitationEmailSentDate%%` => ''
* `%%client.avatarColor%%` => '#e53935'
* `%%client.addressGpsLat%%` => 37.401482000001
* `%%client.addressGpsLon%%` => -121.966545
* `%%client.message%%` => 'This is an example message sent from the Messaging feature.'

### Invoice variables
* `%%invoice.id%%` => 4
* `%%invoice.clientId%%` => 20
* `%%invoice.number%%` => '2016050002'
* `%%invoice.createdDate%%` => '2016-05-03 00:00'
* `%%invoice.dueDate%%` => '2016-05-17 00:00'
* `%%invoice.emailSentDate%%` => '2018-08-24 00:00'
* `%%invoice.maturityDays%%` => 14
* `%%invoice.notes%%` => ''
* `%%invoice.adminNotes%%` => ''
* `%%invoice.subtotal%%` => 7.88
* `%%invoice.discount%%` => ''
* `%%invoice.discountLabel%%` => ''
* `%%invoice.total%%` => 7.88
* `%%invoice.amountPaid%%` => 7.88
* `%%invoice.currencyCode%%` => 'USD'
* `%%invoice.status%%` => 3
* `%%invoice.invoiceTemplateId%%` => 1
* `%%invoice.organizationName%%` => 'UBNT ISP'
* `%%invoice.organizationRegistrationNumber%%` => ''
* `%%invoice.organizationTaxId%%` => ''
* `%%invoice.organizationStreet1%%` => '2580 Orchard Parkway'
* `%%invoice.organizationStreet2%%` => ''
* `%%invoice.organizationCity%%` => 'New York'
* `%%invoice.organizationStateId%%` => 1
* `%%invoice.organizationCountryId%%` => 249
* `%%invoice.organizationZipCode%%` => '10017'
* `%%invoice.organizationBankAccountName%%` => ''
* `%%invoice.organizationBankAccountField1%%` => ''
* `%%invoice.organizationBankAccountField2%%` => ''
* `%%invoice.clientFirstName%%` => 'Tyson'
* `%%invoice.clientLastName%%` => 'Doe'
* `%%invoice.clientCompanyName%%` => ''
* `%%invoice.clientCompanyRegistrationNumber%%` => ''
* `%%invoice.clientCompanyTaxId%%` => ''
* `%%invoice.clientStreet1%%` => '685 Third Avenue'
* `%%invoice.clientStreet2%%` => ''
* `%%invoice.clientCity%%` => 'New York'
* `%%invoice.clientCountryId%%` => 249
* `%%invoice.clientStateId%%` => 5
* `%%invoice.clientZipCode%%` => '10017'
* `%%invoice.uncollectible%%` => false

### Payment variables 
* `%%payment.id%%` => 28
* `%%payment.clientId%%` => 20
* `%%payment.invoiceId%%` => ''
* `%%payment.method%%` => 2
* `%%payment.checkNumber%%` => ''
* `%%payment.createdDate%%` => '2018-08-24 11:36'
* `%%payment.amount%%` => 1
* `%%payment.currencyCode%%` => 'USD'
* `%%payment.note%%` => ''
* `%%payment.receiptSentDate%%` => ''
* `%%payment.providerName%%` => ''
* `%%payment.providerPaymentId%%` => ''
* `%%payment.providerPaymentTime%%` => ''
* `%%payment.creditAmount%%` => 0
* `%%payment.applyToInvoicesAutomatically%%` => false

### Service variables
* `%%service.id%%` => 23
* `%%service.clientId%%` => 20
* `%%service.status%%` => 1
* `%%service.name%%` => 'Mini'
* `%%service.street1%%` => '622 Hide A Way Road'
* `%%service.street2%%` => ''
* `%%service.city%%` => 'San Jose'
* `%%service.countryId%%` => 249
* `%%service.stateId%%` => 5
* `%%service.zipCode%%` => '95135'
* `%%service.note%%` => ''
* `%%service.addressGpsLat%%` => 37.232849
* `%%service.addressGpsLon%%` => -121.752502
* `%%service.servicePlanId%%` => 1
* `%%service.servicePlanPeriodId%%` => 2
* `%%service.price%%` => 25
* `%%service.hasIndividualPrice%%` => false
* `%%service.totalPrice%%` => 25
* `%%service.currencyCode%%` => 'USD'
* `%%service.invoiceLabel%%` => ''
* `%%service.contractId%%` => ''
* `%%service.contractLengthType%%` => 1
* `%%service.minimumContractLengthMonths%%` => ''
* `%%service.activeFrom%%` => '2016-05-03T00:00:00+0000'
* `%%service.activeTo%%` => ''
* `%%service.contractEndDate%%` => ''
* `%%service.discountType%%` => 0
* `%%service.discountValue%%` => ''
* `%%service.discountInvoiceLabel%%` => ''
* `%%service.discountFrom%%` => ''
* `%%service.discountTo%%` => ''
* `%%service.tax1Id%%` => ''
* `%%service.tax2Id%%` => ''
* `%%service.tax3Id%%` => ''
* `%%service.invoicingStart%%` => '2016-05-03T00:00:00+0000'
* `%%service.invoicingPeriodType%%` => 1
* `%%service.invoicingPeriodStartDay%%` => 1
* `%%service.nextInvoicingDayAdjustment%%` => 0
* `%%service.invoicingProratedSeparately%%` => true
* `%%service.invoicingSeparately%%` => false
* `%%service.sendEmailsAutomatically%%` => false
* `%%service.useCreditAutomatically%%` => true
* `%%service.servicePlanName%%` => 'Mini'
* `%%service.servicePlanPrice%%` => 25
* `%%service.servicePlanPeriod%%` => 3
* `%%service.downloadSpeed%%` => 10
* `%%service.uploadSpeed%%` => 10
* `%%service.hasOutage%%` => true
* `%%service.stopReason%%` => 'Payments overdue'


## Developers
* This plugin is MIT-licensed and can be used by developers as a template for integrating with a different messaging solution:
  * Create a new plugin based on this one
  * Replace the TwilioNotifierFacade and any references to it with a different class which extends AbstractMessageNotifierFacade
  * Update libraries in composer.json as needed
  * Communicate with the remote system in the sendMessage() function
  * Preferably also change the SmsNotifier namespace to some other (not strictly necessary).

Read more about creating your own plugin in the [Developer documentation](https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/docs/index.md).

