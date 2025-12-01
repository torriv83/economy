<div>
    {{-- Fixed Sidebar for Desktop --}}
    <x-sidebar :section-title="__('app.self_loans')">
        <x-sidebar-item
            action="showOverview"
            :label="__('app.overview')"
            :active="$currentView === 'overview'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />'
        />
        <x-sidebar-item
            action="showCreate"
            :label="__('app.new_loan')"
            :active="$currentView === 'create'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />'
        />
        <x-sidebar-item
            action="showHistory"
            :label="__('app.repayment_history')"
            :active="$currentView === 'history'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />'
        />
    </x-sidebar>

    {{-- Header --}}
    <x-page-header
        :title="match($currentView) {
            'overview' => __('app.self_loans_overview'),
            'create' => __('app.new_loan'),
            'history' => __('app.repayment_history'),
            default => ''
        }"
        :subtitle="match($currentView) {
            'overview' => __('app.track_self_loans'),
            'create' => __('app.create_new_self_loan_description'),
            'history' => __('app.view_repayment_history_description'),
            default => ''
        }"
    />

    {{-- Main Content --}}
    <div class="animate-fade-in-up">
        @if ($currentView === 'overview')
            <livewire:self-loans.overview />
        @elseif ($currentView === 'create')
            <livewire:self-loans.create-self-loan />
        @elseif ($currentView === 'history')
            <livewire:self-loans.history />
        @endif
    </div>
</div>
