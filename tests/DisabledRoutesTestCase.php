<?php

declare(strict_types=1);

namespace Cbu\Currency\Tests;

class DisabledRoutesTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('cbu-currency.routes.enabled', false);
    }
}
