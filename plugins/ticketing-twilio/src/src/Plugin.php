<?php

declare(strict_types=1);

namespace TicketingTwilio;

use TicketingTwilio\Service\SmsImporter;

class Plugin
{
    public const MANIFEST_CONFIGURATION_KEY_SID = 'twilioSid';
    public const MANIFEST_CONFIGURATION_KEY_TOKEN = 'twilioToken';
    public const MANIFEST_CONFIGURATION_KEY_LAST_IMPORTED_DATE = 'lastImportedDate';

    /**
     * @var SmsImporter
     */
    private $importer;

    public function __construct(SmsImporter $importer)
    {
        $this->importer = $importer;
    }

    public function run(): void
    {
        $this->importer->importToTicketing();
    }
}
