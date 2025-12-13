<?php

declare(strict_types=1);

use App\Http\Middleware\SetApplicationLocale;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

describe('SetApplicationLocale middleware', function () {
    it('sets application locale from settings service', function () {
        $user = User::factory()->create();
        $settingsService = app(SettingsService::class);
        $settingsService->setLocale('no');

        $middleware = new SetApplicationLocale;
        $request = Request::create('/test', 'GET');

        $middleware->handle($request, function ($req) {
            expect(app()->getLocale())->toBe('no');

            return response('OK');
        });
    });

    it('uses default locale when no locale is set', function () {
        $user = User::factory()->create();
        config(['app.locale' => 'en']);

        $middleware = new SetApplicationLocale;
        $request = Request::create('/test', 'GET');

        $middleware->handle($request, function ($req) {
            expect(app()->getLocale())->toBe('en');

            return response('OK');
        });
    });

    it('passes request through to next middleware', function () {
        $user = User::factory()->create();
        $middleware = new SetApplicationLocale;
        $request = Request::create('/test', 'GET');
        $nextCalled = false;

        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return response('OK');
        });

        expect($nextCalled)->toBeTrue();
        expect($response->getContent())->toBe('OK');
    });

    it('sets locale to norwegian when configured', function () {
        $user = User::factory()->create();
        $settingsService = app(SettingsService::class);
        $settingsService->setLocale('no');

        $middleware = new SetApplicationLocale;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return response('Locale: '.app()->getLocale());
        });

        expect($response->getContent())->toBe('Locale: no');
    });

    it('sets locale to english when configured', function () {
        $user = User::factory()->create();
        $settingsService = app(SettingsService::class);
        $settingsService->setLocale('en');

        $middleware = new SetApplicationLocale;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return response('Locale: '.app()->getLocale());
        });

        expect($response->getContent())->toBe('Locale: en');
    });
});
