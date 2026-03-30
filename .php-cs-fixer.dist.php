<?php

declare(strict_types=1);

use PhpCsFixer\Finder;
use Yiisoft\CodeStyle\ConfigBuilder;

$finder = (new Finder())->in([
    __DIR__ . '/src',
    __DIR__ . '/tests',
]);

return ConfigBuilder::build()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Yiisoft/Core' => true,
        '@Yiisoft/Core:risky' => true,
    ])
    ->setFinder($finder);
