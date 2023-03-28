<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths(
        [
            __DIR__ . '/examples',
            __DIR__ . '/plugins/argentina-afip-invoices',
            __DIR__ . '/plugins/routeros-suspension',
            __DIR__ . '/plugins/invoice-csv-export',
            __DIR__ . '/plugins/mkt-queue-sync',
            __DIR__ . '/plugins/revenue-report',
            __DIR__ . '/plugins/backup-sync-dropbox',
            __DIR__ . '/plugins/ucrm-client-signup',
        ]
    );

    $ecsConfig->sets([
        SetList::SPACES,
        SetList::ARRAY,
        SetList::DOCBLOCK,
        SetList::PSR_12,
        SetList::CONTROL_STRUCTURES,
    ]);
};
