<?php

use App\Models\Sector;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Pages secteur')] class extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $intro_title = null;

    public ?string $intro_text = null;

    /** @var array<int, array{title: string, text: string}> */
    public array $cards = [];

    /** @var array<int, array{value: string, label: string}> */
    public array $figures = [];

    public ?string $cta_title = null;

    public ?string $cta_text = null;

    public ?string $cta_label = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Sector::class);
    }

    #[Computed]
    public function sectors()
    {
        return Sector::active()->ordered()->paginate(10);
    }

    public function edit(int $id): void
    {
        $sector = Sector::findOrFail($id);
        $this->authorize('update', $sector);

        $this->editingId = $sector->id;
        $this->name = $sector->name;
        $this->intro_title = $sector->page_intro_title;
        $this->intro_text = $sector->page_intro_text;
        $this->cards = $sector->page_cards ?: [];
        $this->figures = $sector->page_figures ?: [];
        $this->cta_title = $sector->page_cta_title;
        $this->cta_text = $sector->page_cta_text;
        $this->cta_label = $sector->page_cta_label;
        $this->showForm = true;
    }

    public function addCard(): void
    {
        $this->cards[] = ['title' => '', 'text' => ''];
    }

    public function removeCard(int $index): void
    {
        unset($this->cards[$index]);
        $this->cards = array_values($this->cards);
    }

    public function moveCard(int $index, string $direction): void
    {
        $this->moveItem($this->cards, $index, $direction);
    }

    public function addFigure(): void
    {
        $this->figures[] = ['value' => '', 'label' => ''];
    }

    public function removeFigure(int $index): void
    {
        unset($this->figures[$index]);
        $this->figures = array_values($this->figures);
    }

    public function moveFigure(int $index, string $direction): void
    {
        $this->moveItem($this->figures, $index, $direction);
    }

    public function save(): void
    {
        $this->validate([
            'intro_title' => ['nullable', 'string', 'max:255'],
            'intro_text' => ['nullable', 'string'],
            'cards' => ['array', 'max:8'],
            'cards.*.title' => ['nullable', 'string', 'max:255'],
            'cards.*.text' => ['nullable', 'string', 'max:500'],
            'figures' => ['array', 'max:4'],
            'figures.*.value' => ['nullable', 'string', 'max:255'],
            'figures.*.label' => ['nullable', 'string', 'max:255'],
            'cta_title' => ['nullable', 'string', 'max:255'],
            'cta_text' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:255'],
        ]);

        $sector = Sector::findOrFail($this->editingId);
        $this->authorize('update', $sector);

        $sector->update([
            'page_intro_title' => $this->intro_title ?: null,
            'page_intro_text' => $this->intro_text ?: null,
            'page_cards' => $this->cleanItems($this->cards, ['title', 'text']) ?: null,
            'page_figures' => $this->cleanItems($this->figures, ['value', 'label']) ?: null,
            'page_cta_title' => $this->cta_title ?: null,
            'page_cta_text' => $this->cta_text ?: null,
            'page_cta_label' => $this->cta_label ?: null,
        ]);

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'intro_title', 'intro_text', 'cards', 'figures', 'cta_title', 'cta_text', 'cta_label']);
    }

    /**
     * @param  array<int, array<string, string>>  $items
     * @param  array<int, string>  $keys
     * @return array<int, array<string, string>>
     */
    private function cleanItems(array $items, array $keys): array
    {
        return collect($items)
            ->map(function ($item) use ($keys) {
                $row = [];

                foreach ($keys as $key) {
                    $row[$key] = (string) ($item[$key] ?? '');
                }

                return $row;
            })
            ->filter(fn ($row) => implode('', $row) !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, string>>  $items
     */
    private function moveItem(array &$items, int $index, string $direction): void
    {
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($items[$index]) || ! isset($items[$target])) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        $items = array_values($items);
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Pages secteur</flux:heading>
        <flux:subheading>Contenus des pages dédiées : introduction, expertises, chiffres clés et appel à l’action.</flux:subheading>
    </div>

    <flux:table :paginate="$this->sectors">
        <flux:table.columns>
            <flux:table.column>Secteur</flux:table.column>
            <flux:table.column>Introduction</flux:table.column>
            <flux:table.column>Cartes</flux:table.column>
            <flux:table.column>Chiffres</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->sectors as $sector)
                <flux:table.row wire:key="sector-page-row-{{ $sector->id }}">
                    <flux:table.cell variant="strong">{{ $sector->name }}</flux:table.cell>
                    <flux:table.cell>
                        <span class="line-clamp-1 max-w-md text-zinc-500">{{ $sector->page_intro_title ?: '—' }}</span>
                    </flux:table.cell>
                    <flux:table.cell>{{ count($sector->page_cards ?? []) }}</flux:table.cell>
                    <flux:table.cell>{{ count($sector->page_figures ?? []) }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end">
                            <flux:button size="sm" wire:click="edit({{ $sector->id }})">Modifier</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[52rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">Page secteur — {{ $name }}</flux:heading>
                <flux:subheading>Les sections vides ne sont pas affichées sur le site.</flux:subheading>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Titre d’introduction</flux:label>
                    <flux:input wire:model="intro_title" type="text" />
                    <flux:error name="intro_title" />
                </flux:field>
                <flux:field>
                    <flux:label>Libellé du bouton final</flux:label>
                    <flux:input wire:model="cta_label" type="text" placeholder="Ex. Solliciter une étude technique" />
                    <flux:error name="cta_label" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Texte d’introduction</flux:label>
                <flux:textarea wire:model="intro_text" rows="4" />
                <flux:error name="intro_text" />
            </flux:field>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label>Domaines d’expertise (cartes)</flux:label>
                    <flux:button size="sm" icon="plus" wire:click="addCard">Ajouter une carte</flux:button>
                </div>
                @foreach($cards as $index => $card)
                    <div wire:key="card-{{ $index }}" class="grid gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[1fr_2fr_auto] dark:border-zinc-700">
                        <flux:input wire:model="cards.{{ $index }}.title" placeholder="Titre (ex. VRD)" />
                        <flux:input wire:model="cards.{{ $index }}.text" placeholder="Description" />
                        <div class="flex items-center gap-1">
                            <flux:button size="xs" icon="chevron-up" wire:click="moveCard({{ $index }}, 'up')" aria-label="Monter" />
                            <flux:button size="xs" icon="chevron-down" wire:click="moveCard({{ $index }}, 'down')" aria-label="Descendre" />
                            <flux:button size="xs" variant="danger" icon="trash" wire:click="removeCard({{ $index }})" aria-label="Supprimer" />
                        </div>
                    </div>
                @endforeach
                <flux:error name="cards" />
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:label>Chiffres clés (max 4, laisser vide si non disponible)</flux:label>
                    <flux:button size="sm" icon="plus" wire:click="addFigure">Ajouter un chiffre</flux:button>
                </div>
                @foreach($figures as $index => $figure)
                    <div wire:key="figure-{{ $index }}" class="grid gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[1fr_2fr_auto] dark:border-zinc-700">
                        <flux:input wire:model="figures.{{ $index }}.value" placeholder="Valeur (ex. 120)" />
                        <flux:input wire:model="figures.{{ $index }}.label" placeholder="Libellé (ex. km de voies aménagées)" />
                        <div class="flex items-center gap-1">
                            <flux:button size="xs" icon="chevron-up" wire:click="moveFigure({{ $index }}, 'up')" aria-label="Monter" />
                            <flux:button size="xs" icon="chevron-down" wire:click="moveFigure({{ $index }}, 'down')" aria-label="Descendre" />
                            <flux:button size="xs" variant="danger" icon="trash" wire:click="removeFigure({{ $index }})" aria-label="Supprimer" />
                        </div>
                    </div>
                @endforeach
                <flux:error name="figures" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Titre de l’appel à l’action</flux:label>
                    <flux:input wire:model="cta_title" type="text" />
                    <flux:error name="cta_title" />
                </flux:field>
                <flux:field>
                    <flux:label>Texte de l’appel à l’action</flux:label>
                    <flux:input wire:model="cta_text" type="text" />
                    <flux:error name="cta_text" />
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
