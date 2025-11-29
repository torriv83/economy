<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new SettingsService;
    Cache::flush();
});

describe('YNAB settings', function () {
    it('returns false when YNAB is not enabled', function () {
        expect($this->service->isYnabEnabled())->toBeFalse();
    });

    it('can enable YNAB', function () {
        $this->service->setYnabEnabled(true);

        expect($this->service->isYnabEnabled())->toBeTrue();

        $setting = Setting::where('key', 'ynab.enabled')->first();
        expect($setting)->not->toBeNull()
            ->and($setting->type)->toBe('boolean')
            ->and($setting->group)->toBe('ynab');
    });

    it('can disable YNAB', function () {
        $this->service->setYnabEnabled(true);
        $this->service->setYnabEnabled(false);

        expect($this->service->isYnabEnabled())->toBeFalse();
    });

    it('returns null when YNAB token is not set', function () {
        expect($this->service->getYnabToken())->toBeNull();
    });

    it('can set and get encrypted YNAB token', function () {
        $token = 'secret-ynab-token-12345';

        $this->service->setYnabToken($token);

        expect($this->service->getYnabToken())->toBe($token);

        $setting = Setting::where('key', 'ynab.token')->first();
        expect($setting)->not->toBeNull()
            ->and($setting->type)->toBe('encrypted')
            ->and($setting->group)->toBe('ynab')
            ->and($setting->value)->not->toBe($token)
            ->and(Crypt::decryptString($setting->value))->toBe($token);
    });

    it('can delete YNAB token by setting it to null', function () {
        $this->service->setYnabToken('token-123');
        $this->service->setYnabToken(null);

        expect($this->service->getYnabToken())->toBeNull();
        expect(Setting::where('key', 'ynab.token')->exists())->toBeFalse();
    });

    it('returns null when YNAB budget ID is not set', function () {
        expect($this->service->getYnabBudgetId())->toBeNull();
    });

    it('can set and get YNAB budget ID', function () {
        $budgetId = 'budget-uuid-12345';

        $this->service->setYnabBudgetId($budgetId);

        expect($this->service->getYnabBudgetId())->toBe($budgetId);

        $setting = Setting::where('key', 'ynab.budget_id')->first();
        expect($setting)->not->toBeNull()
            ->and($setting->type)->toBe('string')
            ->and($setting->group)->toBe('ynab');
    });

    it('can delete YNAB budget ID by setting it to null', function () {
        $this->service->setYnabBudgetId('budget-123');
        $this->service->setYnabBudgetId(null);

        expect($this->service->getYnabBudgetId())->toBeNull();
        expect(Setting::where('key', 'ynab.budget_id')->exists())->toBeFalse();
    });

    it('returns false when YNAB is not configured', function () {
        expect($this->service->isYnabConfigured())->toBeFalse();
    });

    it('returns false when YNAB is enabled but missing token', function () {
        $this->service->setYnabEnabled(true);
        $this->service->setYnabBudgetId('budget-123');

        expect($this->service->isYnabConfigured())->toBeFalse();
    });

    it('returns false when YNAB is enabled but missing budget ID', function () {
        $this->service->setYnabEnabled(true);
        $this->service->setYnabToken('token-123');

        expect($this->service->isYnabConfigured())->toBeFalse();
    });

    it('returns false when YNAB has credentials but is disabled', function () {
        $this->service->setYnabEnabled(false);
        $this->service->setYnabToken('token-123');
        $this->service->setYnabBudgetId('budget-123');

        expect($this->service->isYnabConfigured())->toBeFalse();
    });

    it('returns true when YNAB is fully configured', function () {
        $this->service->setYnabEnabled(true);
        $this->service->setYnabToken('token-123');
        $this->service->setYnabBudgetId('budget-123');

        expect($this->service->isYnabConfigured())->toBeTrue();
    });

    it('can clear YNAB credentials', function () {
        $this->service->setYnabToken('token-123');
        $this->service->setYnabBudgetId('budget-123');

        $this->service->clearYnabCredentials();

        expect($this->service->getYnabToken())->toBeNull()
            ->and($this->service->getYnabBudgetId())->toBeNull()
            ->and(Setting::where('key', 'ynab.token')->exists())->toBeFalse()
            ->and(Setting::where('key', 'ynab.budget_id')->exists())->toBeFalse();
    });
});

