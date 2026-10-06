<?php

declare(strict_types=1);

namespace BackupSyncGoogleDrive\Utility;

use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Builds a log-friendly description of a Throwable.
 *
 * $throwable->getMessage() alone doesn't say whether a failure came from
 * the UNMS API or the Google Drive API, or what HTTP status/error detail
 * was involved - which makes a transient failure impossible to tell apart
 * from a real bug after the fact.
 */
final class ThrowableFormatter
{
    private const MAX_BODY_PREVIEW = 500;

    public static function describe(Throwable $throwable): string
    {
        $parts = [
            sprintf('%s: %s', get_class($throwable), $throwable->getMessage()),
        ];

        if ($throwable instanceof GoogleServiceException) {
            $parts[] = sprintf('HTTP status: %d', $throwable->getCode());

            $errors = $throwable->getErrors();
            if (! empty($errors)) {
                $preview = json_encode($errors);
                if (is_string($preview) && strlen($preview) > self::MAX_BODY_PREVIEW) {
                    $preview = substr($preview, 0, self::MAX_BODY_PREVIEW) . '... (truncated)';
                }
                $parts[] = 'Errors: ' . $preview;
            }
        } elseif ($throwable instanceof GuzzleException) {
            $hasRequest = method_exists($throwable, 'getRequest');

            if ($hasRequest) {
                try {
                    $request = $throwable->getRequest();
                    $parts[] = sprintf('Request: %s %s', $request->getMethod(), (string) $request->getUri());
                } catch (Throwable $exception) {
                    // best-effort only - never let diagnostic formatting
                    // itself throw and mask the original error.
                }
            }

            if (method_exists($throwable, 'hasResponse') && $throwable->hasResponse()) {
                $response = $throwable->getResponse();
                $parts[] = sprintf(
                    'Response: %d %s%s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    self::bodyPreview($response)
                );
            } elseif ($hasRequest) {
                $parts[] = 'Response: (none - connection-level failure)';
            }
        }

        $previous = $throwable->getPrevious();
        if ($previous !== null) {
            $parts[] = 'Caused by: ' . self::describe($previous);
        }

        return implode(' | ', $parts);
    }

    private static function bodyPreview(ResponseInterface $response): string
    {
        try {
            $body = (string) $response->getBody();
        } catch (Throwable $exception) {
            return '';
        }

        if ($body === '') {
            return '';
        }

        if (strlen($body) > self::MAX_BODY_PREVIEW) {
            $body = substr($body, 0, self::MAX_BODY_PREVIEW) . '... (truncated)';
        }

        return ' - ' . $body;
    }
}
