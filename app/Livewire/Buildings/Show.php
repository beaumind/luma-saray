<?php

namespace App\Livewire\Buildings;

use App\Models\Building;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Building $building;

    public function mount(Building $building): void
    {
        $this->building = $building;
    }

    public function render()
    {
        $this->building->load(['units' => function ($q) {
            $q->with(['activeResidents'])->orderBy('floor')->orderBy('number');
        }]);

        return view('livewire.buildings.show')
            ->title($this->building->name);
    }
}
