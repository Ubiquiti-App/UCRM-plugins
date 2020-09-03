<?php

declare(strict_types=1);

namespace QBExport\Service;

class PluginDataValidator
{
    private const QB_BASE_URL_CHOICES = [
        'Development',
        'Production',
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var array
     */
    private $errors = [];

    public function __construct(Logger $logger, OptionsManager $optionsManager)
    {
        $this->logger = $logger;
        $this->optionsManager = $optionsManager;
    }

    public function validate(): bool
    {
        $pluginData = $this->optionsManager->load();
        $valid = true;
        if (! \in_array($pluginData->qbBaseUrl, self::QB_BASE_URL_CHOICES, true)) {
            $this->errors[] = sprintf(
                'Not valid configuration: baseUrl must be %s',
                implode(' or ', self::QB_BASE_URL_CHOICES)
            );
            $valid = false;
        }

        if ($pluginData->qbAuthorizationUrl && ! $this->errors && ! $pluginData->oauthCode && ! $pluginData->oauthRealmID) {
            $this->errors[] = sprintf(
                'Codes are not received. Configure this public URL as Redirect URI in QuickBook App and confirm connection in Authorization URL displayed in this log. %s',
                $pluginData->qbAuthorizationUrl
            );
            $valid = false;
        }

        $this->logErrors();

        return $valid;
    }

    private function logErrors(): void
    {
        $pluginData = $this->optionsManager->load();
        if ($this->errors) {
            $errorString = implode(PHP_EOL, $this->errors);
            if ($this->errors && $errorString !== $pluginData->displayedErrors) {
                $this->logger->error($errorString);
                $pluginData->displayedErrors = $errorString;
                $this->optionsManager->update();
            }
        } else {
            $pluginData->displayedErrors = null;
            $this->optionsManager->update();
        }
    }
}
