# Invoice QR code

This plugin allows you to create invoices with QR codes containing payment information. It will help your customers pay you easily.

# Configuration

This plugin requires you to manually extend your invoice template and add `<img>` tag pointing to public URL of this plugin.

1. Go to `System > Plugins > Invoice QR code > See details` and copy `Public URL` which should look like `https://ucrm.example.com/_plugins/invoice-csv-export/public.php`.
2. Go to `System > Customization > Invoices`, find your current template and click on the `Edit` button. If you haven't extended the template yet you have to clone the `Default` template using the `Clone` button.
3. Place the `<img src="pluginsPublicUrlPlaceholder?organizationCountry={{ organization.country }}&organizationName={{ organization.name }}&bankAccount={{ organization.bankAccount }}&invoiceNumber={{ invoice.number }}&amountDue={{ totals.amountDue }}&dueDate={{ invoice.dueDate }}">` tag anywhere you want in your template. Don't forget to replace `pluginsPublicUrlPlaceholder` with the URL you have copied in the first step.

It's necessary to enable showing of the bank account on invoices. Otherwise, the plugin will not be working at all. You can do that under `System > Organizations > Your Company, Inc. > Edit > Invoice template` where you can find `Include bank account` toggle.
