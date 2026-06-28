<?php

namespace App\Livewire\Settings\Academic\Concerns;

use App\Models\Setting;

trait AuthorizesAcademicSettings
{
    public function mountAuthorizesAcademicSettings(): void
    {
        $this->authorize('update', Setting::class);
    }
}
