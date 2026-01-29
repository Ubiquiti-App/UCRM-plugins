<?php

declare(strict_types=1);

namespace Ucsp;

class Interpreter
{
    private static $whitelist = [
        'GET' => [
            'countries' => [],
            'countries/states' => [
                'countryId' => null,
            ],
        ],
        'POST' => [
            'clients' => [
                'clientType' => null,
                'isLead' => null,
                'firstName' => null,
                'lastName' => null,
                'street1' => null,
                'street2' => null,
                'city' => null,
                'countryId' => null,
                'stateId' => null,
                'zipCode' => null,
                'username' => null,
                'contacts' => [
                    [
                        'email' => null,
                        'phone' => null,
                        'name' => null,
                    ],
                ],
            ],
        ],
    ];

    private static $dataUrl = null;

    private $response;

    private $code;

    private $ready = false;

    public function __construct()
    {
        $this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
    }

    public static function setDataUrl($dataUrl)
    {
        self::$dataUrl = $dataUrl;
    }

    public static function setFrontendKey($key): bool
    {
        if (! file_exists(self::$dataUrl . 'frontendKey')) {
            file_put_contents(self::$dataUrl . 'frontendKey', $key, LOCK_EX);

            return true;
        }

        return false;
    }

    public static function getFrontendKey()
    {
        if (file_exists(self::$dataUrl . 'frontendKey')) {
            return file_get_contents(self::$dataUrl . 'frontendKey');
        }

        return false;
    }

    public function isReady()
    {
        return $this->ready;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function get($endpoint, $data)
    {
        return $this->api->get($endpoint, $data);
    }

    public function post($endpoint, $data)
    {
        return $this->api->post($endpoint, $data);
    }

    public function run(string $input): void
    {
        // no input means nothing to run
        if ($input === '') {
            return;
        }

        try {
            $payload = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \UnexpectedValueException('invalid request', 400);
        }

        if (
            ! array_key_exists('frontendKey', $payload)
            || ! array_key_exists('api', $payload)
        ) {
            throw new \UnexpectedValueException('invalid request', 400);
        }

        $explode = explode('||', (string) $payload['frontendKey'], 2);
        $payloadFrontendKey = $explode[0] ?? null;
        $payloadCsrfToken = $explode[1] ?? null;

        $data = $payload['api']['data'] ?? null;

        if (
            $payloadFrontendKey !== self::getFrontendKey()
            || $payloadCsrfToken === null
            || $payloadCsrfToken !== $_SESSION['csrf_token']
            || ! is_string(($payload['api']['endpoint'] ?? null))
            || ! is_string(($payload['api']['type'] ?? null))
            || ! self::validateRequest($payload['api']['type'], $payload['api']['endpoint'], $data)
        ) {
            throw new \UnexpectedValueException('invalid request', 400);
        }

        try {
            $method = strtoupper($payload['api']['type']);

            if ($method === 'GET') {
                $response = $this->get($payload['api']['endpoint'], $data);
            } elseif ($method === 'POST') {
                $response = $this->post($payload['api']['endpoint'], $data);
            } else {
                throw new \UnexpectedValueException('invalid request', 400);
            }

            $this->code = 200;
            $this->response = json_encode($response);
            $this->ready = true;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->response = $e->getResponse()->getBody()->getContents();
            $this->code = $e->getCode();
            $this->ready = true;
        }
    }

    private static function validateRequest(string $method, string $endpoint, $data): bool
    {
        $method = strtoupper($method);
        $endpoint = trim($endpoint, '/');

        // check endpoint exists
        if (
            (self::$whitelist[$method] ?? null) === null
            || (self::$whitelist[$method][$endpoint] ?? null) === null
        ) {
            return false;
        }

        // endpoint exists and no data to validate, pass
        if ($data === null) {
            return true;
        }

        // data must be array at this point
        if (! is_array($data)) {
            return false;
        }

        return self::validateFields($data, self::$whitelist[$method][$endpoint]);
    }

    private static function validateFields(array $data, array $schema): bool
    {
        foreach ($data as $key => $value) {
            // key must be whitelisted
            if (! array_key_exists($key, $schema)) {
                return false;
            }

            // null = anything other than array allowed
            if ($schema[$key] === null) {
                if (is_array($value)) {
                    return false;
                }

                continue;
            }

            // must be array here, regular fields handled by null schema values above
            if (! is_array($value)) {
                return false;
            }

            foreach ($value as $item) {
                if (
                    ! is_array($item)
                    // schema = $schema[$key][0] as we only have first item defined in schema, see client contacts
                    || ! self::validateFields($item, $schema[$key][0] ?? $schema[$key])
                ) {
                    return false;
                }
            }
        }

        return true;
    }
}
