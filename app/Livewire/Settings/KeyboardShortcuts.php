<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class KeyboardShortcuts extends Component
{
    public function render(): View
    {
        return view('livewire.settings.keyboard-shortcuts', [
            'shortcuts' => $this->getShortcuts(),
        ]);
    }

    /**
     * @return array<array{key: string, description: string, url: string, view: string|null}>
     */
    private function getShortcuts(): array
    {
        return [
            ['key' => 'h', 'description' => __('app.shortcut_home'), 'url' => '/debts', 'view' => 'overview'],
            ['key' => 'c', 'description' => __('app.shortcut_calendar'), 'url' => '/payoff', 'view' => 'calendar'],
            ['key' => 'p', 'description' => __('app.shortcut_payment_plan'), 'url' => '/payoff', 'view' => 'plan'],
            ['key' => 's', 'description' => __('app.shortcut_strategies'), 'url' => '/payoff', 'view' => 'strategies'],
            ['key' => 'l', 'description' => __('app.shortcut_self_loans'), 'url' => '/self-loans', 'view' => null],
            ['key' => 'L', 'description' => __('app.shortcut_new_self_loan'), 'url' => '/self-loans', 'view' => 'create'],
            ['key' => 'n', 'description' => __('app.shortcut_new_debt'), 'url' => '/debts', 'view' => 'create'],
            ['key' => '?', 'description' => __('app.shortcut_show_shortcuts'), 'url' => '/settings', 'view' => 'shortcuts'],
        ];
    }
}
