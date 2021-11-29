<?php

declare(strict_types=1);


namespace QBExport\Facade;


use DateTime;
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
use QuickBooksOnline\API\Facades\Payment;
use QuickBooksOnline\API\Facades\Line;

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

    /**
     * @var float|int
     */
    private $qbApiDelay = 70 * 1000;

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

            $this->logger->info("Income account numbers in QBO Active accounts:\n$accountsString");
        } catch (ServiceException $exception) {
            $this->invalidateTokens();
        }
    }

    public function exportClients(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

        foreach ($this->ucrmApi->query('clients?direction=ASC') as $ucrmClient) {
            if (file_exists("data/stopclients") || file_exists("data/stop")) {
                $this->logger->info(sprintf('exportClients: stop file detected'));
                break;
            }
            if ($ucrmClient['id'] <= $pluginData->lastExportedClientID) {
                continue;
            }

            if ($ucrmClient['isLead']) {
                continue;
            }

            $this->pauseIfNeeded();
            $entities = $dataService->Query(
                sprintf('SELECT * FROM Customer WHERE DisplayName LIKE \'%%(UCRMID-%d)\'', $ucrmClient['id'])
            );

            if ($entities) {
                $this->logger->info(sprintf('Client ID: %s exists', $ucrmClient['id']));
            } else {
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
                    $this->pauseIfNeeded();
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
            }

            $pluginData->lastExportedClientID = $ucrmClient['id'];
            $this->optionsManager->update();
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

        $this->logger->notice(sprintf('qbBaseUrl: %s', $pluginData->qbBaseUrl));
    }

    public function exportInvoices(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

        if ($pluginData->qbIncomeAccountNumber == 0)
            $query = sprintf('SELECT * FROM Account WHERE AccountType = \'Income\' AND Name = \'%s\'', $pluginData->qbIncomeAccountName);
        else
            $query = sprintf('SELECT * FROM Account WHERE AccountType = \'Income\' AND Id = \'%s\'', $pluginData->qbIncomeAccountNumber);

        $this->pauseIfNeeded();
        $account = $dataService->Query($query);
        if ($account) {
            $ACCOUNT = json_decode(json_encode($account),true);
            $qbIncomeAccountId = $ACCOUNT[0]['Id'];
            $qbIncomeAccountName = $ACCOUNT[0]['Name'];
        } else {
            $this->logger->error("Unable to find Income account (ID {$pluginData->qbIncomeAccountNumber}, Name {$pluginData->qbIncomeAccountName}) set in the plugin config");
            return;
	    }

        $itemCache = null;
        $query = 'invoices?direction=ASC';
        if ($pluginData->invoicesFromDate) {
            $query = sprintf('%s&createdDateFrom=%s', $query, $pluginData->invoicesFromDate);
        }
        foreach ($this->ucrmApi->query($query) as $ucrmInvoice) {

            if (file_exists("data/stopinvoices") || file_exists("data/stop")) {
                $this->logger->info(sprintf('exportInvoices: stop file detected'));
                break;
            }

            if ($ucrmInvoice['id'] <= $pluginData->lastExportedInvoiceID) {
                continue;
            }

            // do not process DRAFT, VOID or PROFORMA invoices
            if (($ucrmInvoice['proforma'] ?? false) ||
                $ucrmInvoice['total'] == 0 ||
                in_array($ucrmInvoice['status'], [InvoiceStatus::DRAFT, InvoiceStatus::VOID], true)
            ) {
                continue;
            }

            $this->logger->info(sprintf('Export of invoice ID %s started.', $ucrmInvoice['id']));

	        $this->pauseIfNeeded();
            $docNumber = "{$ucrmInvoice['number']}/{$ucrmInvoice['id']}";
            $invoice = $dataService->Query("SELECT * FROM INVOICE WHERE DOCNUMBER = '$docNumber'");
            if ($invoice) {
                $this->logger->error(sprintf('Invoice %d already in QBO', $ucrmInvoice['id']));
                continue;
            }

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
                $qbItem = $itemCache[$item['label']];
                if (! $qbItem) {
                    $qbItem = $this->createQBLineFromItem(
                        $dataService,
                        $item,
                        (int)$qbIncomeAccountId,
                        $pluginData->itemNameFormat
                    );
                    if (! $qbItem) {
                        $this->logger->error("Could not get item \"{$item['label']}\" from QBO");
                        continue;
                    }

                    $itemCache[$item['label']] = $qbItem;
                }

                if ($item['tax1Id']) {
                    $TaxCode = 'TAX';
                } else {
                    $TaxCode = 'NON';
                }

                $this->logger->info(sprintf('Description=%s TaxCode=%s', $item['label'], $TaxCode));

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

            // TODO for percent based, not sure if it works (first implemented by Marcbou) -timbze 2021-11-26
//            if ($ucrmInvoice['discount'] > 0) {
//                $lines[] = [
//                    'Amount' => $ucrmInvoice['subtotal'] * $ucrmInvoice['discount'] / 100,
//                    'DiscountLineDetail' => [
//                        'PercentBased' => 'true',
//                        'DiscountPercent' => $ucrmInvoice['discount'],
//                    ],
//                    'DetailType' => 'DiscountLineDetail',
//                    'Description' => $ucrmInvoice['discountLabel'],
//                ];
//            }

            $tryCount = 0;
            $tryNumber = 3;
            do {
                try {
                    $this->logger->info(sprintf('Invoice::create DocNumber=%s DueDate=%s Total=%s TaxCode=%s',
                        sprintf('%s/%s', $ucrmInvoice['number'], $ucrmInvoice['id']), $ucrmInvoice['dueDate'], $ucrmInvoice['total'], $TaxCode));

                    $this->pauseIfNeeded();
                    $response = $dataService->Add(
                        Invoice::create(
                            [
                                'DocNumber' => sprintf('%s/%s', $ucrmInvoice['number'], $ucrmInvoice['id']),
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

                    if ($response instanceof IPPIntuitEntity) {
                        $this->logger->info(
                            sprintf('Invoice ID: %s exported successfully.', $ucrmInvoice['id'])
                        );
                    } else {
                        throw $response;
                    }

                    $this->handleErrorResponse($dataService);
                    break;
                } catch (\Exception $exception) {
                    $this->logger->error(
                        sprintf(
                            'Invoice ID: %s export failed with error %s.',
                            $ucrmInvoice['id'],
                            $exception->getMessage()
                        )
                    );

                    $tryCount++;
                    if ($tryCount < $tryNumber)
                        usleep(10 * 1000); // wait before retry
                }
            } while ($tryCount < $tryNumber);

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

            if (file_exists("data/stoppayments") || file_exists("data/stop")) {
                $this->logger->info(sprintf('exportPayments: stop file detected'));
                break;
            }

            if ($ucrmPayment['id'] <= $pluginData->lastExportedPaymentID || ! $ucrmPayment['clientId']) {
                continue;
            }

            $this->logger->info(sprintf('Payment ID: %s needs to be exported, creditAmount=%s amount=%s createdDate=%s', $ucrmPayment['id'], $ucrmPayment['creditAmount'], $ucrmPayment['amount'], $ucrmPayment['createdDate']));

            $qbClient = $this->getQBClient($dataService, $ucrmPayment['clientId']);

            if (! $qbClient) {
                $this->logger->error(
                    sprintf('Client with Display name containing: UCRMID-%s is not found', $ucrmPayment['clientId'])
                );
                continue;
            }

            $this->logger->info(sprintf('Payment for Client Id=%s DisplayName=%s', $qbClient->Id, $qbClient->DisplayName));

            /* if there is a credit on the payment deal with it first */
            if ($ucrmPayment['creditAmount'] > 0) {
            	$this->logger->info(sprintf('Invoice credit amount is : %s', $ucrmPayment['creditAmount']));

                try {
                    $theResourceObj = Payment::create(
                        [
                            'CustomerRef' => [
                                'value' => $qbClient->Id,
                                'name' => $qbClient->DisplayName,
                            ],
                            'TotalAmt' => $ucrmPayment['creditAmount'],
                            'TxnDate' => substr($ucrmPayment['createdDate'], 0, 10),
                            'PaymentRefNum' => $ucrmPayment['id'],
                            'PrivateNote' => $ucrmPayment['note'],
                        ]
                    );
	                $this->pauseIfNeeded();
                    $response = $dataService->Add($theResourceObj);
                    if ($response instanceof IPPIntuitEntity) {
                        $this->logger->info(
                            sprintf('Payment ID: %s credit exported successfully.', $ucrmPayment['id'])
                        );
                    } else {
                        if ($response instanceof \Exception)
                            throw $response;
                        else
                            throw new \RuntimeException("Unknown QBO error");
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
                    break;
                }
            }

            /* now look and see if part of the payment is applied to existing invoices */
            foreach ($ucrmPayment['paymentCovers'] as $paymentCovers) {

                $LineObj = null;
                $lineArray = null;

                $this->logger->info(sprintf('Payment covers invoiceId %d amount=%s', $paymentCovers['invoiceId'], $paymentCovers['amount']));

                if ($paymentCovers['refundId']) {
                    $this->logger->notice('Payment has refundId, not yet supported!');
                    continue;
                }
                if ($paymentCovers['invoiceId'] == '') {
                    $this->logger->notice('Payment has empty invoiceId!');
                    continue;
                }

                $this->pauseIfNeeded();
                $invId = $paymentCovers['invoiceId'];
                $invoices = $dataService->Query("SELECT * FROM INVOICE WHERE DOCNUMBER LIKE '%/$invId'");
                if (! $invoices) {
                    $this->pauseIfNeeded();
                    $invoices = $dataService->Query("SELECT * FROM INVOICE WHERE DOCNUMBER = '$invId'");
                }

                if ($invoices) {
                    $INVOICES = json_decode(json_encode($invoices), true);
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
                                'PaymentRefNum' => $ucrmPayment['id'],
                                'PrivateNote' => $ucrmPayment['note'],
                            ]
                        );

                        $this->logger->info(sprintf('applying payment to Invoice %s TxnId %s TotalAmt=%s', $INVOICES[0]['DocNumber'], $INVOICES[0]['Id'], $ucrmPayment['amount']));

                        $this->pauseIfNeeded();
                        $response = $dataService->Add($theResourceObj);
                        if ($response instanceof IPPIntuitEntity) {
                            $this->logger->info(
                                sprintf('Payment ID: %s exported successfully.', $ucrmPayment['id'])
                            );
                        } else {
                            if ($response instanceof \Exception)
                                throw $response;
                            else
                                throw new \RuntimeException("Unknown QBO error");
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
		        } else {
                    $this->logger->error(sprintf('Unable to find invoiceId %s covered by paymentID %s', $paymentCovers['invoiceId'], $ucrmPayment['id']));
                }
            }

            // update last processed payment in the end
            $pluginData->lastExportedPaymentID = $ucrmPayment['id'];
            $this->optionsManager->update();
        }
    }

    /**
     * @var int
     */
    private $queryRunCount = 0;

    /**
     * @var DateTime
     */
    private $lastCall;

    /**
     * This function is called before most QB api queries because QB has some limits in how many calls can be
     * made per minute. See <a href="https://developer.intuit.com/app/developer/qbo/docs/learn/rest-api-features#limits-and-throttles">this link</a>.
     */
    private function pauseIfNeeded() {
        if ($this->lastCall === NULL)
            $this->lastCall = date_create();

        $callsBeforeWait = 450;
        if ($this->lastCall->getTimestamp() < (date_create()->getTimestamp() - 60))
            // reset query run count because qb limit is per minute and there has been no call for more than a minute
            $this->queryRunCount = 0;
        elseif ($this->queryRunCount >= $callsBeforeWait) {
            $this->queryRunCount = 0;
            $waitSeconds = $this->qbApiDelay / 1000;
            $this->logger->notice("Now waiting $waitSeconds to run next QB api call because there were at least $callsBeforeWait calls in the last minute");
            usleep($this->qbApiDelay);
        }

        $this->queryRunCount++;
        $this->lastCall = date_create();
    }

    private function getQBClient(DataService $dataService, int $ucrmClientId)
    {
        $tryCount = 0;
        $tryNumber = 3;
        $customers = null;
        do {
            try {
                $this->pauseIfNeeded();
                $customers = $dataService->Query(
                    sprintf('SELECT * FROM Customer WHERE DisplayName LIKE \'%%(UCRMID-%d)\'', $ucrmClientId)
                );
                break;
            } catch (\Exception $e) {
                $this->logger->error("Could not get customer from QBO; id $ucrmClientId; error {$e->getMessage()}");
                $tryCount++;
                if ($tryCount < $tryNumber)
                    usleep(10 * 1000); // wait before trying again
            }
        } while ($tryCount < $tryNumber);

        if (! $customers) {
            return null;
        }

        return reset($customers);
    }

    private function createQBLineFromItem(
        DataService $dataService,
        array $item,
        int $qbIncomeAccountNumber,
        ?string $itemNameFormat
    ): ?IPPIntuitEntity {

        if($itemNameFormat) {
           $itemName=sprintf($itemNameFormat, $item['type']);
        } else {
           $itemName=$item['type'];
        }

        $tryCount = 0;
        $tryNumber = 3;
        $response = null;
        do {
            try {
                $this->pauseIfNeeded();
                $this->logger->info("Get item \"$itemName\" from QBO");
                $response = $dataService->Query("SELECT * FROM Item WHERE Name = '$itemName'");
                break;
            } catch (\Exception $e) {
                $this->logger->error("Trying to get item $itemName from QBO: {$e->getMessage()}");
                $tryCount++;
                if ($tryCount < $tryNumber)
                    usleep(10 * 1000); // wait before retry
            }
        } while ($tryCount < $tryNumber);

        if ($tryCount == $tryNumber) return null;
        if($response) {
            return reset($response);
        }

        $tryCount = 1;
        do {
            try {
                $this->pauseIfNeeded();
                $this->logger->info("Adding new item into QBO: \"$itemName\"");
                $response = $dataService->Add(
                    Item::create(
                        [
                            'Name' => $itemName,
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
                    sprintf('Item: %s create failed with error %s.', $itemName, $exception->getMessage())
                );

                $tryCount++;
                if ($tryCount < $tryNumber)
                    usleep(10 * 1000); // wait before retry
            }
        } while ($tryCount < $tryNumber);

        return null;
    }

    /**
     * @throws QBAuthorizationException
     */
    private function handleErrorResponse(DataService $dataService): void
    {
        /** @var FaultHandler $error */
        if ($error = $dataService->getLastError()) {
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

            $this->pauseIfNeeded();
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
