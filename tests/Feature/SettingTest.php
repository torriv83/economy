<?php

use App\Models\Setting;

it('can create a string setting', function () {
    $setting = Setting::factory()->create([
        'key' => 'site_name',
        'value' => 'My App',
        'type' => 'string',
    ]);

    expect($setting->typed_value)->toBe('My App');
});

it('can create an integer setting', function () {
    $setting = Setting::factory()->integer()->create([
        'key' => 'items_per_page',
        'value' => '25',
    ]);

    expect($setting->typed_value)->toBe(25);
    expect($setting->typed_value)->toBeInt();
});

it('can create a float setting', function () {
    $setting = Setting::factory()->float()->create([
        'key' => 'tax_rate',
        'value' => '7.25',
    ]);

    expect($setting->typed_value)->toBe(7.25);
    expect($setting->typed_value)->toBeFloat();
});

it('can create a boolean setting', function () {
    $setting = Setting::factory()->boolean()->create([
        'key' => 'maintenance_mode',
        'value' => '1',
    ]);

    expect($setting->typed_value)->toBeTrue();

    $setting->update(['value' => '0']);
    $setting->refresh();

    expect($setting->typed_value)->toBeFalse();
});

it('can create an encrypted setting', function () {
    $setting = Setting::factory()->encrypted()->create([
        'key' => 'api_secret',
    ]);

    // Set the value using the mutator
    $setting->typed_value = 'super-secret-key';
    $setting->save();
    $setting->refresh();

    // The raw value should be encrypted (different from original)
    expect($setting->value)->not->toBe('super-secret-key');

    // The typed value should decrypt it back
    expect($setting->typed_value)->toBe('super-secret-key');
});

it('handles null values for all types', function () {
    $types = ['string', 'integer', 'float', 'boolean', 'encrypted'];

    foreach ($types as $type) {
        $setting = Setting::factory()->create([
            'key' => "null_{$type}",
            'value' => null,
            'type' => $type,
        ]);

        expect($setting->typed_value)->toBeNull();
    }
});

it('has unique keys', function () {
    Setting::factory()->create(['key' => 'unique_key']);

    expect(fn () => Setting::factory()->create(['key' => 'unique_key']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('uses ynab group state correctly', function () {
    $setting = Setting::factory()->ynabGroup()->create();

    expect($setting->group)->toBe('ynab');
});

it('uses debt group state correctly', function () {
    $setting = Setting::factory()->debtGroup()->create();

    expect($setting->group)->toBe('debt');
});

it('defaults to general group', function () {
    $setting = Setting::factory()->create();

    expect($setting->group)->toBe('general');
});

it('defaults to string type', function () {
    $setting = Setting::factory()->create();

    expect($setting->type)->toBe('string');
});
