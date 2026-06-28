<?php

use App\Livewire\Setup\Wizard;
use Illuminate\Support\Facades\Route;

Route::middleware(['not.installed'])->group(function () {
    Route::get('/setup', Wizard::class)->name('setup.index');
});
