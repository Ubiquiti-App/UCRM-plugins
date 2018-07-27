# KMZ Map
A [UCRM plugin](https://github.com/Ubiquiti-App/UCRM-plugins) that provides a frontend Google Map for clients to view tower coverage.

_Developed by [Charuwts, LLC](https://charuwts.com)_

When installed onto UCRM the plugin public URL will display a Map and two optional links.

## Instructions

This plugin does not use "Execution Period" or "Execute Manually". It is designed to use the plugin public URL to display a KMZ Map.

If you use a UBNT device you can generate a KMZ file using the airLink simulation https://link.ubnt.com

### Config:
![KMZ Config](https://s3.amazonaws.com/shared-charuwts/images/kmz_map_example_config.png)

- *Maps JavaScript API Key:* Get this from the Google Api Console, make sure to add your domain as an HTTP Referrer.
- *KMZ File URL:* URL to your KMZ file
- *Logo URL:* Displayed next to map on larger screens, above on smaller.
- *Form Description:* Displayed below logo. (Can contain HTML)
- *Link 1:* Display a button on the left. Syntax is title|link Example: Signup Now! | https://www.charuwts.com/plugins
- *Link 2:* Display a button on the right. Syntax is title|link Example: Request for your area! | https://www.charuwts.com/contact

### Screenshot:
![KMZ Config](https://s3.amazonaws.com/shared-charuwts/images/kmz_map_example.png)
