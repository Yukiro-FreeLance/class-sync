<?php

namespace App\Livewire\Settings;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLogService;
use App\Services\Settings\SubscriptionService;
use App\Services\Users\SuperAdminService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Subscription')]
class Subscription extends Component
{
    public string $expires_at = '';

    public string $message = '';

    public function mount(SuperAdminService $superAdmin, SubscriptionService $subscription): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $status = $subscription->status();
        $this->expires_at = $status['expires_at'] ?? '';
        $this->message = $status['message'];
    }

    public function save(
        SubscriptionService $subscription,
        AuditLogService $audit,
        SuperAdminService $superAdmin,
    ): void {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $this->validate([
            'expires_at' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription->update(
            $this->expires_at !== '' ? $this->expires_at : null,
            $this->message,
        );

        $audit->log(
            AuditAction::Settings,
            description: 'Updated system subscription expiry',
            properties: [
                'expires_at' => $this->expires_at !== '' ? $this->expires_at : null,
            ],
        );

        session()->flash('status', 'Subscription settings saved.');
    }

    public function clearExpiry(
        SubscriptionService $subscription,
        AuditLogService $audit,
        SuperAdminService $superAdmin,
    ): void {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $subscription->clear();
        $this->expires_at = '';

        $audit->log(
            AuditAction::Settings,
            description: 'Cleared system subscription expiry (unlimited access)',
        );

        session()->flash('status', 'Subscription expiry cleared. The system has unlimited access.');
    }

    public function render(SubscriptionService $subscription)
    {
        return view('livewire.settings.subscription', [
            'status' => $subscription->status(),
        ]);
    }
}
