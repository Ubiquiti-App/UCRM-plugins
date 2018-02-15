<?php

declare(strict_types=1);


namespace QBExport\Facade;


use QBExport\Factory\DataServiceFactory;
use QBExport\Service\Logger;
use QBExport\Service\OptionsManager;
use QBExport\Service\UcrmApi;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Exception\ServiceException;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Payment;

class QuickBooksFacade
{
    /**
     * @var DataServiceFactory
     */
    private $dataServiceFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    public function __construct(
        DataServiceFactory $dataServiceFactory,
        Logger $logger,
        OptionsManager $optionsManager,
        UcrmApi $ucrmApi
    ) {
        $this->dataServiceFactory = $dataServiceFactory;
        $this->logger = $logger;
        $this->optionsManager = $optionsManager;
        $this->ucrmApi = $ucrmApi;
    }

    public function obtainAuthotizationURL(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_URL_GENERATOR);
        $qbAuthorizationUrl = $dataService->getOAuth2LoginHelper()->getAuthorizationCodeURL();

        $this->logger->notice(sprintf('Authorization URL: %s', $qbAuthorizationUrl));

        $pluginData->qbAuthorizationUrl = $qbAuthorizationUrl;

        $this->optionsManager->update();
    }

    public function obtainTokens(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_EXCHANGE_CODE_FOR_TOKEN);
        try {
            $accessToken = $dataService->getOAuth2LoginHelper()->exchangeAuthorizationCodeForToken(
                $pluginData->oauthCode,
                $pluginData->oauthRealmID
            );
            $pluginData->oauthAccessToken = $accessToken->getAccessToken();
            $pluginData->oauthRefreshToken = $accessToken->getRefreshToken();
            $pluginData->oauthRefreshTokenExpiration = $accessToken->getRefreshTokenExpiresAt();
            $pluginData->oauthAccessTokenExpiration = $accessToken->getAccessTokenExpiresAt();

            $this->optionsManager->update();
            $this->logger->notice('Exchange Authorization Code for Access Token succeeded.');

        } catch (ServiceException $exception) {
            $this->logger->info(
                'Exchange Authorization Code for Access Token failed. You need confirm your connection again.'
            );
            $pluginData->oauthCode = null;
            $pluginData->oauthRealmID = null;
            $pluginData->oauthAccessToken = null;
            $pluginData->oauthRefreshToken = null;
            $pluginData->oauthRefreshTokenExpiration = null;
            $pluginData->oauthAccessTokenExpiration = null;
            $pluginData->qbStateCSRF = null;

            $this->optionsManager->update();

            $this->obtainAuthotizationURL();
        }
    }

    public function exportClients(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

        foreach ($this->ucrmApi->query('clients') as $ucrmClient) {
            if ($ucrmClient['id'] <= $pluginData->lastExportedClientID) {
                continue;
            }

            $entities = $dataService->Query(
                sprintf('SELECT * FROM Customer WHERE DisplayName LIKE \'%%UCRMID-%d%%\'', $ucrmClient['id'])
            );

            if (! $entities) {
                $this->logger->info(sprintf('Client ID: %s needs to be exported', $ucrmClient['id']));
                if ($ucrmClient['clientType'] === 1) {
                    $nameForView = sprintf(
                        '%s %s',
                        $ucrmClient['firstName'],
                        $ucrmClient['lastName']
                    );
                } else {
                    $nameForView = $ucrmClient['companyName'];
                }

                $customerData = [
                    'DisplayName' => sprintf(
                        '%s (UCRMID-%d)',
                        $nameForView,
                        $ucrmClient['id']
                    ),
                    'PrintOnCheckName' => $nameForView,
                    'GivenName' => $ucrmClient['firstName'],
                    'FamilyName' => $ucrmClient['lastName'],
                    'ShipAddr' => [
                        'Line1' => $ucrmClient['street1'],
                        'Line2' => $ucrmClient['street2'],
                        'City' => $ucrmClient['city'],
                        'PostalCode' => $ucrmClient['zipCode'],
                    ],
                    'BillAddr' => [
                        'Line1' => $ucrmClient['invoiceStreet1'],
                        'Line2' => $ucrmClient['invoiceStreet2'],
                        'City' => $ucrmClient['invoiceCity'],
                        'PostalCode' => $ucrmClient['invoiceZipCode'],
                    ],
                ];

                if ($dataService->Add(Customer::create($customerData))) {
                    $this->logger->info(
                        sprintf('Client %s (ID: %s) exported successfully.', $nameForView, $ucrmClient['id'])
                    );
                    $pluginData->lastExportedClientID = $ucrmClient['id'];
                    $this->optionsManager->update();
                } else {
                    return;
                }
            }
        }
    }

    public function refreshExpiredToken(): void
    {
        $pluginData = $this->optionsManager->load();
        if (new \DateTimeImmutable($pluginData->oauthAccessTokenExpiration, new \DateTimeZone('UTC'))
            < new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
        ) {
            $this->logger->notice('Refreshing token');
            $accessToken = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY)
                ->getOAuth2LoginHelper()
                ->refreshToken();

            $pluginData->oauthAccessToken = $accessToken->getAccessToken();
            $pluginData->oauthRefreshToken = $accessToken->getRefreshToken();
            $pluginData->oauthRefreshTokenExpiration = $accessToken->getRefreshTokenExpiresAt();
            $pluginData->oauthAccessTokenExpiration = $accessToken->getAccessTokenExpiresAt();

            $this->optionsManager->update();
            $this->logger->notice('Refresh of Token succeeded.');
        }
    }

    public function exportInvoices(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

        foreach ($this->ucrmApi->query('invoices') as $ucrmInvoice) {
            if ($ucrmInvoice['id'] <= $pluginData->lastExportedInvoiceID) {
                continue;
            }

            $this->logger->info(sprintf('Invoice ID: %s needs to be exported', $ucrmInvoice['id']));

            if ($qbClient = $this->getQBClient($dataService, $ucrmInvoice['clientId'])) {

                $lines = [];
                foreach ($ucrmInvoice['items'] as $item) {
                    $qbItem = $this->createQBLineFromItem($dataService, $item, (int) $pluginData->qbIncomeAccountNumber);
                    $lines[] = [
                        'Amount' => $item['quantity'],
                        'Description' => $item['label'],
                        'DetailType' => 'SalesItemLineDetail',
                        'SalesItemLineDetail' => [
                            'ItemRef' => [
                                'value' => $qbItem->Id
                            ]
                        ]
                    ];
                }

                if (
                    $dataService->Add(
                        Invoice::create(
                            [
                                'Line' => $lines,
                                'CustomerRef'=> [
                                    'value'=> $qbClient->Id
                                ],
                            ]
                        )
                    )
                ) {
                    $this->logger->info(
                        sprintf('Invoice ID: %s exported successfully.', $ucrmInvoice['id'])
                    );
                    $pluginData->lastExportedInvoiceID = $ucrmInvoice['id'];
                    $this->optionsManager->update();
                } else {
                    return;
                }
            }
        }
    }

    public function exportPayments(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

        foreach ($this->ucrmApi->query('payments') as $ucrmPayment) {
            if ($ucrmPayment['id'] <= $pluginData->lastExportedPaymentID) {
                continue;
            }

            $this->logger->info(sprintf('Payment ID: %s needs to be exported', $ucrmPayment['id']));

            if ($ucrmPayment['clientId'] && $qbClient = $this->getQBClient($dataService, $ucrmPayment['clientId'])) {
                $theResourceObj = Payment::create([
                    'CustomerRef' => [
                        'value' => $qbClient->Id
                    ],
                    'TotalAmt' => $ucrmPayment['amount']
                ]);

                if ($dataService->Add($theResourceObj)) {
                    $this->logger->info(
                        sprintf('Payment ID: %s exported successfully.', $ucrmPayment['id'])
                    );
                    $pluginData->lastExportedPaymentID = $ucrmPayment['id'];
                    $this->optionsManager->update();
                } else {
                    return;
                }
            }
        }
    }

    private function getQBClient(DataService $dataService, int $ucrmClientId)
    {
        $customers = $dataService->Query(
            sprintf('SELECT * FROM Customer WHERE DisplayName LIKE \'%%UCRMID-%d%%\'', $ucrmClientId)
        );

        if (! $customers) {
            return null;
        }

        return current($customers);
    }

    private function createQBLineFromItem(DataService $dataService, array $item, int $qbIncomeAccountNumber)
    {
        return $dataService->Add(Item::create([
            'Name' => sprintf('%s (UCRMID-%s)', $item['label'], $item['id']) ,
            'Type' => 'Service',
            'IncomeAccountRef' => [
                'value' => $qbIncomeAccountNumber
            ],
        ]));
    }
}
