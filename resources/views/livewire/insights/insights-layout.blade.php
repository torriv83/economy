<div>
    {{-- Fixed Sidebar for Desktop --}}
    <x-sidebar :section-title="__('app.insights_menu')">
        <x-sidebar-item
            action="showProgress"
            :label="__('app.progress')"
            :active="$currentView === 'progress'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />'
        />
        <x-sidebar-item
            action="showRecommendations"
            :label="__('app.recommendations')"
            :active="$currentView === 'recommendations'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />'
        />
        <x-sidebar-item
            action="showInterest"
            :label="__('app.interest_insights')"
            :active="$currentView === 'interest'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />'
        />
    </x-sidebar>

    {{-- Header --}}
    <x-page-header
        :title="match($currentView) {
            'progress' => __('app.debt_progress'),
            'recommendations' => __('app.recommendations'),
            'interest' => __('app.interest_insights'),
            default => ''
        }"
        :subtitle="match($currentView) {
            'progress' => __('app.debt_progress_description'),
            'recommendations' => __('app.recommendations_description'),
            'interest' => __('app.interest_insights_description'),
            default => ''
        }"
    />

    {{-- Main Content --}}
    <div class="animate-fade-in-up">
        @if ($currentView === 'progress')
            <livewire:debt-progress />
        @elseif ($currentView === 'recommendations')
            <livewire:debts.recommendations />
        @elseif ($currentView === 'interest')
            <livewire:interest-insights />
        @endif
    </div>
</div>
