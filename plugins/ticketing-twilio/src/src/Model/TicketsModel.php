<?php

declare(strict_types=1);

namespace TicketingTwilio\Model;

use DateTimeImmutable;
use TicketingTwilio\Plugin;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

class TicketsModel
{
    /**
     * @var mixed[]
     */
    private $tickets;


    public function __construct(array $tickets)
    {
        $this->tickets = $tickets;
    }

    public static function create(): self
    {
        $config = PluginConfigManager::create()->loadConfig();
        $dateFrom = (new DateTimeImmutable($config[Plugin::MANIFEST_CONFIGURATION_KEY_LAST_IMPORTED_DATE]))
            ->format('Y-m-d');

        $tickets = UcrmApi::create()->get(
            'ticketing/tickets',
            [
                'dateFrom' => $dateFrom,
            ]
        );

        return new self($tickets);
    }

    public function existTicket(MessageInstance $messageInstance): bool
    {
        return (bool) array_filter(
            $this->tickets,
            static function ($ticket) use ($messageInstance) {
                return $messageInstance->dateCreated == new DateTimeImmutable($ticket['createdAt'])
                    && $messageInstance->from === $ticket['phoneFrom'];
            }
        );
    }
}
