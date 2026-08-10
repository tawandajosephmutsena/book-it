<x-layouts::app :title="__('Dashboard')">
    <livewire:pages::teams.pending-invitations-modal />

    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <livewire:dashboard-manager />
    </div>
</x-layouts::app>
