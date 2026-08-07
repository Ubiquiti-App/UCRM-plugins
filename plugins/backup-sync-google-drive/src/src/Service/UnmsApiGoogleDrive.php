<?php

declare(strict_types=1);

namespace BackupSyncGoogleDrive\Service;

use GuzzleHttp\RequestOptions;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

/**
 * Extends the SDK's UnmsApi with a streaming GET, used to download backup
 * archives without buffering them in memory (UnmsApi::get() always calls
 * getBody()->getContents(), which loads the entire response into a PHP
 * string - fine for small JSON responses, not for multi-gigabyte backups).
 *
 * Ported from the sibling backup-sync-dropbox plugin's UnmsApiDropbox,
 * which itself mirrors Ubiquiti's own fix for this problem:
 * https://github.com/Ubiquiti-App/UCRM-plugins/pull/341
 */
final class UnmsApiGoogleDrive extends UnmsApi
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
