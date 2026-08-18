<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <meta name="description" content="Book your strategy session with Ottomate.">
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 font-sans antialiased selection:bg-amber-500/30 selection:text-amber-200">
    <div class="relative min-h-screen flex flex-col justify-between overflow-x-hidden">
        <!-- Top subtle gradient glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[350px] bg-gradient-to-b from-amber-500/10 via-amber-500/5 to-transparent blur-3xl pointer-events-none -z-10"></div>

        <!-- Global Header -->
        <header class="w-full max-w-7xl mx-auto px-6 py-5 flex items-center justify-between z-20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center font-bold text-zinc-950 text-sm shadow-sm shadow-amber-500/20">
                    B
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="font-bold tracking-tight text-white text-lg">Book-it</span>
                    <span class="text-xs uppercase tracking-widest text-zinc-500 font-medium">Ottomate</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-xs text-zinc-400 hover:text-white transition font-medium flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-white/5 border border-transparent hover:border-white/10">
                    <span>Admin Portal</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center w-full py-4 sm:py-8 z-10">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-4 flex flex-col sm:flex-row items-center justify-between text-xs text-zinc-500 gap-2 border-t border-white/5 z-20">
            <div>
                © {{ date('Y') }} Ottomate AGY. All rights reserved.
            </div>
            <div class="flex items-center gap-4">
                <span>Enterprise Booking Platform</span>
                <span class="inline-flex items-center gap-1 text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Systems Operational
                </span>
            </div>
        </footer>
    </div>

    @fluxScripts
</body>
</html>
