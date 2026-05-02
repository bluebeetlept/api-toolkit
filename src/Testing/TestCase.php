<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Testing;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use CreatesTestResponse;
}
