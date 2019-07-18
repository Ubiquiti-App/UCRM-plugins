<?php

use App\CountryConverter;
use App\QrPaymentFactory;
use Ubnt\UcrmPluginSdk\Service;

require_once __DIR__ . '/vendor/autoload.php';

try {
    $pluginLogManager = Service\PluginLogManager::create();
    $api = Service\UcrmApi::create();
    $user = Service\UcrmSecurity::create()->getUser();

    // TODO all parameters should be fetched from API by invoice id
    $organizationCountry = $_GET['organizationCountry'] ?? null; // {{ organization.country }}
    $organizationName = $_GET['organizationName'] ?? null; // {{ organization.name }}
    $bankAccount = $_GET['bankAccount'] ?? null; // {{ organization.bankAccount }}
    $invoiceNumber = $_GET['invoiceNumber'] ?? null; // {{ invoice.number }}
    $amountDue = $_GET['amountDue'] ?? null; // {{ totals.amountDue }}
    $dueDate = $_GET['dueDate'] ?? null; // {{ invoice.dueDate }}

    $organizationCountryIsoCode = (new CountryConverter($api))->convertCountryNameToISO($organizationCountry);
    [$accountNumber, $routingNumber] = explode('/', $bankAccount);
    $amountDue = (float) filter_var($amountDue, FILTER_SANITIZE_NUMBER_FLOAT) / 100;

    // create QrPayment object
    $qrPaymentFactory = new QrPaymentFactory();
    $qrCode = $qrPaymentFactory->createQrCode(
        $organizationCountryIsoCode,
        $organizationName,
        $accountNumber,
        $routingNumber,
        $invoiceNumber,
        $amountDue,
        new DateTimeImmutable($dueDate)
    );

    // set correct headers and write image to output
    $imageString = $qrCode->writeString();
    header('content-type: ' . $qrCode->getContentType());
    header('content-length: ' . strlen($imageString));
    echo $imageString;
} catch (Exception $exception) {
    $pluginLogManager->appendLog("Failed to generate QR code for invoice {$invoiceNumber} - {$exception->getMessage()}. Parameters - " . json_encode([
        'organizationCountry' => $organizationCountry,
        'organizationCountryIsoCode' => $organizationCountryIsoCode,
        'organizationName' => $organizationName,
        'accountNumber' => $accountNumber ?? null,
        'routingNumber' => $routingNumber ?? null,
        'invoiceNumber' => $invoiceNumber,
        'amountDue' => $amountDue,
        'dueDate' => $dueDate,
    ]));

    // return 1x1 image because at this stage so client don't see errors instead of image.
    // at this stage the plugin should be configured properly and template extended. something bad happened on UCRM API or plugin side
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
}
