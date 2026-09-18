<?php

use App\Models\Testimonial;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Témoignages')] class extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $author_name = '';

    public ?string $position = null;

    public ?string $organization = null;

    public string $content = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Testimonial::class);
    }

    #[Computed]
    public function testimonials()
    {
        return Testimonial::query()->ordered()->paginate(10);
    }

    public function create(): void
    {
        $this->authorize('create', Testimonial::class);
        $this->reset(['editingId', 'author_name', 'position', 'organization', 'content', 'sort_order']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $testimonial = Testimonial::findOrFail($id);
        $this->authorize('update', $testimonial);
        $this->editingId = $testimonial->id;
        $this->author_name = $testimonial->author_name;
        $this->position = $testimonial->position;
        $this->organization = $testimonial->organization;
        $this->content = $testimonial->content;
        $this->is_active = $testimonial->is_active;
        $this->sort_order = $testimonial->sort_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        if ($this->editingId) {
            $testimonial = Testimonial::findOrFail($this->editingId);
            $this->authorize('update', $testimonial);
            $testimonial->update($validated);
        } else {
            $this->authorize('create', Testimonial::class);
            Testimonial::create($validated);
        }

        $this->showForm = false;
        $this->reset(['editingId', 'author_name', 'position', 'organization', 'content', 'sort_order']);
        $this->is_active = true;
    }

    public function toggleActive(int $id): void
    {
        $testimonial = Testimonial::findOrFail($id);
        $this->authorize('update', $testimonial);
        $testimonial->update(['is_active' => ! $testimonial->is_active]);
    }

    public function delete(int $id): void
    {
        $testimonial = Testimonial::findOrFail($id);
        $this->authorize('delete', $testimonial);
        $testimonial->delete();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Témoignages</flux:heading>
            <flux:subheading>Citations clients affichées sur la page d’accueil.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouveau témoignage</flux:button>
    </div>

    <flux:table :paginate="$this->testimonials">
        <flux:table.columns>
            <flux:table.column>Auteur</flux:table.column>
            <flux:table.column>Extrait</flux:table.column>
            <flux:table.column>Ordre</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->testimonials as $testimonial)
                <flux:table.row wire:key="testimonial-row-{{ $testimonial->id }}">
                    <flux:table.cell variant="strong">
                        <div>{{ $testimonial->author_name }}</div>
                        @if($testimonial->position || $testimonial->organization)
                            <div class="text-xs font-normal text-zinc-500">
                                {{ trim($testimonial->position.' — '.$testimonial->organization, ' —') }}
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="line-clamp-2 max-w-md text-zinc-500">{{ $testimonial->content }}</span>
                    </flux:table.cell>
                    <flux:table.cell>{{ $testimonial->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        @if($testimonial->is_active)
                            <flux:badge size="sm" color="green">Actif</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Inactif</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="toggleActive({{ $testimonial->id }})">
                                {{ $testimonial->is_active ? 'Désactiver' : 'Activer' }}
                            </flux:button>
                            <flux:button size="sm" wire:click="edit({{ $testimonial->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $testimonial->id }})" wire:confirm="Supprimer ce témoignage ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le témoignage' : 'Nouveau témoignage' }}</flux:heading>
                <flux:subheading>Affiché sur la page d’accueil s’il est actif.</flux:subheading>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Auteur *</flux:label>
                    <flux:input wire:model="author_name" type="text" />
                    <flux:error name="author_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Fonction</flux:label>
                    <flux:input wire:model="position" type="text" placeholder="Ex. Directeur de promotion" />
                    <flux:error name="position" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Organisation</flux:label>
                <flux:input wire:model="organization" type="text" />
                <flux:error name="organization" />
            </flux:field>

            <flux:field>
                <flux:label>Citation *</flux:label>
                <flux:textarea wire:model="content" rows="4" />
                <flux:error name="content" />
            </flux:field>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Ordre</flux:label>
                    <flux:input wire:model="sort_order" type="number" min="0" />
                    <flux:error name="sort_order" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="is_active" />
                    <flux:label>Actif</flux:label>
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
