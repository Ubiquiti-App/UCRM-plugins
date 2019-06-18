# Security
*Note: This feature is available since UCRM 2.14.0-beta1.*

The `public.php` file can be used to provide a customized public page or even more of them if you decide to use `$_GET` parameters with some routing.

However you might want to restrict some or all of these pages to only be available to the admins of the UCRM installation and/or to the clients. Since the plugin is running on the same domain as UCRM itself, it will also receive the UCRM cookies. In this case you can use the `PHPSESSID` cookie to ask UCRM about the current session. This is done by calling the `/current-user` URL and forwarding the `PHPSESSID` cookie.

> *Note that, the URL and the required cookies changed in UNMS 1.0 (integrated version).*  
> The new URL is `/crm/current-user`.  
> The required cookies are `nms-crm-php-session-id` and `nms-session`.  
> You can use [UCRM Plugin SDK](https://github.com/Ubiquiti-App/UCRM-Plugin-SDK), which supports the new way since version 0.5.1.

You can use the received information to:
- determine if the user can access the page
- check if they should be somehow limited (for example that they only have read access)
- show only information relevant for that user (for example only show services for the current client)

Here is an example function using CURL:

```php
function retrieveCurrentUser(string $ucrmPublicUrl): array
{
    $url = sprintf('%scurrent-user', $ucrmPublicUrl);

    $headers = [
        'Content-Type: application/json',
        'Cookie: PHPSESSID=' . preg_replace('~[^a-zA-Z0-9]~', '', $_COOKIE['PHPSESSID'] ?? ''),
    ];

    return curlQuery($url, $headers);
}

function curlQuery(string $url, array $headers = [], array $parameters = []): array
{
    if ($parameters) {
        $url .= '?' . http_build_query($parameters);
    }

    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);

    $result = curl_exec($c);

    $error = curl_error($c);
    $errno = curl_errno($c);

    if ($errno || $error) {
        throw new \Exception(sprintf('Error for request %s. Curl error %s: %s', $url, $errno, $error));
    }

    $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new \Exception(
            sprintf('Error for request %s. HTTP error (%s): %s', $url, $httpCode, $result),
            $httpCode
        );
    }

    curl_close($c);

    if (! $result) {
        throw new \Exception(sprintf('Error for request %s. Empty result.', $url));
    }

    $decodedResult = json_decode($result, true);

    if ($decodedResult === null) {
        throw new \Exception(
            sprintf('Error for request %s. Failed JSON decoding. Response: %s', $url, $result)
        );
    }

    return $decodedResult;
}
```

Below you can see an example JSON that the `/current-user` endpoint returns for a logged in admin.

For client the `isClient` field will be true, `clientId` will be set, but `userGroup`, `permissions` and `specialPermissions` will all be empty.

If the user is not authenticated the endpoint will return a 403 HTTP code.

The items in `specialPermissions` can have these values: `allow`, `deny`.

The items in `permissions` can have these values: `view`, `edit`, `denied`.

```
{
  "userId": 1,
  "username": "admin",
  "isClient": false,
  "clientId": null,
  "userGroup": "Admin Group",
  "specialPermissions": {
    "special/view_financial_information": "allow",
    "special/client_export": "allow",
    "special/job_comment_edit_delete": "allow",
    "special/show_device_passwords": "allow",
    "special/client_log_edit_delete": "allow",
    "special/clients_financial_information": "allow",
    "special/client_impersonation": "allow"
  },
  "permissions": {
    "clients/clients": "edit",
    "network/devices": "edit",
    "network/device_interfaces": "edit",
    "billing/invoices": "edit",
    "system/billing/organization_bank_accounts": "edit",
    "system/organizations": "edit",
    "billing/payments": "edit",
    "system/items/products": "edit",
    "clients/services": "edit",
    "system/other/reasons_for_suspending_service": "edit",
    "system/settings": "edit",
    "network/sites": "edit",
    "system/items/surcharges": "edit",
    "system/items/service_plans": "edit",
    "system/billing/taxes": "edit",
    "system/security/users": "edit",
    "system/security/groups_and_permissions": "edit",
    "system/other/vendors": "edit",
    "reports/invoiced_revenue": "edit",
    "reports/taxes": "edit",
    "system/logs/device_log": "edit",
    "system/logs/email_log": "edit",
    "system/logs/system_log": "edit",
    "system/tools/backup": "edit",
    "billing/refunds": "edit",
    "system/tools/ssl_certificate": "edit",
    "system/other/sandbox_termination": "edit",
    "network/outages": "edit",
    "network/unknown_devices": "edit",
    "system/security/app_keys": "edit",
    "clients/documents": "edit",
    "system/tools/webroot": "edit",
    "system/billing/invoicing": "edit",
    "system/billing/suspension": "edit",
    "system/tools/downloads": "edit",
    "network/network_map": "edit",
    "system/customization/invoice_templates": "edit",
    "system/tools/fcc_reports": "edit",
    "system/other/custom_attributes": "edit",
    "scheduling/all_jobs": "edit",
    "scheduling/my_jobs": "edit",
    "system/other/client_tags": "edit",
    "system/tools/updates": "edit",
    "ticketing/ticketing": "edit",
    "system/billing/fees": "edit",
    "system/tools/mailing": "edit",
    "reports/data_usage": "edit",
    "system/other/contact_types": "edit",
    "system/customization/notification_settings": "edit",
    "system/customization/suspension_templates": "edit",
    "system/customization/email_templates": "edit",
    "system/customization/appearance": "edit",
    "system/customization/quote_templates": "edit",
    "billing/quotes": "edit",
    "system/plugins": "edit",
    "system/webhooks": "edit",
    "system/customization/payment_receipt_templates": "edit",
    "system/customization/client_zone_pages": "edit",
    "system/tools/client_import": "edit",
    "system/tools/payment_import": "edit",
    "system/customization/account_statement_templates": "edit"
  }
}
```
