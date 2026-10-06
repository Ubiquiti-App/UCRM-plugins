<?php

declare(strict_types=1);

namespace BackupSyncGoogleDrive\Data;

final class UnmsBackup
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $filename;

    public function __construct(string $id, string $filename)
    {
        $this->id = $id;
        $this->filename = $filename;
    }
}
