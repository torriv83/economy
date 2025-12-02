<div>
    {{-- Fixed Sidebar for Desktop --}}
    <x-sidebar :section-title="__('app.payoff_planning')">
        <x-sidebar-item
            action="showCalendar"
            :label="__('app.calendar')"
            :active="$currentView === 'calendar'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />'
        />
        <x-sidebar-item
            action="showPlan"
            :label="__('app.repayments')"
            :active="$currentView === 'plan'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />'
        />
        <x-sidebar-item
            action="showStrategies"
            :label="__('app.strategies')"
            :active="$currentView === 'strategies'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />'
        />
    </x-sidebar>

    {{-- Header --}}
    <x-page-header
        :title="match($currentView) {
            'strategies' => __('app.payoff_strategies'),
            'plan' => __('app.payment_plan'),
            'calendar' => __('app.payoff_calendar'),
            default => ''
        }"
        :subtitle="match($currentView) {
            'strategies' => __('app.strategies_description'),
            'plan' => __('app.payment_plan_description'),
            'calendar' => __('app.calendar_description'),
            default => ''
        }"
    >
        @if (app(\App\Services\SettingsService::class)->isYnabEnabled() && $currentView !== 'strategies')
            <x-slot:actions>
                <livewire:ynab.ready-to-assign :compact="true" />
            </x-slot:actions>
        @endif
    </x-page-header>

    {{-- Main Content --}}
    <div class="animate-fade-in-up">
        @if ($currentView === 'strategies')
            <livewire:strategy-comparison />
        @elseif ($currentView === 'plan')
            <livewire:payment-plan :extraPayment="$extraPayment" :strategy="$strategy" />
        @elseif ($currentView === 'calendar')
            <livewire:payoff-calendar :extraPayment="$extraPayment" :strategy="$strategy" />
        @endif
    </div>
</div>
