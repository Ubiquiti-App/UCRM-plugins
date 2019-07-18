<?php


namespace App;

use DateTimeImmutable;
use Endroid\QrCode\QrCode;
use InvalidArgumentException;
use rikudou\CzQrPayment;
use rikudou\EuQrPayment;
use rikudou\SkQrPayment;

class QrPaymentFactory
{
    private const EU_COUNTRIES_ISO_CODES = [
        'BE',
        'EL',
        'LT',
        'PT',
        'BG',
        'ES',
        'LU',
        'RO',
        'CZ',
        'FR',
        'HU',
        'SI',
        'DK',
        'HR',
        'MT',
        'SK',
        'DE',
        'IT',
        'NL',
        'FI',
        'EE',
        'CY',
        'AT',
        'SE',
        'IE',
        'LV',
        'PL',
        'UK',
    ];

    public function createQrCode(
        string $countryIsoCode,
        string $beneficiaryName,
        string $accountNumber,
        string $routingNumber,
        string $variableSymbol,
        string $amount,
        DateTimeImmutable $dueDate
    ): QrCode {
        $accountNumberWithoutWhitespaces = preg_replace('/\s+/', '', $accountNumber);
        $routingNumberWithoutWhitespaces = preg_replace('/\s+/', '', $routingNumber);

        if ($countryIsoCode === 'CZ') {
            $qrPayment = new CzQrPayment\QrPayment($accountNumberWithoutWhitespaces, $routingNumberWithoutWhitespaces);
            $qrPayment
                ->setVariableSymbol($variableSymbol)
                ->setAmount($amount)
                ->setCurrency('CZK')
                ->setDueDate($dueDate->format('Y-m-d'));

            return $qrPayment->getQrImage();
        }

        if ($countryIsoCode === 'SK') {
            $qrPayment = SkQrPayment\QrPayment::fromIBAN($accountNumberWithoutWhitespaces);
            $qrPayment
                ->setSwift($routingNumberWithoutWhitespaces)
                ->setVariableSymbol($variableSymbol)
                ->setAmount($amount)
                ->setCurrency('EUR')
                ->setDueDate($dueDate->format('Y-m-d'));

            return $qrPayment->getQrImage();
        }

        if (in_array($countryIsoCode, self::EU_COUNTRIES_ISO_CODES, true)) {
            $payment = new EuQrPayment\QrPayment($accountNumberWithoutWhitespaces);
            $payment
                ->setCharacterSet(EuQrPayment\Sepa\CharacterSet::UTF_8)
                ->setBic($routingNumberWithoutWhitespaces)
                ->setBeneficiaryName($beneficiaryName)
                ->setComment($variableSymbol)
                ->setAmount(100)
                ->setPurpose(EuQrPayment\Sepa\Purpose::INVOICE_PAYMENT)
                ->setInformation('UCRM Invoice')
                ->setCurrency('EUR');

            return $payment->getQrImage();
        }

        throw new InvalidArgumentException("QR Code generation is not supported for country '{$countryIsoCode}'.");
    }
}
