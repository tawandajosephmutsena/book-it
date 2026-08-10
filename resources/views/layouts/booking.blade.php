<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <title>Book-it | More Than a Website</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Syncopate:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="h-screen bg-zinc-950 text-zinc-100 antialiased overflow-hidden selection:bg-zinc-800 selection:text-white">
    <!-- Navbar/Header -->
    <header class="absolute top-0 left-0 right-0 w-full max-w-7xl mx-auto flex justify-between items-center px-6 sm:px-8 py-4 z-20">
        <div class="flex items-center space-x-3">
            <span class="text-xl font-semibold tracking-tight font-display" style="font-family: 'Inter', sans-serif;">Ottomate</span>
            <span class="text-[10px] uppercase tracking-[0.3em] text-zinc-500 font-syncopate mt-0.5">Book-it</span>
        </div>
        
        <!-- Sleek indicator -->
        <div class="flex items-center space-x-2 text-xs font-medium text-zinc-400 dark:text-zinc-500">
            <span>Secure Booking</span>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
    </header>

    <!-- Main Content Area — fills remaining space -->
    <main class="h-full w-full relative z-10">
        {{ $slot }}
    </main>

    <!-- GSAP Core -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/CustomEase.min.js"></script>
    <!-- Vanilla Tilt for 3D card interactions -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>

    <!-- Custom Cursor -->
    <div id="cursor-dot" class="fixed top-0 left-0 w-3 h-3 bg-zinc-100 rounded-full mix-blend-difference pointer-events-none z-[9999] opacity-0 transition-opacity duration-300 transform -translate-x-1/2 -translate-y-1/2" style="will-change: transform;"></div>
    <div id="cursor-ring" class="fixed top-0 left-0 w-10 h-10 border-2 border-zinc-500 rounded-full mix-blend-difference pointer-events-none z-[9998] opacity-0 transition-opacity duration-300 transform -translate-x-1/2 -translate-y-1/2" style="will-change: transform;"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cursorDot = document.getElementById('cursor-dot');
            const cursorRing = document.getElementById('cursor-ring');
            
            if (window.matchMedia("(pointer: fine)").matches) {
                cursorDot.style.opacity = '1';
                cursorRing.style.opacity = '1';
                
                let mouseX = 0, mouseY = 0;
                let ringX = 0, ringY = 0;
                let isHovering = false;
                
                window.addEventListener('mousemove', (e) => {
                    mouseX = e.clientX;
                    mouseY = e.clientY;
                    
                    cursorDot.style.transform = `translate(${mouseX}px, ${mouseY}px) translate(-50%, -50%)`;
                });
                
                const render = () => {
                    ringX += (mouseX - ringX) * 0.15;
                    ringY += (mouseY - ringY) * 0.15;
                    
                    cursorRing.style.transform = `translate(${ringX}px, ${ringY}px) translate(-50%, -50%) ${isHovering ? 'scale(1.5)' : 'scale(1)'}`;
                    requestAnimationFrame(render);
                };
                requestAnimationFrame(render);
                
                // Add hover states to interactable elements
                const interactiveElements = document.querySelectorAll('button, a, input, select, textarea, .magnetic');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => {
                        isHovering = true;
                        cursorRing.style.borderColor = '#fff';
                    });
                    el.addEventListener('mouseleave', () => {
                        isHovering = false;
                        cursorRing.style.borderColor = 'rgba(113, 113, 122, 1)'; // zinc-500
                    });
                });
            }
        });
    </script>

    @fluxScripts
</body>
</html>
