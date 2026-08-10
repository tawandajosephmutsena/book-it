<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Team;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public $step = 1;

    public $date;

    public $time;

    public $guest_name;

    public $guest_email;

    public $guest_phone = '';

    public $guest_company = '';

    public $guest_industry = '';

    public $project_brief = '';

    public $guest_timezone;

    public $lead_data = [];

    public $calendarLinks = [];

    // Calendar state
    public $currentYear;

    public $currentMonth;

    public $calendarDays = [];

    public $availableTimes = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

    public function mount(Team $team)
    {
        $this->team = $team;
        $this->currentYear = Carbon::now()->year;
        $this->currentMonth = Carbon::now()->month;
        $this->guest_timezone = 'UTC';
        $this->calculateCalendarDays();
    }

    public function calculateCalendarDays()
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $startOfMonth->daysInMonth;
        $startDayOfWeek = $startOfMonth->dayOfWeek;

        $days = [];

        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($this->currentYear, $this->currentMonth, $day);
            $days[] = [
                'day' => $day,
                'dateStr' => $date->format('Y-m-d'),
                'isPast' => $date->isPast() && ! $date->isToday(),
                'isWeekend' => $date->isWeekend(),
                'isToday' => $date->isToday(),
            ];
        }

        $this->calendarDays = $days;
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;
        $this->calculateCalendarDays();
    }

    public function prevMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);
        if ($date->isAfter(Carbon::now()->startOfMonth())) {
            $date->subMonth();
            $this->currentYear = $date->year;
            $this->currentMonth = $date->month;
            $this->calculateCalendarDays();
        }
    }

    public function selectDate($dateStr)
    {
        $this->date = $dateStr;
        $dayOfWeek = Carbon::parse($dateStr)->format('l');

        $availabilities = Availability::where('team_id', $this->team->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->get();

        $slots = [];
        if ($availabilities->count() > 0) {
            foreach ($availabilities as $avail) {
                try {
                    $start = Carbon::parse($avail->start_time);
                    $end = Carbon::parse($avail->end_time);
                    while ($start->lt($end)) {
                        $slots[] = $start->format('H:i');
                        $start->addHour();
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to parse availability time for team '.$this->team->id.': '.$e->getMessage());
                }
            }
            $allTimes = array_values(array_unique($slots));
        } else {
            $allTimes = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
        }

        // Get already-booked time slots for this date
        $bookedTimes = Booking::where('team_id', $this->team->id)
            ->whereDate('start_time', $dateStr)
            ->where('status', 'confirmed')
            ->get()
            ->map(fn ($booking) => Carbon::parse($booking->start_time)->format('H:i'))
            ->toArray();

        // Build structured slots with availability flag for gray-out UX
        $this->availableTimes = array_map(fn ($time) => [
            'time' => $time,
            'available' => ! in_array($time, $bookedTimes),
        ], $allTimes);
    }

    public function nextStep()
    {
        $this->validateStep();
        $this->step++;
    }

    public function prevStep()
    {
        $this->step--;
    }

    public function validateStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'date' => 'required',
                'time' => 'required',
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'guest_name' => 'required|string|max:255',
                'guest_email' => 'required|email|max:255',
                'guest_phone' => 'nullable|string|max:20',
                'guest_company' => 'nullable|string|max:255',
                'guest_industry' => 'nullable|string|max:255',
                'project_brief' => 'nullable|string|max:1000',
            ]);
        }
    }

    public function submit()
    {
        $this->validateStep();

        $startTime = Carbon::parse($this->date.' '.$this->time, $this->guest_timezone)->setTimezone('UTC');

        // Atomic double-booking prevention with row-level locking
        $conflict = DB::transaction(function () use ($startTime) {
            $existingCount = Booking::where('team_id', $this->team->id)
                ->where('start_time', $startTime)
                ->where('status', 'confirmed')
                ->lockForUpdate()
                ->count();

            if ($existingCount > 0) {
                return true;
            }

            try {
                $booking = Booking::create([
                    'team_id' => $this->team->id,
                    'user_id' => $this->team->owner()->id,
                    'guest_name' => $this->guest_name,
                    'guest_email' => $this->guest_email,
                    'guest_timezone' => $this->guest_timezone,
                    'start_time' => $startTime,
                    'end_time' => (clone $startTime)->addHour(),
                    'lead_data' => [
                        'company' => $this->guest_company,
                        'phone' => $this->guest_phone,
                        'industry' => $this->guest_industry,
                        'project_brief' => $this->project_brief,
                        'notes' => $this->lead_data['notes'] ?? '',
                    ],
                    'status' => 'confirmed',
                ]);

                NotificationService::trigger($booking);

                $startUTC = $booking->start_time->format('Ymd\\THis\\Z');
                $endUTC = $booking->end_time->format('Ymd\\THis\\Z');
                $title = urlencode('Strategy Session with '.$this->team->name);
                $details = urlencode('Join here: '.($booking->meet_link ?? 'Link will be provided'));

                $this->calendarLinks = [
                    'google' => "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$startUTC}/{$endUTC}&details={$details}",
                    'outlook' => "https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent&subject={$title}&startdt={$startUTC}&enddt={$endUTC}&body={$details}",
                ];

                $this->step = 3;
            } catch (Exception $e) {
                Log::error('Booking submit error: '.$e->getMessage());
                throw $e;
            }

            return false;
        });

        if ($conflict) {
            $this->addError('time', 'Sorry, this time was just booked. Please pick another.');
            $this->dispatch('booking-error', message: 'Sorry, this time was just booked. Please pick another.');
            $this->step = 1;
            $this->time = null;
            // Refresh available times for the selected date
            $this->selectDate($this->date);

            return;
        }
    }
};
?>

