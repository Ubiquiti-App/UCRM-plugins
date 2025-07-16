<?php

declare(strict_types=1);


namespace App;

final class HttpGetParametersData
{
    public ?string $organization = null;

    public ?string $since = null;

    public ?string $until = null;

    public function __construct()
    {
        $this->organization = StringHelper::trimNonEmpty((string) $_GET['organization']);
        $this->since = StringHelper::trimNonEmpty((string) $_GET['since']);
        $this->until = StringHelper::trimNonEmpty((string) $_GET['until']);
    }
}
