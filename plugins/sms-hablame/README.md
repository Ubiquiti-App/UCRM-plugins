# SMS Notificaciones - Hablame Colombia

* Este complemento envía notificaciones por SMS a los clientes.
* SMS se desencadena por un evento que ocurrió en UCRM, p. se emitió una nueva factura o se suspendió el servicio del cliente.
* Solo envía SMS a clientes que tienen un número de teléfono establecido en los detalles de sus contactos.
* Se requiere una cuenta [Hablame] (https://www.hablame.co/) para acceder a su API.

# Configuration

* Instale el complemento en UCRM y habilítelo. Es decir. descargue el repositorio completo y cárguelo en UCRM en Sistema> Complementos.
* Mantenga el período de ejecución en "no ejecutar automáticamente": el complemento reaccionará a los eventos de webhook.
* Configure con los datos que obtiene de [Hablame] (https://www.hablame.co):
* Cuenta SID
* Token de autenticación
   
* Personaliza los textos que deseas enviar a un cliente cuando ocurre un evento
* Cada evento tiene su propia fila
* La fila vacía significa "no enviar SMS para esto"
* Es posible reemplazar variables predefinidas: `%% some.variable %%`, vea la lista completa a continuación
* Si no se establece una variable para un cliente, se reemplaza con una cadena vacía
* Guardar la configuración
* Habilitar el complemento
* Agregar webhook (botón al lado de URL pública)
* Guardar webhook usando los valores predeterminados
  * Opcionalmente, seleccione eventos sobre los cuales notificar a los clientes

# Uso
* En el administrador de UCRM, vaya a Sistema / Webhooks / Endpoints
* Haga clic en Test Endpoint
* Vaya a Sistema / Complementos / notificaciones por SMS a través de Hablame
* En la salida del registro, verá `Prueba de Webhook exitosa`.

# Variables reemplazadas

Estos se cargan desde la API UCRM y reflejan la estructura devuelta.
Las variables del cliente se reemplazan siempre; factura de pago y servicio solo con los eventos aplicables.

### Variables de cliente

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


