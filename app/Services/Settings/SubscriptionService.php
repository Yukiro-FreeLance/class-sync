<?php

namespace App\Services\Settings;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    public const GROUP = 'subscription';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function expiresAt(): ?CarbonInterface
    {
        $value = $this->settings->get('expires_at', null, self::GROUP);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function message(): string
    {
        $message = $this->settings->get('message', null, self::GROUP);

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return 'This system is temporarily unavailable. The school subscription has expired. Please contact your system administrator.';
    }

    public function isConfigured(): bool
    {
        return $this->expiresAt() !== null;
    }

    public function isActive(): bool
    {
        $expiresAt = $this->expiresAt();

        if ($expiresAt === null) {
            return true;
        }

        return now()->lte($expiresAt);
    }

    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    public function daysRemaining(): ?int
    {
        $expiresAt = $this->expiresAt();

        if ($expiresAt === null) {
            return null;
        }

        return (int) round(now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false));
    }

    /**
     * Show a warning banner to non–super-admin users while hosting is still active but has an end date.
     */
    public function shouldShowExpiryBanner(?User $user): bool
    {
        if ($user === null || $user->isSuperAdmin()) {
            return false;
        }

        return $this->isConfigured() && $this->isActive();
    }

    /**
     * @return array{expires_at: string, expires_at_label: string, days_remaining: int, storage_key: string}|null
     */
    public function expiryBanner(?User $user): ?array
    {
        if (! $this->shouldShowExpiryBanner($user)) {
            return null;
        }

        $expiresAt = $this->expiresAt();

        if ($expiresAt === null || $user === null) {
            return null;
        }

        $daysRemaining = $this->daysRemaining() ?? 0;

        return [
            'expires_at' => $expiresAt->toDateString(),
            'expires_at_label' => $expiresAt->format('M j, Y'),
            'days_remaining' => $daysRemaining,
            'storage_key' => 'classsync.subscription_banner.'.$user->id.'.'.$expiresAt->toDateString(),
        ];
    }

    /**
     * @return array{expires_at: ?string, message: string, is_active: bool, is_configured: bool, days_remaining: ?int}
     */
    public function status(): array
    {
        $expiresAt = $this->expiresAt();

        return [
            'expires_at' => $expiresAt?->toDateString(),
            'message' => $this->message(),
            'is_active' => $this->isActive(),
            'is_configured' => $this->isConfigured(),
            'days_remaining' => $this->daysRemaining(),
        ];
    }

    public function update(?string $expiresAt, ?string $message = null): void
    {
        $normalizedExpiry = null;

        if (is_string($expiresAt) && trim($expiresAt) !== '') {
            $normalizedExpiry = Carbon::parse($expiresAt)->toDateString();
        }

        $this->settings->set('expires_at', $normalizedExpiry, self::GROUP);

        if ($message !== null) {
            $trimmed = trim($message);
            $this->settings->set('message', $trimmed !== '' ? $trimmed : null, self::GROUP);
        }
    }

    public function clear(): void
    {
        $this->settings->set('expires_at', null, self::GROUP);
    }
}
