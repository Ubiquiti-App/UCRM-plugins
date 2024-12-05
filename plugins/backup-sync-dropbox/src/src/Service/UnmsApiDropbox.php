<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Service;

use GuzzleHttp\RequestOptions;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

final class UnmsApiDropbox extends UnmsApi
{
    public function getSink(string $endpoint, string $filePath): void
    {
        $this->request(
            'GET',
            $endpoint,
            [
                RequestOptions::SINK => $filePath,
            ]
        );
    }
}
