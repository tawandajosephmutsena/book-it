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

    // Step 1: Scheduling
    public $date;
    public $time;
    public $guest_timezone = 'UTC';
    public $currentYear;
    public $currentMonth;
    public $calendarDays = [];
    public $availableTimes = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
    public $timezones = [
        'UTC' => 'UTC (Coordinated Universal Time)',
        'Africa/Harare' => 'Harare, Johannesburg (CAT / GMT+2)',
        'Africa/Lagos' => 'Lagos, London (WAT / GMT+1)',
        'Europe/London' => 'London, Dublin (GMT/BST)',
        'Europe/Paris' => 'Paris, Berlin, Rome (CET / GMT+1)',
        'America/New_York' => 'New York, Eastern (EDT / GMT-4)',
        'America/Chicago' => 'Chicago, Central (CDT / GMT-5)',
        'America/Denver' => 'Denver, Mountain (MDT / GMT-6)',
        'America/Los_Angeles' => 'Los Angeles, Pacific (PDT / GMT-7)',
        'Asia/Dubai' => 'Dubai, UAE (GST / GMT+4)',
        'Asia/Singapore' => 'Singapore, Hong Kong (SGT / GMT+8)',
        'Australia/Sydney' => 'Sydney, Melbourne (AEST / GMT+10)',
    ];

    // Step 2: Intake Details
    public $guest_name = '';
    public $guest_email = '';
    public $guest_phone = '';
    public $guest_company = '';
    public $guest_industry = '';
    public $project_brief = '';

    // Step 3: Confirmation
    public $confirmedBooking = null;
    public $calendarLinks = [];

    public function mount(Team $team)
    {
        $this->team = $team;
        $this->currentYear = Carbon::now()->year;
        $this->currentMonth = Carbon::now()->month;
        $this->calculateCalendarDays();
    }

    public function updatedGuestTimezone()
    {
        if ($this->date) {
            $this->selectDate($this->date);
        }
    }

    public function calculateCalendarDays()
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $startOfMonth->daysInMonth;
        $startDayOfWeek = $startOfMonth->dayOfWeek; // 0 = Sunday, 1 = Monday...

        $days = [];

        // Fill leading blank days
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $days[] = null;
        }

        // Fill days of the month
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
        $this->time = null;
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
                } catch (\Exception $e) {
                    Log::warning('Failed to parse availability time: '.$e->getMessage());
                }
            }
            $allTimes = array_values(array_unique($slots));
        } else {
            // Default business hours
            $allTimes = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
        }

        // Get booked slots for this date in UTC converted to guest timezone
        $targetDateCarbon = Carbon::parse($dateStr);
        $bookedTimes = Booking::where('team_id', $this->team->id)
            ->where('status', 'confirmed')
            ->get()
            ->filter(function ($booking) use ($dateStr) {
                $localTime = Carbon::parse($booking->start_time)->setTimezone($this->guest_timezone);
                return $localTime->format('Y-m-d') === $dateStr;
            })
            ->map(function ($booking) {
                return Carbon::parse($booking->start_time)->setTimezone($this->guest_timezone)->format('H:i');
            })
            ->toArray();

        $this->availableTimes = array_map(fn ($time) => [
            'time' => substr($time, 0, 5),
            'formatted' => Carbon::parse($time)->format('g:i A'),
            'available' => ! in_array(substr($time, 0, 5), $bookedTimes),
        ], $allTimes);
    }

    public function selectTime($timeStr)
    {
        $this->time = $timeStr;
    }

    public function goToStep2()
    {
        $this->validate([
            'date' => 'required|date',
            'time' => 'required|string',
            'guest_timezone' => 'required|string',
        ]);
        $this->step = 2;
    }

    public function nextStep()
    {
        $this->goToStep2();
    }

    public function goToStep1()
    {
        $this->step = 1;
    }

    public function prevStep()
    {
        $this->goToStep1();
    }

    public function submit()
    {
        $this->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'nullable|string|max:25',
            'guest_company' => 'nullable|string|max:255',
            'guest_industry' => 'nullable|string|max:255',
            'project_brief' => 'nullable|string|max:1500',
        ]);

        $startTime = Carbon::parse($this->date.' '.$this->time, $this->guest_timezone)->setTimezone('UTC');
        $datePrefix = $startTime->format('Y-m-d');
        $timePrefix = $startTime->format('H:i');

        // Atomic double-booking lock
        $conflict = DB::transaction(function () use ($startTime, $datePrefix, $timePrefix) {
            $existingCount = Booking::where('team_id', $this->team->id)
                ->where(function ($q) use ($startTime, $datePrefix, $timePrefix) {
                    $q->where('start_time', $startTime)
                      ->orWhere('start_time', $startTime->format('Y-m-d H:i:s'))
                      ->orWhere('start_time', 'like', "{$datePrefix}%{$timePrefix}%");
                })
                ->where('status', 'confirmed')
                ->lockForUpdate()
                ->count();

            if ($existingCount > 0) {
                return true;
            }

            try {
                $owner = $this->team->owner();
                $booking = Booking::create([
                    'team_id' => $this->team->id,
                    'user_id' => $owner ? $owner->id : $this->team->user_id,
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
                        'notes' => '',
                    ],
                    'status' => 'confirmed',
                ]);

                // Dispatch Calendar Sync and Notifications
                NotificationService::trigger($booking);
                $booking->refresh();

                $this->confirmedBooking = $booking;

                // Build 1-click calendar links
                $startUTC = $booking->start_time->format('Ymd\\THis\\Z');
                $endUTC = $booking->end_time->format('Ymd\\THis\\Z');
                $title = urlencode('Strategy Session with '.$this->team->name);
                $details = urlencode('Google Meet: '.($booking->meet_link ?? 'Link in confirmation email')."\n\nGuest: {$this->guest_name}\nCompany: {$this->guest_company}");

                $this->calendarLinks = [
                    'google' => "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$startUTC}/{$endUTC}&details={$details}",
                    'outlook' => "https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent&subject={$title}&startdt={$startUTC}&enddt={$endUTC}&body={$details}",
                ];

                $this->step = 3;
            } catch (\Exception $e) {
                Log::error('Booking submission error: '.$e->getMessage());
                throw $e;
            }

            return false;
        });

        if ($conflict) {
            $this->addError('time', 'This time slot was just taken. Please select another slot.');
            $this->step = 1;
            $this->time = null;
            $this->selectDate($this->date);

            throw \Illuminate\Validation\ValidationException::withMessages([
                'time' => 'This time slot was just taken. Please select another slot.',
            ]);
        }
    }

    public function resetBooking()
    {
        $this->step = 1;
        $this->time = null;
        $this->guest_name = '';
        $this->guest_email = '';
        $this->guest_phone = '';
        $this->guest_company = '';
        $this->guest_industry = '';
        $this->project_brief = '';
        $this->confirmedBooking = null;
        $this->selectDate($this->date);
    }
};
?>

