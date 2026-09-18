<?php

use App\Support\UniqueSlug;
use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Pages éditoriales')] class extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public ?string $content = null;

    public bool $is_published = true;

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    #[Computed]
    public function pages()
    {
        return Page::query()->orderBy('title')->paginate(10);
    }

    public function updatedTitle(): void
    {
        $isAutomaticSlug = $this->editingId === null;

        if (! $isAutomaticSlug) {
            $page = Page::find($this->editingId);
            $isAutomaticSlug = $page !== null
                && $this->slug === $page->slug
                && $page->slug === Str::slug($page->title);
        }

        if ($isAutomaticSlug) {
            $this->slug = UniqueSlug::for(Page::class, $this->title, $this->editingId);
        }
    }

    public function create(): void
    {
        $this->authorize('create', Page::class);
        $this->reset(['editingId', 'title', 'slug', 'content']);
        $this->is_published = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $page = Page::findOrFail($id);
        $this->authorize('update', $page);
        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->content = $page->content;
        $this->is_published = $page->is_published;
        $this->showForm = true;
    }

    public function save(): void
    {
        if (trim($this->slug) === '') {
            $this->slug = UniqueSlug::for(Page::class, $this->title, $this->editingId);
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('pages', 'slug')->ignore($this->editingId)],
            'content' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);

        if ($this->editingId) {
            $page = Page::findOrFail($this->editingId);
            $this->authorize('update', $page);
            $page->update($validated);
        } else {
            $this->authorize('create', Page::class);
            Page::create($validated);
        }

        $this->showForm = false;
        $this->reset(['editingId', 'title', 'slug', 'content']);
        $this->is_published = true;
    }

    public function togglePublish(int $id): void
    {
        $page = Page::findOrFail($id);
        $this->authorize('update', $page);
        $page->update(['is_published' => ! $page->is_published]);
    }

    public function delete(int $id): void
    {
        $page = Page::findOrFail($id);
        $this->authorize('delete', $page);
        $page->delete();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Pages éditoriales</flux:heading>
            <flux:subheading>Contenus des pages légales et pages libres (Markdown).</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouvelle page</flux:button>
    </div>

    <flux:table :paginate="$this->pages">
        <flux:table.columns>
            <flux:table.column>Titre</flux:table.column>
            <flux:table.column>Adresse</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->pages as $page)
                <flux:table.row wire:key="page-row-{{ $page->id }}">
                    <flux:table.cell variant="strong">{{ $page->title }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">/{{ $page->slug }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($page->is_published)
                            <flux:badge size="sm" color="green">Publiée</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Brouillon</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="togglePublish({{ $page->id }})">
                                {{ $page->is_published ? 'Dépublier' : 'Publier' }}
                            </flux:button>
                            <flux:button size="sm" wire:click="edit({{ $page->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $page->id }})" wire:confirm="Supprimer cette page ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[48rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier la page' : 'Nouvelle page' }}</flux:heading>
                <flux:subheading>Le contenu accepte le Markdown (titres `##`, listes, liens, gras).</flux:subheading>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Titre *</flux:label>
                    <flux:input wire:model.live.debounce.400ms="title" type="text" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Slug *</flux:label>
                    <flux:input wire:model="slug" type="text" />
                    <flux:description>Généré automatiquement depuis le titre si laissé vide.</flux:description>
                    <flux:error name="slug" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Contenu</flux:label>
                <flux:textarea wire:model="content" rows="16" class="font-mono text-sm" />
                <flux:error name="content" />
            </flux:field>

            <flux:field variant="inline">
                <flux:switch wire:model="is_published" />
                <flux:label>Publiée</flux:label>
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
