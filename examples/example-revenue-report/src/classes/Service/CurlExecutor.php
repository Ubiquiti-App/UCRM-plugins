<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\CurlException;

class CurlExecutor
{
    /**
     * @throws CurlException
     */
    public function curlCommand($url, $method, array $headers = [], array $data = null): void
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode((object) $data));
        }

        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);

        $result = curl_exec($c);

        $error = curl_error($c);
        $errno = curl_errno($c);

        if ($errno || $error) {
            throw new CurlException(sprintf('Error for request %s. Curl error %s: %s', $url, $errno, $error));
        }

        $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new CurlException(
                sprintf('Error for request %s. HTTP error (%s): %s', $url, $httpCode, $result),
                $httpCode
            );
        }

        curl_close($c);
    }

    /**
     * @throws CurlException
     */
    public function curlQuery($url, array $headers = [], array $parameters = []): array
    {
        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);

        $result = curl_exec($c);

        $error = curl_error($c);
        $errno = curl_errno($c);

        if ($errno || $error) {
            throw new CurlException(sprintf('Error for request %s. Curl error %s: %s', $url, $errno, $error));
        }

        $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new CurlException(
                sprintf('Error for request %s. HTTP error (%s): %s', $url, $httpCode, $result),
                $httpCode
            );
        }

        curl_close($c);

        if (! $result) {
            throw new CurlException(sprintf('Error for request %s. Empty result.', $url));
        }

        $data = json_decode($result, true);

        if (! is_array($data)) {
            throw new CurlException(
                sprintf('Error for request %s. Failed JSON decoding. Response: %s', $url, $result)
            );
        }

        return $data;
    }
}