<div class="w-full text-zinc-100 font-sans">
    
    <!-- Step Navigation Indicator -->
    <div class="flex items-center justify-between pb-6 mb-6 border-b border-zinc-800">
        <div class="flex items-center gap-2">
            <span @class([
                'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                'bg-amber-500 text-zinc-950 shadow-sm shadow-amber-500/30' => $step === 1,
                'bg-zinc-800 text-emerald-400' => $step > 1,
            ])>
                @if($step > 1) ✓ @else 1 @endif
            </span>
            <span class="text-xs font-semibold uppercase tracking-wider {{ $step === 1 ? 'text-white' : 'text-zinc-400' }}">Time</span>
        </div>

        <div class="h-0.5 w-12 bg-zinc-800">
            <div class="h-full bg-amber-500 transition-all duration-300" style="width: {{ $step === 1 ? '0%' : ($step === 2 ? '50%' : '100%') }}"></div>
        </div>

        <div class="flex items-center gap-2">
            <span @class([
                'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                'bg-amber-500 text-zinc-950 shadow-sm shadow-amber-500/30' => $step === 2,
                'bg-zinc-800 text-emerald-400' => $step > 2,
                'bg-zinc-800 text-zinc-500' => $step < 2,
            ])>
                @if($step > 2) ✓ @else 2 @endif
            </span>
            <span class="text-xs font-semibold uppercase tracking-wider {{ $step === 2 ? 'text-white' : 'text-zinc-500' }}">Intake</span>
        </div>

        <div class="h-0.5 w-12 bg-zinc-800">
            <div class="h-full bg-amber-500 transition-all duration-300" style="width: {{ $step === 3 ? '100%' : '0%' }}"></div>
        </div>

        <div class="flex items-center gap-2">
            <span @class([
                'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                'bg-emerald-500 text-zinc-950 shadow-sm shadow-emerald-500/30' => $step === 3,
                'bg-zinc-800 text-zinc-500' => $step < 3,
            ])>
                3
            </span>
            <span class="text-xs font-semibold uppercase tracking-wider {{ $step === 3 ? 'text-emerald-400' : 'text-zinc-500' }}">Confirmed</span>
        </div>
    </div>

    <!-- STEP 1: Date & Time Picker -->
    @if($step === 1)
        <div class="space-y-6">
            
            <!-- Timezone Selector Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-xl bg-zinc-950/60 border border-zinc-800/80">
                <div class="flex items-center gap-2 text-xs text-zinc-400">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Timezone:</span>
                </div>
                <select wire:model.live="guest_timezone" class="bg-zinc-900 border border-zinc-700 text-zinc-200 text-xs rounded-lg px-3 py-1.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @foreach($timezones as $tzKey => $tzLabel)
                        <option value="{{ $tzKey }}">{{ $tzLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid md:grid-cols-12 gap-6 items-start">
                
                <!-- Calendar (7 cols) -->
                <div class="md:col-span-7 bg-zinc-950/50 p-4 rounded-xl border border-zinc-800">
                    <!-- Month Header -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-bold text-base text-zinc-100">
                            {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="prevMonth" class="p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" wire:click="nextMonth" class="p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Days of Week -->
                    <div class="grid grid-cols-7 gap-1 text-center mb-2">
                        @foreach(['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $dow)
                            <div class="text-[11px] font-semibold text-zinc-500 py-1">{{ $dow }}</div>
                        @endforeach
                    </div>

                    <!-- Days Grid -->
                    <div class="grid grid-cols-7 gap-1.5 text-center">
                        @foreach($calendarDays as $dayObj)
                            @if(is_null($dayObj))
                                <div class="h-9"></div>
                            @else
                                @php
                                    $isSelected = ($date === $dayObj['dateStr']);
                                    $isDisabled = $dayObj['isPast'] || $dayObj['isWeekend'];
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectDate('{{ $dayObj['dateStr'] }}')"
                                    @disabled($isDisabled)
                                    @class([
                                        'h-9 rounded-lg text-xs font-medium flex items-center justify-center transition-all relative',
                                        'bg-amber-500 text-zinc-950 font-bold shadow-sm shadow-amber-500/30' => $isSelected,
                                        'text-zinc-600 cursor-not-allowed bg-transparent' => $isDisabled && !$isSelected,
                                        'text-zinc-200 hover:bg-zinc-800/80 bg-zinc-900/60' => !$isDisabled && !$isSelected,
                                        'border border-amber-500/50' => $dayObj['isToday'] && !$isSelected,
                                    ])
                                >
                                    {{ $dayObj['day'] }}
                                    @if($dayObj['isToday'] && !$isSelected)
                                        <span class="absolute bottom-1 w-1 h-1 rounded-full bg-amber-400"></span>
                                    @endif
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Available Slots Column (5 cols) -->
                <div class="md:col-span-5 flex flex-col space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                            {{ $date ? \Carbon\Carbon::parse($date)->format('D, M j') : 'Select a date' }}
                        </span>
                        <span class="text-[11px] text-zinc-500">45 Min Strategy Call</span>
                    </div>

                    <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                        @forelse($availableTimes as $slot)
                            @php
                                $slotTime = is_array($slot) ? $slot['time'] : $slot;
                                $slotFormatted = is_array($slot) ? ($slot['formatted'] ?? \Carbon\Carbon::parse($slotTime)->format('g:i A')) : \Carbon\Carbon::parse($slotTime)->format('g:i A');
                                $slotAvailable = is_array($slot) ? ($slot['available'] ?? true) : true;
                            @endphp
                            @if($slotAvailable)
                                <button
                                    type="button"
                                    wire:click="selectTime('{{ $slotTime }}')"
                                    @class([
                                        'w-full py-2.5 px-4 rounded-xl text-xs font-semibold transition-all flex items-center justify-between border',
                                        'bg-amber-500 text-zinc-950 border-amber-400 shadow-sm shadow-amber-500/20' => $time === $slotTime,
                                        'bg-zinc-950/60 text-zinc-200 border-zinc-800 hover:border-zinc-700 hover:bg-zinc-900' => $time !== $slotTime,
                                    ])
                                >
                                    <span>{{ $slotFormatted }}</span>
                                    <span class="text-[10px] opacity-75 font-normal">Available</span>
                                </button>
                            @else
                                <div class="w-full py-2.5 px-4 rounded-xl text-xs font-medium text-zinc-600 bg-zinc-950/30 border border-zinc-900 flex items-center justify-between cursor-not-allowed">
                                    <span class="line-through">{{ $slotFormatted }}</span>
                                    <span class="text-[10px] uppercase tracking-wider text-zinc-600 font-normal">Booked</span>
                                </div>
                            @endif
                        @empty
                            <div class="text-center py-8 text-xs text-zinc-500 bg-zinc-950/30 rounded-xl border border-zinc-800/50">
                                No slots available for this day.
                            </div>
                        @endforelse
                    </div>

                    @error('time')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Continue Button -->
                    <div class="pt-2">
                        <button
                            type="button"
                            wire:click="goToStep2"
                            @disabled(!$date || !$time)
                            @class([
                                'w-full py-3 px-4 rounded-xl text-xs font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-2',
                                'bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 hover:from-amber-400 hover:to-amber-500 shadow-lg shadow-amber-500/20 cursor-pointer' => $date && $time,
                                'bg-zinc-800 text-zinc-500 cursor-not-allowed' => !$date || !$time,
                            ])
                        >
                            <span>Next: Enter Details</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

            </div>

        </div>
    @endif

    <!-- STEP 2: Lead Intake Form -->
    @if($step === 2)
        <form wire:submit="submit" class="space-y-4">
            
            <!-- Summary of chosen slot -->
            <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between text-xs text-amber-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span><strong>{{ $date ? \Carbon\Carbon::parse($date)->format('l, F j, Y') : 'Date Selected' }}</strong> at <strong>{{ $time ? \Carbon\Carbon::parse($time)->format('g:i A') : 'Time Selected' }}</strong> ({{ $guest_timezone }})</span>
                </div>
                <button type="button" wire:click="goToStep1" class="text-amber-400 hover:underline font-semibold text-[11px]">Change</button>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                    <input
                        type="text"
                        wire:model="guest_name"
                        placeholder="Tawanda Moyo"
                        class="w-full bg-zinc-950 border border-zinc-700 rounded-xl px-3.5 py-2.5 text-xs text-zinc-100 placeholder-zinc-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none"
                    />
                    @error('guest_name') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Work Email -->
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Work Email <span class="text-red-400">*</span></label>
                    <input
                        type="email"
                        wire:model="guest_email"
                        placeholder="tawanda@company.com"
                        class="w-full bg-zinc-950 border border-zinc-700 rounded-xl px-3.5 py-2.5 text-xs text-zinc-100 placeholder-zinc-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none"
                    />
                    @error('guest_email') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <!-- Phone Number -->
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">WhatsApp / Phone</label>
                    <input
                        type="tel"
                        wire:model="guest_phone"
                        placeholder="+263 77 123 4567"
                        class="w-full bg-zinc-950 border border-zinc-700 rounded-xl px-3.5 py-2.5 text-xs text-zinc-100 placeholder-zinc-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none"
                    />
                    @error('guest_phone') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Company Name -->
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Company Name</label>
                    <input
                        type="text"
                        wire:model="guest_company"
                        placeholder="Apex Logistics"
                        class="w-full bg-zinc-950 border border-zinc-700 rounded-xl px-3.5 py-2.5 text-xs text-zinc-100 placeholder-zinc-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none"
                    />
                    @error('guest_company') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Industry -->
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Industry</label>
                    <input
                        type="text"
                        wire:model="guest_industry"
                        placeholder="Fintech, Retail, etc."
                        class="w-full bg-zinc-950 border border-zinc-700 rounded-xl px-3.5 py-2.5 text-xs text-zinc-100 placeholder-zinc-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none"
                    />
                    @error('guest_industry') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Project Brief / Automation Goals -->
            <div>
                <label class="block text-xs font-medium text-zinc-300 mb-1.5">What are your primary goals or operational bottlenecks?</label>
                <textarea
                    wire:model="project_brief"
                    rows="3"
                    placeholder="Describe what processes you want to streamline, current tech stack, or challenges you're facing..."
                    class="w-full bg-zinc-950 border border-zinc-700 rounded-xl px-3.5 py-2.5 text-xs text-zinc-100 placeholder-zinc-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none"
                ></textarea>
                @error('project_brief') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Actions Bar -->
            <div class="flex items-center justify-between pt-4 border-t border-zinc-800">
                <button
                    type="button"
                    wire:click="goToStep1"
                    class="py-2.5 px-4 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white hover:bg-zinc-800 transition"
                >
                    ← Back
                </button>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="py-3 px-6 rounded-xl text-xs font-bold uppercase tracking-wider bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 hover:from-amber-400 hover:to-amber-500 shadow-lg shadow-amber-500/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                >
                    <span wire:loading.remove>Confirm & Schedule Session</span>
                    <span wire:loading>Securing your slot...</span>
                </button>
            </div>

        </form>
    @endif

    <!-- STEP 3: Booking Confirmed -->
    @if($step === 3 && $confirmedBooking)
        <div class="text-center space-y-6 py-4">
            
            <!-- Success Icon -->
            <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mx-auto text-emerald-400 shadow-lg shadow-emerald-500/20">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>

            <div>
                <h3 class="text-2xl font-bold text-white">Strategy Session Confirmed!</h3>
                <p class="text-xs text-zinc-400 mt-1.5">A calendar invitation and confirmation email have been sent to <strong>{{ $confirmedBooking->guest_email }}</strong>.</p>
            </div>

            <!-- Session Details Summary Card -->
            <div class="max-w-md mx-auto p-4 rounded-xl bg-zinc-950 border border-zinc-800 text-left space-y-3 text-xs">
                <div class="flex items-start justify-between border-b border-zinc-800/80 pb-2.5">
                    <span class="text-zinc-400">Date & Time:</span>
                    <span class="font-semibold text-zinc-100 text-right">
                        {{ $confirmedBooking->start_time->setTimezone($confirmedBooking->guest_timezone)->format('l, F j, Y') }}<br>
                        <span class="text-amber-400">{{ $confirmedBooking->start_time->setTimezone($confirmedBooking->guest_timezone)->format('g:i A') }} ({{ $confirmedBooking->guest_timezone }})</span>
                    </span>
                </div>

                <div class="flex items-center justify-between border-b border-zinc-800/80 pb-2.5">
                    <span class="text-zinc-400">Host:</span>
                    <span class="font-semibold text-zinc-100">{{ $team->name }}</span>
                </div>

                <div class="flex items-center justify-between border-b border-zinc-800/80 pb-2.5">
                    <span class="text-zinc-400">Meeting Platform:</span>
                    <span class="font-semibold text-emerald-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z"/></svg>
                        Google Meet Video
                    </span>
                </div>

                @if(!empty($confirmedBooking->meet_link))
                    <div class="pt-1">
                        <a href="{{ $confirmedBooking->meet_link }}" target="_blank" class="w-full py-2 px-3 rounded-lg bg-zinc-900 border border-zinc-700 hover:border-amber-500/50 text-amber-300 font-semibold text-center block transition">
                            Open Google Meet Link ↗
                        </a>
                    </div>
                @endif
            </div>

            <!-- 1-Click Add to Calendar Buttons -->
            <div class="space-y-2 pt-2">
                <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Add to your calendar</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    @if(isset($calendarLinks['google']))
                        <a href="{{ $calendarLinks['google'] }}" target="_blank" class="py-2 px-4 rounded-lg bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-xs font-medium text-zinc-200 transition flex items-center gap-2">
                            <span>Google Calendar</span>
                        </a>
                    @endif
                    @if(isset($calendarLinks['outlook']))
                        <a href="{{ $calendarLinks['outlook'] }}" target="_blank" class="py-2 px-4 rounded-lg bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-xs font-medium text-zinc-200 transition flex items-center gap-2">
                            <span>Outlook Calendar</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Book Another Button -->
            <div class="pt-4 border-t border-zinc-800">
                <button type="button" wire:click="resetBooking" class="text-xs text-zinc-400 hover:text-white transition">
                    Book another appointment
                </button>
            </div>

        </div>
    @endif

</div>