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

    /** @var CountryConverter */
    private $countryConverter;

    public function __construct(CountryConverter $countryConverter)
    {
        $this->countryConverter = $countryConverter;
    }


    public function createQrCode(
        int $countryId,
        string $beneficiaryName,
        string $bankAccountNumber,
        string $bankRoutingCode,
        string $variableSymbol,
        string $amount,
        string $currency,
        DateTimeImmutable $dueDate
    ): QrCode {
        $countryIsoCode = $this->countryConverter->convertUcrmIdToISO($countryId);
        $bankAccountNumberWithoutWhitespaces = preg_replace('/\s+/', '', $bankAccountNumber);

        if ($countryIsoCode === 'CZ') {
            $qrPayment = new CzQrPayment\QrPayment($bankAccountNumber, $bankRoutingCode);
            $qrPayment
                ->setVariableSymbol($variableSymbol)
                ->setAmount($amount)
                ->setCurrency($currency)
                ->setDueDate($dueDate->format('Y-m-d'));

            return $qrPayment->getQrImage();
        }

        if ($countryIsoCode === 'SK') {
            $qrPayment = SkQrPayment\QrPayment::fromIBAN($bankAccountNumberWithoutWhitespaces);
            $qrPayment
                ->setSwift($bankRoutingCode)
                ->setVariableSymbol($variableSymbol)
                ->setAmount($amount)
                ->setCurrency($currency)
                ->setDueDate($dueDate->format('Y-m-d'));

            return $qrPayment->getQrImage();
        }

        if (in_array($countryIsoCode, self::EU_COUNTRIES_ISO_CODES, true)) {
            $payment = new EuQrPayment\QrPayment($bankAccountNumberWithoutWhitespaces);
            $payment
                ->setCharacterSet(EuQrPayment\Sepa\CharacterSet::UTF_8)
                ->setBic($bankRoutingCode)
                ->setBeneficiaryName($beneficiaryName)
                ->setComment($variableSymbol)
                ->setAmount(100)
                ->setPurpose(EuQrPayment\Sepa\Purpose::INVOICE_PAYMENT)
                ->setInformation('UCRM Invoice')
                ->setCurrency($currency);

            return $payment->getQrImage();
        }

        throw new InvalidArgumentException("QR Code generation is not supported for country $countryId ({$countryIsoCode}).");
    }
}
