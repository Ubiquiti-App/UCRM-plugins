# Invoice QR code

This plugin allows you to create invoices with QR codes containing payment information. It will help your customers pay you easily.

# Configuration

This plugin requires you to manually extend your invoice template and add `<img>` tag pointing to public URL of this plugin.

1. go to `System > Plugins`
2. find `Invoice QR code` plugin and open `See details` link
3. copy `Public URL` - it looks like `https://ucrm.example.com/_plugins/invoice-csv-export/public.php`  
4. go to `System > Customization > Invoices`
5. find your current template - it should be called `Default` if you haven't changed your invoice template yet
6. click `Clone` icon on the right side and place
7. new template will be created and you can place `<img src="https://ucrm.example.com/_plugins/invoice-csv-export/public.php?invoiceId={{ invoice.number }}">` anywhere you want in your template 
