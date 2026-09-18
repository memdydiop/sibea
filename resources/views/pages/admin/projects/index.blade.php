<?php

use App\Concerns\AddsMediaFromUploads;
use App\Enums\ProjectStatus;
use App\Models\Expertise;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Réalisations')] class extends Component
{
    use AddsMediaFromUploads, WithFileUploads, WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public ?string $short_description = null;

    public ?string $description = null;

    public ?string $challenge = null;

    public ?string $solution = null;

    public ?string $impact = null;

    public ?string $location = null;

    public ?string $project_date = null;

    public ?string $status = null;

    public ?string $duration = null;

    public ?string $surface = null;

    public ?string $budget = null;

    public ?string $client_name = null;

    public bool $client_publishable = false;

    public ?string $testimonial_quote = null;

    public ?string $testimonial_author = null;

    public ?string $latitude = null;

    public ?string $longitude = null;

    public ?string $results_text = null;

    public bool $is_active = true;

    public bool $is_published = false;

    /** @var array<int> */
    public array $sector_ids = [];

    /** @var array<int> */
    public array $expertise_ids = [];

    /** @var array<int> */
    public array $service_ids = [];

    public $cover = null;

    /** @var array */
    public array $gallery = [];

    /** @var array */
    public array $documents = [];

    public ?string $coverPreview = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    #[Computed]
    public function projects()
    {
        return Project::query()
            ->with('sectors')
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function sectors()
    {
        return Sector::active()->ordered()->get();
    }

    #[Computed]
    public function expertises()
    {
        return Expertise::active()->ordered()->get();
    }

    #[Computed]
    public function services()
    {
        return Service::active()->ordered()->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTitle(): void
    {
        if ($this->editingId === null) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function create(): void
    {
        $this->authorize('create', Project::class);
        $this->reset(['editingId', 'title', 'slug', 'short_description', 'description', 'challenge', 'solution', 'impact', 'location', 'project_date', 'status', 'duration', 'surface', 'budget', 'client_name', 'testimonial_quote', 'testimonial_author', 'latitude', 'longitude', 'results_text', 'sector_ids', 'expertise_ids', 'service_ids', 'cover', 'gallery', 'documents', 'coverPreview']);
        $this->is_active = true;
        $this->client_publishable = false;
        $this->is_published = false;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $project = Project::with(['sectors', 'expertises', 'services'])->findOrFail($id);
        $this->authorize('update', $project);
        $this->editingId = $project->id;
        $this->title = $project->title;
        $this->slug = $project->slug;
        $this->short_description = $project->short_description;
        $this->description = $project->description;
        $this->challenge = $project->challenge;
        $this->solution = $project->solution;
        $this->impact = $project->impact;
        $this->location = $project->location;
        $this->project_date = $project->project_date?->format('Y-m-d');
        $this->status = $project->status?->value;
        $this->duration = $project->duration;
        $this->surface = $project->surface;
        $this->budget = $project->budget;
        $this->client_name = $project->client_name;
        $this->client_publishable = $project->client_publishable;
        $this->testimonial_quote = $project->testimonial_quote;
        $this->testimonial_author = $project->testimonial_author;
        $this->latitude = $project->latitude !== null ? (string) $project->latitude : null;
        $this->longitude = $project->longitude !== null ? (string) $project->longitude : null;
        $this->results_text = $project->results ? implode("\n", $project->results) : null;
        $this->is_active = $project->is_active;
        $this->is_published = $project->is_published;
        $this->sector_ids = $project->sectors->pluck('id')->all();
        $this->expertise_ids = $project->expertises->pluck('id')->all();
        $this->service_ids = $project->services->pluck('id')->all();
        $this->coverPreview = $project->getFirstMediaUrl('cover') ?: null;
        $this->reset(['cover', 'gallery', 'documents']);
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($this->editingId)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'challenge' => ['nullable', 'string'],
            'solution' => ['nullable', 'string'],
            'impact' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'project_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'duration' => ['nullable', 'string', 'max:255'],
            'surface' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_publishable' => ['boolean'],
            'testimonial_quote' => ['nullable', 'string'],
            'testimonial_author' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'results_text' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_published' => ['boolean'],
            'sector_ids' => ['array'],
            'sector_ids.*' => ['exists:sectors,id'],
            'expertise_ids' => ['array'],
            'expertise_ids.*' => ['exists:expertises,id'],
            'service_ids' => ['array'],
            'service_ids.*' => ['exists:services,id'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'gallery' => ['array', 'max:10'],
            'gallery.*' => ['image', 'max:5120'],
            'documents' => ['array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf', 'max:10240'],
        ]);

        $results = collect(preg_split('/\r?\n/', $validated['results_text'] ?? ''))
            ->map(fn ($line) => trim($line))->filter()->values()->all();

        $data = $validated;
        $data['results'] = $results ?: null;
        unset($data['results_text'], $data['cover'], $data['gallery'], $data['documents'], $data['sector_ids'], $data['expertise_ids'], $data['service_ids']);

        if ($this->editingId) {
            $project = Project::findOrFail($this->editingId);
            $this->authorize('update', $project);
            $project->update($data);
        } else {
            $this->authorize('create', Project::class);
            $project = Project::create($data);
        }

        $project->sectors()->sync($validated['sector_ids'] ?? []);
        $project->expertises()->sync($validated['expertise_ids'] ?? []);
        $project->services()->sync($validated['service_ids'] ?? []);

        if ($this->cover) {
            $project->clearMediaCollection('cover');
            static::addMediaFromUpload($project, $this->cover, 'cover');
        }
        foreach ($this->gallery as $photo) {
            static::addMediaFromUpload($project, $photo, 'gallery');
        }
        foreach ($this->documents as $document) {
            static::addMediaFromUpload($project, $document, 'documents');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'title', 'slug', 'short_description', 'description', 'challenge', 'solution', 'impact', 'location', 'project_date', 'status', 'duration', 'surface', 'budget', 'client_name', 'testimonial_quote', 'testimonial_author', 'latitude', 'longitude', 'results_text', 'sector_ids', 'expertise_ids', 'service_ids', 'cover', 'gallery', 'documents', 'coverPreview']);
        $this->is_active = true;
        $this->client_publishable = false;
        $this->is_published = false;
    }

    public function togglePublish(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);
        $project->update(['is_published' => ! $project->is_published]);
    }

    public function delete(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project);
        $project->delete();
    }

    #[Computed]
    public function editingProject(): ?Project
    {
        if ($this->editingId === null) {
            return null;
        }

        return Project::with('media')->find($this->editingId);
    }

    public function removeCover(): void
    {
        $project = $this->editingProject;

        if ($project === null) {
            return;
        }

        $this->authorize('update', $project);
        $project->clearMediaCollection('cover');
        $this->coverPreview = null;
        unset($this->editingProject);
    }

    public function deleteMedia(int $mediaId): void
    {
        $project = $this->editingProject;

        if ($project === null) {
            return;
        }

        $this->authorize('update', $project);
        $project->media()->whereKey($mediaId)->first()?->delete();
        unset($this->editingProject);
    }

    public function moveMedia(int $mediaId, string $direction): void
    {
        $project = $this->editingProject;

        if ($project === null || ! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $this->authorize('update', $project);

        $media = $project->media()->whereKey($mediaId)->first();

        if ($media === null) {
            return;
        }

        $siblings = $project->getMedia($media->collection_name)->values();
        $index = $siblings->search(fn ($item) => $item->id === $media->id);
        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $targetIndex < 0 || $targetIndex >= $siblings->count()) {
            return;
        }

        $siblings->each(fn ($item, $position) => $item->updateQuietly(['order_column' => $position + 1]));

        $target = $siblings[$targetIndex];
        $media->updateQuietly(['order_column' => $targetIndex + 1]);
        $target->updateQuietly(['order_column' => $index + 1]);

        unset($this->editingProject);
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Réalisations</flux:heading>
            <flux:subheading>Projets publiés sur le site vitrine.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouvelle réalisation</flux:button>
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input wire:model.live="search" type="search" placeholder="Rechercher une réalisation…" />
    </div>

    <flux:table :paginate="$this->projects">
        <flux:table.columns>
            <flux:table.column>Titre</flux:table.column>
            <flux:table.column>Secteurs</flux:table.column>
            <flux:table.column>Publication</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->projects as $project)
                <flux:table.row wire:key="project-row-{{ $project->id }}">
                    <flux:table.cell variant="strong">{{ $project->title }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @foreach($project->sectors as $sector)
                                <flux:badge size="sm" color="zinc">{{ $sector->name }}</flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($project->is_published)
                            <flux:badge size="sm" color="green">Publié</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Brouillon</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="togglePublish({{ $project->id }})">
                                {{ $project->is_published ? 'Dépublier' : 'Publier' }}
                            </flux:button>
                            <flux:button size="sm" wire:click="edit({{ $project->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $project->id }})" wire:confirm="Supprimer cette réalisation ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[46rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier la réalisation' : 'Nouvelle réalisation' }}</flux:heading>
                <flux:subheading>Contenus, visuels (max 5 Mo) et associations.</flux:subheading>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Titre *</flux:label>
                    <flux:input wire:model="title" type="text" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Slug *</flux:label>
                    <flux:input wire:model="slug" type="text" />
                    <flux:error name="slug" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Description courte</flux:label>
                <flux:textarea wire:model="short_description" rows="2" />
                <flux:error name="short_description" />
            </flux:field>

            <flux:field>
                <flux:label>Description complète</flux:label>
                <flux:textarea wire:model="description" rows="4" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>Contexte & enjeu</flux:label>
                <flux:textarea wire:model="challenge" rows="3" />
                <flux:error name="challenge" />
            </flux:field>

            <flux:field>
                <flux:label>Notre réponse</flux:label>
                <flux:textarea wire:model="solution" rows="3" />
                <flux:error name="solution" />
            </flux:field>

            <flux:field>
                <flux:label>Impact</flux:label>
                <flux:textarea wire:model="impact" rows="3" />
                <flux:error name="impact" />
            </flux:field>

            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Lieu</flux:label>
                    <flux:input wire:model="location" type="text" />
                    <flux:error name="location" />
                </flux:field>

                <flux:field>
                    <flux:label>Date du projet</flux:label>
                    <input type="date" wire:model="project_date" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800">
                    <flux:error name="project_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Statut</flux:label>
                    <flux:select wire:model="status">
                        <option value="">Non précisé</option>
                        @foreach(ProjectStatus::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Durée</flux:label>
                    <flux:input wire:model="duration" type="text" placeholder="Ex. 18 mois" />
                    <flux:error name="duration" />
                </flux:field>

                <flux:field>
                    <flux:label>Surface</flux:label>
                    <flux:input wire:model="surface" type="text" placeholder="Ex. 4 200 m²" />
                    <flux:error name="surface" />
                </flux:field>

                <flux:field>
                    <flux:label>Budget</flux:label>
                    <flux:input wire:model="budget" type="text" placeholder="Ex. 2,4 milliards FCFA" />
                    <flux:error name="budget" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Client</flux:label>
                    <flux:input wire:model="client_name" type="text" />
                    <flux:error name="client_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Résultats (un par ligne)</flux:label>
                    <flux:textarea wire:model="results_text" rows="2" />
                    <flux:error name="results_text" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Auteur du témoignage</flux:label>
                    <flux:input wire:model="testimonial_author" type="text" placeholder="Ex. Directeur de promotion" />
                    <flux:error name="testimonial_author" />
                </flux:field>

                <flux:field>
                    <flux:label>Citation</flux:label>
                    <flux:textarea wire:model="testimonial_quote" rows="2" />
                    <flux:error name="testimonial_quote" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Latitude</flux:label>
                    <flux:input wire:model="latitude" type="text" placeholder="Ex. 5.3540" />
                    <flux:error name="latitude" />
                </flux:field>

                <flux:field>
                    <flux:label>Longitude</flux:label>
                    <flux:input wire:model="longitude" type="text" placeholder="Ex. -3.9861" />
                    <flux:error name="longitude" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Image de couverture (max 5 Mo)</flux:label>
                    @if($coverPreview)
                        <img src="{{ $coverPreview }}" alt="" class="h-16 w-24 object-cover rounded mb-2">
                    @endif
                    <input type="file" wire:model="cover" accept="image/*" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                    <flux:error name="cover" />
                </flux:field>

                <flux:field>
                    <flux:label>Galerie (max 10 images, 5 Mo chacune)</flux:label>
                    <input type="file" wire:model="gallery" accept="image/*" multiple class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                    <flux:error name="gallery" />
                </flux:field>

                <flux:field>
                    <flux:label>Documents PDF (max 5, 10 Mo chacun)</flux:label>
                    <input type="file" wire:model="documents" accept="application/pdf" multiple class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                    <flux:error name="documents" />
                </flux:field>
            </div>

            @if($this->editingProject && ($this->editingProject->hasMedia('cover') || $this->editingProject->hasMedia('gallery') || $this->editingProject->hasMedia('documents')))
                <div class="space-y-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="text-sm font-semibold">Médias existants</div>

                    @if($existingCover = $this->editingProject->getFirstMedia('cover'))
                        <div class="flex items-center gap-3">
                            <img src="{{ $existingCover->getUrl('thumb') }}" alt="" class="h-12 w-20 rounded object-cover">
                            <span class="text-sm text-zinc-500">Couverture</span>
                            <flux:button size="xs" variant="danger" class="ml-auto" wire:click="removeCover" wire:confirm="Retirer la couverture ?">Retirer</flux:button>
                        </div>
                    @endif

                    @if($this->editingProject->getMedia('gallery')->isNotEmpty())
                        <div>
                            <div class="mb-2 text-xs text-zinc-500">Galerie — déplacer ou supprimer</div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach($this->editingProject->getMedia('gallery') as $media)
                                    <div wire:key="gallery-media-{{ $media->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-1">
                                        <img src="{{ $media->getUrl('thumb') }}" alt="" class="h-16 w-full rounded object-cover">
                                        <div class="mt-1 flex items-center justify-between gap-1">
                                            <flux:button size="xs" icon="chevron-left" wire:click="moveMedia({{ $media->id }}, 'up')" aria-label="Déplacer vers la gauche" />
                                            <flux:button size="xs" icon="chevron-right" wire:click="moveMedia({{ $media->id }}, 'down')" aria-label="Déplacer vers la droite" />
                                            <flux:button size="xs" variant="danger" icon="trash" wire:click="deleteMedia({{ $media->id }})" wire:confirm="Supprimer cette image ?" aria-label="Supprimer l’image" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($this->editingProject->getMedia('documents')->isNotEmpty())
                        <div>
                            <div class="mb-2 text-xs text-zinc-500">Documents</div>
                            <ul class="space-y-2">
                                @foreach($this->editingProject->getMedia('documents') as $document)
                                    <li wire:key="document-media-{{ $document->id }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm">
                                        <span class="flex-1 truncate">{{ $document->name }}</span>
                                        <span class="text-xs text-zinc-500">{{ $document->human_readable_size }}</span>
                                        <flux:button size="xs" variant="danger" wire:click="deleteMedia({{ $document->id }})" wire:confirm="Supprimer ce document ?">Supprimer</flux:button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Secteurs</flux:label>
                    <div class="grid gap-2 mt-1">
                        @foreach($this->sectors as $sector)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="sector_ids" :value="$sector->id" />
                                {{ $sector->name }}
                            </label>
                        @endforeach
                    </div>
                </flux:field>

                <flux:field>
                    <flux:label>Expertises</flux:label>
                    <div class="grid gap-2 mt-1">
                        @foreach($this->expertises as $expertise)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="expertise_ids" :value="$expertise->id" />
                                {{ $expertise->name }}
                            </label>
                        @endforeach
                    </div>
                </flux:field>

                <flux:field>
                    <flux:label>Services</flux:label>
                    <div class="grid gap-2 mt-1">
                        @foreach($this->services as $service)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="service_ids" :value="$service->id" />
                                {{ $service->name }}
                            </label>
                        @endforeach
                    </div>
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <flux:field variant="inline">
                    <flux:switch wire:model="is_active" />
                    <flux:label>Actif</flux:label>
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="is_published" />
                    <flux:label>Publié</flux:label>
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="client_publishable" />
                    <flux:label>Client diffusable</flux:label>
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
