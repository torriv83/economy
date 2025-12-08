<div>
    {{-- Fixed Sidebar for Desktop --}}
    <x-sidebar :section-title="__('app.settings')">
        <x-sidebar-item action="showPlan" :label="__('app.plan_settings')" :active="$currentView === 'plan'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />' />
        <x-sidebar-item action="showDebt" :label="__('app.debt_settings')" :active="$currentView === 'debt'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />' />
        <x-sidebar-item action="showYnab" :label="__('app.ynab_settings')" :active="$currentView === 'ynab'"
            active-color="cyan"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />' />
        <x-sidebar-item action="showRecommendations" :label="__('app.recommendation_settings')"
            :active="$currentView === 'recommendations'" active-color="purple"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />' />
        <x-sidebar-item action="showShortcuts" :label="__('app.keyboard_shortcuts')"
            :active="$currentView === 'shortcuts'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />' />
    </x-sidebar>

    {{-- Header --}}
    <x-page-header :title="__('app.settings')" :subtitle="__('app.settings_description')" />

    {{-- Main Content --}}
    <div class="animate-fade-in-up">
        @if ($currentView === 'plan')
            <livewire:payoff.payoff-settings lazy />
        @elseif ($currentView === 'debt')
            <livewire:settings.debt-settings />
        @elseif ($currentView === 'ynab')
            <livewire:settings.ynab-settings />
        @elseif ($currentView === 'recommendations')
            <livewire:settings.recommendation-settings />
        @elseif ($currentView === 'shortcuts')
            <livewire:settings.keyboard-shortcuts />
        @endif
    </div>
</div>