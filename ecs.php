<?php

declare(strict_types = 1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use BlueBeetle\CodingStandards\Config\BlueBeetleEcsConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    BlueBeetleEcsConfig::setup($ecsConfig);
};
