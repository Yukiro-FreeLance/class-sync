<x-app-layout>
    <x-page-header title="Profile" subtitle="Manage your account settings" />

    <div class="space-y-6 max-w-2xl">
        <div class="panel">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="panel">
            <livewire:profile.update-password-form />
        </div>

        <div class="panel">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
