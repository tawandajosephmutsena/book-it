<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\GoogleIntegration;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

new class extends Component
{
    public Team $team;
    public $tab = 'bookings'; // bookings, availability, settings, analytics

    // Selected booking for detailed inspection drawer
    public $selectedBookingId = null;

    // Edit Modal State
    public $isEditing = false;
    public $editForm = [
        'id' => null,
        'guest_name' => '',
        'guest_email' => '',
        'start_time' => '',
        'status' => 'confirmed',
        'phone' => '',
        'company' => '',
        'industry' => '',
        'project_brief' => '',
        'notes' => '',
    ];

    // Search, Filter, Sort, Bulk Selection
    public $search = '';
    public $statusFilter = 'all'; // all, confirmed, completed, canceled
    public $sortBy = 'start_time';
    public $sortDirection = 'desc';
    public $selectedIds = [];
    public $selectAll = false;

    // Multi-session availability settings per day of week
    public $availabilitySettings = [];

    // Customizable landing page text
    public $badge_text = 'Strategy Session Booking';
    public $headline = 'Automate your entire business.';
    public $subheadline = 'Select a convenient date and time on the calendar below to map out a custom automation blueprint for your business with the Ottomate team.';
    public $benefit_1 = 'Free 30-Min Strategy Call';
    public $benefit_2 = 'Custom Architecture Blueprint';
    public $benefit_3 = 'Zero Commitment Required';

    // Customizable notifications
    public $whatsapp_access_token = '';
    public $whatsapp_phone_number_id = '';
    public $whatsapp_template_name = 'booking_confirmation';
    public $email_subject = 'Booking Confirmed: {team_name} Session';
    public $email_body = "Hi {guest_name},\n\nYour meeting with {team_name} is confirmed for {start_time}.\n\nGoogle Meet: {meet_link}\n\nWe look forward to meeting you!";

    public function mount()
    {
        $this->team = auth()->user()->currentTeam ?? auth()->user()->personalTeam();
        $this->loadAvailability();
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $frontFile = storage_path('app/landing_page_settings.json');
        if (file_exists($frontFile)) {
            $front = json_decode(file_get_contents($frontFile), true);
            $this->badge_text = $front['badge_text'] ?? $this->badge_text;
            $this->headline = $front['headline'] ?? $this->headline;
            $this->subheadline = $front['subheadline'] ?? $this->subheadline;
            $this->benefit_1 = $front['benefit_1'] ?? $this->benefit_1;
            $this->benefit_2 = $front['benefit_2'] ?? $this->benefit_2;
            $this->benefit_3 = $front['benefit_3'] ?? $this->benefit_3;
        }

        $notifFile = storage_path('app/notification_settings.json');
        if (file_exists($notifFile)) {
            $notif = json_decode(file_get_contents($notifFile), true);
            $this->whatsapp_access_token = $notif['whatsapp_access_token'] ?? '';
            $this->whatsapp_phone_number_id = $notif['whatsapp_phone_number_id'] ?? '';
            $this->whatsapp_template_name = $notif['whatsapp_template_name'] ?? 'booking_confirmation';
            $this->email_subject = $notif['email_subject'] ?? $this->email_subject;
            $this->email_body = $notif['email_body'] ?? $this->email_body;
        }
    }

    public function loadAvailability()
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $existing = Availability::where('team_id', $this->team->id)->where('type', 'recurring')->get();

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
                    'sessions' => $sessions,
                ];
            } else {
                $isWeekend = in_array($day, ['Saturday', 'Sunday']);
                $this->availabilitySettings[$day] = [
                    'is_available' => !$isWeekend,
                    'sessions' => $isWeekend ? [] : [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                ];
            }
        }
    }

    public function toggleDay($day)
    {
        $this->availabilitySettings[$day]['is_available'] = !$this->availabilitySettings[$day]['is_available'];
        if ($this->availabilitySettings[$day]['is_available'] && empty($this->availabilitySettings[$day]['sessions'])) {
            $this->availabilitySettings[$day]['sessions'][] = ['start_time' => '09:00', 'end_time' => '17:00'];
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

        $this->dispatch('toast', message: 'Availability schedule updated successfully.', variant: 'success');
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
        $this->dispatch('toast', message: 'Landing page content updated.', variant: 'success');
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

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->bookings->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function setSort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function selectBooking($id)
    {
        $this->selectedBookingId = $id;
    }

    public function closeDrawer()
    {
        $this->selectedBookingId = null;
    }

    public function startEditing($id)
    {
        $booking = Booking::where('team_id', $this->team->id)->findOrFail($id);
        $this->editForm = [
            'id' => $booking->id,
            'guest_name' => $booking->guest_name,
            'guest_email' => $booking->guest_email,
            'start_time' => $booking->start_time ? $booking->start_time->format('Y-m-d\TH:i') : '',
            'status' => $booking->status,
            'phone' => $booking->phone ?? '',
            'company' => $booking->company ?? '',
            'industry' => $booking->industry ?? '',
            'project_brief' => $booking->project_brief ?? '',
            'notes' => $booking->notes ?? '',
        ];
        $this->isEditing = true;
    }

    public function cancelEdit()
    {
        $this->isEditing = false;
        $this->editForm = [];
    }

    public function saveEdit()
    {
        $this->validate([
            'editForm.guest_name' => 'required|string|max:255',
            'editForm.guest_email' => 'required|email|max:255',
            'editForm.start_time' => 'required|date',
            'editForm.status' => 'required|string|in:confirmed,completed,canceled,pending',
        ]);

        $booking = Booking::where('team_id', $this->team->id)->findOrFail($this->editForm['id']);

        $leadData = $booking->lead_data ?? [];
        $leadData['phone'] = $this->editForm['phone'];
        $leadData['company'] = $this->editForm['company'];
        $leadData['industry'] = $this->editForm['industry'];
        $leadData['project_brief'] = $this->editForm['project_brief'];
        $leadData['notes'] = $this->editForm['notes'];

        $booking->update([
            'guest_name' => $this->editForm['guest_name'],
            'guest_email' => $this->editForm['guest_email'],
            'start_time' => Carbon::parse($this->editForm['start_time']),
            'status' => $this->editForm['status'],
            'lead_data' => $leadData,
        ]);

        $this->isEditing = false;
        $this->dispatch('toast', message: 'Appointment details updated.', variant: 'success');
    }

    public function updateStatus($id, $newStatus)
    {
        $booking = Booking::where('team_id', $this->team->id)->findOrFail($id);
        $booking->update(['status' => $newStatus]);
        $this->dispatch('toast', message: "Appointment marked as {$newStatus}.", variant: 'success');
    }

    public function deleteBooking($id)
    {
        $booking = Booking::where('team_id', $this->team->id)->findOrFail($id);
        $booking->delete();

        if ($this->selectedBookingId == $id) {
            $this->selectedBookingId = null;
        }

        $this->dispatch('toast', message: 'Appointment deleted.', variant: 'success');
    }

    public function deleteSelected()
    {
        if (!empty($this->selectedIds)) {
            Booking::where('team_id', $this->team->id)->whereIn('id', $this->selectedIds)->delete();
            $this->selectedIds = [];
            $this->selectAll = false;
            $this->dispatch('toast', message: 'Selected appointments deleted.', variant: 'success');
        }
    }

    public function completeSelected()
    {
        if (!empty($this->selectedIds)) {
            Booking::where('team_id', $this->team->id)->whereIn('id', $this->selectedIds)->update(['status' => 'completed']);
            $this->selectedIds = [];
            $this->selectAll = false;
            $this->dispatch('toast', message: 'Selected appointments marked as completed.', variant: 'success');
        }
    }

    public function exportCsv()
    {
        $bookings = $this->bookings;
        $csvHeader = ['ID', 'Guest Name', 'Email', 'Phone', 'Company', 'Industry', 'Scheduled Time (UTC)', 'Status', 'Google Meet Link', 'Notes', 'Created At'];

        $callback = function () use ($bookings, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);

            foreach ($bookings as $b) {
                fputcsv($file, [
                    $b->id,
                    $b->guest_name,
                    $b->guest_email,
                    $b->phone ?? 'N/A',
                    $b->company ?? 'N/A',
                    $b->industry ?? 'N/A',
                    $b->start_time ? $b->start_time->format('Y-m-d H:i:s') : 'N/A',
                    $b->status,
                    $b->meet_link ?? 'N/A',
                    $b->notes ?? '',
                    $b->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'appointments-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportMarkdownReport()
    {
        $bookings = $this->bookings;
        $total = $bookings->count();
        $confirmed = $bookings->where('status', 'confirmed')->count();
        $completed = $bookings->where('status', 'completed')->count();
        $canceled = $bookings->where('status', 'canceled')->count();

        $md = "# Appointments & Lead Report\n**Team:** {$this->team->name}\n**Generated:** " . now()->format('Y-m-d H:i') . "\n\n";
        $md .= "## 📊 Key Metrics\n- **Total Records:** {$total}\n- **Confirmed:** {$confirmed}\n- **Completed:** {$completed}\n- **Canceled:** {$canceled}\n\n";
        $md .= "## 📅 Appointment Log\n| ID | Guest Name | Email | Company | Scheduled Time | Status | Google Meet |\n|---|---|---|---|---|---|---|\n";

        foreach ($bookings as $b) {
            $timeStr = $b->start_time ? $b->start_time->format('Y-m-d H:i') : 'N/A';
            $company = $b->company ?? '-';
            $meet = $b->meet_link ? "[Meet]({$b->meet_link})" : '-';
            $md .= "| {$b->id} | {$b->guest_name} | {$b->guest_email} | {$company} | {$timeStr} | {$b->status} | {$meet} |\n";
        }

        return response()->streamDownload(function () use ($md) {
            echo $md;
        }, 'appointments-report-' . now()->format('Y-m-d') . '.md', [
            'Content-Type' => 'text/markdown',
        ]);
    }

    public function getBookingsProperty()
    {
        $query = Booking::where('team_id', $this->team->id);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('guest_name', 'like', '%' . $this->search . '%')
                  ->orWhere('guest_email', 'like', '%' . $this->search . '%')
                  ->orWhere('lead_data', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)->get();
    }

    public function getSelectedBookingProperty()
    {
        if (!$this->selectedBookingId) return null;
        return Booking::where('team_id', $this->team->id)->find($this->selectedBookingId);
    }

    public function getGoogleSyncProperty()
    {
        return GoogleIntegration::where('user_id', auth()->id())->first();
    }

    public function disconnectGoogle()
    {
        GoogleIntegration::where('user_id', auth()->id())->delete();
        $this->dispatch('toast', message: 'Google Calendar disconnected.', variant: 'warning');
    }

    #[Livewire\Attributes\On('executeCommand')]
    public function executeCommand($data = [])
    {
        // Safe command handler
    }
};
?>

<div class="space-y-6">

    <!-- Dashboard Title & Overview -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-zinc-200 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Appointments & Operations</h1>
            <p class="text-xs text-zinc-500 mt-0.5">Manage bookings, availability, and settings for {{ $team->name }}.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/" target="_blank" class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition flex items-center gap-1.5">
                <span>Public Page ↗</span>
            </a>
        </div>
    </div>

    <!-- Top Executive KPIs Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
        
        <!-- Total Bookings Card -->
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Bookings</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->bookings->count() }}</span>
                <span class="text-xs text-zinc-400">Clients</span>
            </div>
        </div>

        <!-- Confirmed Bookings -->
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider">Upcoming</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->bookings->where('status', 'confirmed')->count() }}
                </span>
                <span class="text-xs text-amber-500 font-medium">Confirmed</span>
            </div>
        </div>

        <!-- Completed Sessions -->
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Completed</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->bookings->where('status', 'completed')->count() }}
                </span>
                <span class="text-xs text-emerald-500 font-medium">Fulfilled</span>
            </div>
        </div>

        <!-- Canceled Sessions -->
        <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <p class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider">Canceled</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ $this->bookings->where('status', 'canceled')->count() }}
                </span>
                <span class="text-xs text-zinc-400">Rate: {{ $this->bookings->count() > 0 ? round(($this->bookings->where('status', 'canceled')->count() / $this->bookings->count()) * 100) : 0 }}%</span>
            </div>
        </div>

        <!-- Google Calendar Sync Status -->
        <div class="col-span-2 sm:col-span-4 lg:col-span-1 p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Integrations & Sync</p>
            <div class="mt-2 flex items-center justify-between">
                @if($this->googleSync)
                    <div class="flex items-center gap-1.5 text-xs text-emerald-500 font-semibold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Connected</span>
                    </div>
                @else
                    <a href="{{ route('google.connect') }}" class="text-xs text-amber-500 hover:text-amber-400 font-semibold flex items-center gap-1">
                        <span>Connect Google ↗</span>
                    </a>
                @endif
                <span class="text-[10px] text-zinc-400">Google Meet</span>
            </div>
        </div>

    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="$set('tab', 'bookings')"
                @class([
                    'px-4 py-2 rounded-lg text-xs font-semibold transition-all flex items-center gap-2',
                    'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 shadow-sm' => $tab === 'bookings',
                    'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800' => $tab !== 'bookings',
                ])
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Appointments</span>
            </button>

            <button
                type="button"
                wire:click="$set('tab', 'availability')"
                @class([
                    'px-4 py-2 rounded-lg text-xs font-semibold transition-all flex items-center gap-2',
                    'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 shadow-sm' => $tab === 'availability',
                    'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800' => $tab !== 'availability',
                ])
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Availability</span>
            </button>

            <button
                type="button"
                wire:click="$set('tab', 'settings')"
                @class([
                    'px-4 py-2 rounded-lg text-xs font-semibold transition-all flex items-center gap-2',
                    'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 shadow-sm' => $tab === 'settings',
                    'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800' => $tab !== 'settings',
                ])
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Integrations & Notification Templates</span>
            </button>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="exportCsv"
                class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </button>
            <button
                type="button"
                wire:click="exportMarkdownReport"
                class="px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Report (.md)</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: APPOINTMENTS LEDGER -->
    @if($tab === 'bookings')
        <div class="space-y-4">
            
            <!-- Filter & Search Controls Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-zinc-900 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, email, company..."
                        class="w-full pl-9 pr-4 py-2 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-lg text-xs text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-amber-500"
                    />
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto">
                    @foreach(['all' => 'All', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'canceled' => 'Canceled'] as $sKey => $sLabel)
                        <button
                            type="button"
                            wire:click="$set('statusFilter', '{{ $sKey }}')"
                            @class([
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
                                'bg-amber-500 text-zinc-950 shadow-sm' => $statusFilter === $sKey,
                                'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' => $statusFilter !== $sKey,
                            ])
                        >
                            {{ $sLabel }}
                        </button>
                    @endforeach
                </div>

            </div>

            <!-- Bulk Action Bar -->
            @if(count($selectedIds) > 0)
                <div class="flex items-center justify-between p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-900 dark:text-amber-200">
                    <span class="font-semibold">{{ count($selectedIds) }} appointments selected</span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="completeSelected"
                            class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-500 transition"
                        >
                            Mark Completed
                        </button>
                        <button
                            type="button"
                            wire:click="deleteSelected"
                            wire:confirm="Are you sure you want to delete the selected appointments?"
                            class="px-3 py-1.5 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-500 transition"
                        >
                            Delete Selected
                        </button>
                    </div>
                </div>
            @endif

            <!-- Main Data Table -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-zinc-700 dark:text-zinc-300">
                        <thead class="bg-zinc-50 dark:bg-zinc-950/70 text-zinc-500 uppercase tracking-wider font-semibold border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="p-3.5 w-10 text-center">
                                    <input type="checkbox" wire:model.live="selectAll" class="rounded border-zinc-300 text-amber-500 focus:ring-amber-500" />
                                </th>
                                <th class="p-3.5 cursor-pointer select-none" wire:click="setSort('start_time')">
                                    <div class="flex items-center gap-1">
                                        <span>Scheduled Time</span>
                                        @if($sortBy === 'start_time') <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                                    </div>
                                </th>
                                <th class="p-3.5 cursor-pointer select-none" wire:click="setSort('guest_name')">
                                    <div class="flex items-center gap-1">
                                        <span>Guest</span>
                                        @if($sortBy === 'guest_name') <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                                    </div>
                                </th>
                                <th class="p-3.5">Company & Brief</th>
                                <th class="p-3.5">Google Meet</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($this->bookings as $booking)
                                <tr @class([
                                    'hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition',
                                    'bg-amber-50/40 dark:bg-amber-950/10' => $booking->isNew(),
                                ])>
                                    <td class="p-3.5 text-center">
                                        <input type="checkbox" wire:model.live="selectedIds" value="{{ $booking->id }}" class="rounded border-zinc-300 text-amber-500 focus:ring-amber-500" />
                                    </td>
                                    
                                    <!-- Scheduled Time -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $booking->start_time ? $booking->start_time->format('M j, Y') : 'N/A' }}
                                        </div>
                                        <div class="text-[11px] text-zinc-500">
                                            {{ $booking->start_time ? $booking->start_time->format('g:i A') : 'N/A' }} ({{ $booking->guest_timezone }})
                                        </div>
                                    </td>

                                    <!-- Guest Details -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <button type="button" wire:click="selectBooking({{ $booking->id }})" class="font-semibold text-zinc-900 dark:text-white hover:text-amber-500 text-left">
                                                {{ $booking->guest_name }}
                                            </button>
                                            @if($booking->isNew())
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-500 border border-amber-500/30">NEW</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-zinc-500">{{ $booking->guest_email }}</div>
                                    </td>

                                    <!-- Company & Brief -->
                                    <td class="p-3.5 max-w-xs truncate">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $booking->company ?: '-' }}</div>
                                        <div class="text-[11px] text-zinc-500 truncate">{{ $booking->project_brief ?: 'No brief provided' }}</div>
                                    </td>

                                    <!-- Google Meet Link -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        @if(!empty($booking->meet_link))
                                            <a href="{{ $booking->meet_link }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-medium hover:bg-emerald-500/20 transition">
                                                <span>Join Meet</span>
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                        @else
                                            @if($booking->status === 'confirmed' && $this->googleSync)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-500 text-[11px] font-medium" title="Calendar sync failed — check logs or reconnect Google Calendar">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    Sync Failed
                                                </span>
                                            @else
                                                <span class="text-zinc-400">-</span>
                                            @endif
                                        @endif
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        @if($booking->status === 'confirmed')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Confirmed
                                            </span>
                                        @elseif($booking->status === 'completed')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Completed
                                            </span>
                                        @elseif($booking->status === 'canceled')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Canceled
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                                {{ ucfirst($booking->status) }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Quick Actions -->
                                    <td class="p-3.5 whitespace-nowrap text-right space-x-1">
                                        <button
                                            type="button"
                                            wire:click="selectBooking({{ $booking->id }})"
                                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="startEditing({{ $booking->id }})"
                                            class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition"
                                            title="Edit Appointment"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        @if($booking->status === 'confirmed')
                                            <button
                                                type="button"
                                                wire:click="updateStatus({{ $booking->id }}, 'completed')"
                                                class="p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-950/30 text-emerald-600 transition"
                                                title="Mark as Completed"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="deleteBooking({{ $booking->id }})"
                                            wire:confirm="Are you sure you want to delete this booking?"
                                            class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-950/30 text-red-500 transition"
                                            title="Delete Booking"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-zinc-500">
                                        No appointments found matching your search criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endif

    <!-- TAB 2: WEEKLY SCHEDULE (AVAILABILITY) -->
    @if($tab === 'availability')
        <div class="space-y-4">
            <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Recurring Weekly Schedule</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Configure the time windows during which guests can schedule appointments with your team.</p>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-800 border-y border-zinc-200 dark:border-zinc-800">
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                        <div class="py-4 flex flex-col md:flex-row md:items-start justify-between gap-4">
                            
                            <!-- Day Toggle -->
                            <div class="w-40 flex items-center gap-3">
                                <button
                                    type="button"
                                    wire:click="toggleDay('{{ $day }}')"
                                    @class([
                                        'w-10 h-5 rounded-full transition-colors relative cursor-pointer',
                                        'bg-amber-500' => $availabilitySettings[$day]['is_available'] ?? false,
                                        'bg-zinc-300 dark:bg-zinc-700' => !($availabilitySettings[$day]['is_available'] ?? false),
                                    ])
                                >
                                    <span @class([
                                        'w-4 h-4 rounded-full bg-white transition-transform absolute top-0.5',
                                        'left-5' => $availabilitySettings[$day]['is_available'] ?? false,
                                        'left-1' => !($availabilitySettings[$day]['is_available'] ?? false),
                                    ])></span>
                                </button>
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $day }}</span>
                            </div>

                            <!-- Time Slots Editor -->
                            <div class="flex-1 space-y-2">
                                @if($availabilitySettings[$day]['is_available'] ?? false)
                                    @foreach($availabilitySettings[$day]['sessions'] as $sIdx => $session)
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="time"
                                                wire:model="availabilitySettings.{{ $day }}.sessions.{{ $sIdx }}.start_time"
                                                class="px-2.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-zinc-100"
                                            />
                                            <span class="text-xs text-zinc-400">to</span>
                                            <input
                                                type="time"
                                                wire:model="availabilitySettings.{{ $day }}.sessions.{{ $sIdx }}.end_time"
                                                class="px-2.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-zinc-100"
                                            />
                                            <button
                                                type="button"
                                                wire:click="removeSession('{{ $day }}', {{ $sIdx }})"
                                                class="p-1 text-red-400 hover:text-red-500"
                                                title="Remove Slot"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    @endforeach

                                    <button
                                        type="button"
                                        wire:click="addSession('{{ $day }}')"
                                        class="text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold flex items-center gap-1 pt-1"
                                    >
                                        + Add Time Window
                                    </button>
                                @else
                                    <span class="text-xs text-zinc-400 italic">Unavailable / Closed</span>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>

                <div class="pt-3 flex justify-end">
                    <button
                        type="button"
                        wire:click="saveAvailability"
                        class="px-5 py-2.5 rounded-lg bg-amber-500 text-zinc-950 font-bold text-xs hover:bg-amber-400 transition shadow-sm"
                    >
                        Save Availability Schedule
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 3: SETTINGS & COPY -->
    @if($tab === 'settings')
        <div class="space-y-6">
            
            <!-- Google Calendar Connection Card -->
            <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-500 shrink-0">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zm-7 5h5v5h-5v-5z"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Google Calendar & Meet Video</h3>
                                @if($this->googleSync)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">CONNECTED</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-500 border border-zinc-200 dark:border-zinc-700">NOT CONNECTED</span>
                                @endif
                            </div>
                            <p class="text-xs text-zinc-500 mt-1">
                                Automatically push bookings to your primary Google Calendar and generate native Google Meet video links for every scheduled appointment.
                            </p>
                            @if($this->googleSync)
                                <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 mt-2">
                                    Connected as: <span class="text-amber-500">{{ $this->googleSync->email }}</span>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($this->googleSync)
                            <a href="{{ route('google.connect') }}" class="px-3.5 py-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-semibold text-xs transition">
                                Reconnect
                            </a>
                            <button
                                type="button"
                                wire:click="disconnectGoogle"
                                wire:confirm="Are you sure you want to disconnect your Google Calendar?"
                                class="px-3.5 py-2 rounded-lg bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 font-semibold text-xs transition"
                            >
                                Disconnect
                            </button>
                        @else
                            <a href="{{ route('google.connect') }}" class="px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zm-7 5h5v5h-5v-5z"/></svg>
                                <span>Connect Google Calendar</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
            
            <!-- Landing Page Copy Settings -->
            <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Landing Page Text</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Customize the hero headline, badge, and value proposition cards on your public page.</p>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Badge Text</label>
                        <input type="text" wire:model="badge_text" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Main Headline</label>
                        <input type="text" wire:model="headline" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Subheadline</label>
                        <textarea wire:model="subheadline" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">Perk 1</label>
                            <input type="text" wire:model="benefit_1" class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">Perk 2</label>
                            <input type="text" wire:model="benefit_2" class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">Perk 3</label>
                            <input type="text" wire:model="benefit_3" class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="button" wire:click="saveFrontEnd" class="px-4 py-2 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-bold text-xs hover:opacity-90 transition">
                        Save Landing Copy
                    </button>
                </div>
            </div>

            <!-- Notifications & WhatsApp Settings -->
            <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Email & WhatsApp Templates</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Automate post-booking confirmations. Supported tags: <code class="text-amber-500 font-mono">{guest_name}</code>, <code class="text-amber-500 font-mono">{team_name}</code>, <code class="text-amber-500 font-mono">{start_time}</code>, <code class="text-amber-500 font-mono">{meet_link}</code>.</p>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email Subject</label>
                        <input type="text" wire:model="email_subject" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email Message Body</label>
                        <textarea wire:model="email_body" rows="4" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white resize-none font-mono"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">WhatsApp Phone ID</label>
                            <input type="text" wire:model="whatsapp_phone_number_id" placeholder="Meta Phone Number ID" class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">Meta Access Token</label>
                            <input type="password" wire:model="whatsapp_access_token" placeholder="Bearer EAAG..." class="w-full px-2.5 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-900 dark:text-white" />
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="button" wire:click="saveNotifications" class="px-4 py-2 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-bold text-xs hover:opacity-90 transition">
                        Save Notification Templates
                    </button>
                </div>
            </div>

            </div>
        </div>
    @endif

    <!-- LEAD INSPECTION DRAWER / SLIDE-OVER -->
    @if($this->selectedBooking)
        <div class="fixed inset-0 z-50 overflow-hidden bg-black/50 backdrop-blur-xs flex justify-end">
            <div class="w-full max-w-lg bg-white dark:bg-zinc-900 h-full p-6 shadow-2xl overflow-y-auto space-y-6 border-l border-zinc-200 dark:border-zinc-800">
                
                <!-- Drawer Header -->
                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Lead Dossier</h3>
                        <p class="text-xs text-zinc-500">ID: #{{ $this->selectedBooking->id }} · {{ $this->selectedBooking->created_at->diffForHumans() }}</p>
                    </div>
                    <button type="button" wire:click="closeDrawer" class="p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 hover:text-zinc-600 dark:hover:text-white">
                        ✕
                    </button>
                </div>

                <!-- Contact & Company Card -->
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-3 text-xs">
                    <div>
                        <span class="text-zinc-400 uppercase tracking-wider text-[10px] font-bold">Guest Contact</span>
                        <p class="text-base font-bold text-zinc-900 dark:text-white mt-0.5">{{ $this->selectedBooking->guest_name }}</p>
                        <p class="text-zinc-500">{{ $this->selectedBooking->guest_email }}</p>
                        @if($this->selectedBooking->phone)
                            <p class="text-zinc-500">{{ $this->selectedBooking->phone }}</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                        <div>
                            <span class="text-zinc-400 text-[10px] uppercase font-bold">Company</span>
                            <p class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $this->selectedBooking->company ?: 'Not Specified' }}</p>
                        </div>
                        <div>
                            <span class="text-zinc-400 text-[10px] uppercase font-bold">Industry</span>
                            <p class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $this->selectedBooking->industry ?: 'Not Specified' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Schedule & Video Link -->
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-2 text-xs">
                    <span class="text-zinc-400 uppercase tracking-wider text-[10px] font-bold">Appointment Time</span>
                    <p class="text-sm font-bold text-amber-600 dark:text-amber-400">
                        {{ $this->selectedBooking->start_time ? $this->selectedBooking->start_time->format('l, F j, Y — g:i A') : 'N/A' }} ({{ $this->selectedBooking->guest_timezone }})
                    </p>

                    @if(!empty($this->selectedBooking->meet_link))
                        <div class="pt-2">
                            <a href="{{ $this->selectedBooking->meet_link }}" target="_blank" class="w-full py-2 px-3 rounded-lg bg-emerald-600 text-white font-semibold text-center block text-xs hover:bg-emerald-500 transition">
                                Launch Google Meet Video ↗
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Project Brief -->
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-2 text-xs">
                    <span class="text-zinc-400 uppercase tracking-wider text-[10px] font-bold">Project Brief & Bottlenecks</span>
                    <p class="text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap leading-relaxed">
                        {{ $this->selectedBooking->project_brief ?: 'No brief submitted.' }}
                    </p>
                </div>

                <!-- Internal Notes -->
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-2 text-xs">
                    <span class="text-zinc-400 uppercase tracking-wider text-[10px] font-bold">Internal Team Notes</span>
                    <p class="text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap leading-relaxed">
                        {{ $this->selectedBooking->notes ?: 'No internal notes added.' }}
                    </p>
                </div>

                <!-- Drawer Actions -->
                <div class="pt-4 flex items-center justify-between border-t border-zinc-200 dark:border-zinc-800">
                    <button
                        type="button"
                        wire:click="startEditing({{ $this->selectedBooking->id }})"
                        class="px-4 py-2 rounded-lg bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 font-semibold text-xs hover:opacity-90 transition"
                    >
                        Edit Details
                    </button>

                    <button
                        type="button"
                        wire:click="deleteBooking({{ $this->selectedBooking->id }})"
                        wire:confirm="Are you sure you want to delete this booking?"
                        class="px-4 py-2 rounded-lg bg-red-600/10 text-red-600 font-semibold text-xs hover:bg-red-600 hover:text-white transition"
                    >
                        Delete
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- EDIT APPOINTMENT MODAL -->
    @if($isEditing)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-white dark:bg-zinc-900 rounded-2xl p-6 shadow-2xl border border-zinc-200 dark:border-zinc-800 space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Edit Appointment #{{ $editForm['id'] }}</h3>
                    <button type="button" wire:click="cancelEdit" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-white">✕</button>
                </div>

                <form wire:submit="saveEdit" class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Guest Name</label>
                            <input type="text" wire:model="editForm.guest_name" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Guest Email</label>
                            <input type="email" wire:model="editForm.guest_email" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date & Time</label>
                            <input type="datetime-local" wire:model="editForm.start_time" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                            <select wire:model="editForm.status" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white">
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Phone</label>
                            <input type="text" wire:model="editForm.phone" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Company</label>
                            <input type="text" wire:model="editForm.company" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Internal Notes</label>
                        <textarea wire:model="editForm.notes" rows="3" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                        <button type="button" wire:click="cancelEdit" class="px-4 py-2 rounded-lg text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-500 text-zinc-950 font-bold hover:bg-amber-400 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
