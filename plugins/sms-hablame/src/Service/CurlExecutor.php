<?php

declare(strict_types=1);


namespace SmsNotifier\Service;


use SmsNotifier\Exception\CurlException;

class CurlExecutor
{
    /**
     * @throws CurlException
     */
    public function curlCommand($url, $method, array $headers = [], $data = null, bool $verifySsl = true): void
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        if ($verifySsl) {
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            // we are disabling verification by request
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $result = curl_exec($c);

        $error = curl_error($c);
        $errno = curl_errno($c);

        if ($errno || $error) {
            throw new CurlException("Error for request $url. Curl error $errno: $error");
        }

        $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new CurlException("Error for request $url. HTTP error ($httpCode): $result", $httpCode);
        }

        curl_close($c);
    }

    /**
     * @throws CurlException
     */
    public function curlQuery($url, array $headers = [], array $parameters = [], bool $verifySsl = true): array
    {
        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        if ($verifySsl) {
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            // we are disabling verification by request
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $result = curl_exec($c);

        $error = curl_error($c);
        $errno = curl_errno($c);

        if ($errno || $error) {
            throw new CurlException("Error for request $url. Curl error $errno: $error");
        }

        $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new CurlException("Error for request $url. HTTP error ($httpCode): $result", $httpCode);
        }

        curl_close($c);

        if (! $result) {
            throw new CurlException("Error for request $url. Empty result.");
        }

        return json_decode($result, true);
    }
}
