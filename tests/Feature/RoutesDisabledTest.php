<?php

declare(strict_types=1);

namespace Cbu\Currency\Tests\Feature;

use Cbu\Currency\Tests\DisabledRoutesTestCase;
use Illuminate\Support\Facades\Route;

class RoutesDisabledTest extends DisabledRoutesTestCase
{
    public function test_does_not_register_any_package_routes(): void
    {
        $this->assertFalse(Route::has('cbu.currencies.index'));
        $this->assertFalse(Route::has('cbu.rates.index'));
        $this->assertFalse(Route::has('cbu.convert'));
    }

    public function test_returns_404_for_package_endpoints(): void
    {
        $this->getJson('api/cbu/rates/today')->assertNotFound();
    }
}
