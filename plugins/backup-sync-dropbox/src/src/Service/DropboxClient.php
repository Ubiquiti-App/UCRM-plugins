<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Service;

use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\UploadSessionCursor;

final class DropboxClient extends Client
{
    /**
     * Uploads a file in chunks and correctly stops when a sized stream
     * reaches its exact end.
     *
     * @return array<mixed>
     */
    public function uploadChunked(
        string $path,
        mixed $contents,
        string $mode = 'add',
        ?int $chunkSize = null
    ): array {
        if ($chunkSize === null || $chunkSize > $this->maxChunkSize) {
            $chunkSize = $this->maxChunkSize;
        }

        $stream = $this->getStream($contents);

        $cursor = $this->uploadChunk(
            self::UPLOAD_SESSION_START,
            $stream,
            $chunkSize,
            null
        );

        while (! $stream->eof()) {
            $streamSize = $stream->getSize();

            if ($streamSize !== null && $stream->tell() >= $streamSize) {
                break;
            }

            $cursor = $this->uploadChunk(
                self::UPLOAD_SESSION_APPEND,
                $stream,
                $chunkSize,
                $cursor
            );
        }

        return $this->uploadSessionFinish('', $cursor, $path, $mode);
    }

    protected function uploadChunk(
        int $type,
        StreamInterface &$stream,
        int $chunkSize,
        ?UploadSessionCursor $cursor = null
    ): UploadSessionCursor {
        $maximumTries = $stream->isSeekable() ? $this->maxUploadChunkRetries : 0;
        $pos = $stream->tell();

        if ($this->isDebugEnabled()) {
            $chunkType = $type === self::UPLOAD_SESSION_START ? 'START' : 'APPEND';

            $this->writeDebugLog(sprintf(
                'Dropbox chunk %s starting at offset %d, size %d.',
                $chunkType,
                $pos,
                $chunkSize
            ));
        }

        $tries = 0;

        tryUpload:
        try {
            ++$tries;

            $chunkStream = new Psr7\LimitStream(
                $stream,
                $chunkSize,
                $stream->tell()
            );

            if ($type === self::UPLOAD_SESSION_START) {
                $result = $this->uploadSessionStart($chunkStream);

                if ($this->isDebugEnabled()) {
                    $this->writeDebugLog(sprintf(
                        'Dropbox chunk START completed; stream offset now %d.',
                        $stream->tell()
                    ));
                }

                return $result;
            }

            if ($type === self::UPLOAD_SESSION_APPEND && $cursor !== null) {
                $result = $this->uploadSessionAppend($chunkStream, $cursor);

                if ($this->isDebugEnabled()) {
                    $this->writeDebugLog(sprintf(
                        'Dropbox chunk APPEND completed; stream offset now %d.',
                        $stream->tell()
                    ));
                }

                return $result;
            }

            throw new Exception('Invalid upload chunk type.');
        } catch (RequestException $exception) {
            if ($tries < $maximumTries) {
                $stream->seek($pos, SEEK_SET);

                goto tryUpload;
            }

            throw $exception;
        }
    }

    public function uploadSessionFinish(
        mixed $contents,
        UploadSessionCursor $cursor,
        string $path,
        string $mode = 'add',
        bool $autorename = false,
        bool $mute = false
    ): array {
        if ($this->isDebugEnabled()) {
            $this->writeDebugLog('Dropbox final commit starting.');
        }

        $result = parent::uploadSessionFinish(
            $contents,
            $cursor,
            $path,
            $mode,
            $autorename,
            $mute
        );

        if ($this->isDebugEnabled()) {
            $this->writeDebugLog('Dropbox final commit completed.');
        }

        return $result;
    }

    private function isDebugEnabled(): bool
    {
        return defined('BACKUP_SYNC_DROPBOX_DEBUG')
            && BACKUP_SYNC_DROPBOX_DEBUG;
    }

    private function writeDebugLog(string $message): void
    {
        file_put_contents(
            __DIR__ . '/../../data/plugin.log',
            sprintf(
                '%s (%s)%s',
                $message,
                date('c'),
                PHP_EOL
            ),
            FILE_APPEND
        );
    }
}
