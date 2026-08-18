@extends('layouts.public')

@section('content')
    @php
        $team = \App\Models\Team::where('slug', 'ottomate-space')->first() ?? \App\Models\Team::first();
        
        $frontSettingsFile = storage_path('app/landing_page_settings.json');
        $frontSettings = file_exists($frontSettingsFile) ? json_decode(file_get_contents($frontSettingsFile), true) : [];
        
        $badgeText = $frontSettings['badge_text'] ?? 'Strategy Session Booking';
        $headline = $frontSettings['headline'] ?? 'Automate your entire business.';
        $subheadline = $frontSettings['subheadline'] ?? 'Select a convenient date and time on the calendar to map out a custom automation blueprint for your business with the Ottomate team.';
        $benefit1 = $frontSettings['benefit_1'] ?? 'Free 30-Min Strategy Call';
        $benefit2 = $frontSettings['benefit_2'] ?? 'Custom Architecture Blueprint';
        $benefit3 = $frontSettings['benefit_3'] ?? 'Zero Commitment Required';
    @endphp

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Left Column: Hero & Value Proposition -->
            <div class="lg:col-span-5 flex flex-col justify-center space-y-6 lg:sticky lg:top-8 pt-2">
                
                <!-- Live Availability Badge -->
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-zinc-900 border border-zinc-800 text-zinc-300 text-xs font-medium shadow-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="tracking-wide">{{ $badgeText }}</span>
                    </div>
                </div>
                
                <!-- Main Headline -->
                <div class="space-y-4">
                    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-white leading-[1.1]">
                        {{ $headline }}
                    </h1>
                    <p class="text-base text-zinc-400 leading-relaxed">
                        {{ $subheadline }}
                    </p>
                </div>

                <!-- Strategic Benefits Cards -->
                <div class="grid gap-3 pt-2">
                    <div class="flex items-center gap-3.5 p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/80">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-200">{{ $benefit1 }}</p>
                            <p class="text-xs text-zinc-500">Focused 1-on-1 discovery with senior systems engineers.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3.5 p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/80">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-200">{{ $benefit2 }}</p>
                            <p class="text-xs text-zinc-500">Actionable technology roadmap delivered post-call.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3.5 p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/80">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-200">{{ $benefit3 }}</p>
                            <p class="text-xs text-zinc-500">Pure strategic value with no pressure or strings attached.</p>
                        </div>
                    </div>
                </div>

                <!-- Host Profile Snapshot -->
                <div class="pt-4 border-t border-zinc-800/60 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-amber-500 to-amber-300 p-0.5 shadow-sm">
                        <div class="w-full h-full rounded-full bg-zinc-950 flex items-center justify-center text-xs font-bold text-amber-300">
                            OA
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-200">Ottomate Strategy Team</p>
                        <p class="text-xs text-zinc-500">Google Meet video conference link included automatically</p>
                    </div>
                </div>

            </div>

            <!-- Right Column: Clean Interactive Booking Wizard -->
            <div class="lg:col-span-7">
                <div class="w-full bg-zinc-900/90 rounded-2xl border border-zinc-800 shadow-2xl p-4 sm:p-6 backdrop-blur-md">
                    @if($team)
                        <livewire:booking-wizard :team="$team" />
                    @else
                        <div class="text-center py-16 text-zinc-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-lg font-semibold text-zinc-200">No Booking Team Configured</p>
                            <p class="text-sm mt-1 text-zinc-500">Please login to the admin dashboard to configure your team.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
