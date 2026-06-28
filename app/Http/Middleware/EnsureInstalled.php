<?php

namespace App\Http\Middleware;

use App\Services\Setup\InstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function __construct(
        protected InstallerService $installer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->installer->isInstalled()) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
