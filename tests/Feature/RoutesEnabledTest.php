<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

describe('routes enabled (default)', function () {
    it('registers package routes', function () {
        expect(Route::has('cbu.currencies.index'))->toBeTrue()
            ->and(Route::has('cbu.rates.index'))->toBeTrue()
            ->and(Route::has('cbu.convert'))->toBeTrue();
    });

    it('applies the configured route prefix', function () {
        $route = Route::getRoutes()->getByName('cbu.rates.index');

        expect($route->uri())->toBe('api/cbu/rates');
    });
});