describe('debt settings', function () {
    it('returns default kredittkort percentage when not set', function () {
        expect($this->service->getKredittkortPercentage())->toBe(0.03);
    });

    it('can set and get kredittkort percentage', function () {
        $this->service->setKredittkortPercentage(0.05);

        expect($this->service->getKredittkortPercentage())->toBe(0.05);

        $setting = Setting::where('key', 'debt.kredittkort_percentage')->first();
        expect($setting)->not->toBeNull()
            ->and($setting->type)->toBe('float')
            ->and($setting->group)->toBe('debt');
    });

    it('returns default kredittkort minimum when not set', function () {
        expect($this->service->getKredittkortMinimum())->toBe(300.0);
    });

    it('can set and get kredittkort minimum', function () {
        $this->service->setKredittkortMinimum(500.0);

        expect($this->service->getKredittkortMinimum())->toBe(500.0);

        $setting = Setting::where('key', 'debt.kredittkort_minimum')->first();
        expect($setting)->not->toBeNull()
            ->and($setting->type)->toBe('float')
            ->and($setting->group)->toBe('debt');
    });

    it('returns default forbrukslån payoff months when not set', function () {
        expect($this->service->getForbrukslånPayoffMonths())->toBe(60);
    });

    it('can set and get forbrukslån payoff months', function () {
        $this->service->setForbrukslånPayoffMonths(48);

        expect($this->service->getForbrukslånPayoffMonths())->toBe(48);

        $setting = Setting::where('key', 'debt.forbrukslan_payoff_months')->first();
        expect($setting)->not->toBeNull()
            ->and($setting->type)->toBe('integer')
            ->and($setting->group)->toBe('debt');
    });

    it('can reset debt settings to defaults', function () {
        $this->service->setKredittkortPercentage(0.05);
        $this->service->setKredittkortMinimum(500.0);
        $this->service->setForbrukslånPayoffMonths(48);

        expect(Setting::where('group', 'debt')->count())->toBe(3);

        $this->service->resetDebtSettingsToDefaults();

        expect(Setting::where('group', 'debt')->count())->toBe(0)
            ->and($this->service->getKredittkortPercentage())->toBe(0.03)
            ->and($this->service->getKredittkortMinimum())->toBe(300.0)
            ->and($this->service->getForbrukslånPayoffMonths())->toBe(60);
    });
});

describe('caching', function () {
    it('caches setting values', function () {
        $this->service->set('test.key', 'test-value', 'string', 'test');

        // First get() should cache it
        expect($this->service->get('test.key', 'string'))->toBe('test-value');

        // Delete from database but cache should still have it
        Setting::where('key', 'test.key')->delete();

        // Should still return cached value
        expect($this->service->get('test.key', 'string'))->toBe('test-value');
    });

    it('clears cache when setting a value', function () {
        $this->service->set('test.key', 'original', 'string', 'test');

        // Verify cached
        expect($this->service->get('test.key', 'string'))->toBe('original');

        // Update should clear cache
        $this->service->set('test.key', 'updated', 'string', 'test');

        // Should get new value from database
        expect($this->service->get('test.key', 'string'))->toBe('updated');
    });

    it('clears cache when deleting YNAB credentials', function () {
        $this->service->setYnabToken('token-123');
        $this->service->setYnabBudgetId('budget-123');

        // Verify cached
        expect($this->service->getYnabToken())->toBe('token-123');

        $this->service->clearYnabCredentials();

        // Should return null from fresh database query
        expect($this->service->getYnabToken())->toBeNull()
            ->and($this->service->getYnabBudgetId())->toBeNull();
    });

    it('clears cache when resetting debt settings', function () {
        $this->service->setKredittkortPercentage(0.05);

        // Verify cached
        expect($this->service->getKredittkortPercentage())->toBe(0.05);

        $this->service->resetDebtSettingsToDefaults();

        // Should return default from fresh database query
        expect($this->service->getKredittkortPercentage())->toBe(0.03);
    });
});

describe('generic get and set', function () {
    it('can get and set string values', function () {
        $this->service->set('test.string', 'hello world', 'string', 'test');

        expect($this->service->get('test.string', 'string'))->toBe('hello world');
    });

    it('can get and set integer values', function () {
        $this->service->set('test.integer', 42, 'integer', 'test');

        expect($this->service->get('test.integer', 'integer'))->toBe(42);
    });

    it('can get and set float values', function () {
        $this->service->set('test.float', 3.14, 'float', 'test');

        expect($this->service->get('test.float', 'float'))->toBe(3.14);
    });

    it('can get and set boolean values', function () {
        $this->service->set('test.bool_true', true, 'boolean', 'test');
        $this->service->set('test.bool_false', false, 'boolean', 'test');

        expect($this->service->get('test.bool_true', 'boolean'))->toBeTrue()
            ->and($this->service->get('test.bool_false', 'boolean'))->toBeFalse();
    });

    it('can get and set encrypted values', function () {
        $this->service->set('test.encrypted', 'secret-data', 'encrypted', 'test');

        expect($this->service->get('test.encrypted', 'encrypted'))->toBe('secret-data');

        $setting = Setting::where('key', 'test.encrypted')->first();
        expect($setting->value)->not->toBe('secret-data');
    });

    it('returns null for non-existent keys', function () {
        expect($this->service->get('does.not.exist', 'string'))->toBeNull();
    });
});
