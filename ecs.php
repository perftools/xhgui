<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/webroot',
    ])
    ->withRules([
    ])
    ->withPhpCsFixerSets(
        php80Migration: true,
    )
    ->withSkip([
        // Keep compat with php 7.2
        \PhpCsFixer\Fixer\Operator\AssignNullCoalescingToCoalesceEqualFixer::class,
        // Unclear why this is enabled with php74Migration
        \PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer::class,
    ]);
