<?php

declare(strict_types=1);

namespace App\Service;

class TemplateRenderer
{
    /**
     * @param array<string, mixed> $__parameters
     */
    public function render(string $__template, array $__parameters): void
    {
        foreach ($__parameters as $__name => $__value) {
            ${$__name} = $__value;
        }

        require $__template;
    }
}
