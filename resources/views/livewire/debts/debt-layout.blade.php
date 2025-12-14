<div>
    {{-- Fixed Sidebar for Desktop --}}
    <x-sidebar :section-title="__('app.debts')">
        <x-sidebar-item
            action="showOverview"
            :label="__('app.overview')"
            :active="in_array($currentView, ['overview', 'edit', 'detail'])"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />'
        />
        <x-sidebar-item
            action="showCreate"
            :label="__('app.add_debt')"
            :active="$currentView === 'create'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />'
        />
        <x-sidebar-item
            action="showPay"
            :label="__('app.pay_debt')"
            :active="$currentView === 'pay'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
        />
        <x-sidebar-item
            action="showReconciliations"
            :label="__('app.reconciliation_history')"
            :active="$currentView === 'reconciliations'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
        />
    </x-sidebar>

    {{-- Header (not for overview or detail - they render their own) --}}
    @if (!in_array($currentView, ['overview', 'detail']))
        <x-page-header
            :title="match($currentView) {
                'create' => __('app.create_debt'),
                'progress' => __('app.debt_progress'),
                'recommendations' => __('app.recommendations'),
                'edit' => __('app.edit_debt'),
                'insights' => __('app.interest_insights'),
                'reconciliations' => __('app.reconciliation_history'),
                'pay' => __('app.pay_debt'),
                default => ''
            }"
            :subtitle="match($currentView) {
                'create' => __('app.create_debt_description'),
                'progress' => __('app.debt_progress_description'),
                'recommendations' => __('app.recommendations_description'),
                'edit' => __('app.edit_debt_description'),
                'insights' => __('app.interest_insights_description'),
                'reconciliations' => __('app.reconciliation_history_description'),
                'pay' => __('app.pay_debt_description'),
                default => ''
            }"
        />
    @endif

    {{-- Main Content --}}
    <div class="animate-fade-in-up">
        @if ($currentView === 'overview')
            <livewire:debt-list />
        @elseif ($currentView === 'create')
            <livewire:create-debt />
        @elseif ($currentView === 'progress')
            <livewire:debt-progress />
        @elseif ($currentView === 'recommendations')
            <livewire:debts.recommendations />
        @elseif ($currentView === 'detail' && $viewingDebt)
            <livewire:debts.debt-detail :key="'detail-' . $viewingDebtId" :debt="$viewingDebt" :embedded="true" />
        @elseif ($currentView === 'edit' && $editingDebt)
            <livewire:edit-debt :key="'edit-' . $editingDebtId" :debt="$editingDebt" />
        @elseif ($currentView === 'insights')
            <livewire:interest-insights />
        @elseif ($currentView === 'reconciliations')
            <livewire:reconciliation-history />
        @elseif ($currentView === 'pay')
            <livewire:pay-debt />
        @endif
    </div>
</div>
