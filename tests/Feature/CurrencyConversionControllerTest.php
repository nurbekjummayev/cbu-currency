<?php

declare(strict_types=1);

describe('POST /api/cbu/convert - Validation Tests', function () {
    test('fails when amount is missing', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('fails when amount is zero or negative', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 0,
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $response = $this->postJson('/api/cbu/convert', [
            'amount' => -10,
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('fails when from currency is missing', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    });

    test('fails when to currency is missing', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    });

    test('fails when currency code is invalid', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'INVALID',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    });

    test('fails when date format is invalid', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'date' => '2025/01/15',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('fails when date is in the future', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('validates numeric amount', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 'not-a-number',
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('validates minimum amount', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 0.001,
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('accepts valid currency conversion request structure', function () {
        // This test just validates the request structure passes validation
        // It won't test actual conversion since that requires API/DB setup
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'date' => now()->format('Y-m-d'),
        ]);

        // We expect either 200 (success) or 500 (API error)
        // But NOT 422 (validation error)
        expect($response->status())->not->toBe(422);
    });
});

describe('GET /api/cbu/convert/rate/{from}/{to} - Route Tests', function () {
    test('route exists and accepts valid currency codes', function () {
        $response = $this->getJson('/api/cbu/convert/rate/USD/EUR');

        // Should not return 404 (route exists)
        expect($response->status())->not->toBe(404);
    });

    test('route requires uppercase currency codes', function () {
        $response = $this->getJson('/api/cbu/convert/rate/usd/eur');

        // Lowercase should return 404 (route requires uppercase)
        expect($response->status())->toBe(404);
    });

    test('accepts date query parameter', function () {
        $response = $this->getJson('/api/cbu/convert/rate/USD/EUR?date=' . now()->format('Y-m-d'));

        // Should not return 404
        expect($response->status())->not->toBe(404);
    });
});
