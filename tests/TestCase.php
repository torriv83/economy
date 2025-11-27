<?php

namespace Tests;

use Database\Factories\PaymentFactory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the payment factory month number tracker between tests
        // to avoid unique constraint violations
        PaymentFactory::resetMonthNumberTracker();

        // Flush all caches to ensure test isolation
        Cache::flush();
    }
}
