<?php

use Livewire\Component;
use App\Models\Booking;
use App\Models\Availability;
use App\Models\GoogleIntegration;
use App\Models\Team;
use Carbon\Carbon;

new class extends Component
{
    public Team $team;
    public $tab = 'analytics';
    
    // Selected booking for response management details
    public $selectedBooking = null;

    // Availability settings (array of sessions per day)
    public $availabilitySettings = [];

    // Front-end customizable text
    public $badge_text = '';
    public $headline = '';
    public $subheadline = '';
    public $benefit_1 = '';
    public $benefit_2 = '';
    public $benefit_3 = '';

    // Notification customizable settings (WhatsApp & Email)
    public $whatsapp_access_token = '';
    public $whatsapp_phone_number_id = '';
    public $whatsapp_template_name = '';
    public $email_subject = '';
    public $email_body = '';

    public function mount()
    {
        $this->team = auth()->user()->currentTeam;
        
        // 1. Load Availability
        $this->loadAvailability();

        // 2. Load Front-End customizable text
        $frontFile = storage_path('app/landing_page_settings.json');
        if (file_exists($frontFile)) {
            $front = json_decode(file_get_contents($frontFile), true);
            $this->badge_text = $front['badge_text'] ?? 'Strategy Session Booking';
            $this->headline = $front['headline'] ?? 'Automate your entire business.';
            $this->subheadline = $front['subheadline'] ?? 'Select a convenient date and time on the calendar below to map out a custom automation blueprint for your business with the Ottomate team.';
            $this->benefit_1 = $front['benefit_1'] ?? 'Free 30-Min Call';
            $this->benefit_2 = $front['benefit_2'] ?? 'Custom Blueprint SVG';
            $this->benefit_3 = $front['benefit_3'] ?? 'No Commitment';
        } else {
            $this->badge_text = 'Strategy Session Booking';
            $this->headline = 'Automate your entire business.';
            $this->subheadline = 'Select a convenient date and time on the calendar below to map out a custom automation blueprint for your business with the Ottomate team.';
            $this->benefit_1 = 'Free 30-Min Call';
            $this->benefit_2 = 'Custom Blueprint SVG';
            $this->benefit_3 = 'No Commitment';
        }

        // 3. Load Notification settings
        $notifFile = storage_path('app/notification_settings.json');
        if (file_exists($notifFile)) {
            $notif = json_decode(file_get_contents($notifFile), true);
            $this->whatsapp_access_token = $notif['whatsapp_access_token'] ?? '';
            $this->whatsapp_phone_number_id = $notif['whatsapp_phone_number_id'] ?? '';
            $this->whatsapp_template_name = $notif['whatsapp_template_name'] ?? 'booking_confirmation';
            $this->email_subject = $notif['email_subject'] ?? 'Booking Confirmed: {team_name} Session';
            $this->email_body = $notif['email_body'] ?? "Hi {guest_name},\n\nYour meeting with {team_name} is confirmed for {start_time}.\n\nGoogle Meet: {meet_link}\n\nWe look forward to meeting you!";
        } else {
            $this->whatsapp_template_name = 'booking_confirmation';
            $this->email_subject = 'Booking Confirmed: {team_name} Session';
            $this->email_body = "Hi {guest_name},\n\nYour meeting with {team_name} is confirmed for {start_time}.\n\nGoogle Meet: {meet_link}\n\nWe look forward to meeting you!";
        }
    }

    public function loadAvailability()
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        $existing = Availability::where('team_id', $this->team->id)
            ->where('type', 'recurring')
            ->get();

        foreach ($days as $day) {
            $daySlots = $existing->where('day_of_week', $day);

            if ($daySlots->count() > 0) {
                $sessions = [];
                foreach ($daySlots as $slot) {
                    $sessions[] = [
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                    ];
                }
                $this->availabilitySettings[$day] = [
                    'is_available' => true,
                    'sessions' => $sessions
                ];
            } else {
                $this->availabilitySettings[$day] = [
                    'is_available' => in_array($day, ['Saturday', 'Sunday']) ? false : true,
                    'sessions' => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ]
                ];
            }
        }
    }

    public function addSession($day)
    {
        $this->availabilitySettings[$day]['sessions'][] = ['start_time' => '18:00', 'end_time' => '20:00'];
    }

    public function removeSession($day, $index)
    {
        unset($this->availabilitySettings[$day]['sessions'][$index]);
        $this->availabilitySettings[$day]['sessions'] = array_values($this->availabilitySettings[$day]['sessions']);
    }

    public function saveAvailability()
    {
        // Clear existing recurring slots for this team
        Availability::where('team_id', $this->team->id)->where('type', 'recurring')->delete();

        foreach ($this->availabilitySettings as $day => $config) {
            if (!empty($config['is_available']) && !empty($config['sessions'])) {
                foreach ($config['sessions'] as $session) {
                    Availability::create([
                        'team_id' => $this->team->id,
                        'type' => 'recurring',
                        'day_of_week' => $day,
                        'start_time' => $session['start_time'] ?? '09:00',
                        'end_time' => $session['end_time'] ?? '17:00',
                        'is_available' => true,
                    ]);
                }
            }
        }

        $this->dispatch('toast', message: 'Multiple daily sessions updated.', variant: 'success');
    }

    public function saveFrontEnd()
    {
        $this->validate([
            'badge_text' => 'required|string|max:100',
            'headline' => 'required|string|max:200',
            'subheadline' => 'required|string|max:500',
            'benefit_1' => 'required|string|max:100',
            'benefit_2' => 'required|string|max:100',
            'benefit_3' => 'required|string|max:100',
        ]);

        $settings = [
            'badge_text' => $this->badge_text,
            'headline' => $this->headline,
            'subheadline' => $this->subheadline,
            'benefit_1' => $this->benefit_1,
            'benefit_2' => $this->benefit_2,
            'benefit_3' => $this->benefit_3,
        ];

        file_put_contents(storage_path('app/landing_page_settings.json'), json_encode($settings, JSON_PRETTY_PRINT));
        $this->dispatch('toast', message: 'Front-end text content saved.', variant: 'success');
    }

    public function saveNotifications()
    {
        $this->validate([
            'whatsapp_access_token' => 'nullable|string',
            'whatsapp_phone_number_id' => 'nullable|string',
            'whatsapp_template_name' => 'nullable|string',
            'email_subject' => 'required|string|max:200',
            'email_body' => 'required|string',
        ]);

        $settings = [
            'whatsapp_access_token' => $this->whatsapp_access_token,
            'whatsapp_phone_number_id' => $this->whatsapp_phone_number_id,
            'whatsapp_template_name' => $this->whatsapp_template_name,
            'email_subject' => $this->email_subject,
            'email_body' => $this->email_body,
        ];

        file_put_contents(storage_path('app/notification_settings.json'), json_encode($settings, JSON_PRETTY_PRINT));
        $this->dispatch('toast', message: 'Email & WhatsApp templates saved.', variant: 'success');
    }

    public function cancelBooking($id)
    {
        $booking = Booking::where('team_id', $this->team->id)->findOrFail($id);
        $booking->update(['status' => 'canceled']);
        
        if ($this->selectedBooking && $this->selectedBooking->id === (int) $id) {
            $this->selectedBooking = $booking;
        }

        $this->dispatch('toast', message: 'Booking canceled.', variant: 'warning');
    }

    public function selectBooking($id)
    {
        $this->selectedBooking = Booking::where('team_id', $this->team->id)->findOrFail($id);
    }

    public function exportMarkdownReport()
    {
        $bookings = $this->bookings;
        $total = $bookings->count();
        $confirmed = $bookings->where('status', 'confirmed')->count();
        $canceled = $bookings->where('status', 'canceled')->count();
        $rate = $total > 0 ? round(($confirmed / $total) * 100, 1) : 0;

        $md = "# Appointments & Lead Responses Analytics Report\n";
        $md .= "**Team:** {$this->team->name}\n";
        $md .= "**Generated On:** " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        $md .= "## 📊 Performance Metrics\n";
        $md .= "- **Total Appointments:** {$total}\n";
        $md .= "- **Confirmed:** {$confirmed}\n";
        $md .= "- **Canceled:** {$canceled}\n";
        $md .= "- **Conversion / Confirmation Rate:** {$rate}%\n\n";

        $md .= "## 📅 Appointment & Screening Details\n";
        $md .= "| ID | Guest Name | Guest Email | Scheduled Time | Timezone | Status | Lead Notes |\n";
        $md .= "|---|---|---|---|---|---|---|\n";

        foreach ($bookings as $b) {
            $notes = str_replace(["\r", "\n"], " ", $b->lead_data['notes'] ?? 'N/A');
            $timeStr = $b->start_time->format('Y-m-d H:i');
            $md .= "| {$b->id} | {$b->guest_name} | {$b->guest_email} | {$timeStr} | {$b->guest_timezone} | {$b->status} | {$notes} |\n";
        }

        $md .= "\n---\n*Exported directly from Ottomate Book-it Analytics*";

        return response()->streamDownload(function () use ($md) {
            echo $md;
        }, 'bookings-analytics-report-' . now()->format('Y-m-d') . '.md', [
            'Content-Type' => 'text/markdown',
        ]);
    }

    public function getBookingsProperty()
    {
        return Booking::where('team_id', $this->team->id)
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getGoogleSyncProperty()
    {
        return GoogleIntegration::where('user_id', auth()->id())->first();
    }
    public function getPeakBookingHourProperty()
    {
        $bookings = $this->bookings;
        if ($bookings->isEmpty()) return 'N/A';
        
        $hours = $bookings->map(function ($b) {
            return $b->start_time->format('H');
        })->countBy();
        
        $peakHour = $hours->sortDesc()->keys()->first();
        return \Carbon\Carbon::createFromFormat('H', $peakHour)->format('g A');
    }

    public function getConversionRateProperty()
    {
        $views = $this->team->page_views ?? 0;
        $totalBookings = $this->bookings->count();
        if ($views === 0) return 0;
        return round(($totalBookings / $views) * 100, 1);
    }

    public function getCancellationRateProperty()
    {
        $total = $this->bookings->count();
        if ($total === 0) return 0;
        $canceled = $this->bookings->where('status', 'canceled')->count();
        return round(($canceled / $total) * 100, 1);
    }

    #[Livewire\Attributes\On('executeCommand')]
    public function executeCommand($data)
    {
        $action = $data['action'] ?? null;
        switch ($action) {
            case 'copy_link':
                $this->dispatch('toast', message: 'Booking link copied to clipboard.', variant: 'success');
                break;
            case 'new_event':
                $this->dispatch('toast', message: 'Event creation modal opening...', variant: 'default');
                break;
            case 'block_time':
                $this->dispatch('toast', message: 'Time blocked for today.', variant: 'success');
                break;
            case 'search_guests':
                $this->tab = 'analytics';
                $this->dispatch('toast', message: 'Searching guests...', variant: 'default');
                break;
            case 'toggle_theme':
                $this->dispatch('toggle-dark-mode');
                $this->dispatch('toast', message: 'Theme toggled.', variant: 'success');
                break;
        }
    }
};
?>

