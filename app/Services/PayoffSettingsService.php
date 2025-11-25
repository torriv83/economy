<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PayoffSetting;

class PayoffSettingsService
{
    protected ?PayoffSetting $cachedSettings = null;

    public function getSettings(): PayoffSetting
    {
        return $this->cachedSettings ??= PayoffSetting::firstOrCreate([], [
            'extra_payment' => 2000.00,
            'strategy' => 'avalanche',
        ]);
    }

    public function getExtraPayment(): float
    {
        return $this->getSettings()->extra_payment;
    }

    public function getStrategy(): string
    {
        return $this->getSettings()->strategy;
    }

    public function setExtraPayment(float $amount): void
    {
        $settings = $this->getSettings();
        $settings->extra_payment = $amount;
        $settings->save();
    }

    public function setStrategy(string $strategy): void
    {
        $settings = $this->getSettings();
        $settings->strategy = $strategy;
        $settings->save();
    }

    public function saveSettings(float $extraPayment, string $strategy): void
    {
        $settings = $this->getSettings();
        $settings->extra_payment = $extraPayment;
        $settings->strategy = $strategy;
        $settings->save();
    }
}
