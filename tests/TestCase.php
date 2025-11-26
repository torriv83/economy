<?php

namespace Tests;

use Database\Factories\PaymentFactory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the payment factory month number tracker between tests
        // to avoid unique constraint violations
        PaymentFactory::resetMonthNumberTracker();
    }
}
