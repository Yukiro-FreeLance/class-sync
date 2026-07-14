<?php

namespace App\Http\Middleware;

use App\Services\Settings\SubscriptionService;
use App\Services\Users\SuperAdminService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function __construct(
        protected SubscriptionService $subscription,
        protected SuperAdminService $superAdmin,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->subscription->isActive()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $this->superAdmin->is($user)) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $this->subscription->message(),
                'subscription_expired' => true,
            ], 503);
        }

        return redirect()->route('subscription.expired');
    }

    protected function isExempt(Request $request): bool
    {
        return $request->routeIs(
            'subscription.expired',
            'logout',
            'profile',
        );
    }
}
