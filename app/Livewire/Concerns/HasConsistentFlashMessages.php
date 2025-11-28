<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Log;

trait HasConsistentFlashMessages
{
    /**
     * Flash a success message to the session.
     *
     * @param  array<string, string|int|float>  $replace
     */
    protected function flashSuccess(string $translationKey, array $replace = []): void
    {
        session()->flash('message', __($translationKey, $replace));
    }

    /**
     * Flash an error message to the session.
     *
     * @param  array<string, string|int|float>  $replace
     */
    protected function flashError(string $translationKey, array $replace = []): void
    {
        session()->flash('error', __($translationKey, $replace));
    }

    /**
     * Flash a warning message to the session.
     *
     * @param  array<string, string|int|float>  $replace
     */
    protected function flashWarning(string $translationKey, array $replace = []): void
    {
        session()->flash('warning', __($translationKey, $replace));
    }

    /**
     * Execute an action with consistent error handling.
     *
     * @param  callable  $action  The action to execute
     * @param  string  $successKey  Translation key for success message
     * @param  string  $errorKey  Translation key for error message
     * @param  array<string, string|int|float>  $successReplace  Replacements for success message
     * @param  array<string, string|int|float>  $errorReplace  Replacements for error message
     * @return bool Whether the action succeeded
     */
    protected function executeWithFeedback(
        callable $action,
        string $successKey,
        string $errorKey,
        array $successReplace = [],
        array $errorReplace = []
    ): bool {
        try {
            $action();
            $this->flashSuccess($successKey, $successReplace);

            return true;
        } catch (\Exception $e) {
            Log::error('Action failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->flashError($errorKey, $errorReplace);

            return false;
        }
    }
}
