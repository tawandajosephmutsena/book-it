<x-layouts::app.sidebar :title="__('Documentation')">
    <flux:main class="bg-[#1c1917]">
        <div class="max-w-4xl mx-auto px-6 py-10">
            <!-- Header -->
            <div class="mb-12 text-center">
                <div class="inline-flex items-center gap-3 px-5 py-2 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-bold uppercase tracking-[0.2em] mb-6">
                    <span class="text-lg">⚙️</span> The Book-it Manual
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4" style="font-family: 'Instrument Sans', sans-serif;">
                    How to Actually Use This Thing
                </h1>
                <p class="text-zinc-400 max-w-xl mx-auto text-lg leading-relaxed">
                    Written by someone who has answered the same question 47 times. 
                    You're welcome. Now grab a coffee and let's go. ☕
                </p>
            </div>

            <!-- Step 1 -->
            <div class="mb-10 bg-[#151310] rounded-2xl border border-amber-900/20 p-6 sm:p-8 shadow-lg">
                <div class="flex items-start gap-5">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center text-2xl shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                        🔗
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-amber-400 mb-2" style="font-family: 'Instrument Sans', sans-serif;">
                            1. Connect Your Google Calendar
                        </h2>
                        <p class="text-zinc-300 mb-4 leading-relaxed">
                            Without this, Book-it is just a very pretty "404" page. Head to the 
                            <strong class="text-white">Integrations</strong> tab on your dashboard and hit that big shiny 
                            "Sign in with Google" button. Grant the permissions (yes, all of them — we need to 
                            read your calendar and create events, not send emails to your ex).
                        </p>
                        <div class="bg-[#0d0b09] rounded-lg p-4 border border-zinc-800/50 text-sm">
                            <p class="text-zinc-400 mb-2 font-mono text-xs uppercase tracking-wider">⚠️ Common Screw-Up</p>
                            <p class="text-zinc-500">
                                If you see "Error 400: invalid_request", your Google OAuth credentials aren't set up. 
                                Ping Tawanda — he's got the keys to the kingdom. 🔑
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="mb-10 bg-[#151310] rounded-2xl border border-amber-900/20 p-6 sm:p-8 shadow-lg">
                <div class="flex items-start gap-5">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center text-2xl shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                        🎨
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-amber-400 mb-2" style="font-family: 'Instrument Sans', sans-serif;">
                            2. Customise Your Booking Page
                        </h2>
                        <p class="text-zinc-300 mb-4 leading-relaxed">
                            The <strong class="text-white">Front End Settings</strong> panel on your dashboard 
                            controls what visitors see. Change the headline, subtext, and three benefit bullets. 
                            Hit save. Refresh your public page. Marvel at your own genius.
                        </p>
                        <div class="bg-[#0d0b09] rounded-lg p-4 border border-zinc-800/50 text-sm">
                            <p class="text-zinc-400 mb-2 font-mono text-xs uppercase tracking-wider">💡 Pro Tip</p>
                            <p class="text-zinc-500">
                                The headline period gets a gold accent colour automatically. So if you write 
                                "Let's build something." — that dot will glow amber. Design magic. ✨
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="mb-10 bg-[#151310] rounded-2xl border border-amber-900/20 p-6 sm:p-8 shadow-lg">
                <div class="flex items-start gap-5">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center text-2xl shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                        📅
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-amber-400 mb-2" style="font-family: 'Instrument Sans', sans-serif;">
                            3. Your Booking Calendar (Availability)
                        </h2>
                        <p class="text-zinc-300 mb-4 leading-relaxed">
                            Book-it pulls your <strong class="text-white">Google Calendar availability</strong> 
                            automatically. If you're busy 9-5 on Tuesday, those slots won't show. 
                            The system checks your working hours (Mon-Fri, 9AM-5PM, Harare time) 
                            and only offers free 30-minute slots.
                        </p>
                        <ul class="space-y-2 text-zinc-400">
                            <li class="flex items-center gap-2"><span class="text-amber-400">📌</span> Block time in Google Calendar = slot disappears from Book-it</li>
                            <li class="flex items-center gap-2"><span class="text-amber-400">📌</span> Bookings create Google Calendar events with guest details</li>
                            <li class="flex items-center gap-2"><span class="text-amber-400">📌</span> Confirmation emails go to both you AND the client</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="mb-10 bg-[#151310] rounded-2xl border border-amber-900/20 p-6 sm:p-8 shadow-lg">
                <div class="flex items-start gap-5">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center text-2xl shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                        🧪
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-amber-400 mb-2" style="font-family: 'Instrument Sans', sans-serif;">
                            4. Test It (Before Your Client Does)
                        </h2>
                        <p class="text-zinc-300 mb-4 leading-relaxed">
                            Open your public page in an incognito window. Run through the wizard: 
                            pick a date → pick a time → fill in fake details → hit confirm. 
                            Check that:
                        </p>
                        <div class="flex flex-col gap-2 text-sm">
                            <span class="inline-flex items-center gap-2 text-green-400"><span>✓</span> The confirmation screen shows up (no white screen of death)</span>
                            <span class="inline-flex items-center gap-2 text-green-400"><span>✓</span> You get a confirmation email within 60 seconds</span>
                            <span class="inline-flex items-center gap-2 text-green-400"><span>✓</span> The event appears on your Google Calendar</span>
                            <span class="inline-flex items-center gap-2 text-green-400"><span>✓</span> The client gets THEIR confirmation email (check spam!)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="mb-10 bg-[#151310] rounded-2xl border border-amber-900/20 p-6 sm:p-8 shadow-lg">
                <div class="flex items-start gap-5">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center text-2xl shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                        🧠
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-amber-400 mb-2" style="font-family: 'Instrument Sans', sans-serif;">
                            5. What Happens When Someone Books
                        </h2>
                        <p class="text-zinc-300 mb-4 leading-relaxed">
                            Here's the magic sequence, in order:
                        </p>
                        <ol class="space-y-3 text-zinc-400 list-decimal list-inside">
                            <li>
                                <span class="text-white font-medium">Booking lands in the database.</span> 
                                <span class="text-zinc-500">Quietly. No fanfare.</span>
                            </li>
                            <li>
                                <span class="text-white font-medium">Google Calendar event created.</span> 
                                <span class="text-zinc-500">With the guest's name, email, phone, company, and project brief in the description.</span>
                            </li>
                            <li>
                                <span class="text-white font-medium">Confirmation email to the guest.</span> 
                                <span class="text-zinc-500">"You're booked! Here's when…" with an .ics attachment.</span>
                            </li>
                            <li>
                                <span class="text-white font-medium">Notification email to YOU.</span> 
                                <span class="text-zinc-500">"Someone wants to talk to you. Don't mess this up."</span>
                            </li>
                            <li>
                                <span class="text-white font-medium">That time slot is now blocked.</span> 
                                <span class="text-zinc-500">No double-booking. Ever. We're not animals.</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div class="mb-10 bg-[#151310] rounded-2xl border border-amber-900/20 p-6 sm:p-8 shadow-lg">
                <div class="flex items-start gap-5">
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center text-2xl shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                        🙋
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-amber-400 mb-4" style="font-family: 'Instrument Sans', sans-serif;">
                            Stuff People Actually Ask
                        </h2>
                        
                        <div class="space-y-6">
                            <div>
                                <p class="text-white font-semibold mb-1">"Can I change my working hours?"</p>
                                <p class="text-zinc-400 text-sm">Currently set to 9AM-5PM Harare time, Mon-Fri. It's in the code. Tawanda can tweak it in 2 minutes. Just ask nicely.</p>
                            </div>
                            <div>
                                <p class="text-white font-semibold mb-1">"What if I use Outlook instead of Google?"</p>
                                <p class="text-zinc-400 text-sm">Then you're living in 2007 and we can't help you. Just kidding — reach out and we'll add Microsoft 365 integration. (Actually, we're not kidding about the 2007 part.)</p>
                            </div>
                            <div>
                                <p class="text-white font-semibold mb-1">"Why does the page look different on my phone?"</p>
                                <p class="text-zinc-400 text-sm">It shouldn't. Book-it is fully responsive. If something's broken, take a screenshot and send it to Tawanda before your client sees it. 🫣</p>
                            </div>
                            <div>
                                <p class="text-white font-semibold mb-1">"Can I embed this on my own website?"</p>
                                <p class="text-zinc-400 text-sm">Soon™. We're working on an embeddable widget. For now, link to your Book-it subdomain or the full page URL.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center pt-6 border-t border-amber-900/10">
                <p class="text-zinc-600 text-sm">
                    Built with ☕ and mild existential dread by <a href="https://ottomate.space" class="text-amber-500 hover:text-amber-400 underline underline-offset-2 transition-colors" target="_blank">Ottomate</a>.
                    <br class="sm:hidden">
                    <span class="hidden sm:inline">—</span> Still confused? That's what Tawanda's WhatsApp is for.
                </p>
            </div>
        </div>
    </flux:main>
</x-layouts::app.sidebar>
