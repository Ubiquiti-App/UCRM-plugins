<?php

declare(strict_types=1);

namespace TicketingTwilio\Service;

use TicketingTwilio\Plugin;
use Twilio\Rest\Client;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;

class TwilioClientFactory
{
    /**
     * @var PluginConfigManager
     */
    private $pluginConfigManager;

    public function __construct(PluginConfigManager $pluginConfigManager)
    {
        $this->pluginConfigManager = $pluginConfigManager;
    }

    public function create(): Client
    {
        $config = $this->pluginConfigManager->loadConfig();

        return new Client(
            $config[Plugin::MANIFEST_CONFIGURATION_KEY_SID] ?? null,
            $config[Plugin::MANIFEST_CONFIGURATION_KEY_TOKEN] ?? null
        );
    }
}
