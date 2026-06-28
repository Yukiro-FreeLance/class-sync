<?php

namespace App\Livewire\Settings\Academic;

use App\Models\Room;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Rooms')]
class Rooms extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $building = '';

    public ?int $capacity = null;

    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorize('update', Setting::class);
    }

    public function edit(int $id): void
    {
        $room = Room::query()->findOrFail($id);
        $this->editingId = $room->id;
        $this->name = $room->name;
        $this->code = $room->code ?? '';
        $this->building = $room->building ?? '';
        $this->capacity = $room->capacity;
        $this->isActive = $room->is_active;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'building', 'capacity', 'isActive']);
        $this->isActive = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'building' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'isActive' => ['boolean'],
        ]);

        Room::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'code' => $this->code ?: null,
                'building' => $this->building ?: null,
                'capacity' => $this->capacity,
                'is_active' => $this->isActive,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', message: 'Room saved.', type: 'success');
    }

    public function delete(int $id): void
    {
        Room::query()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Room removed.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.academic.rooms', [
            'rooms' => Room::query()->orderBy('building')->orderBy('name')->get(),
        ]);
    }
}
