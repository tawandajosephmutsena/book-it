<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            /* Steampunk sidebar item overrides */
            [data-flux-sidebar-item] { transition: all 0.2s ease; }
            [data-flux-sidebar-item]:hover { background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(180,83,9,0.05)); border-left: 2px solid rgba(245,158,11,0.3); }
            [data-flux-sidebar-item][data-current] { background: linear-gradient(135deg, rgba(245,158,11,0.12), rgba(180,83,9,0.08)); color: #fbbf24; border-left: 2px solid #f59e0b; }
            [data-flux-sidebar-item] svg { transition: color 0.2s; }
            [data-flux-sidebar-item]:hover svg { color: #f59e0b; }
            [data-flux-sidebar-item][data-current] svg { color: #fbbf24; }
            /* Sidebar group headings */
            [data-flux-sidebar-group] > [data-flux-sidebar-heading] { color: #a16207; font-size: 0.65rem; letter-spacing: 0.15em; text-transform: uppercase; }
            /* Brass divider texture effect on sidebar */
            flux\\:sidebar::before {
                content: ''; position: absolute; top: 0; right: 0; width: 1px; height: 100%;
                background: linear-gradient(to bottom, transparent, rgba(180,83,9,0.15) 20%, rgba(245,158,11,0.25) 50%, rgba(180,83,9,0.15) 80%, transparent);
                pointer-events: none; z-index: 10;
            }
            /* Steampunk gear watermark — subtle */
            flux\\:sidebar::after {
                content: '⚙'; position: absolute; bottom: 4rem; right: 0.5rem;
                font-size: 5rem; opacity: 0.03; transform: rotate(15deg); pointer-events: none; z-index: 0;
            }
            /* Main content area */
            [data-flux-main] { background: #1c1917; }
        </style>
    </head>
    <body class="min-h-screen bg-[#1c1917] dark:bg-[#1c1917]">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-amber-900/20 bg-[#151310] dark:bg-[#151310] shadow-[inset_-1px_0_0_rgba(180,83,9,0.08)]">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <livewire:team-switcher />

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Book-it')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" href="/" target="_blank" wire:navigate="false">
                        {{ __('Public Page') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" :href="route('docs')" wire:navigate>
                        {{ __('Documentation') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" wire:navigate>
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="arrow-top-right-on-square" href="https://ottomate.space" target="_blank">
                    {{ __('Ottomate.space') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden border-b border-amber-900/20 bg-[#151310]">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <livewire:create-team-modal />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
