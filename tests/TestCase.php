<?php

namespace Oliweb\StatamicCspNonce\Tests;

use Oliweb\StatamicCspNonce\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
