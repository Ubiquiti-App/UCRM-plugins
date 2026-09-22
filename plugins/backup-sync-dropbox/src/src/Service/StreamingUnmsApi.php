<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Service;

use Ubnt\UcrmPluginSdk\Service\UnmsApi;

final class StreamingUnmsApi extends UnmsApi
{
    /**
     * Returns the response body as a stream instead of loading it into memory.
     *
     * @return resource
     */
    public function getStream(string $endpoint)
    {
        $response = $this->request('GET', $endpoint);

        return $response->getBody()->detach();
    }
}
