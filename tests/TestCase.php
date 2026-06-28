<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->markAsInstalled();
    }

    protected function markAsInstalled(): void
    {
        File::ensureDirectoryExists(storage_path());
        File::put(storage_path('installed'), now()->toIso8601String());
    }

    protected function markAsNotInstalled(): void
    {
        if (File::exists(storage_path('installed'))) {
            File::delete(storage_path('installed'));
        }
    }
}
