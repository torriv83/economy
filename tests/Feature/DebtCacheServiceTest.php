<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Services\DebtCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DebtCacheService', function () {
    it('returns all debts', function () {
        $debts = Debt::factory()->count(3)->create();

        $service = app(DebtCacheService::class);
        $result = $service->getAll();

        expect($result)->toHaveCount(3);
        expect($result->pluck('id')->toArray())->toBe($debts->pluck('id')->toArray());
    });

    it('returns all debts with payments loaded', function () {
        $debt = Debt::factory()->create();
        Payment::factory()->count(3)->for($debt)->create();

        $service = app(DebtCacheService::class);
        $result = $service->getAllWithPayments();

        expect($result)->toHaveCount(1);
        expect($result->first()->relationLoaded('payments'))->toBeTrue();
        expect($result->first()->payments)->toHaveCount(3);
    });
});
