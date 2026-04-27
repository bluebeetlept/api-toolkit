<?php

declare(strict_types = 1);

use Rector\Config\RectorConfig;
use BlueBeetle\CodingStandards\Config\BlueBeetleRectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();

    BlueBeetleRectorConfig::setup($rectorConfig);

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
};
