<?php

declare(strict_types=1);


namespace QBExport\Facade;

use QBExport\Data\InvoiceStatus;
use QBExport\Exception\QBAuthorizationException;
use QBExport\Factory\DataServiceFactory;
use QBExport\Service\Logger;
use QBExport\Service\OptionsManager;
use QBExport\Service\UcrmApi;
use QuickBooksOnline\API\Core\HttpClients\FaultHandler;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Exception\ServiceException;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Line;
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

    public function obtainAuthorizationURL(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_URL_GENERATOR);
        $qbAuthorizationUrl = $dataService->getOAuth2LoginHelper()->getAuthorizationCodeURL();

        $this->logger->notice(sprintf('Authorization URL: %s', $qbAuthorizationUrl));

        $pluginData->qbAuthorizationUrl = $qbAuthorizationUrl;

        $this->optionsManager->update();
    }

    /**
     * @throws QBAuthorizationException
     * @throws \QuickBooksOnline\API\Exception\SdkException
     * @throws \ReflectionException
     */
    public function obtainTokens(): void
    {
        try {
            $pluginData = $this->optionsManager->load();
            $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_EXCHANGE_CODE_FOR_TOKEN);
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

            $activeAccounts = $this->getAccounts();

            $accountsString = '';
            foreach ($activeAccounts as $account) {
                $accountsString .= 'Account:' . $account->Name . ' ID: ' . $account->Id . PHP_EOL;
            }

            $this->logger->info(
                sprintf(
                    'Income account numbers in QBO Active accounts:\n %s',
                    $accountsString
                )
            );
        } catch (ServiceException $exception) {
            $this->invalidateTokens();
        }
    }

    public function exportClients(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

        foreach ($this->ucrmApi->query('clients?direction=ASC') as $ucrmClient) {
            if ($ucrmClient['id'] <= $pluginData->lastExportedClientID) {
                continue;
            }

            if ($ucrmClient['isLead']) {
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

                try {
                    $response = $dataService->Add(Customer::create($customerData));
                    if ($response instanceof IPPIntuitEntity) {
                        $this->logger->info(
                            sprintf('Client %s (ID: %s) exported successfully.', $nameForView, $ucrmClient['id'])
                        );
                    }
                    if (! $response) {
                        $this->logger->info(
                            sprintf('Client %s (ID: %s) export failed.', $nameForView, $ucrmClient['id'])
                        );
                    }
                    if ($response instanceof \Exception) {
                        throw $response;
                    }
                    $this->handleErrorResponse($dataService);
                } catch (\Exception $exception) {
                    $this->logger->error(
                        sprintf(
                            'Client %s (ID: %s) export failed with error %s.',
                            $nameForView,
                            $ucrmClient['id'],
                            $exception->getMessage()
                        )
                    );
                }

                $pluginData->lastExportedClientID = $ucrmClient['id'];
                $this->optionsManager->update();
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

        $activeAccounts = $this->getAccounts();

        if (! array_key_exists((int) $pluginData->qbIncomeAccountNumber, $activeAccounts)) {
            $accountsString = '';
            foreach ($activeAccounts as $account) {
                $accountsString .= 'Account:' . $account->Name . ' ID: ' . $account->Id . PHP_EOL;
            }

            $this->logger->info(
                sprintf(
                    'Income account number (%s) set in the plugin config does not exist in QB or is not active. Active accounts:\n %s',
                    $pluginData->qbIncomeAccountNumber,
                    $accountsString
                )
            );

            return;
        }

        $query = 'invoices?direction=ASC';
        if ($pluginData->invoicesFromDate) {
            $query = sprintf('%s&createdDateFrom=%s', $query, $pluginData->invoicesFromDate);
        }
        foreach ($this->ucrmApi->query($query) as $ucrmInvoice) {
            if ($ucrmInvoice['id'] <= $pluginData->lastExportedInvoiceID) {
                continue;
            }

            // do not process DRAFT, VOID or PROFORMA invoices
            if (
                ($ucrmInvoice['proforma'] ?? false)
                || in_array($ucrmInvoice['status'], [InvoiceStatus::DRAFT, InvoiceStatus::VOID], true)
            ) {
                continue;
            }

            $this->logger->info(sprintf('Export of invoice ID %s started.', $ucrmInvoice['id']));

            $qbClient = $this->getQBClient($dataService, $ucrmInvoice['clientId']);

            if (! $qbClient) {
                $this->logger->error(
                    sprintf('Client with Display name containing: UCRMID-%s is not found.', $ucrmInvoice['clientId'])
                );
                continue;
            }

            $lines = [];
            $TaxCode = 'NON';
            foreach ($ucrmInvoice['items'] as $item) {
                $qbItem = $this->createQBLineFromItem(
                    $dataService,
                    $item,
                    (int) $pluginData->qbIncomeAccountNumber
                );
                if ($qbItem) {
                    if ($item['tax1Id']) {
                        $TaxCode = 'TAX';
                    } else {
                        $TaxCode = 'NON';
                    }

                    $lines[] = [
                        'Amount' => $item['total'],
                        'Description' => $item['label'],
                        'DetailType' => 'SalesItemLineDetail',
                        'SalesItemLineDetail' => [
                            'ItemRef' => [
                                'value' => $qbItem->Id,
                            ],
                            'UnitPrice' => $item['price'],
                            'Qty' => $item['quantity'],
                            'TaxCodeRef' => [
                                'value' => $TaxCode,
                            ],
                        ],
                    ];
                    if ($item['discountTotal'] < 0) {
                        $lines[] = [
                            'Amount' => $item['discountTotal'] * -1,
                            'DiscountLineDetail' => [
                                'PercentBased' => 'false',
                            ],
                            'DetailType' => 'DiscountLineDetail',
                            'Description' => 'Discount given',
                        ];
                    }
                }
            }

            try {
                $response = $dataService->Add(
                    Invoice::create(
                        [
                            'DocNumber' => $ucrmInvoice['id'],
                            'DueDate' => $ucrmInvoice['dueDate'],
                            'TxnDate' => $ucrmInvoice['createdDate'],
                            'Line' => $lines,
                            'TxnTaxDetail' => [
                                'TotalTax' => $ucrmInvoice['taxes']['totalValue'],
                                'TxnTaxCodeRef' => $TaxCode,
                            ],
                            'CustomerRef' => [
                                'value' => $qbClient->Id,
                            ],
                        ]
                    )
                );

                if ($response instanceof \Exception) {
                    throw $response;
                }

                if ($response instanceof IPPIntuitEntity) {
                    $this->logger->info(
                        sprintf('Invoice ID: %s exported successfully.', $ucrmInvoice['id'])
                    );
                } else {
                    $this->logger->info(
                        sprintf('Invoice ID: %s export failed.', $ucrmInvoice['id'])
                    );
                }

                $this->handleErrorResponse($dataService);
            } catch (\Exception $exception) {
                $this->logger->error(
                    sprintf(
                        'Invoice ID: %s export failed with error %s.',
                        $ucrmInvoice['id'],
                        $exception->getMessage()
                    )
                );
            }

            $pluginData->lastExportedInvoiceID = $ucrmInvoice['id'];
            $this->optionsManager->update();
        }
    }

    public function exportPayments(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);
        $query = 'payments?direction=ASC';
        if ($pluginData->paymentsFromDate) {
            $query = sprintf('%s&createdDateFrom=%s', $query, $pluginData->paymentsFromDate);
        }
        $ucrmPayments = $this->ucrmApi->query($query);
        usort(
            $ucrmPayments,
            function (array $a, array $b) {
                return $a['id'] <=> $b['id'];
            }
        );
        foreach ($ucrmPayments as $ucrmPayment) {
            if ($ucrmPayment['id'] <= $pluginData->lastExportedPaymentID || ! $ucrmPayment['clientId']) {
                continue;
            }

            $this->logger->info(sprintf('Payment ID: %s needs to be exported', $ucrmPayment['id']));

            $qbClient = $this->getQBClient($dataService, $ucrmPayment['clientId']);

            if (! $qbClient) {
                $this->logger->error(
                    sprintf('Client with Display name containing: UCRMID-%s is not found', $ucrmPayment['clientId'])
                );
                continue;
            }

            /* if there is a credit on the payment deal with it first */
            $this->logger->notice(sprintf('Invoice credit amount is : %s', $ucrmPayment['creditAmount']));
            if ($ucrmPayment['creditAmount'] > 0) {
                try {
                    $theResourceObj = Payment::create(
                        [
                            'CustomerRef' => [
                                'value' => $qbClient->Id,
                                'name' => $qbClient->DisplayName,
                            ],
                            'TotalAmt' => $ucrmPayment['creditAmount'],
                            'TxnDate' => substr($ucrmPayment['createdDate'], 0, 10),
                        ]
                    );
                    $response = $dataService->Add($theResourceObj);
                    if ($response instanceof IPPIntuitEntity) {
                        $this->logger->info(
                            sprintf('Payment ID: %s credit exported successfully.', $ucrmPayment['id'])
                        );
                    }
                    if (! $response) {
                        $this->logger->info(
                            sprintf('Payment ID: %s credit export failed.', $ucrmPayment['id'])
                        );
                    }
                    if ($response instanceof \Exception) {
                        throw $response;
                    }
                    $this->handleErrorResponse($dataService);
                } catch (\Exception $exception) {
                    $this->logger->error(
                        sprintf(
                            'Payment ID: %s export failed with error %s.',
                            $ucrmPayment['id'],
                            $exception->getMessage()
                        )
                    );
                }
            }

            /* now look and see if part of the payment is applied to existing invoices */
            foreach ($ucrmPayment['paymentCovers'] as $paymentCovers) {
                $LineObj = null;
                $lineArray = null;

                $invoices = $dataService->Query(
                    sprintf('SELECT * FROM INVOICE WHERE DOCNUMBER = \'%d\'', $paymentCovers['invoiceId'])
                );

                $INVOICES = json_decode(json_encode($invoices), true);

                if ($invoices) {
                    $LineObj = Line::create(
                        [
                            'Amount' => $ucrmPayment['amount'],
                            'LinkedTxn' => [
                                'TxnId' => $INVOICES[0]['Id'],
                                'TxnType' => 'Invoice',
                            ],
                        ]
                    );

                    $lineArray[] = $LineObj;

                    try {
                        $theResourceObj = Payment::create(
                            [
                                'CustomerRef' => [
                                    'value' => $qbClient->Id,
                                    'name' => $qbClient->DisplayName,
                                ],
                                'TotalAmt' => $ucrmPayment['amount'],
                                'Line' => $lineArray,
                                'TxnDate' => substr($ucrmPayment['createdDate'], 0, 10),
                            ]
                        );

                        $response = $dataService->Add($theResourceObj);
                        if ($response instanceof IPPIntuitEntity) {
                            $this->logger->info(
                                sprintf('Payment ID: %s exported successfully.', $ucrmPayment['id'])
                            );
                        }
                        if (! $response) {
                            $this->logger->info(
                                sprintf('Payment ID: %s export failed.', $ucrmPayment['id'])
                            );
                        }
                        if ($response instanceof \Exception) {
                            throw $response;
                        }

                        $this->handleErrorResponse($dataService);
                    } catch (\Exception $exception) {
                        $this->logger->error(
                            sprintf(
                                'Payment ID: %s export failed with error %s.',
                                $ucrmPayment['id'],
                                $exception->getMessage()
                            )
                        );
                    }
                }
            }

            // update last processed payment in the end
            $pluginData->lastExportedPaymentID = $ucrmPayment['id'];
            $this->optionsManager->update();
        }
    }

    private function getQBClient(DataService $dataService, int $ucrmClientId)
    {
        $customers = $dataService->Query(
            sprintf('SELECT * FROM Customer WHERE DisplayName LIKE \'%%UCRMID-%d)\'', $ucrmClientId)
        );

        if (! $customers) {
            return null;
        }

        return reset($customers);
    }

    private function createQBLineFromItem(
        DataService $dataService,
        array $item,
        int $qbIncomeAccountNumber
    ): ?IPPIntuitEntity {
        try {
            $response = $dataService->Add(
                Item::create(
                    [
                        'Name' => sprintf('%s (UCRMID-%s)', $item['label'], $item['id']),
                        'Type' => 'Service',
                        'IncomeAccountRef' => [
                            'value' => $qbIncomeAccountNumber,
                        ],
                    ]
                )
            );

            $this->handleErrorResponse($dataService);

            return $response;
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Item ID: %s export failed with error %s.', $item['id'], $exception->getMessage())
            );
        }

        return null;
    }

    /**
     * @throws QBAuthorizationException
     */
    private function handleErrorResponse(DataService $dataService): void
    {
        $error = $dataService->getLastError();
        if ($error instanceof FaultHandler) {
            try {
                $xml = new \SimpleXMLElement($error->getResponseBody());

                if (isset($xml->Fault)) {
                    foreach ($xml->Fault->attributes() as $attributeName => $attributeValue) {
                        if ($attributeName === 'type' && (string) $attributeValue === 'AUTHENTICATION') {
                            $this->invalidateTokens();
                        }
                    }
                }

                if (isset($xml->Fault->Error->Detail)) {
                    $message = (string) $xml->Fault->Error->Detail;
                }

                throw new \RuntimeException(
                    $message ?? sprintf('Unexpected XML response: %s', $error->getResponseBody()),
                    $error->getHttpStatusCode()
                );
            } catch (QBAuthorizationException $exception) {
                throw new QBAuthorizationException($exception->getMessage());
            } catch (\Exception $exception) {
                throw new \RuntimeException(
                    sprintf('It is not possible to parse QB error: %s', $error->getResponseBody()),
                    $error->getHttpStatusCode()
                );
            }
        }
    }

    private function getAccounts(): array
    {
        $activeAccounts = [];

        try {
            $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

            $response = $dataService->FindAll('Account');

            $this->handleErrorResponse($dataService);

            foreach ($response as $account) {
                if (! $account->Active) {
                    continue;
                }

                $activeAccounts[$account->Id] = $account;
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Account: Getting all Accounts failed with error %s.', $exception->getMessage())
            );
        }

        return $activeAccounts;
    }

    /**
     * @throws QBAuthorizationException
     * @throws \ReflectionException
     */
    private function invalidateTokens(): void
    {
        $this->logger->info(
            'Connection failed. Check your connection settings. You may need to remove and add the plugin again.'
        );

        $pluginData = $this->optionsManager->load();
        $pluginData->oauthCode = null;
        $pluginData->oauthRealmID = null;
        $pluginData->oauthAccessToken = null;
        $pluginData->oauthRefreshToken = null;
        $pluginData->oauthRefreshTokenExpiration = null;
        $pluginData->oauthAccessTokenExpiration = null;
        $pluginData->qbStateCSRF = null;

        $this->optionsManager->update();

        $this->obtainAuthorizationURL();

        throw new QBAuthorizationException('Connection failed');
    }
}
