<?php

declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Eufaturo\CodingStandards\Config\EufaturoRectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();

    EufaturoRectorConfig::setup($rectorConfig);

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
};
