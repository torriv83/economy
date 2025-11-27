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
     * @return array<array{key: string, description: string}>
     */
    private function getShortcuts(): array
    {
        return [
            ['key' => 'h', 'description' => __('app.shortcut_home')],
            ['key' => 'c', 'description' => __('app.shortcut_calendar')],
            ['key' => 'p', 'description' => __('app.shortcut_payment_plan')],
            ['key' => 's', 'description' => __('app.shortcut_strategies')],
            ['key' => 'l', 'description' => __('app.shortcut_self_loans')],
            ['key' => 'L', 'description' => __('app.shortcut_new_self_loan')],
            ['key' => 'n', 'description' => __('app.shortcut_new_debt')],
            ['key' => '?', 'description' => __('app.shortcut_show_shortcuts')],
        ];
    }
}
