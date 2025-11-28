<?php

use App\Livewire\Concerns\HasConsistentFlashMessages;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Set locale to English for consistent test expectations
    App::setLocale('en');

    // Create an anonymous class that exposes the trait methods publicly for testing
    $this->component = new class
    {
        use HasConsistentFlashMessages;

        public function publicFlashSuccess(string $translationKey, array $replace = []): void
        {
            $this->flashSuccess($translationKey, $replace);
        }

        public function publicFlashError(string $translationKey, array $replace = []): void
        {
            $this->flashError($translationKey, $replace);
        }

        public function publicFlashWarning(string $translationKey, array $replace = []): void
        {
            $this->flashWarning($translationKey, $replace);
        }

        public function publicExecuteWithFeedback(
            callable $action,
            string $successKey,
            string $errorKey,
            array $successReplace = [],
            array $errorReplace = []
        ): bool {
            return $this->executeWithFeedback($action, $successKey, $errorKey, $successReplace, $errorReplace);
        }
    };
});

it('flashes success messages with translation', function () {
    $this->component->publicFlashSuccess('app.debt_saved');

    expect(session('message'))->toBe('Debt saved successfully!');
});

it('flashes success messages with replacements', function () {
    $this->component->publicFlashSuccess('app.delete_debt', ['name' => 'Credit Card']);

    expect(session('message'))->toBe('Delete Credit Card');
});

it('flashes error messages with translation', function () {
    $this->component->publicFlashError('app.flash_operation_failed');

    expect(session('error'))->toBe('Operation failed. Please try again.');
});

it('flashes error messages with replacements', function () {
    $this->component->publicFlashError('app.balance_higher_than_calculated', ['amount' => '1000']);

    expect(session('error'))->toContain('1000 kr higher');
});

it('flashes warning messages with translation', function () {
    $this->component->publicFlashWarning('app.flash_unexpected_error');

    expect(session('warning'))->toBe('An unexpected error occurred.');
});

it('executes action with feedback on success', function () {
    $actionExecuted = false;

    $result = $this->component->publicExecuteWithFeedback(
        function () use (&$actionExecuted) {
            $actionExecuted = true;
        },
        'app.flash_save_success',
        'app.flash_save_failed'
    );

    expect($result)->toBeTrue();
    expect($actionExecuted)->toBeTrue();
    expect(session('message'))->toBe('Changes saved successfully.');
});

it('executes action with feedback on failure', function () {
    Log::shouldReceive('error')->once();

    $result = $this->component->publicExecuteWithFeedback(
        function () {
            throw new Exception('Test error');
        },
        'app.flash_save_success',
        'app.flash_save_failed'
    );

    expect($result)->toBeFalse();
    expect(session('error'))->toBe('Failed to save changes.');
});

it('executes action with feedback using replacements on success', function () {
    $result = $this->component->publicExecuteWithFeedback(
        function () {
            // Do nothing, just succeed
        },
        'app.delete_debt',
        'app.flash_operation_failed',
        ['name' => 'Student Loan'],
        []
    );

    expect($result)->toBeTrue();
    expect(session('message'))->toBe('Delete Student Loan');
});

it('executes action with feedback using replacements on failure', function () {
    Log::shouldReceive('error')->once();

    $result = $this->component->publicExecuteWithFeedback(
        function () {
            throw new Exception('Test error');
        },
        'app.flash_save_success',
        'app.balance_higher_than_calculated',
        [],
        ['amount' => '500']
    );

    expect($result)->toBeFalse();
    expect(session('error'))->toContain('500 kr higher');
});
