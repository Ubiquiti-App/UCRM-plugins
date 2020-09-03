<?php

declare(strict_types=1);

return PhpCsFixer\Config::create()
    ->setRules(
        [
            '@PHP73Migration' => true,
            '@Symfony' => true,
            'array_syntax' => [
                'syntax' => 'short',
            ],
            'not_operator_with_successor_space' => true,
            'concat_space' => [
                'spacing' => 'one',
            ],
            'no_useless_else' => true,
            'no_useless_return' => true,
            'ordered_imports' => true,
            'yoda_style' => false,
            'self_accessor' => false,
            'no_superfluous_phpdoc_tags' => true,
            'single_quote' => true,
            'no_superfluous_elseif' => true,
            'phpdoc_to_comment' => false,
            'phpdoc_summary' => false,
            'single_line_throw' => false,
            'heredoc_indentation' => false,
        ]
    )->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->notPath('vendor')
    );
