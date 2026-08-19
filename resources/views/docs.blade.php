<x-layouts::app.sidebar :title="__('Documentation')">
    <flux:main class="bg-zinc-50 dark:bg-zinc-950">
        <div class="mx-auto max-w-6xl space-y-8 px-6 py-8 lg:px-8">
            <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 bg-gradient-to-br from-zinc-950 via-zinc-900 to-amber-950/70 px-6 py-8 dark:border-zinc-800 lg:px-8">
                    <div class="max-w-3xl space-y-4">
                        <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            Book-it Operations Playbook
                        </div>

                        <div class="space-y-3">
                            <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                                Run bookings, calendar sync, and client follow-up from one place.
                            </h1>
                            <p class="max-w-2xl text-sm leading-6 text-zinc-300 sm:text-base">
                                This guide covers the exact operating flow for Book-it: connecting Google Calendar,
                                shaping the public booking experience, managing availability, reviewing incoming leads,
                                and diagnosing sync issues without losing context.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 px-6 py-6 sm:grid-cols-2 xl:grid-cols-4 lg:px-8">
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">Primary Workflow</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Connect → Configure → Book → Review</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">The product is designed so calendar connectivity and booking operations stay visible from the same dashboard.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">Public Experience</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Landing page + booking wizard</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">Visitors select a time, submit their details, and receive the confirmation state immediately.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">Internal Operations</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Appointments & lead dossier</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">Every booking can be reviewed from the drawer with contact details, brief, notes, and sync state.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">Troubleshooting</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Meet links, API access, notifications</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">When something fails, inspect the booking first, then the integration card, then recent logs.</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-xl text-amber-500">1</div>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Connect Google Calendar & Meet</p>
                                <h2 class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">Turn on native event creation before taking live traffic.</h2>
                            </div>
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                Use the <span class="font-semibold text-zinc-900 dark:text-white">Integrations & Notification Templates</span>
                                tab to connect the consultant’s Google account. Book-it uses that connection to create a real Google
                                Calendar event and save the generated Meet URL directly on the booking record.
                            </p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                                    <p class="text-xs font-semibold text-zinc-900 dark:text-white">Required outcome</p>
                                    <p class="mt-2 text-xs leading-5 text-zinc-500">A connected account badge appears in the dashboard and new bookings begin storing a Meet link.</p>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                                    <p class="text-xs font-semibold text-zinc-900 dark:text-white">Best practice</p>
                                    <p class="mt-2 text-xs leading-5 text-zinc-500">After connecting, make one test booking and confirm the event appears in Google Calendar before announcing the page.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Connection checklist</p>
                    <ul class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                        <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-amber-500"></span><span>Google account connected from the dashboard.</span></li>
                        <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-amber-500"></span><span>Google Calendar API enabled in the linked Google Cloud project.</span></li>
                        <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-amber-500"></span><span>New booking produces a saved Meet link on the booking record.</span></li>
                        <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-amber-500"></span><span>Guest confirmation email includes the final meeting link.</span></li>
                    </ul>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Customise the public page</p>
                    <h2 class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">Keep the public booking page aligned with your offer.</h2>
                    <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        In the dashboard, the front-end settings let you update the badge text, headline,
                        supporting copy, and the three value bullets shown beside the booking wizard.
                        This keeps the public story editable without changing templates by hand.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <li>• Update the headline when your offer or campaign changes.</li>
                        <li>• Use the subheadline to frame what the session is for and what clients should prepare.</li>
                        <li>• Keep the three bullets outcome-driven so visitors understand the value before they book.</li>
                    </ul>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Availability & booking rules</p>
                    <h2 class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">Control when clients can actually schedule.</h2>
                    <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        The availability tab defines recurring working windows for each day. Book-it only presents
                        time slots that fall inside those windows and are not already occupied by confirmed bookings.
                    </p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white">Use multiple sessions</p>
                            <p class="mt-2 text-xs leading-5 text-zinc-500">Split mornings and afternoons, or add evening consulting windows when needed.</p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white">Avoid manual overlap</p>
                            <p class="mt-2 text-xs leading-5 text-zinc-500">Confirmed bookings lock the slot, so clients cannot double-book the same appointment time.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Booking flow lifecycle</p>
                <h2 class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">What happens from the moment a visitor clicks confirm.</h2>
                <div class="mt-6 grid gap-4 lg:grid-cols-5">
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Step 01</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Booking record saved</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">Guest details, timezone, schedule, and lead data are stored immediately.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Step 02</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Calendar sync runs</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">Book-it attempts to create the Google Calendar event and save the Meet link.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Step 03</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Guest notifications</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">The guest receives the configured confirmation email, including the final Meet link when available.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Step 04</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Ops review</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">Open the booking drawer to inspect contact details, brief, notes, and sync state.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Step 05</p>
                        <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">Follow-through</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">Use the record to prepare for the meeting, update notes, or clean up exceptions quickly.</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Inside the dashboard</p>
                    <h2 class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">Use the appointment drawer as the single source of truth.</h2>
                    <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        Selecting a booking opens the lead dossier. This is where you can inspect the guest profile,
                        project brief, notes, scheduled time, and the new calendar sync status panel for that specific record.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <li>• Use the status card to confirm whether the Meet link was created or attention is needed.</li>
                        <li>• The connected account field helps you confirm which Google account was responsible for the sync attempt.</li>
                        <li>• If a booking has no Meet link, review the “What to check next” list directly inside that drawer.</li>
                    </ul>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Notifications & templates</p>
                    <h2 class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">Keep outbound messages accurate and reusable.</h2>
                    <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        The notification settings let you control the guest email subject and body, plus WhatsApp delivery credentials.
                        Use placeholders such as <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] dark:bg-zinc-800">{guest_name}</code>,
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] dark:bg-zinc-800">{team_name}</code>,
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] dark:bg-zinc-800">{start_time}</code>, and
                        <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] dark:bg-zinc-800">{meet_link}</code>.
                    </p>
                    <p class="mt-4 text-xs leading-5 text-zinc-500">
                        If a booking syncs after the guest message is built, the saved Meet link becomes the canonical meeting URL shown inside the dashboard record.
                    </p>
                </div>
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:p-7">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Troubleshooting & Recovery</p>
                <h2 class="mt-2 text-xl font-semibold text-zinc-900 dark:text-white">Use this order when something feels off.</h2>
                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">1. Open the booking record</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">If the meeting link is missing, the drawer will now show the sync status, connected account, and next checks for that booking.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">2. Review integrations</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">Confirm the Google connection is still active and reconnect if consent, token scope, or account selection changed.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">3. Verify Google-side access</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">If bookings save but no Meet link is created, verify Google Calendar API access and then run one clean test booking.</p>
                    </div>
                </div>
            </section>
        </div>
    </flux:main>
</x-layouts::app.sidebar>
