<?php

declare(strict_types=1);

function loadConfig(string $file): array
{
    $content = file_get_contents($file);

    return json_decode($content, true);
}

function retrieveCurrentUser(string $ucrmPublicUrl): array
{
    $url = sprintf('%scurrent-user', $ucrmPublicUrl);

    $headers = [
        'Content-Type: application/json',
        'Cookie: PHPSESSID=' . preg_replace('~[^a-zA-Z0-9]~', '', $_COOKIE['PHPSESSID'] ?? ''),
    ];

    return curlQuery($url, $headers);
}

function curlQuery(string $url, array $headers = [], array $parameters = []): array
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
        throw new \Exception(sprintf('Error for request %s. Curl error %s: %s', $url, $errno, $error));
    }

    $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new \Exception(
            sprintf('Error for request %s. HTTP error (%s): %s', $url, $httpCode, $result),
            $httpCode
        );
    }

    curl_close($c);

    if (! $result) {
        throw new \Exception(sprintf('Error for request %s. Empty result.', $url));
    }

    $decodedResult = json_decode($result, true);

    if ($decodedResult === null) {
        throw new \Exception(
            sprintf('Error for request %s. Failed JSON decoding. Response: %s', $url, $result)
        );
    }

    return $decodedResult;
}
