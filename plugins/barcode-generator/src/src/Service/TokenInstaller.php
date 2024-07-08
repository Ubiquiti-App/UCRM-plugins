<?php

declare(strict_types=1);


namespace UBarcode\Service;

use Nette\Utils\Json;
use Symfony\Component\Filesystem\Filesystem;
use UBarcode\Util\Strings;

final class TokenInstaller
{
    private string $token;

    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->token = Strings::generateUuidWithDashes();
    }

    public function install(): void
    {
        $this->filesystem->dumpFile('data/config.json', Json::encode([
            'token' => $this->token,
        ]));
    }
}
