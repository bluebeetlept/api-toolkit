<?php

declare(strict_types = 1);

use Eufaturo\CodingStandards\Config\EufaturoEcsConfig;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return EufaturoEcsConfig::setup(ECSConfig::configure())
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
;
