@extends('layouts.public')

@section('content')
    @php
        $team = \App\Models\Team::where('slug', 'ottomate-space')->first();
        
        $frontSettingsFile = storage_path('app/landing_page_settings.json');
        $frontSettings = file_exists($frontSettingsFile) ? json_decode(file_get_contents($frontSettingsFile), true) : [];
        
        $badgeText = $frontSettings['badge_text'] ?? 'Strategy Session Booking';
        $headline = $frontSettings['headline'] ?? 'Automate your entire business.';
        $subheadline = $frontSettings['subheadline'] ?? 'Select a convenient date and time on the calendar below to map out a custom automation blueprint for your business with the Ottomate team.';
        $benefit1 = $frontSettings['benefit_1'] ?? 'Free 30-Min Call';
        $benefit2 = $frontSettings['benefit_2'] ?? 'Custom Blueprint SVG';
        $benefit3 = $frontSettings['benefit_3'] ?? 'No Commitment';
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Georgia&display=swap');
        
        .font-syncopate { font-family: 'Instrument Sans', sans-serif; }
        .font-clash { font-family: 'Instrument Sans', sans-serif; font-weight: 600; }
        
        /* Hide scrollbar for the booking wizard container */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Slightly smaller text throughout the booking form */
        .gsap-glass-card { font-size: 0.875rem; }
        .gsap-glass-card h3 { font-size: 1.1rem !important; }
        .gsap-glass-card .text-xl { font-size: 1.05rem !important; }
        .gsap-glass-card .text-2xl { font-size: 1.15rem !important; }
        .gsap-glass-card .text-3xl { font-size: 1.5rem !important; }
        .gsap-glass-card .text-4xl { font-size: 1.75rem !important; }
        .gsap-glass-card button { font-size: 0.8rem; }
        .gsap-glass-card .text-xs { font-size: 0.6rem !important; }
    </style>

    <div class="relative w-full h-full bg-[#1a1815] flex items-center justify-center">
        <!-- Canvas for WebGL / Ambient Orb effect -->
        <canvas id="fluid-canvas" class="absolute inset-0 w-full h-full z-0 opacity-80"></canvas>
        
        <!-- SVG Noise Overlay for Shader Texture -->
        <div class="absolute inset-0 z-0 pointer-events-none mix-blend-overlay opacity-30">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                <filter id="noiseFilter">
                    <feTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="3" stitchTiles="stitch"/>
                </filter>
                <rect width="100%" height="100%" filter="url(#noiseFilter)"/>
            </svg>
        </div>

        <!-- Main Content -->
        <div class="w-full max-w-[90rem] mx-auto h-full px-6 sm:px-10 lg:px-16 relative z-10 flex flex-col justify-center">
            
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center h-full">
                
                <!-- Left Column -->
                <div class="lg:col-span-6 flex flex-col justify-center space-y-6">
                    
                    <div>
                        <!-- Badge -->
                        <div style="overflow: hidden; padding: 15px; margin: -15px;">
                            <div class="gsap-reveal magnetic hero-badge inline-flex items-center space-x-3 px-4 py-2 rounded-full bg-white/5 backdrop-blur-xl border border-white/10 shadow-[0_0_20px_rgba(255,255,255,0.03)] cursor-pointer">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                </span>
                                <span class="font-syncopate text-[10px] uppercase tracking-[0.2em] text-amber-50 font-bold mt-[2px]">{{ $badgeText }}</span>
                            </div>
                        </div>
                        
                        <!-- Headline -->
                        <div class="mt-4" style="overflow: hidden; padding-bottom: 10px;">
                            <h1 class="gsap-reveal font-clash text-5xl sm:text-6xl lg:text-[5.5rem] font-medium tracking-tight text-white leading-[1.05]">
                                {!! str_replace('.', '<span class="text-amber-400">.</span>', $headline) !!}
                            </h1>
                        </div>
                        
                        <!-- Subheadline -->
                        <div class="mt-8" style="overflow: hidden; padding-bottom: 5px;">
                            <p class="gsap-reveal max-w-xl text-lg sm:text-xl text-zinc-300/80 leading-relaxed font-light">
                                {{ $subheadline }}
                            </p>
                        </div>
                    </div>

                    <!-- Benefits -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-6 pt-6 border-t border-white/5">
                        <div style="overflow: hidden; padding: 5px; margin: -5px;">
                            <div class="gsap-reveal benefit-item flex items-center space-x-3 magnetic cursor-pointer">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
                                <span class="text-sm font-medium tracking-wide text-zinc-300">{{ $benefit1 }}</span>
                            </div>
                        </div>
                        <div style="overflow: hidden; padding: 5px; margin: -5px;">
                            <div class="gsap-reveal benefit-item flex items-center space-x-3 magnetic cursor-pointer">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
                                <span class="text-sm font-medium tracking-wide text-zinc-300">{{ $benefit2 }}</span>
                            </div>
                        </div>
                        <div style="overflow: hidden; padding: 5px; margin: -5px;">
                            <div class="gsap-reveal benefit-item flex items-center space-x-3 magnetic cursor-pointer">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
                                <span class="text-sm font-medium tracking-wide text-zinc-300">{{ $benefit3 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Glassmorphism Calendar) -->
                <div class="lg:col-span-6 relative z-10 w-full h-full max-h-[820px] flex items-center justify-center">
                    <div class="gsap-glass-card w-full max-w-2xl h-full max-h-[95%] rounded-[2.5rem] bg-[#09090b]/40 backdrop-blur-3xl border border-white/10 shadow-[0_30px_60px_-15px_rgba(0,0,0,0.8)] p-2 relative flex flex-col">
                        
                        <!-- Internal subtle glow for glass depth -->
                        <div class="absolute top-[-20%] left-[-10%] w-64 h-64 bg-amber-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                        <div class="absolute bottom-[-20%] right-[-10%] w-64 h-64 bg-orange-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                        <div class="absolute inset-0 rounded-[2.5rem] border border-white/5 pointer-events-none"></div>

                        <div class="relative z-10 flex-1 overflow-y-auto no-scrollbar rounded-[2rem] bg-black/20">
                            @if($team)
                                <livewire:booking-wizard :team="$team" />
                            @else
                                <div class="flex items-center justify-center h-full text-center text-red-300/80 p-8">
                                    <div>
                                        <p class="font-clash text-xl mb-2">Configuration Missing</p>
                                        <p class="text-sm font-light">Default team not found. Please run the seeder.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // --- High-End Ambient Orb Canvas Background ---
        (function() {
            const canvas = document.getElementById('fluid-canvas');
            const ctx = canvas.getContext('2d');
            let width, height;
            
            function resize() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            }
            
            window.addEventListener('resize', resize);
            resize();
            
            const orbs = [
                { x: 0.2, y: 0.2, r: 0.6, vx: 0.0005, vy: 0.0007, color: [245, 158, 11] },   // Amber
                { x: 0.8, y: 0.8, r: 0.7, vx: -0.0008, vy: -0.0006, color: [249, 115, 22] }, // Orange
                { x: 0.5, y: 0.5, r: 0.8, vx: 0.0006, vy: -0.0005, color: [234, 179, 8] },  // Gold
                { x: 0.8, y: 0.2, r: 0.5, vx: -0.0009, vy: 0.0008, color: [180, 83, 9] }    // Bronze
            ];
            
            let time = 0;
            
            function animate() {
                time += 0.005;
                
                // Clear background
                ctx.fillStyle = '#1a1815';
                ctx.fillRect(0, 0, width, height);
                
                orbs.forEach(orb => {
                    // Update positions with sine wave oscillation
                    let currentX = (orb.x + Math.sin(time + orb.vx * 1000) * 0.15) * width;
                    let currentY = (orb.y + Math.cos(time + orb.vy * 1000) * 0.15) * height;
                    
                    const gradient = ctx.createRadialGradient(
                        currentX, currentY, 0,
                        currentX, currentY, orb.r * Math.max(width, height)
                    );
                    
                    // Alpha pulsing for dynamic lighting
                    const alpha = 0.5 + Math.sin(time * 3 + orb.x) * 0.1;
                    
                    gradient.addColorStop(0, `rgba(${orb.color[0]}, ${orb.color[1]}, ${orb.color[2]}, ${alpha})`);
                    gradient.addColorStop(1, `rgba(${orb.color[0]}, ${orb.color[1]}, ${orb.color[2]}, 0)`);
                    
                    ctx.globalCompositeOperation = 'screen';
                    ctx.fillStyle = gradient;
                    ctx.fillRect(0, 0, width, height);
                });
                
                ctx.globalCompositeOperation = 'source-over';
                requestAnimationFrame(animate);
            }
            
            animate();
        })();

        // --- Cinematic GSAP Entrance ---
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof gsap !== 'undefined') {
                if (typeof CustomEase !== 'undefined') {
                    CustomEase.create("cinematic", "0.25, 1, 0.5, 1");
                }
                
                const ease = typeof CustomEase !== 'undefined' ? "cinematic" : "power4.out";

                const tl = gsap.timeline();
                
                // Unmasking text elements
                tl.from('.gsap-reveal', {
                    yPercent: 120,
                    scale: 0.95,
                    opacity: 0,
                    rotation: 2,
                    duration: 1.2,
                    stagger: 0.1,
                    ease: ease,
                    delay: 0.2,
                    clearProps: "all"
                })
                // Glass card scale/fade in
                .from('.gsap-glass-card', {
                    y: 60,
                    opacity: 0,
                    scale: 0.95,
                    duration: 1.5,
                    ease: ease,
                    clearProps: "all"
                }, "-=1");
            }
        });
    </script>
@endsection
