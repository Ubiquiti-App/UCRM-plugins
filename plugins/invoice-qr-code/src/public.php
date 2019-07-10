<?php

use App\CountryConverter;
use App\Http;
use App\QrPaymentFactory;
use GuzzleHttp\Exception\RequestException;
use Ubnt\UcrmPluginSdk\Security;
use Ubnt\UcrmPluginSdk\Service;

require_once __DIR__ . '/vendor/autoload.php';

$pluginLogManager = Service\PluginLogManager::create();
$api = Service\UcrmApi::create();
$user = Service\UcrmSecurity::create()->getUser();

// check that invoiceNumber is setr
$invoiceId = $_GET['invoiceId'] ?? null;
if ($invoiceId === null) {
    $pluginLogManager->appendLog('Request was made with missing invoiceId. Have you configured the plugin correctly? See README.md.');
    Http::badRequest();
}

// check that user is logged in and has permission to view invoices
if ($user === null || !$user->hasViewPermission(Security\PermissionNames::BILLING_INVOICES)) {
    Http::forbidden();
}

// load invoice from UCRM API
try {
    $invoiceId = $api->get("invoices?number={$invoiceId}")[0]['id']; // TODO remove this single line when invoice.id become available in invoice templates
    $invoice = $api->get("invoices/{$invoiceId}");
} catch (RequestException $exception) {
    $pluginLogManager->appendLog("Failed to load data for invoice {$invoiceId}.");
    Http::notFound();
}
// check if user is client and if he's accessing his own invoice
if ($user->isClient && $user->clientId !== $invoice['clientId']) {
    Http::forbidden();
}

// create QrPayment object
$qrPaymentFactory = new QrPaymentFactory(new CountryConverter($api));
try {
    $qrCode = $qrPaymentFactory->createQrCode(
        $invoice['organizationCountryId'],
        $invoice['organizationName'],
        $invoice['organizationBankAccountField1'],
        $invoice['organizationBankAccountField2'],
        $invoice['number'],
        $invoice['total'],
        $invoice['currencyCode'],
        new DateTimeImmutable($invoice['dueDate'])
    );

    // set correct headers and write image to output
    header('Content-Type: ' . $qrCode->getContentType());
    echo $qrCode->writeString();
} catch (Exception $exception) {
    $pluginLogManager->appendLog("Failed to generate QR code for invoice {$invoiceId} - {$exception->getMessage()}.");

    // return 1x1 image because at this stage so client don't see errors instead of image.
    // at this stage the plugin should be configured properly and template extended. something bad happened on UCRM API or plugin side
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
}
