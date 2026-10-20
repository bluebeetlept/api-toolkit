<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\OpenApi;

use ReflectionMethod;

/**
 * The lines of code a method is written in, for what reflection cannot tell: the classes it names, or the comments
 * above its rules.
 */
final class MethodSource
{
    public static function of(ReflectionMethod $method): string | null
    {
        $file = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();

        if ($file === false || $start === false || $end === false || ! is_file($file)) {
            return null;
        }

        $lines = file($file);

        return $lines === false ? null : implode('', array_slice($lines, $start - 1, $end - $start + 1));
    }
}
