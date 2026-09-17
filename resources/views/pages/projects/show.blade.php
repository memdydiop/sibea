<?php

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Project $project;

    public function mount(): void
    {
        abort_if(! $this->project->is_published, 404);
    }
};
?>

<div>
    <section class="bg-nuit text-white py-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">
                RÉALISATION
            </div>
            <h1 class="font-[Manrope] font-extrabold text-5xl lg:text-6xl mb-6">
                {{ $project->title }}
            </h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">
                {{ $project->short_description }}
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
        <div class="prose max-w-2xl mb-12">
            <p>{{ $project->description }}</p>
        </div>

        @if($project->client_publishable && $project->client_name)
            <p class="text-sm text-ardoise mb-12">Client : {{ $project->client_name }}</p>
        @endif

        <div class="text-center">
            <flux:button variant="primary" class="!bg-cuivre" x-on:click="$flux.modal('contact').open()">
                Un projet similaire ? Contactez-nous
            </flux:button>
        </div>
    </section>
</div>
