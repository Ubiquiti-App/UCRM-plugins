<?php

declare(strict_types=1);

namespace TicketingTwilio\Service;

use DateTimeImmutable;
use DateTimeZone;
use TicketingTwilio\Model\TicketsModel;
use TicketingTwilio\Plugin;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

class SmsImporter
{
    /**
     * @var TwilioClientFactory
     */
    private $twilioClientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    public function __construct(
        TwilioClientFactory $twilioClientFactory,
        Logger $logger
    ) {
        $this->twilioClientFactory = $twilioClientFactory;
        $this->logger = $logger;

        $this->ucrmApi = UcrmApi::create();
    }

    public function importToTicketing(): void
    {
        $config = PluginConfigManager::create()->loadConfig();
        $dateSentAfter = (new DateTimeImmutable($config[Plugin::MANIFEST_CONFIGURATION_KEY_LAST_IMPORTED_DATE]))
            ->setTimezone(new DateTimeZone('GMT'))
            ->modify('-1 day')
            ->format('Y-m-d');

        $this->logger->info(sprintf('Starts read of messages sent after %s (GMT).', $dateSentAfter));

        $ticketModel = TicketsModel::create();
        $client = $this->twilioClientFactory->create();
        foreach ($client->messages->read(['dateSentAfter' => $dateSentAfter]) as $messageInstance) {
            if ($messageInstance->direction !== 'inbound' || $ticketModel->existTicket($messageInstance)) {
                continue;
            }

            $this->createTicketComment($messageInstance);
        }
        $this->logger->info('Ends read of messages.');
    }

    private function createTicketComment(MessageInstance $messageInstance): void
    {
        $this->logger->info('Creating ticket.');
        $ticket = $this->ucrmApi->post(
            'ticketing/tickets',
            [
                'subject' => $this->createSubject($messageInstance),
                'createdAt' => $messageInstance->dateCreated->format(DATE_ATOM),
                'phoneFrom' => $messageInstance->from,
                'clientId' => $this->findClientId($messageInstance->from),
                'activity' => [
                    [
                        'createdAt' => $messageInstance->dateCreated->format(DATE_ATOM),
                        'comment' => [
                            'body' => $messageInstance->body,
                        ],
                    ],
                ],
            ]
        );

        $this->logger->info(sprintf('Ticket ID %s created.', $ticket['id'] ?? ''));
    }

    private function findClientId(string $from): ?int
    {
        $clients = $this->ucrmApi->get('clients', ['phone' => $from, 'limit' => 1]);

        if ($clients === []) {
            $this->logger->info(sprintf('Client with phone %s not found.', $from));

            return null;
        }

        return reset($clients)['id'];
    }

    private function createSubject(MessageInstance $messageInstance): string
    {
        return mb_substr(sprintf('Message from Twilio SMS: %s ', $messageInstance->body), 0, 120);
    }
}
