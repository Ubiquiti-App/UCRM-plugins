<?php

    require_once __DIR__ . '/vendor/autoload.php';

    class UCRMAPIAccess {
        const API_URL = 'YOUR_UISP_URL';
        const API_KEY = 'YOUR_API_KEY';

        /**
         * @param string $url
         * @param string $method
         * @param array $_POST
         * 
         * @return array|null
         */

        public static function doRequest($url, $method = 'GET', $post = []) {
            $method = strtoupper($method);

            $ch = curl_init();

            curl_setopt(
                $ch,
                CURLOPT_URL,
                sprintf('%s/%s', self::API_URL, $url)
            );

            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    sprintf('X-Auth-App-Key: %s', self::API_KEY),
                ]
            );

            if($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            $response = curl_exec($ch);

            if(curl_errno($ch) !== 0) {
                echo sprintf('Curl error: %s', curl_error($ch)) . PHP_EOL;
            }

            if(curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
                echo sprintf('API error: %s', $response) . PHP_EOL;
                $response = false;
            }

            curl_close($ch);
            return $response !== false ? json_decode($response, true) : null;
        }
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
    
    set_time_limit(0);
