<?php

declare(strict_types=1);

namespace App\Service;

class TemplateRenderer
{
    public function render(string $__template, array $__parameters): void
    {
        foreach ($__parameters as $__name => $__value) {
            ${$__name} = $__value;
        }

        require $__template;
    }
}
