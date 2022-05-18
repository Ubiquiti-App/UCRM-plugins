<?php

declare(strict_types=1);


namespace QBExport\Facade;


use DateTime;
use QBExport\Data\InvoiceStatus;
use QBExport\Data\PluginData;
use QBExport\Exception\QBAuthorizationException;
use QBExport\Factory\DataServiceFactory;
use QBExport\Service\Logger;
use QBExport\Service\OptionsManager;
use QBExport\Service\UcrmApi;
use QuickBooksOnline\API\Core\HttpClients\FaultHandler;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Exception\ServiceException;
use QuickBooksOnline\API\Facades\CreditMemo;
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
     * @var int
     */
    private $qbApiTimeoutDelay = 70;

    /**
     * @var int
     */
    private $qbApiErrorDelay = 5;

    private $itemCache = [];
    private $depositToCache = [];

    /**
     * @var int
     */
    private $queryRunCount = 0;

    /**
     * @var DateTime
     */
    private $lastCall;

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

            $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);
            $this->getAndLogAccounts($dataService);
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

            $entities = $this->dataServiceQuery($dataService,
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

                $email = null;
                $emailCheck = $ucrmClient['contacts'][0]['email'];
                if (filter_var($emailCheck, FILTER_VALIDATE_EMAIL))
                    $email = $emailCheck;

                $customerData = [
                    'DisplayName' => sprintf(
                        '%s (UCRMID-%d)',
                        $nameForView,
                        $ucrmClient['id']
                    ),
                    'PrintOnCheckName' => $nameForView,
                    'GivenName' => $ucrmClient['firstName'],
                    'FamilyName' => $ucrmClient['lastName'],
                    'PrimaryEmailAddr' => [
                        'Address' => $email
                    ],
                    'Mobile' => [
                        'FreeFormNumber' => $ucrmClient['contacts'][0]['phone']
                    ],
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
                    $response = $this->dataServiceAdd($dataService, Customer::create($customerData));
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

        $account = $this->dataServiceQuery($dataService, $query);
        if ($account) {
            $qbIncomeAccountId = $account[0]->Id;
            $this->logger->debug("Found income account id $qbIncomeAccountId");
        } else {
            $this->logger->error("Unable to find Income account (ID {$pluginData->qbIncomeAccountNumber}, Name {$pluginData->qbIncomeAccountName}) set in the plugin config");
            return;
	    }

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

            $this->logger->debug(sprintf('Export of invoice ID %s started.', $ucrmInvoice['id']));

            $docNumber = "{$ucrmInvoice['number']}/{$ucrmInvoice['id']}";
            $invoice = $this->dataServiceQuery($dataService,"SELECT * FROM INVOICE WHERE DOCNUMBER = '$docNumber'");
            if ($invoice) {
                $this->logger->error(sprintf('Invoice %s already in QBO', $docNumber));
                continue;
            }

            $qbClient = $this->getQBClient($dataService, $ucrmInvoice['clientId']);

            if (! $qbClient) {
                $this->logger->error(
                    sprintf('Client with Display name containing: UCRMID-%s is not found.', $ucrmInvoice['clientId'])
                );
                continue;
            }

            $lines = $this->getItems($ucrmInvoice['items'], (int)$qbIncomeAccountId, false, $dataService, $pluginData);

            try {
                $this->logger->info(sprintf('Invoice::create DocNumber=%s DueDate=%s Total=%s',
                    sprintf('%s/%s', $ucrmInvoice['number'], $ucrmInvoice['id']), $ucrmInvoice['dueDate'], $ucrmInvoice['total']));

                $response = $this->dataServiceAdd($dataService,
                    Invoice::create(
                        [
                            'DocNumber' => sprintf('%s/%s', $ucrmInvoice['number'], $ucrmInvoice['id']),
                            'DueDate' => $ucrmInvoice['dueDate'],
                            'TxnDate' => $ucrmInvoice['createdDate'],
                            'Line' => $lines,
                            'TxnTaxDetail' => [
                                'TotalTax' => $ucrmInvoice['taxes']['totalValue'],
                                'TxnTaxCodeRef' => 'TAX',
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
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    sprintf(
                        'Invoice ID: %s export failed with error %s.',
                        $ucrmInvoice['id'],
                        $exception->getMessage()
                    )
                );

                // don't mark as done yet if there was error. If there's more successful exports after this
                //  then the final saved exportedInvoiceId will still be higher than this one which had error
                continue;
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

        $paymentIdComplete = null;
        $paymentMethodQbCache = null;
        foreach ($ucrmPayments as $ucrmPayment) {

            if (file_exists("data/stoppayments") || file_exists("data/stop")) {
                $this->logger->info(sprintf('exportPayments: stop file detected'));
                break;
            }

            $paymentId = $ucrmPayment['id'];
            if ($paymentId <= $pluginData->lastExportedPaymentID || ! $ucrmPayment['clientId']) {
                continue;
            }

            $qbClient = $this->getQBClient($dataService, $ucrmPayment['clientId']);

            if (! $qbClient) {
                $this->logger->error(
                    sprintf("Payment ID $paymentId export failed. Client with Display name containing: UCRMID-{$ucrmPayment['clientId']} is not found")
                );
                continue;
            }

            $paymentInfoText = "Payment ID $paymentId created {$ucrmPayment['createdDate']},creditAmount={$ucrmPayment['creditAmount']}," .
                "total={$ucrmPayment['amount']} for Client Id={$qbClient->Id} DisplayName={$qbClient->DisplayName}";
            $this->logger->debug("Exporting $paymentInfoText");

            $qbPaymentMethod = $paymentMethodQbCache[$ucrmPayment['methodId']];
            if (!$qbPaymentMethod) {
                $paymentMethod = $this->ucrmApi->query("payment-methods/{$ucrmPayment['methodId']}");
                $qbPaymentMethodResponse = $this->dataServiceQuery($dataService, "SELECT * FROM PaymentMethod WHERE Name = '{$paymentMethod['name']}'");
                if ($qbPaymentMethodResponse) {
                    $qbPaymentMethod = $qbPaymentMethodResponse[0];
                    $paymentMethodQbCache[$ucrmPayment['methodId']] = $qbPaymentMethod;
                }
            }

            [$lineArray, $additionalUnapplied, $totalApplied] = $this->getPaymentCovers($ucrmPayment['paymentCovers'], $dataService);

            try {
                $totalUnapplied = $ucrmPayment['creditAmount'] + $additionalUnapplied;
                if ($ucrmPayment['creditAmount'] > 0)
                    $this->logger->debug(sprintf('Non-applied credit amount was: %s, total set as unapplied will be: %s', $ucrmPayment['creditAmount'], $totalUnapplied));

                $refNumber = $ucrmPayment['checkNumber'];
                $note = $ucrmPayment['note'];
                if ($refNumber && trim($refNumber) != '') {
                    $refNumber = "Chk $refNumber";
                    if ($note && trim($note) != '')
                        $refNumber = $refNumber . ", $note";
                } else {
                    $refNumber = $note;
                }
                $paymentArray = [
                    'CustomerRef' => [
                        'value' => $qbClient->Id
                    ],
                    'TotalAmt' => $ucrmPayment['amount'],
                    'UnappliedAmt' => $totalUnapplied,
                    'Line' => $lineArray,
                    'TxnDate' => substr($ucrmPayment['createdDate'], 0, 10),
                    'PaymentRefNum' => $refNumber,
                    'PrivateNote' => "UCRM Payment ID " . $paymentId,
                ];

                if ($qbPaymentMethod) {
                    $this->logger->debug("Adding payment method; Id {$qbPaymentMethod->Id}, name {$qbPaymentMethod->Name}");
                    $paymentArray['PaymentMethodRef'] = [
                        'value' => $qbPaymentMethod->Id
                    ];

                    $depositToId = $this->getDepositToIdForPayment($qbPaymentMethod->Name, $dataService, $pluginData);
                    if ($depositToId)
                        $paymentArray['DepositToAccountRef'] = [
                            'value' => $depositToId
                        ];
                }

                $paymentObject = Payment::create($paymentArray);

                $response = $this->dataServiceAdd($dataService, $paymentObject);
                if ($response instanceof IPPIntuitEntity) {
                    $this->logger->info(
                        sprintf('Payment exported successfully. %s', $paymentInfoText)
                    );
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    sprintf(
                        'Payment ID: %s export failed with error %s. Info: %s',
                        $paymentId,
                        $exception->getMessage(),
                        $paymentInfoText
                    )
                );

                continue;
            }

            $paymentIdComplete = $paymentId;
        }

        if ($paymentIdComplete) {
            // update last processed payment in the end
            $pluginData->lastExportedPaymentID = $paymentIdComplete;
            $this->optionsManager->update();
        }
    }

    public function exportCreditNotes(): void
    {
        $pluginData = $this->optionsManager->load();
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_QUERY);

        if ($pluginData->qbIncomeAccountNumber == 0)
            $query = sprintf('SELECT * FROM Account WHERE AccountType = \'Income\' AND Name = \'%s\'', $pluginData->qbIncomeAccountName);
        else
            $query = sprintf('SELECT * FROM Account WHERE AccountType = \'Income\' AND Id = \'%s\'', $pluginData->qbIncomeAccountNumber);

        $account = $this->dataServiceQuery($dataService, $query);
        if ($account) {
            $qbIncomeAccountId = $account[0]->Id;
            $this->logger->debug("Found income account id $qbIncomeAccountId");
        } else {
            $this->logger->error("Unable to find Income account (ID {$pluginData->qbIncomeAccountNumber}, Name {$pluginData->qbIncomeAccountName}) set in the plugin config");
            return;
        }

        $query = 'credit-notes?direction=ASC';
        if ($pluginData->creditsFromDate) {
            $query = sprintf('%s&createdDateFrom=%s', $query, $pluginData->creditsFromDate);
        }

        foreach ($this->ucrmApi->query($query) as $ucrmCredit) {
            if (file_exists("data/stopcredits") || file_exists("data/stop")) {
                $this->logger->info(sprintf('exportCredits: stop file detected'));
                break;
            }

            if ($ucrmCredit['id'] <= $pluginData->lastExportedCreditID)
                continue;

            $this->logger->debug(sprintf('Export of credit memo ID %s started.', $ucrmCredit['id']));

            $qbClient = $this->getQBClient($dataService, $ucrmCredit['clientId']);

            if (!$qbClient) {
                $this->logger->error(
                    sprintf('Client with Display name containing: UCRMID-%s is not found.', $ucrmCredit['clientId'])
                );
                continue;
            }

            $docNumber = sprintf('C%s/%s', $ucrmCredit['number'], $ucrmCredit['id']);
            $credit = $this->dataServiceQuery($dataService,"SELECT * FROM CreditMemo WHERE DocNumber = '$docNumber'");
            if ($credit) {
                $this->logger->error(sprintf('Credit Memo %s already in QBO', $docNumber));
                continue;
            }

            $lines = $this->getItems($ucrmCredit['items'], (int)$qbIncomeAccountId, true, $dataService, $pluginData);

            $this->logger->info(sprintf('CreditMemo::create DocNumber=%s DueDate=%s Total=%s',
                $docNumber, $ucrmCredit['dueDate'], $ucrmCredit['total']));

            try {
                $response = $this->dataServiceAdd($dataService,
                    CreditMemo::create(
                        [
                            'DocNumber' => $docNumber,
                            'TxnDate' => $ucrmCredit['createdDate'],
                            'Line' => $lines,
                            'TxnTaxDetail' => [
                                'TotalTax' => 0-$ucrmCredit['taxes']['totalValue'],
                                'TxnTaxCodeRef' => 'TAX',
                            ],
                            'CustomerRef' => [
                                'value' => $qbClient->Id,
                            ],
                        ]
                    )
                );

                if ($response instanceof IPPIntuitEntity) {
                    $this->logger->info(
                        sprintf('Credit Memo ID: %s exported successfully.', $ucrmCredit['id'])
                    );
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    sprintf(
                        'Credit ID: %s export failed with error %s.',
                        $ucrmCredit['id'],
                        $exception->getMessage()
                    )
                );

                // don't mark as done yet if there was error. If there's more successful exports after this
                //  then the final saved exportedCreditId will still be higher than this one which had error
                continue;
            }

            if ($response instanceof IPPIntuitEntity) {
                $txnId = $response->Id;
                [$lineArray, $additionalUnapplied, $totalApplied] = $this->getPaymentCovers($ucrmCredit['paymentCovers'], $dataService);

                if (!$lineArray || $totalApplied == 0 || $additionalUnapplied <> 0) {
                    if ($lineArray && $totalApplied <> 0) // issue is with additionalUnapplied not being 0. Credit can only be applied if all invoice(s) are found to link to
                        $this->logger->warning("Will not apply credit to invoices because not all invoices could be found");
                } else {
                    try {
                        $lineArray[] = Line::create(
                            [
                                'Amount' => $totalApplied,
                                'LinkedTxn' => [
                                    'TxnId' => $txnId,
                                    'TxnType' => 'CreditMemo',
                                ],
                            ]
                        );

                        $paymentArray = [
                            'CustomerRef' => [
                                'value' => $qbClient->Id
                            ],
                            'TotalAmt' => 0.0,
                            'Line' => $lineArray,
                            'TxnDate' => substr($ucrmCredit['createdDate'], 0, 10),
                            'PaymentRefNum' => 'Auto-apply credit',
                            'PrivateNote' => 'Created by UCRM to link credits to charges',
                        ];

                        $paymentObject = Payment::create($paymentArray);

                        $response = $this->dataServiceAdd($dataService, $paymentObject);
                        if ($response instanceof IPPIntuitEntity) {
                            $this->logger->info(
                                sprintf('Payment to apply credit exported successfully. Total credit was: %s, Credit applied: %s', $ucrmCredit['total'], $totalApplied)
                            );
                        }
                    } catch (\Exception $exception) {
                        $this->logger->error(
                            sprintf(
                                'Payment to apply credit export failed with error %s',
                                $exception->getMessage()
                            )
                        );
                    }
                }
            }

            $pluginData->lastExportedCreditID = $ucrmCredit['id'];
            $this->optionsManager->update();
        } // end foreach $ucrmCredit
    }

    private function getPaymentCovers($payments, DataService $dataService): array {
        $lineArray = null;
        $additionalUnapplied = 0.0;
        $totalApplied = 0.0;
        foreach ($payments as $paymentCovers) {
            if ($paymentCovers['amount'] == 0) continue;

            if ($paymentCovers['refundId']) {
                $this->logger->notice('Payment has refundId, not yet supported! Will set as unapplied');
                $additionalUnapplied += $paymentCovers['amount'];
                continue;
            }
            if ($paymentCovers['invoiceId'] == '') {
                $this->logger->notice('Payment has empty invoiceId! Will set as unapplied');
                $additionalUnapplied += $paymentCovers['amount'];
                continue;
            }

            $invId = $paymentCovers['invoiceId'];
            $invoices = $this->dataServiceQuery($dataService,"SELECT * FROM INVOICE WHERE DOCNUMBER LIKE '%/$invId'");
            if (!$invoices)
                $invoices = $this->dataServiceQuery($dataService,"SELECT * FROM INVOICE WHERE DOCNUMBER = '$invId'");

            if (!$invoices) {
                $this->logger->warning(sprintf('Unable to find invoiceId %s covered by payment, will set as unapplied', $paymentCovers['invoiceId']));
                $additionalUnapplied += $paymentCovers['amount'];
                continue;
            }

            $lineArray[] = Line::create(
                [
                    'Amount' => $paymentCovers['amount'],
                    'LinkedTxn' => [
                        'TxnId' => $invoices[0]->Id,
                        'TxnType' => 'Invoice',
                    ],
                ]
            );

            $totalApplied += $paymentCovers['amount'];
            $this->logger->debug("Payment applying \${$paymentCovers['amount']} to Invoice {$invoices[0]->DocNumber}");
        }

        return [$lineArray, $additionalUnapplied, $totalApplied];
    }

    private function getItems($items, int $qbIncomeAccountId, bool $negateQty, DataService $dataService, PluginData $pluginData): array {
        $lines = [];
        foreach ($items as $item) {
            $qbItem = $this->itemCache[$item['label']];
            if (! $qbItem) {
                $qbItem = $this->createQBLineFromItem(
                    $dataService,
                    $item,
                    $qbIncomeAccountId,
                    $pluginData->itemNameFormat
                );
                if (! $qbItem) {
                    $this->logger->error("Could not get item \"{$item['label']}\" from QBO");
                    continue;
                }

                $this->itemCache[$item['label']] = $qbItem;
            }

            if ($item['tax1Id']) {
                $TaxCode = 'TAX';
            } else {
                $TaxCode = 'NON';
            }

            $this->logger->debug(sprintf('Description=%s TaxCode=%s', $item['label'], $TaxCode));

            $lines[] = [
                'Amount' => $this->negateAmount($item['total'], $negateQty),
                'Description' => $item['label'],
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => [
                        'value' => $qbItem->Id,
                    ],
                    'UnitPrice' => $this->negateAmount($item['price'], $negateQty),
                    'Qty' => $this->negateAmount($item['quantity'], $negateQty),
                    'TaxCodeRef' => [
                        'value' => $TaxCode,
                    ],
                ],
            ];
            if ($item['discountTotal'] < 0) {
                $lines[] = [
                    'Amount' => 0-$item['discountTotal'],
                    'DiscountLineDetail' => [
                        'PercentBased' => 'false',
                    ],
                    'DetailType' => 'DiscountLineDetail',
                    'Description' => 'Discount given',
                ];
            }
        }

        return $lines;
    }

    private function negateAmount($amount, $doNegate) {
        if ($doNegate)
            return 0-$amount;
        else
            return $amount;
    }

    /**
     * @throws QBAuthorizationException
     * @throws \Exception
     */
    private function dataServiceQuery(DataService $dataService, string $query, bool $throwForErrors = false): ?array {
        $tryCount = 0;
        $tryNumber = 3;
        $output = null;
        do {
            try {
                $this->pauseIfNeeded();
                $output = $dataService->Query($query);
                break;
            } catch (\Exception $e) {
                $tryCount++;
                if ($tryCount < $tryNumber) {
                    $this->logger->info("Waiting {$this->qbApiErrorDelay} seconds to retry after issue getting data from QBO");
                    sleep($this->qbApiErrorDelay);
                }
                elseif ($throwForErrors)
                    throw $e;
            }
        } while ($tryCount < $tryNumber);

        if ($throwForErrors) {
            if ($output instanceof \Exception)
                throw $output;
            $this->handleErrorResponse($dataService);
        }

        return $output;
    }

    /**
     * @throws QBAuthorizationException
     * @throws \QuickBooksOnline\API\Exception\IdsException
     * @throws \Exception
     */
    private function dataServiceAdd(DataService $dataService, IPPIntuitEntity $entity, bool $throwForErrors = true) {
        $tryCount = 0;
        $tryNumber = 3;
        $output = null;
        do {
            try {
                $this->pauseIfNeeded();
                $output = $dataService->Add($entity);
                break;
            } catch (\Exception $e) {
                $tryCount++;
                if ($tryCount < $tryNumber) {
                    $this->logger->info("Waiting {$this->qbApiErrorDelay} seconds to retry after issue getting data from QBO");
                    sleep($this->qbApiErrorDelay);
                }
                elseif ($throwForErrors)
                    throw $e;
            }
        } while ($tryCount < $tryNumber);

        if ($throwForErrors) {
            if ($output instanceof \Exception)
                throw $output;
            $this->handleErrorResponse($dataService);
        }

        return $output;
    }

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
            $this->logger->notice("Now waiting {$this->qbApiTimeoutDelay} to run next QB api call because there were at least $callsBeforeWait calls in the last minute");
            sleep($this->qbApiTimeoutDelay);
        }

        $this->queryRunCount++;
        $this->lastCall = date_create();
    }

    private function getQBClient(DataService $dataService, int $ucrmClientId)
    {
        $customers = null;
        try {
            $customers = $this->dataServiceQuery($dataService,
                sprintf('SELECT * FROM Customer WHERE DisplayName LIKE \'%%(UCRMID-%d)\'', $ucrmClientId),
                false, true
            );
        } catch (\Exception $e) {
            $this->logger->error("Could not get customer from QBO; id $ucrmClientId; error {$e->getMessage()}");
        }

        if (!$customers) {
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

        $response = null;
        try {
            $this->logger->debug("Get item \"$itemName\" from QBO");
            $response = $this->dataServiceQuery($dataService,"SELECT * FROM Item WHERE Name = '$itemName'", false, true);
        } catch (\Exception $e) {
            $this->logger->error("Trying to get item $itemName from QBO: {$e->getMessage()}");
        }

        if($response) {
            return reset($response);
        }

        try {
            $this->logger->info("Adding new item into QBO: \"$itemName\"");
            return $this->dataServiceAdd($dataService,
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
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Item: %s create failed with error %s.', $itemName, $exception->getMessage())
            );
        }

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

    private function getAndLogAccounts(DataService $dataService): void
    {
        try {
            $response = $this->dataServiceQuery($dataService,"SELECT * FROM Account WHERE Active = true", false, true);

            $accountsString = '';
            foreach ($response as $account)
                $accountsString .= 'Account:' . $account->Name . ' ID: ' . $account->Id . PHP_EOL;

            $this->logger->info("Income account numbers in QBO Active accounts:\n$accountsString");
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Account: Getting all Accounts failed with error %s.', $exception->getMessage())
            );
        }
    }

    private function getDepositToIdForPayment(string $paymentMethodName, DataService $dataService, PluginData $pluginData): ?string
    {
        $links = $pluginData->paymentTypeWithAccountLink;
        if (!$links) return null;
        $this->logger->debug("Checking DepositTo user saved info: $links");
        $lines = preg_split("(\r\n|\n|\r)", $links);
        if (!$lines) return null;

        foreach ($lines as $line) {
            if (trim($line) == '') continue;
            $this->logger->debug("Checking DepositTo line: $line");
            $methodAcct = explode("=", $line, 2);
            if (!$methodAcct) continue;

            if (count($methodAcct) != 2) {
                $this->logger->warning("Line for link payment method does not have 2 parts (separated by = sign): $line");
                continue;
            }
            $this->logger->debug("Checking DepositTo line: [0]={$methodAcct[0]} [1]={$methodAcct[1]}");

            $payType = trim($methodAcct[0]);
            if ($payType != $paymentMethodName) continue;

            $cachedId = $this->depositToCache[$payType];
            if ($cachedId)
                return $cachedId;

            $depositAcct = trim($methodAcct[1]);
            $accounts = $this->dataServiceQuery($dataService, "SELECT * FROM Account WHERE Name = '$depositAcct'");
            if ($accounts) {
                $useAccount = null;
                foreach ($accounts as $account) {
                    if ($account->AccountType == 'Bank' || $account->AccountType == 'Other Current Asset') {
                        $useAccount = $account;
                        break;
                    }
                }

                if ($useAccount) {
                    $id = $useAccount->Id;
                    $this->depositToCache[$payType] = $id;
                    $this->logger->debug("Found account for payment \"deposit to\" with Id $id");
                    return $id;
                }
            }

            $this->logger->warning("Payment \"deposit to\" account not found for \"$depositAcct\"");

            break;
        }

        return null;
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