<div class="w-full max-w-5xl mx-auto relative z-20 text-white bg-zinc-900/40 backdrop-blur-3xl border border-white/10 shadow-[0_8px_32px_0_rgba(0,0,0,0.5)] rounded-[2rem] p-6 md:p-10 overflow-hidden" style="box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05); font-family: 'Plus Jakarta Sans', sans-serif;">
    <!-- Ambient Glows -->
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-green-500/10 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="relative z-10" x-data="{ showError: false, errorMessage: '' }" @booking-error.window="showError = true; errorMessage = $event.detail.message; setTimeout(() => showError = false, 6000)">
        <!-- Error Toast -->
        <div x-show="showError" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4"
             class="mb-6 flex items-center justify-between gap-3 bg-red-500/10 border border-red-500/30 text-red-300 px-5 py-4 rounded-2xl backdrop-blur-md shadow-[0_0_20px_rgba(239,68,68,0.15)]"
             x-cloak>
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-sm font-medium" x-text="errorMessage"></span>
            </div>
            <button type="button" @click="showError = false" class="text-red-400/50 hover:text-red-300 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <!-- Wizard Header -->
        <div class="flex items-center justify-between mb-10 relative px-2 md:px-8">
            <div class="absolute left-10 right-10 top-1/2 -translate-y-1/2 h-px bg-white/5 -z-10">
                <div class="h-full bg-gradient-to-r from-zinc-100 to-zinc-300 transition-all duration-700 ease-out shadow-[0_0_10px_rgba(255,255,255,0.5)]" 
                     style="width: {{ $step === 1 ? '0%' : ($step === 2 ? '50%' : '100%') }}"></div>
            </div>
            
            <!-- Step 1 -->
            <div class="flex flex-col items-center gap-3">
                <div @class([
                    'w-12 h-12 rounded-full flex items-center justify-center text-base font-medium transition-all duration-500 border relative',
                    'bg-zinc-100 border-zinc-100 text-zinc-900 shadow-[0_0_20px_rgba(255,255,255,0.3)] scale-110' => $step === 1,
                    'bg-zinc-100 border-zinc-100 text-zinc-900' => $step > 1,
                    'bg-zinc-900/80 border-white/10 text-white/40 backdrop-blur-md' => $step < 1
                ])>
                    @if($step > 1)
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    @else
                        1
                    @endif
                </div>
                <span class="text-[10px] sm:text-xs font-bold tracking-widest uppercase transition-colors duration-300 {{ $step >= 1 ? 'text-white' : 'text-white/40' }}" style="font-family: 'Clash Display', sans-serif;">Date & Time</span>
            </div>
            
            <!-- Step 2 -->
            <div class="flex flex-col items-center gap-3">
                <div @class([
                    'w-12 h-12 rounded-full flex items-center justify-center text-base font-medium transition-all duration-500 border relative',
                    'bg-zinc-100 border-zinc-100 text-zinc-900 shadow-[0_0_20px_rgba(255,255,255,0.3)] scale-110' => $step === 2,
                    'bg-zinc-100 border-zinc-100 text-zinc-900' => $step > 2,
                    'bg-zinc-900/80 border-white/10 text-white/40 backdrop-blur-md' => $step < 2
                ])>
                    @if($step > 2)
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    @else
                        2
                    @endif
                </div>
                <span class="text-[10px] sm:text-xs font-bold tracking-widest uppercase transition-colors duration-300 {{ $step >= 2 ? 'text-white' : 'text-white/40' }}" style="font-family: 'Clash Display', sans-serif;">Details</span>
            </div>
            
            <!-- Step 3 -->
            <div class="flex flex-col items-center gap-3">
                <div @class([
                    'w-12 h-12 rounded-full flex items-center justify-center text-base font-medium transition-all duration-500 border relative',
                    'bg-green-400 border-green-400 text-zinc-900 shadow-[0_0_20px_rgba(74,222,128,0.4)] scale-110' => $step === 3,
                    'bg-zinc-900/80 border-white/10 text-white/40 backdrop-blur-md' => $step < 3
                ])>3</div>
                <span class="text-[10px] sm:text-xs font-bold tracking-widest uppercase transition-colors duration-300 {{ $step == 3 ? 'text-white' : 'text-white/40' }}" style="font-family: 'Clash Display', sans-serif;">Done</span>
            </div>
        </div>

        <div>
            @if($step === 1)
            <div wire:key="step-1" class="animate-in fade-in slide-in-from-bottom-8 duration-700 ease-out">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 items-start relative">
                    
                    <!-- Left: Calendar -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="text-lg md:text-xl font-semibold text-white tracking-wide" style="font-family: 'Clash Display', sans-serif;">
                                {{ Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }}
                            </h3>
                            <div class="flex items-center space-x-1 bg-zinc-800/30 p-1 rounded-full border border-white/5">
                                <button type="button" wire:click="prevMonth" class="p-2 hover:bg-white/10 rounded-full transition-colors text-white/50 hover:text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button type="button" wire:click="nextMonth" class="p-2 hover:bg-white/10 rounded-full transition-colors text-white/50 hover:text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-7 gap-y-4 gap-x-2 text-center text-[10px] font-bold text-white/40 tracking-widest uppercase" style="font-family: 'Clash Display', sans-serif;">
                            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                        </div>

                        <div class="grid grid-cols-7 gap-y-3 gap-x-2">
                            @foreach($calendarDays as $dayInfo)
                                @if(is_null($dayInfo))
                                    <div class="aspect-square"></div>
                                @else
                                    @php
                                        $isSelected = $date === $dayInfo['dateStr'];
                                        $isDisabled = $dayInfo['isPast'];
                                    @endphp
                                    <button 
                                        type="button"
                                        wire:click="selectDate('{{ $dayInfo['dateStr'] }}')"
                                        @disabled($isDisabled)
                                        @class([
                                            'aspect-square w-full rounded-full flex flex-col items-center justify-center text-xs font-medium transition-all duration-300 relative focus:outline-none',
                                            'bg-white text-zinc-900 shadow-[0_0_20px_rgba(255,255,255,0.4)] scale-110 z-10' => $isSelected,
                                            'text-white/20 pointer-events-none' => $isDisabled,
                                            'text-white/80 hover:text-white hover:bg-white/10 border border-transparent hover:border-white/10 hover:scale-105' => !$isSelected && !$isDisabled,
                                        ])
                                    >
                                        <span>{{ $dayInfo['day'] }}</span>
                                        @if($isSelected)
                                            <div class="absolute -bottom-1 w-1 h-1 bg-white rounded-full"></div>
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- Right: Time Slots -->
                    <div class="space-y-6 md:pl-8 md:border-l md:border-white/5 h-full flex flex-col">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="text-xl md:text-2xl font-semibold text-white tracking-wide" style="font-family: 'Clash Display', sans-serif;">
                                @if($date) Select Time @else Pick Date @endif
                            </h3>
                            <div class="text-[10px] font-medium text-white/50 bg-zinc-800/40 px-3 py-1.5 rounded-full border border-white/5 backdrop-blur-sm" x-data x-init="$wire.set('guest_timezone', Intl.DateTimeFormat().resolvedOptions().timeZone)">
                                <span x-text="Intl.DateTimeFormat().resolvedOptions().timeZone"></span>
                            </div>
                        </div>

                        @if($date)
                            @error('time')
                                <div class="mb-3 px-4 py-3 bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-xl backdrop-blur-md flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                            <div class="grid grid-cols-2 gap-3 flex-1 overflow-y-auto pr-2 stylish-scroll max-h-[320px] content-start pb-4">
                                @foreach($availableTimes as $slot)
                                    @php
                                        $slotTime = is_array($slot) ? $slot['time'] : $slot;
                                        $slotAvailable = is_array($slot) ? ($slot['available'] ?? true) : true;
                                    @endphp
                                    <button 
                                        type="button"
                                        @if($slotAvailable)
                                        wire:click="$set('time', '{{ $slotTime }}')"
                                        @endif
                                        @disabled(!$slotAvailable)
                                        @class([
                                            'w-full py-4 px-4 text-sm font-medium rounded-2xl border transition-all duration-300 flex items-center justify-between focus:outline-none group',
                                            'bg-white text-zinc-900 border-white shadow-[0_0_20px_rgba(255,255,255,0.2)]' => $time === $slotTime,
                                            'bg-zinc-800/30 text-white/70 border-white/5 hover:bg-zinc-800/60 hover:text-white hover:border-white/20 hover:shadow-lg' => $time !== $slotTime && $slotAvailable,
                                            'bg-zinc-800/10 text-white/20 border-white/5 opacity-40 cursor-not-allowed pointer-events-none' => !$slotAvailable,
                                        ])
                                    >
                                        <div class="flex items-center space-x-2">
                                            <svg @class([
                                                'w-4 h-4 transition-colors',
                                                'text-zinc-900' => $time === $slotTime,
                                                'text-white/30 group-hover:text-white/50' => $time !== $slotTime && $slotAvailable,
                                                'text-white/10' => !$slotAvailable,
                                            ]) fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span>{{ $slotTime }}</span>
                                        </div>
                                        @if(!$slotAvailable)
                                            <span class="text-[10px] font-medium text-white/10 uppercase tracking-wider">Booked</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="flex-1 flex flex-col items-center justify-center border border-dashed border-white/10 rounded-3xl bg-zinc-800/20 p-8 text-center min-h-[320px]">
                                <div class="w-16 h-16 bg-zinc-800/50 rounded-full flex items-center justify-center mb-4 border border-white/5">
                                    <svg class="w-8 h-8 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <span class="text-sm text-white/40 leading-relaxed font-medium">Select a date from the calendar<br>to view available times</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-8 flex justify-end border-t border-white/5 pt-8">
                    <button type="button" wire:click="nextStep" class="magnetic bg-white hover:bg-zinc-200 text-zinc-900 text-sm font-bold py-3.5 px-8 rounded-full transition-all duration-300 disabled:opacity-30 disabled:cursor-not-allowed shadow-[0_0_20px_rgba(255,255,255,0.2)] hover:shadow-[0_0_30px_rgba(255,255,255,0.4)] flex items-center space-x-2" @disabled(!$date || !$time)>
                        <span>Continue</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </div>
            @endif

            @if($step === 2)
            <form wire:submit="submit" wire:key="step-2" class="animate-in fade-in slide-in-from-right-8 duration-700 ease-out">
                
                <div class="bg-zinc-800/40 border border-white/5 backdrop-blur-md p-5 rounded-2xl flex items-center justify-between mb-8 shadow-inner">
                    <div class="flex items-center space-x-4 text-sm text-white/70">
                        <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center border border-white/5">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <div class="font-semibold text-white text-base">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</div>
                            <div class="text-xs mt-0.5 text-white/60">{{ $time }} ({{ $guest_timezone }})</div>
                        </div>
                    </div>
                    <button type="button" wire:click="prevStep" class="magnetic text-xs font-bold tracking-wide uppercase text-white/50 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-4 py-2 rounded-full border border-white/10">Change</button>
                </div>
                
                <div class="space-y-6 max-h-[400px] overflow-y-auto pr-2 stylish-scroll text-sm pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <flux:input wire:model="guest_name" label="Full Name" placeholder="Jane Doe" required class="!bg-zinc-800/50 !border-white/5 focus:!border-white/20 !text-white !placeholder-white/20 transition-all rounded-xl" />
                        <flux:input wire:model="guest_email" type="email" label="Email Address" placeholder="jane@example.com" required class="!bg-zinc-800/50 !border-white/5 focus:!border-white/20 !text-white !placeholder-white/20 transition-all rounded-xl" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <flux:input wire:model="guest_phone" label="Phone (optional)" placeholder="+1 (555) 000-0000" class="!bg-zinc-800/50 !border-white/5 focus:!border-white/20 !text-white !placeholder-white/20 transition-all rounded-xl" />
                        <flux:input wire:model="guest_company" label="Company (optional)" placeholder="Acme Corp" class="!bg-zinc-800/50 !border-white/5 focus:!border-white/20 !text-white !placeholder-white/20 transition-all rounded-xl" />
                    </div>

                    <flux:select wire:model="guest_industry" label="Industry" placeholder="Select industry..." class="!bg-zinc-800/50 !border-white/5 focus:!border-white/20 !text-white transition-all rounded-xl">
                        <flux:select.option value="Technology">Technology</flux:select.option>
                        <flux:select.option value="Finance">Finance</flux:select.option>
                        <flux:select.option value="Healthcare">Healthcare</flux:select.option>
                        <flux:select.option value="Education">Education</flux:select.option>
                        <flux:select.option value="Hospitality">Hospitality</flux:select.option>
                        <flux:select.option value="Retail">Retail</flux:select.option>
                        <flux:select.option value="Real Estate">Real Estate</flux:select.option>
                        <flux:select.option value="Other">Other</flux:select.option>
                    </flux:select>
                    
                    <flux:textarea wire:model="project_brief" label="Project Brief" placeholder="Tell us a little about what you're looking to achieve..." rows="3" class="!bg-zinc-800/50 !border-white/5 focus:!border-white/20 !text-white !placeholder-white/20 transition-all rounded-xl" />
                </div>

                <div class="flex items-center justify-between mt-8 pt-8 border-t border-white/5">
                    <button type="button" wire:click="prevStep" class="magnetic text-white/50 hover:text-white text-sm font-semibold transition-colors flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>Back</span>
                    </button>
                    <button type="submit" class="magnetic bg-white hover:bg-zinc-200 text-zinc-900 text-sm font-bold py-3.5 px-8 rounded-full transition-all duration-300 shadow-[0_0_20px_rgba(255,255,255,0.2)] hover:shadow-[0_0_30px_rgba(255,255,255,0.4)] flex items-center space-x-2">
                        <span>Confirm Booking</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                </div>
            </form>
            @endif

            @if($step === 3)
            <div wire:key="step-3" class="text-center animate-in zoom-in-95 duration-700 ease-out py-10">
                <div class="w-24 h-24 bg-zinc-800/50 border border-white/10 rounded-full flex items-center justify-center mx-auto mb-8 relative">
                    <div class="absolute inset-0 bg-green-400/20 rounded-full blur-xl"></div>
                    <svg class="w-10 h-10 text-green-400 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                </div>
                
                <h2 class="text-3xl md:text-4xl font-semibold text-white mb-4 tracking-wide" style="font-family: 'Clash Display', sans-serif;">Booking Confirmed!</h2>
                <p class="text-white/60 text-base mb-10 font-medium">
                    {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }} at {{ $time }}
                </p>

                <div class="flex flex-col space-y-3 mb-10 max-w-sm mx-auto">
                    <a href="{{ $calendarLinks['google'] ?? '#' }}" target="_blank" class="magnetic w-full inline-flex items-center justify-center space-x-3 px-5 py-4 bg-zinc-800/50 hover:bg-zinc-800/80 border border-white/5 rounded-2xl text-sm font-semibold text-white transition-all hover:shadow-[0_0_15px_rgba(255,255,255,0.05)]">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.333.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                        <span>Add to Google Calendar</span>
                    </a>
                    <a href="{{ $calendarLinks['outlook'] ?? '#' }}" target="_blank" class="magnetic w-full inline-flex items-center justify-center space-x-3 px-5 py-4 bg-[#0078D4]/20 hover:bg-[#0078D4]/40 border border-[#0078D4]/30 rounded-2xl text-sm font-semibold text-white transition-all hover:shadow-[0_0_20px_rgba(0,120,212,0.3)]">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M1.146 5.826a.853.853 0 0 1 .537-.791l10.455-4.182a.853.853 0 0 1 1.05.385l8.181 16.363a.853.853 0 0 1-.384 1.05L10.53 22.834a.853.853 0 0 1-1.05-.385L1.3 6.086a.853.853 0 0 1-.154-.26zm11.516.4L3.892 9.73l8.77 1.83zm.507 1.341 8.77-1.83-6.577-13.153zm-1.077.58L3.322 9.977l6.576 13.154zm1.185.203 8.77 1.83-8.77-17.54z"/></svg>
                        <span>Add to Outlook</span>
                    </a>
                </div>

                <div class="bg-zinc-800/30 border border-white/5 rounded-2xl p-6 mx-auto max-w-sm backdrop-blur-sm shadow-inner">
                    <div class="text-center text-sm text-white/60">
                        <p class="mb-2">Booking Reference: <strong class="text-white">{{ $guest_name }}</strong></p>
                        <p class="text-[11px] text-white/40 leading-relaxed">A confirmation email has been sent to<br>{{ $guest_email }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <style>
        .stylish-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .stylish-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 4px;
        }
        .stylish-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        .stylish-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</div>