<div>
<style>
@import url('https://fonts.googleapis.com/css2?family=Syncopate:wght@400;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=IBM+Plex+Mono:wght@400;700&display=swap');

:root {
  --neon-gold: #ffbe3b;
  --copper-glow: #b87333;
  --dark-glass: rgba(26, 17, 10, 0.8);
  --brass: #b5873a;
  --deep-black: #0a0705;
}
.font-syncopate { font-family: 'Syncopate', sans-serif; }
.font-playfair { font-family: 'Playfair Display', serif; }
.font-mono { font-family: 'IBM Plex Mono', monospace; }

@keyframes gear-rotate { to { transform: rotate(360deg); } }
.gear-spin { animation: gear-rotate 20s linear infinite; }

::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: #0a0705; }
::-webkit-scrollbar-thumb { background: #b5873a; border-radius: 3px; }

.magnetic { display: inline-block; cursor: pointer; }
</style>

<!-- SVG Gradients & Filters for High-Fidelity Glow -->
<svg style="width:0;height:0;position:absolute;" aria-hidden="true" focusable="false">
    <defs>
        <filter id="neon-glow" x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur in="SourceGraphic" stdDeviation="3" result="blur1" />
            <feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur2" />
            <feMerge>
                <feMergeNode in="blur2" />
                <feMergeNode in="blur1" />
                <feMergeNode in="SourceGraphic" />
            </feMerge>
        </filter>
        <filter id="copper-glow" x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur in="SourceGraphic" stdDeviation="4" result="blur1" />
            <feMerge>
                <feMergeNode in="blur1" />
                <feMergeNode in="SourceGraphic" />
            </feMerge>
        </filter>
        <filter id="red-glow" x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur in="SourceGraphic" stdDeviation="3" result="blur1" />
            <feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur2" />
            <feMerge>
                <feMergeNode in="blur2" />
                <feMergeNode in="blur1" />
                <feMergeNode in="SourceGraphic" />
            </feMerge>
        </filter>
        <filter id="green-glow" x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur in="SourceGraphic" stdDeviation="3" result="blur1" />
            <feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur2" />
            <feMerge>
                <feMergeNode in="blur2" />
                <feMergeNode in="blur1" />
                <feMergeNode in="SourceGraphic" />
            </feMerge>
        </filter>
    </defs>
</svg>

<div class="space-y-8 bg-[#0a0705] min-h-screen p-8 text-[#f5e6c8] relative overflow-hidden font-mono" x-data="commandPalette()">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <!-- Global Command Palette Listener -->
    <div @keydown.window.prevent.cmd.k="open = true" @keydown.window.prevent.ctrl.k="open = true"></div>

    <!-- Command Palette Modal -->
    <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-start justify-center min-h-screen pt-24 px-4 pb-20 text-center sm:p-0">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-[#0a0705]/90 backdrop-blur-md transition-opacity" @click="open = false"></div>
            
            <div x-show="open" x-transition class="relative bg-[#1a110a] rounded-xl max-w-xl w-full border border-[#b5873a]/50 shadow-[0_0_30px_rgba(255,190,59,0.2)] overflow-hidden text-left align-middle backdrop-blur-xl">
                <div class="flex items-center px-4 py-3 border-b border-[#b5873a]/30">
                    <svg class="w-5 h-5 text-[#ffbe3b]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" x-model="search" class="w-full bg-transparent border-0 focus:ring-0 text-[#f5e6c8] placeholder-[#b5873a]/50 sm:text-sm ml-2 font-mono outline-none" placeholder="Search mechanisms..." x-ref="searchInput" autofocus>
                    <span class="text-[10px] text-[#ffbe3b] border border-[#ffbe3b]/50 rounded px-1.5 py-0.5 bg-[#b5873a]/20">ESC</span>
                </div>
                
                <div class="max-h-72 overflow-y-auto p-2">
                    <template x-for="item in filteredItems" :key="item.id">
                        <button @click="executeCommand(item)" class="w-full text-left flex items-center px-4 py-3 hover:bg-[#b5873a]/20 rounded border border-transparent hover:border-[#ffbe3b]/30 group transition-all">
                            <span class="flex-1 text-sm font-bold text-[#f5e6c8] font-mono" x-text="item.title"></span>
                            <span class="text-xs text-[#ffbe3b] opacity-0 group-hover:opacity-100 transition-opacity uppercase tracking-widest font-mono shadow-[0_0_10px_#ffbe3b]">Execute &rarr;</span>
                        </button>
                    </template>
                    <div x-show="filteredItems.length === 0" class="py-8 text-center text-sm text-[#ffbe3b]/50 font-mono italic">
                        No commands recognized in the machinery.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('commandPalette', () => ({
                open: false,
                search: '',
                items: [
                    { id: 1, title: 'Copy Booking Link', action: 'copy_link' },
                    { id: 2, title: 'Create New Event Type', action: 'new_event' },
                    { id: 3, title: 'Block Time Today', action: 'block_time' },
                    { id: 4, title: 'Search Guests', action: 'search_guests' },
                    { id: 5, title: 'Toggle Theme', action: 'toggle_theme' },
                ],
                get filteredItems() {
                    if (this.search === '') return this.items;
                    return this.items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase()));
                },
                executeCommand(item) {
                    this.open = false;
                    this.search = '';
                    Livewire.dispatch('executeCommand', { action: item.action });
                },
                init() {
                    this.$watch('open', value => {
                        if (value) setTimeout(() => this.$refs.searchInput.focus(), 50);
                    });
                    this.$el.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && this.open) this.open = false;
                    });
                }
            }));
        });
    </script>

    <!-- Decorative Gears -->
    <svg class="gear-spin absolute -top-24 -left-24 w-64 h-64 text-[#ffbe3b]/5 pointer-events-none filter drop-shadow-[0_0_15px_rgba(255,190,59,0.1)]" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97s-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1s.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64L19.43 12.97z"/>
    </svg>
    <svg class="gear-spin absolute -bottom-32 -right-32 w-96 h-96 text-[#b87333]/5 pointer-events-none filter drop-shadow-[0_0_15px_rgba(184,115,51,0.1)]" viewBox="0 0 24 24" fill="currentColor" style="animation-direction: reverse; animation-duration: 40s;">
        <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97s-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1s.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64L19.43 12.97z"/>
    </svg>

    <!-- Page Header -->
    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 border-b border-[#b5873a]/30 pb-6">
        <div>
            <h1 class="text-3xl md:text-5xl font-bold tracking-tight text-[#f5e6c8] font-syncopate uppercase drop-shadow-[0_0_15px_rgba(255,190,59,0.3)]">
                <span class="text-[#ffbe3b] drop-shadow-[0_0_10px_#ffbe3b]">⚙️</span> BOOK-IT <span class="text-[#b5873a] opacity-50">·</span> LEDGER
            </h1>
            <p class="mt-2 text-[#ffbe3b] font-mono text-sm tracking-widest uppercase opacity-80 shadow-[0_0_5px_#ffbe3b]">Master Operational Command Center</p>
        </div>
        
        <!-- QR Code & Booking Link Widget -->
        <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#ffbe3b]/50 rounded-lg p-3 shadow-[0_0_20px_rgba(255,190,59,0.15)] flex items-center gap-4 relative group hover:border-[#ffbe3b] transition-colors">
            <div class="absolute -top-1.5 -left-1.5 w-3 h-3 rounded-full bg-[#ffbe3b] shadow-[0_0_10px_#ffbe3b] border border-[#0a0705]"></div>
            <div class="absolute -bottom-1.5 -right-1.5 w-3 h-3 rounded-full bg-[#ffbe3b] shadow-[0_0_10px_#ffbe3b] border border-[#0a0705]"></div>
            
            <div class="text-[#f5e6c8]">
                <div class="text-[10px] font-bold uppercase tracking-widest text-[#b5873a] mb-1 font-mono">Public Scheduling Uplink</div>
                <div class="flex items-center gap-2">
                    <code class="text-xs bg-[#0a0705] text-[#ffbe3b] px-2 py-1 rounded font-mono border border-[#b5873a]/30">
                        {{ config('app.url') }}/{{ $this->team->slug ?? 'book' }}
                    </code>
                    <button wire:click="$dispatch('executeCommand', { action: 'copy_link' })" class="p-1.5 bg-[#b5873a]/20 hover:bg-[#ffbe3b]/30 text-[#ffbe3b] rounded border border-[#ffbe3b]/50 transition-all magnetic" title="Copy Link">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    </button>
                </div>
                <div class="text-[10px] font-mono italic opacity-50 mt-1">Share this link in your outreach</div>
            </div>
        </div>
    </div>

    <!-- Retro Analog Dial Gauges -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative z-10 mb-8">
        @php
            $totalBookings = $this->bookings->count();
            $bookingTarget = 100;
            $bookingPercentage = min(($totalBookings / $bookingTarget) * 100, 100);
            
            $convRate = $this->conversionRate;
            $convPercentage = min($convRate, 100);
            
            $peak = $this->peakBookingHour;
            // Map peak hour string to a pseudo-percentage for the dial (e.g. 12 PM = 50%)
            $peakVal = 0;
            if($peak !== 'N/A') {
                $time = \Carbon\Carbon::parse($peak);
                $peakVal = ($time->hour / 24) * 100;
            }

            $cancelRate = $this->cancellationRate;
            $cancelPercentage = min($cancelRate, 100);
        @endphp

        <!-- Total Bookings Gauge -->
        <div class="ledger-card relative bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] overflow-hidden group hover:border-[#ffbe3b]/50 transition-colors">
            <div class="absolute top-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute top-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            
            <div class="text-xs font-bold uppercase tracking-wider text-[#b5873a] text-center font-mono">Volume</div>
            
            <div class="relative w-32 h-32 mx-auto my-3">
                <svg viewBox="0 0 120 120" class="w-full h-full drop-shadow-md">
                    <circle cx="60" cy="60" r="56" fill="transparent" stroke="#0a0705" stroke-width="2"/>
                    <circle cx="60" cy="60" r="52" fill="transparent" stroke="#b5873a" stroke-width="1" opacity="0.3"/>
                    
                    @for($i = 0; $i <= 10; $i++)
                        @php $angle = -135 + ($i * 27); $x1 = 60 + 42 * cos(deg2rad($angle)); $y1 = 60 + 42 * sin(deg2rad($angle)); $x2 = 60 + 48 * cos(deg2rad($angle)); $y2 = 60 + 48 * sin(deg2rad($angle)); @endphp
                        <line x1="{{$x1}}" y1="{{$y1}}" x2="{{$x2}}" y2="{{$y2}}" stroke="#b87333" stroke-width="{{ $i % 5 == 0 ? 3 : 1 }}"/>
                    @endfor
                    
                    <path d="M 20 95 A 50 50 0 1 1 100 95" fill="none" stroke="#0a0705" stroke-width="8" stroke-linecap="round"/>
                    <path d="M 20 95 A 50 50 0 1 1 100 95" fill="none" stroke="#ffbe3b" stroke-width="6" stroke-linecap="round" class="gauge-arc" filter="url(#neon-glow)" style="stroke-dasharray: {{ $bookingPercentage * 2.35 }} 235;"/>
                    
                    <text x="60" y="70" text-anchor="middle" fill="#ffbe3b" font-size="28" font-weight="bold" class="font-playfair" filter="url(#neon-glow)">{{ $totalBookings }}</text>
                </svg>
                <div class="gauge-needle absolute bottom-[18%] left-1/2 -translate-x-1/2 w-1 h-14 bg-[#ffbe3b] origin-bottom rounded-t-full shadow-[0_0_15px_#ffbe3b] z-10" data-angle="{{ -135 + ($bookingPercentage * 2.7) }}"></div>
                <div class="absolute bottom-[18%] left-1/2 -translate-x-1/2 translate-y-1/2 w-4 h-4 bg-[#1a110a] rounded-full border-2 border-[#ffbe3b] shadow-[0_0_10px_#ffbe3b] z-20"></div>
            </div>
            
            <div class="text-center text-[10px] text-[#b5873a] uppercase tracking-widest font-bold border-t border-[#b5873a]/30 pt-2 font-mono">Total Appointments</div>
        </div>

        <!-- Conversion Rate Gauge -->
        <div class="ledger-card relative bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] overflow-hidden group hover:border-[#34d399]/50 transition-colors">
            <div class="absolute top-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute top-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            
            <div class="text-xs font-bold uppercase tracking-wider text-[#b5873a] text-center font-mono">Efficiency</div>
            
            <div class="relative w-32 h-32 mx-auto my-3">
                <svg viewBox="0 0 120 120" class="w-full h-full drop-shadow-md">
                    <circle cx="60" cy="60" r="56" fill="transparent" stroke="#0a0705" stroke-width="2"/>
                    <circle cx="60" cy="60" r="52" fill="transparent" stroke="#b5873a" stroke-width="1" opacity="0.3"/>
                    
                    @for($i = 0; $i <= 10; $i++)
                        @php $angle = -135 + ($i * 27); $x1 = 60 + 42 * cos(deg2rad($angle)); $y1 = 60 + 42 * sin(deg2rad($angle)); $x2 = 60 + 48 * cos(deg2rad($angle)); $y2 = 60 + 48 * sin(deg2rad($angle)); @endphp
                        <line x1="{{$x1}}" y1="{{$y1}}" x2="{{$x2}}" y2="{{$y2}}" stroke="#b87333" stroke-width="{{ $i % 5 == 0 ? 3 : 1 }}"/>
                    @endfor
                    
                    <path d="M 20 95 A 50 50 0 1 1 100 95" fill="none" stroke="#0a0705" stroke-width="8" stroke-linecap="round"/>
                    <path d="M 20 95 A 50 50 0 1 1 100 95" fill="none" stroke="#34d399" stroke-width="6" stroke-linecap="round" class="gauge-arc" filter="url(#green-glow)" style="stroke-dasharray: {{ $convPercentage * 2.35 }} 235;"/>
                    
                    <text x="60" y="70" text-anchor="middle" fill="#34d399" font-size="22" font-weight="bold" class="font-playfair" filter="url(#green-glow)">{{ $convRate }}%</text>
                </svg>
                <div class="gauge-needle absolute bottom-[18%] left-1/2 -translate-x-1/2 w-1 h-14 bg-[#34d399] origin-bottom rounded-t-full shadow-[0_0_15px_#34d399] z-10" data-angle="{{ -135 + ($convPercentage * 2.7) }}"></div>
                <div class="absolute bottom-[18%] left-1/2 -translate-x-1/2 translate-y-1/2 w-4 h-4 bg-[#1a110a] rounded-full border-2 border-[#34d399] shadow-[0_0_10px_#34d399] z-20"></div>
            </div>
            
            <div class="text-center text-[10px] text-[#b5873a] uppercase tracking-widest font-bold border-t border-[#b5873a]/30 pt-2 font-mono">Conversion Rate</div>
        </div>

        <!-- Peak Hours Gauge -->
        <div class="ledger-card relative bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] overflow-hidden group hover:border-[#b87333]/50 transition-colors">
            <div class="absolute top-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute top-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            
            <div class="text-xs font-bold uppercase tracking-wider text-[#b5873a] text-center font-mono">Chronograph</div>
            
            <div class="relative w-32 h-32 mx-auto my-3">
                <svg viewBox="0 0 120 120" class="w-full h-full drop-shadow-md">
                    <circle cx="60" cy="60" r="56" fill="transparent" stroke="#0a0705" stroke-width="2"/>
                    <circle cx="60" cy="60" r="52" fill="transparent" stroke="#b5873a" stroke-width="1" opacity="0.3"/>
                    
                    @for($i = 0; $i < 12; $i++)
                        @php $angle = ($i * 30) - 90; $x1 = 60 + 42 * cos(deg2rad($angle)); $y1 = 60 + 42 * sin(deg2rad($angle)); $x2 = 60 + 48 * cos(deg2rad($angle)); $y2 = 60 + 48 * sin(deg2rad($angle)); @endphp
                        <line x1="{{$x1}}" y1="{{$y1}}" x2="{{$x2}}" y2="{{$y2}}" stroke="#b87333" stroke-width="{{ $i % 3 == 0 ? 3 : 1 }}"/>
                    @endfor
                    
                    <text x="60" y="45" text-anchor="middle" fill="#b87333" font-size="8" font-family="sans-serif" font-weight="bold">PEAK</text>
                    <text x="60" y="80" text-anchor="middle" fill="#b87333" font-size="20" font-weight="bold" class="font-playfair" filter="url(#copper-glow)">{{ $peak }}</text>
                </svg>
                <div class="gauge-needle absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-1 h-12 bg-[#b87333] origin-bottom rounded-t-full shadow-[0_0_15px_#b87333] z-10" style="transform-origin: bottom center; margin-top: -24px;" data-angle="{{ ($peakVal * 3.6) }}"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 bg-[#1a110a] rounded-full border-2 border-[#b87333] shadow-[0_0_10px_#b87333] z-20"></div>
            </div>
            
            <div class="text-center text-[10px] text-[#b5873a] uppercase tracking-widest font-bold border-t border-[#b5873a]/30 pt-2 font-mono">Busiest Hour</div>
        </div>

        <!-- Cancellation Rate Gauge -->
        <div class="ledger-card relative bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-5 shadow-[0_0_25px_rgba(0,0,0,0.8)] overflow-hidden group hover:border-[#f87171]/50 transition-colors">
            <div class="absolute top-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute top-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 left-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            <div class="absolute bottom-2 right-2 w-2.5 h-2.5 rounded-full bg-[#b5873a]/50 shadow-inner border border-[#0a0705]"></div>
            
            <div class="text-xs font-bold uppercase tracking-wider text-[#b5873a] text-center font-mono">Attrition</div>
            
            <div class="relative w-32 h-32 mx-auto my-3">
                <svg viewBox="0 0 120 120" class="w-full h-full drop-shadow-md">
                    <circle cx="60" cy="60" r="56" fill="transparent" stroke="#0a0705" stroke-width="2"/>
                    <circle cx="60" cy="60" r="52" fill="transparent" stroke="#b5873a" stroke-width="1" opacity="0.3"/>
                    
                    @for($i = 0; $i <= 10; $i++)
                        @php $angle = -135 + ($i * 27); $x1 = 60 + 42 * cos(deg2rad($angle)); $y1 = 60 + 42 * sin(deg2rad($angle)); $x2 = 60 + 48 * cos(deg2rad($angle)); $y2 = 60 + 48 * sin(deg2rad($angle)); @endphp
                        <line x1="{{$x1}}" y1="{{$y1}}" x2="{{$x2}}" y2="{{$y2}}" stroke="#b87333" stroke-width="{{ $i % 5 == 0 ? 3 : 1 }}"/>
                    @endfor
                    
                    <path d="M 20 95 A 50 50 0 1 1 100 95" fill="none" stroke="#0a0705" stroke-width="8" stroke-linecap="round"/>
                    <path d="M 20 95 A 50 50 0 1 1 100 95" fill="none" stroke="#f87171" stroke-width="6" stroke-linecap="round" class="gauge-arc" filter="url(#red-glow)" style="stroke-dasharray: {{ $cancelPercentage * 2.35 }} 235;"/>
                    
                    <text x="60" y="70" text-anchor="middle" fill="#f87171" font-size="22" font-weight="bold" class="font-playfair" filter="url(#red-glow)">{{ $cancelRate }}%</text>
                </svg>
                <div class="gauge-needle absolute bottom-[18%] left-1/2 -translate-x-1/2 w-1 h-14 bg-[#f87171] origin-bottom rounded-t-full shadow-[0_0_15px_#f87171] z-10" data-angle="{{ -135 + ($cancelPercentage * 2.7) }}"></div>
                <div class="absolute bottom-[18%] left-1/2 -translate-x-1/2 translate-y-1/2 w-4 h-4 bg-[#1a110a] rounded-full border-2 border-[#f87171] shadow-[0_0_10px_#f87171] z-20"></div>
            </div>
            
            <div class="text-center text-[10px] text-[#b5873a] uppercase tracking-widest font-bold border-t border-[#b5873a]/30 pt-2 font-mono">Cancellations</div>
        </div>
    </div>

    <!-- Steampunk Navigation Tabs -->
    <div class="relative z-10 flex flex-wrap items-center gap-3 border-b border-[#b5873a]/30 pb-4 mb-8 bg-[#1a110a]/60 p-3 rounded-xl backdrop-blur-xl">
        <button wire:click="$set('tab', 'analytics')" @class([
            'magnetic px-5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all rounded-lg border shadow-sm font-mono',
            'bg-[#b5873a]/20 text-[#ffbe3b] border-[#ffbe3b] shadow-[0_0_15px_rgba(255,190,59,0.3)]' => $tab === 'analytics',
            'bg-[#0a0705] text-[#b5873a] border-[#b5873a]/30 hover:bg-[#1a110a] hover:border-[#ffbe3b]/50 hover:text-[#ffbe3b]' => $tab !== 'analytics'
        ])>
            Master Agenda
        </button>
        <button wire:click="$set('tab', 'clients')" @class([
            'magnetic px-5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all rounded-lg border shadow-sm font-mono',
            'bg-[#b5873a]/20 text-[#ffbe3b] border-[#ffbe3b] shadow-[0_0_15px_rgba(255,190,59,0.3)]' => $tab === 'clients',
            'bg-[#0a0705] text-[#b5873a] border-[#b5873a]/30 hover:bg-[#1a110a] hover:border-[#ffbe3b]/50 hover:text-[#ffbe3b]' => $tab !== 'clients'
        ])>
            Client Database
        </button>
        <button wire:click="$set('tab', 'availability')" @class([
            'magnetic px-5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all rounded-lg border shadow-sm font-mono',
            'bg-[#b5873a]/20 text-[#ffbe3b] border-[#ffbe3b] shadow-[0_0_15px_rgba(255,190,59,0.3)]' => $tab === 'availability',
            'bg-[#0a0705] text-[#b5873a] border-[#b5873a]/30 hover:bg-[#1a110a] hover:border-[#ffbe3b]/50 hover:text-[#ffbe3b]' => $tab !== 'availability'
        ])>
            Multi-Session Hours
        </button>
        <button wire:click="$set('tab', 'frontend')" @class([
            'magnetic px-5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all rounded-lg border shadow-sm font-mono',
            'bg-[#b5873a]/20 text-[#ffbe3b] border-[#ffbe3b] shadow-[0_0_15px_rgba(255,190,59,0.3)]' => $tab === 'frontend',
            'bg-[#0a0705] text-[#b5873a] border-[#b5873a]/30 hover:bg-[#1a110a] hover:border-[#ffbe3b]/50 hover:text-[#ffbe3b]' => $tab !== 'frontend'
        ])>
            Landing Page Copy
        </button>
        <button wire:click="$set('tab', 'notifications')" @class([
            'magnetic px-5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all rounded-lg border shadow-sm font-mono',
            'bg-[#b5873a]/20 text-[#ffbe3b] border-[#ffbe3b] shadow-[0_0_15px_rgba(255,190,59,0.3)]' => $tab === 'notifications',
            'bg-[#0a0705] text-[#b5873a] border-[#b5873a]/30 hover:bg-[#1a110a] hover:border-[#ffbe3b]/50 hover:text-[#ffbe3b]' => $tab !== 'notifications'
        ])>
            Notification Templates
        </button>
        <button wire:click="$set('tab', 'integrations')" @class([
            'magnetic px-5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all rounded-lg border shadow-sm font-mono',
            'bg-[#b5873a]/20 text-[#ffbe3b] border-[#ffbe3b] shadow-[0_0_15px_rgba(255,190,59,0.3)]' => $tab === 'integrations',
            'bg-[#0a0705] text-[#b5873a] border-[#b5873a]/30 hover:bg-[#1a110a] hover:border-[#ffbe3b]/50 hover:text-[#ffbe3b]' => $tab !== 'integrations'
        ])>
            Integrations
        </button>
    </div>

    <!-- Main Content Area -->
    <div class="relative z-10">
        <!-- Master Agenda Tab -->
        @if($tab === 'analytics')
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 animate-in fade-in duration-500">
            
            <div class="xl:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold font-playfair text-[#ffbe3b] drop-shadow-[0_0_10px_rgba(255,190,59,0.2)]">Chronological Ledger</h3>
                    <button wire:click="exportMarkdownReport" class="magnetic text-xs text-[#ffbe3b] bg-[#b5873a]/20 hover:bg-[#ffbe3b]/20 border border-[#ffbe3b]/50 px-4 py-2 font-bold uppercase tracking-widest rounded shadow-[0_0_10px_rgba(255,190,59,0.1)] transition-all flex items-center gap-2 font-mono">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Imprint .MD
                    </button>
                </div>
                
                <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl overflow-hidden shadow-[0_0_30px_rgba(0,0,0,0.8)] relative">
                    <div class="overflow-x-auto relative z-10">
                        <table class="w-full text-left border-collapse text-[#f5e6c8]">
                            <thead>
                                <tr class="bg-[#0a0705]/50 border-b border-[#b5873a]/30 text-[#ffbe3b] font-bold uppercase tracking-widest text-xs font-mono">
                                    <th class="p-4 border-r border-[#b5873a]/10">Patron</th>
                                    <th class="p-4 border-r border-[#b5873a]/10">Appointed Hour</th>
                                    <th class="p-4 border-r border-[#b5873a]/10 text-center">Seal of Status</th>
                                    <th class="p-4 text-right">Directives</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#b5873a]/10">
                                @forelse($this->bookings as $booking)
                                    <tr wire:click="selectBooking({{ $booking->id }})" class="cursor-pointer hover:bg-[#b5873a]/10 transition-colors {{ $selectedBooking && $selectedBooking->id === $booking->id ? 'bg-[#ffbe3b]/10 border-l-2 border-l-[#ffbe3b]' : 'border-l-2 border-l-transparent' }}">
                                        <td class="p-4 border-r border-[#b5873a]/10">
                                            <div class="font-bold font-playfair text-lg text-[#ffbe3b]">{{ $booking->guest_name }}</div>
                                            <div class="text-xs font-mono opacity-70">{{ $booking->guest_email }}</div>
                                        </td>
                                        <td class="p-4 border-r border-[#b5873a]/10 font-mono">
                                            <div class="font-bold text-[#f5e6c8]">{{ $booking->start_time->format('M j, Y') }}</div>
                                            <div class="text-xs opacity-50">{{ $booking->start_time->format('g:i A') }}</div>
                                        </td>
                                        <td class="p-4 border-r border-[#b5873a]/10 text-center">
                                            <span @class([
                                                'inline-flex items-center justify-center px-3 py-1 text-[10px] font-bold uppercase tracking-widest border rounded font-mono',
                                                'bg-[#064e3b]/30 text-[#34d399] border-[#34d399]/50 shadow-[0_0_10px_rgba(52,211,153,0.4)]' => $booking->status === 'confirmed',
                                                'bg-[#7f1d1d]/30 text-[#f87171] border-[#f87171]/50 shadow-[0_0_10px_rgba(248,113,113,0.4)] line-through decoration-[#f87171]' => $booking->status === 'canceled',
                                                'bg-[#78350f]/30 text-[#fbbf24] border-[#fbbf24]/50 shadow-[0_0_10px_rgba(251,191,36,0.4)]' => $booking->status === 'pending',
                                                'bg-[#1e3a8a]/30 text-[#60a5fa] border-[#60a5fa]/50 shadow-[0_0_10px_rgba(96,165,250,0.4)]' => $booking->status === 'completed',
                                            ]) style="transform: rotate({{ rand(-1, 1) }}deg);">
                                                {{ ucfirst($booking->status) }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <button wire:click.stop="selectBooking({{ $booking->id }})" class="magnetic text-[10px] font-mono text-[#ffbe3b] bg-[#0a0705] hover:bg-[#1a110a] uppercase tracking-widest font-bold px-3 py-1.5 border border-[#b5873a]/50 rounded transition-all shadow-[0_0_10px_rgba(255,190,59,0.1)] hover:border-[#ffbe3b]">
                                                Inspect
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-[#b5873a] font-mono italic">
                                            The ledger remains empty.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Selected Response Details -->
            <div class="space-y-4">
                <h3 class="text-2xl font-bold font-playfair text-[#ffbe3b] drop-shadow-[0_0_10px_rgba(255,190,59,0.2)]">Dossier</h3>
                
                @if($selectedBooking)
                    <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#ffbe3b]/50 rounded-xl p-6 space-y-6 shadow-[0_0_30px_rgba(255,190,59,0.1)] text-[#f5e6c8] relative animate-in fade-in duration-300">
                        
                        <div class="border-b border-[#b5873a]/30 pb-4 relative z-10 text-center">
                            <h4 class="text-2xl font-bold font-playfair text-[#ffbe3b]">{{ $selectedBooking->guest_name }}</h4>
                            <p class="text-sm font-mono mt-1 opacity-60">{{ $selectedBooking->guest_email }}</p>
                        </div>

                        <div class="space-y-4 text-sm font-mono relative z-10">
                            <div class="bg-[#0a0705]/50 border border-[#b5873a]/30 p-3 rounded">
                                <div class="text-[10px] text-[#ffbe3b] uppercase tracking-widest font-bold mb-1">Temporal Coordinates</div>
                                <div class="font-bold text-lg text-[#f5e6c8]">{{ $selectedBooking->start_time->format('l, F j, Y') }}</div>
                                <div class="text-xs mt-1 text-[#f5e6c8]/60">
                                    {{ $selectedBooking->start_time->format('g:i A') }} - {{ $selectedBooking->end_time->format('g:i A') }} ({{ $selectedBooking->guest_timezone }})
                                </div>
                            </div>

                            <div class="bg-[#0a0705]/50 border border-[#b5873a]/30 p-3 rounded">
                                <div class="text-[10px] text-[#ffbe3b] uppercase tracking-widest font-bold mb-1">Aetheric Frequency (Meet)</div>
                                <div class="mt-1">
                                    @if($selectedBooking->meet_link)
                                        <a href="{{ $selectedBooking->meet_link }}" target="_blank" class="text-sm text-[#b87333] hover:text-[#ffbe3b] break-all font-bold underline transition-colors">{{ $selectedBooking->meet_link }}</a>
                                    @else
                                        <span class="text-xs italic opacity-40">Frequency unestablished</span>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-[#0a0705]/50 border border-[#b5873a]/30 p-3 rounded">
                                <div class="text-[10px] text-[#ffbe3b] uppercase tracking-widest font-bold mb-1">Inscribed Missives</div>
                                <div class="mt-1.5 p-3 bg-[#1a110a] border border-[#b5873a]/20 rounded text-sm italic font-playfair leading-relaxed text-[#b5873a]">
                                    "{{ $selectedBooking->lead_data['notes'] ?? 'No extra notes provided.' }}"
                                </div>
                            </div>
                        </div>

                        <!-- AI Pre-Meeting Intelligence Dossier -->
                        <div class="pt-4 border-t border-dashed border-[#b5873a]/50 relative z-10">
                            <div class="bg-[#0a0705] border border-[#ffbe3b]/50 rounded p-4 flex flex-col gap-2 shadow-[0_0_15px_rgba(255,190,59,0.05)]">
                                <div class="flex items-center gap-2 text-[#ffbe3b] font-bold text-sm uppercase tracking-widest font-mono">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                    Automaton Analysis
                                </div>
                                <p class="text-xs text-[#b5873a] leading-relaxed font-mono">
                                    Patron affiliated with <strong class="text-[#ffbe3b]">{{ Str::after($selectedBooking->guest_email, '@') }}</strong>. 
                                    Mechanism suggests corporate enterprise. Engage specialized demonstration of industrial-grade automation protocols.
                                </p>
                            </div>
                        </div>

                        @if($selectedBooking->status === 'confirmed')
                            <div class="pt-4 border-t border-[#b5873a]/30 relative z-10">
                                <button wire:click="cancelBooking({{ $selectedBooking->id }})" wire:confirm="Are you certain you wish to sever this engagement?" class="w-full py-3 bg-[#7f1d1d]/30 hover:bg-[#7f1d1d]/50 text-[#f87171] font-bold uppercase tracking-widest font-mono rounded border border-[#f87171]/50 shadow-[0_0_15px_rgba(248,113,113,0.2)] transition-all text-xs magnetic">
                                    Sever Appointment
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="bg-[#1a110a]/50 border border-dashed border-[#b5873a]/30 rounded-xl p-6 text-center h-48 flex flex-col items-center justify-center text-[#b5873a] font-mono shadow-inner backdrop-blur-xl">
                        <svg class="w-8 h-8 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <span class="text-sm uppercase tracking-widest">Select an entry from the ledger</span>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Client Database Tab -->
        @if($tab === 'clients')
        <div class="animate-in fade-in duration-500 space-y-6" x-data="{ expandedClient: null }">
            <div>
                <h3 class="text-2xl font-bold font-playfair text-[#ffbe3b] drop-shadow-[0_0_10px_rgba(255,190,59,0.2)]">Patron Registry</h3>
                <p class="text-sm font-mono text-[#b5873a] mt-1">An organized index of all distinct entities documented in the ledger.</p>
            </div>

            @php
                $clients = $this->bookings->groupBy('guest_email');
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($clients as $email => $clientBookings)
                    @php
                        $latestBooking = $clientBookings->first();
                        $name = $latestBooking->guest_name;
                        $initials = collect(explode(' ', $name))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('');
                        $company = $latestBooking->lead_data['company'] ?? Str::after($email, '@');
                    @endphp
                    <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-5 shadow-[0_0_20px_rgba(0,0,0,0.6)] text-[#f5e6c8] relative hover:border-[#ffbe3b]/50 transition-colors cursor-pointer group" @click="expandedClient = expandedClient === '{{ $email }}' ? null : '{{ $email }}'">
                        <!-- Rivets -->
                        <div class="absolute top-2 left-2 w-1 h-1 rounded-full bg-[#ffbe3b]/50 shadow-[0_0_5px_#ffbe3b]"></div>
                        <div class="absolute top-2 right-2 w-1 h-1 rounded-full bg-[#ffbe3b]/50 shadow-[0_0_5px_#ffbe3b]"></div>
                        <div class="absolute bottom-2 left-2 w-1 h-1 rounded-full bg-[#ffbe3b]/50 shadow-[0_0_5px_#ffbe3b]"></div>
                        <div class="absolute bottom-2 right-2 w-1 h-1 rounded-full bg-[#ffbe3b]/50 shadow-[0_0_5px_#ffbe3b]"></div>

                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-[#0a0705] border border-[#ffbe3b]/50 flex items-center justify-center text-[#ffbe3b] font-bold font-playfair text-xl shadow-[0_0_10px_rgba(255,190,59,0.2)]">
                                {{ strtoupper($initials) }}
                            </div>
                            <div>
                                <h4 class="font-bold font-playfair text-lg leading-tight text-[#ffbe3b] group-hover:text-[#fff] transition-colors">{{ $name }}</h4>
                                <div class="text-[10px] font-mono uppercase tracking-widest text-[#b5873a]">{{ $company }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs font-mono bg-[#0a0705]/50 border border-[#b5873a]/30 p-2 rounded mb-2">
                            <div>
                                <div class="text-[#ffbe3b] uppercase text-[9px] font-bold">Engagements</div>
                                <div class="font-bold text-[#b5873a]">{{ $clientBookings->count() }} documented</div>
                            </div>
                            <div>
                                <div class="text-[#ffbe3b] uppercase text-[9px] font-bold">Last Noted</div>
                                <div class="font-bold text-[#b5873a]">{{ $latestBooking->start_time->format('M j, Y') }}</div>
                            </div>
                        </div>

                        <div x-show="expandedClient === '{{ $email }}'" x-collapse>
                            <div class="mt-4 pt-4 border-t border-dashed border-[#b5873a]/30">
                                <div class="bg-[#0a0705] border border-[#ffbe3b]/30 rounded p-3 text-[#f5e6c8] font-mono text-xs shadow-[0_0_10px_rgba(255,190,59,0.05)]">
                                    <div class="text-[#ffbe3b] font-bold uppercase tracking-widest mb-2 border-b border-[#b5873a]/30 pb-1 flex justify-between items-center">
                                        <span>Automaton Briefing</span>
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                    </div>
                                    <p class="leading-relaxed text-[#b5873a]">Patron of <span class="text-[#ffbe3b]">{{ $company }}</span>. Total contact history: {{ $clientBookings->count() }} interactions. Ensure machinery is calibrated for specialized discourse regarding their specific enterprise vectors.</p>
                                    
                                    <div class="mt-3 text-[10px] text-[#ffbe3b]/50">
                                        Email Designation: {{ $email }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Multi-Session Availability Tab -->
        @if($tab === 'availability')
        <div class="space-y-6 animate-in fade-in duration-500">
            <div class="flex items-center justify-between border-b border-[#b5873a]/30 pb-4">
                <div>
                    <h3 class="text-2xl font-bold font-playfair text-[#ffbe3b] drop-shadow-[0_0_10px_rgba(255,190,59,0.2)]">Temporal Operations Matrix</h3>
                    <p class="text-sm font-mono text-[#b5873a] mt-1">Calibrate the availability machinery for receiving patrons.</p>
                </div>
                <button wire:click="saveAvailability" class="magnetic text-sm text-[#0a0705] bg-[#ffbe3b] hover:bg-[#ffbe3b]/80 border border-[#ffbe3b] px-6 py-2.5 font-bold uppercase tracking-widest rounded shadow-[0_0_15px_rgba(255,190,59,0.4)] transition-all font-mono">
                    Engage Settings
                </button>
            </div>
            
            <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-6 space-y-6 shadow-[0_0_30px_rgba(0,0,0,0.8)] text-[#f5e6c8]">
                @foreach($availabilitySettings as $day => $config)
                    <div class="py-4 border-b border-dashed border-[#b5873a]/30 last:border-0 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" wire:model="availabilitySettings.{{ $day }}.is_available" id="check_{{ $day }}" class="w-5 h-5 rounded bg-[#0a0705] text-[#ffbe3b] focus:ring-[#ffbe3b] border border-[#ffbe3b]/50">
                                <label for="check_{{ $day }}" class="text-lg font-bold font-playfair text-[#ffbe3b]">{{ $day }}</label>
                            </div>
                            
                            @if($availabilitySettings[$day]['is_available'] ?? false)
                                <button wire:click="addSession('{{ $day }}')" class="magnetic text-xs text-[#b87333] hover:text-[#ffbe3b] font-bold uppercase tracking-widest font-mono flex items-center gap-1 transition-all border border-[#b87333]/50 hover:border-[#ffbe3b]/50 hover:shadow-[0_0_10px_rgba(255,190,59,0.2)] px-2 py-1 rounded">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Interval
                                </button>
                            @endif
                        </div>

                        @if($availabilitySettings[$day]['is_available'] ?? false)
                            <div class="space-y-3 pl-8 font-mono">
                                @foreach($availabilitySettings[$day]['sessions'] as $index => $session)
                                    <div class="flex items-center space-x-3 text-sm bg-[#0a0705]/50 p-2 rounded border border-[#b5873a]/30 inline-flex shadow-inner">
                                        <span class="text-xs text-[#ffbe3b] font-bold uppercase tracking-widest">Gear {{ $index + 1 }}:</span>
                                        <input type="text" wire:model="availabilitySettings.{{ $day }}.sessions.{{ $index }}.start_time" class="rounded border border-[#b5873a]/50 bg-[#1a110a] p-1.5 w-20 text-center text-xs font-bold text-[#f5e6c8] focus:ring-[#ffbe3b] focus:border-[#ffbe3b] outline-none" placeholder="09:00">
                                        <span class="text-[#ffbe3b] text-xs font-bold">&rarr;</span>
                                        <input type="text" wire:model="availabilitySettings.{{ $day }}.sessions.{{ $index }}.end_time" class="rounded border border-[#b5873a]/50 bg-[#1a110a] p-1.5 w-20 text-center text-xs font-bold text-[#f5e6c8] focus:ring-[#ffbe3b] focus:border-[#ffbe3b] outline-none" placeholder="12:00">
                                        
                                        @if(count($availabilitySettings[$day]['sessions']) > 1)
                                            <button wire:click="removeSession('{{ $day }}', {{ $index }})" class="text-[#f87171] hover:text-[#fca5a5] p-1 bg-[#7f1d1d]/30 border border-[#f87171]/50 hover:shadow-[0_0_10px_rgba(248,113,113,0.3)] rounded ml-2 transition-all">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="pl-8 text-xs text-[#b5873a] font-mono italic">Machinery powered down for this cycle.</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Landing Page Copy Tab -->
        @if($tab === 'frontend')
        <div class="space-y-6 animate-in fade-in duration-500">
            <div class="border-b border-[#b5873a]/30 pb-4">
                <h3 class="text-2xl font-bold font-playfair text-[#ffbe3b] drop-shadow-[0_0_10px_rgba(255,190,59,0.2)]">Public Facade Inscriptions</h3>
                <p class="text-sm font-mono text-[#b5873a] mt-1">Engrave the textual apparatus displayed to the public domain.</p>
            </div>

            <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-6 space-y-6 shadow-[0_0_30px_rgba(0,0,0,0.8)] text-[#f5e6c8]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 font-mono">
                    <flux:input wire:model="badge_text" label="Plaque Title" placeholder="Strategy Session Booking" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                    <flux:input wire:model="headline" label="Primary Engraving" placeholder="Automate your entire business." class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="subheadline" label="Supporting Inscription" placeholder="Select a convenient date and time..." rows="3" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                    </div>
                    <flux:input wire:model="benefit_1" label="Advantage I" placeholder="Free 30-Min Call" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                    <flux:input wire:model="benefit_2" label="Advantage II" placeholder="Custom Blueprint SVG" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                    <flux:input wire:model="benefit_3" label="Advantage III" placeholder="No Commitment" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                </div>

                <div class="pt-6 flex justify-end border-t border-dashed border-[#b5873a]/30">
                    <button wire:click="saveFrontEnd" class="magnetic text-sm text-[#0a0705] bg-[#ffbe3b] hover:bg-[#ffbe3b]/80 border border-[#ffbe3b] px-6 py-2.5 font-bold uppercase tracking-widest rounded shadow-[0_0_15px_rgba(255,190,59,0.4)] transition-all font-mono">
                        Commit Engravings
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Notification Templates Tab -->
        @if($tab === 'notifications')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in duration-500">
            <!-- Email -->
            <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-6 space-y-6 shadow-[0_0_30px_rgba(0,0,0,0.8)] text-[#f5e6c8] flex flex-col justify-between">
                <div class="space-y-4 font-mono">
                    <h3 class="text-xl font-bold font-playfair text-[#ffbe3b] border-b border-[#b5873a]/30 pb-2 drop-shadow-[0_0_5px_rgba(255,190,59,0.2)]">Electronic Mail Directives</h3>
                    <p class="text-xs text-[#b5873a] leading-relaxed">
                        Configure telegraphic dispatches. Available variable spools: <br>
                        <code class="text-[#ffbe3b] font-bold bg-[#0a0705] px-1 border border-[#b5873a]/30 rounded">{guest_name}</code>, 
                        <code class="text-[#ffbe3b] font-bold bg-[#0a0705] px-1 border border-[#b5873a]/30 rounded">{team_name}</code>, 
                        <code class="text-[#ffbe3b] font-bold bg-[#0a0705] px-1 border border-[#b5873a]/30 rounded">{start_time}</code>, 
                        <code class="text-[#ffbe3b] font-bold bg-[#0a0705] px-1 border border-[#b5873a]/30 rounded">{meet_link}</code>, 
                        <code class="text-[#ffbe3b] font-bold bg-[#0a0705] px-1 border border-[#b5873a]/30 rounded">{notes}</code>.
                    </p>
                    <div class="space-y-4 pt-2">
                        <flux:input wire:model="email_subject" label="Header Dispatch" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                        <flux:textarea wire:model="email_body" label="Parchment Contents" rows="8" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                    </div>
                </div>

                <div class="pt-6 flex justify-end border-t border-dashed border-[#b5873a]/30">
                    <button wire:click="saveNotifications" class="magnetic text-sm text-[#0a0705] bg-[#ffbe3b] hover:bg-[#ffbe3b]/80 border border-[#ffbe3b] px-6 py-2.5 font-bold uppercase tracking-widest rounded shadow-[0_0_15px_rgba(255,190,59,0.4)] transition-all font-mono">
                        Lock Templates
                    </button>
                </div>
            </div>

            <!-- WhatsApp -->
            <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-6 space-y-6 shadow-[0_0_30px_rgba(0,0,0,0.8)] text-[#f5e6c8] flex flex-col justify-between">
                <div class="space-y-4 font-mono">
                    <h3 class="text-xl font-bold font-playfair text-[#ffbe3b] border-b border-[#b5873a]/30 pb-2 drop-shadow-[0_0_5px_rgba(255,190,59,0.2)]">WhatsApp Telegraphy</h3>
                    <p class="text-xs text-[#b5873a]">Configure Meta Cloud API credentials to trigger automated mechanized alerts.</p>
                    
                    <div class="space-y-4 pt-2">
                        <flux:input wire:model="whatsapp_phone_number_id" label="Line Identifier" placeholder="e.g. 10982348912" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                        <flux:input wire:model="whatsapp_access_token" label="Cryptographic Token" type="password" placeholder="Meta Graph API Token" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                        <flux:input wire:model="whatsapp_template_name" label="Mold Designation" placeholder="e.g. booking_confirmation" class="bg-[#0a0705] border-[#b5873a]/50 text-[#f5e6c8] focus:border-[#ffbe3b] focus:ring-[#ffbe3b]" />
                    </div>
                </div>

                <div class="pt-6 flex justify-end border-t border-dashed border-[#b5873a]/30">
                    <button wire:click="saveNotifications" class="magnetic text-sm text-[#0a0705] bg-[#ffbe3b] hover:bg-[#ffbe3b]/80 border border-[#ffbe3b] px-6 py-2.5 font-bold uppercase tracking-widest rounded shadow-[0_0_15px_rgba(255,190,59,0.4)] transition-all font-mono">
                        Lock Configuration
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Integrations Tab -->
        @if($tab === 'integrations')
        <div class="bg-[#1a110a]/80 backdrop-blur-xl border border-[#b5873a]/30 rounded-xl p-6 space-y-6 max-w-xl shadow-[0_0_30px_rgba(0,0,0,0.8)] text-[#f5e6c8] animate-in fade-in duration-500 font-mono relative">
            <div class="absolute top-3 right-3 w-3 h-3 rounded-full border border-[#f87171]/50 bg-[#7f1d1d] shadow-[0_0_10px_rgba(248,113,113,0.5)]"></div>

            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-[#0a0705] border border-[#ffbe3b]/50 rounded flex items-center justify-center text-[#ffbe3b] shrink-0 shadow-[0_0_10px_rgba(255,190,59,0.2)]">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-6.887 4.114-4.68 0-8.486-3.83-8.486-8.5s3.805-8.5 8.486-8.5c2.316 0 4.28.8 5.786 2.214l3.197-3.197C18.665.86 15.725 0 12.24 0 5.58 0 0 5.373 0 12s5.58 12 12.24 12c6.96 0 11.57-4.89 11.57-11.79 0-.795-.085-1.4-.24-1.92H12.24z"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold font-playfair text-[#ffbe3b] drop-shadow-[0_0_5px_rgba(255,190,59,0.2)]">Google Calendar Machinery</h3>
                    <p class="text-xs text-[#b5873a] mt-1">Synchronize the temporal apparatus.</p>
                </div>
            </div>

            @if($this->googleSync)
                <div class="bg-[#064e3b]/30 border border-[#34d399]/50 p-4 rounded text-center shadow-[0_0_15px_rgba(52,211,153,0.1)]">
                    <div class="text-sm font-bold text-[#34d399] uppercase tracking-widest drop-shadow-[0_0_5px_rgba(52,211,153,0.3)]">
                        Circuit Established: <span class="underline text-[#f5e6c8] block mt-1">{{ $this->googleSync->email }}</span>
                    </div>
                </div>
            @else
                <div class="bg-[#0a0705]/50 border border-[#b5873a]/30 p-4 rounded text-xs leading-relaxed italic text-center text-[#b5873a]">
                    Establish the connection to allow autonomous synchronization and transmission of calendar invites.
                </div>
                <div class="text-center">
                    <a href="{{ route('google.connect') }}" class="magnetic inline-flex items-center px-6 py-3 text-sm font-bold uppercase tracking-widest text-[#0a0705] bg-[#ffbe3b] border border-[#ffbe3b] hover:bg-[#ffbe3b]/80 rounded shadow-[0_0_15px_rgba(255,190,59,0.4)] transition-all">
                        Couple Apparatus
                    </a>
                </div>
            @endif
        </div>
        @endif
    </div>

    <!-- GSAP Animations Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof gsap !== 'undefined') {
                
                // Magnetic Interaction
                const magneticItems = document.querySelectorAll('.magnetic');
                magneticItems.forEach(item => {
                    item.addEventListener('mousemove', (e) => {
                        const rect = item.getBoundingClientRect();
                        const x = e.clientX - rect.left - rect.width / 2;
                        const y = e.clientY - rect.top - rect.height / 2;
                        
                        gsap.to(item, {
                            x: x * 0.2,
                            y: y * 0.2,
                            duration: 0.3,
                            ease: 'power2.out'
                        });
                    });
                    
                    item.addEventListener('mouseleave', () => {
                        gsap.to(item, {
                            x: 0,
                            y: 0,
                            duration: 0.5,
                            ease: 'elastic.out(1, 0.3)'
                        });
                    });
                });

                // Intro animations
                gsap.from('.ledger-card', { 
                    y: 30, 
                    opacity: 0, 
                    stagger: 0.1, 
                    duration: 1,
                    ease: "back.out(1.2)"
                });
                
                // Animate gauge needles
                document.querySelectorAll('.gauge-needle').forEach((needle, i) => {
                    const angle = needle.dataset.angle || 0;
                    gsap.to(needle, { 
                        rotation: angle, 
                        duration: 2.5, 
                        delay: 0.5 + (i * 0.2), 
                        ease: 'elastic.out(1, 0.4)',
                        transformOrigin: 'bottom center'
                    });
                });
            }
        });
    </script>
</div>
</div>
