<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="More than a website. Book your strategy session with Ottomate.">
    <title>Book-it | More Than a Website</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ["'Instrument Sans'", 'sans-serif'],
                        mono: ["'IBM Plex Mono'", 'monospace'],
                        body: ["'Plus Jakarta Sans'", 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="h-screen overflow-hidden bg-[#1a1815] font-body antialiased">
    @yield('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
    <script>
        // Ambient fluid canvas
        (function() {
            const canvas = document.getElementById('fluid-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let w, h, time = 0;
            function resize() {
                w = canvas.width = canvas.offsetWidth;
                h = canvas.height = canvas.offsetHeight;
            }
            resize();
            window.addEventListener('resize', resize);
            function draw() {
                time += 0.005;
                ctx.clearRect(0, 0, w, h);
                for (let i = 0; i < 3; i++) {
                    const cx = w/2 + Math.sin(time * (1 + i*0.3)) * w * 0.25;
                    const cy = h/2 + Math.cos(time * (1 + i*0.3)) * h * 0.25;
                    const grad = ctx.createRadialGradient(cx, cy, 0, cx, cy, w * 0.35);
                    const hues = [260, 280, 340];
                    grad.addColorStop(0, `hsla(${hues[i]}, 80%, 50%, 0.12)`);
                    grad.addColorStop(0.5, `hsla(${hues[i]}, 70%, 30%, 0.06)`);
                    grad.addColorStop(1, 'transparent');
                    ctx.fillStyle = grad;
                    ctx.fillRect(0, 0, w, h);
                }
                requestAnimationFrame(draw);
            }
            draw();
        })();
    </script>
</body>
</html>
