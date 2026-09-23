<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex min-h-svh flex-col">
        <div class="grow">
            {{ $slot }}
        </div>

        <footer class="border-t border-zinc-200 bg-white px-6 py-4 text-center text-xs text-slate-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
            Copyright © {{ date('Y') }} SIBEA. Conçu avec <span class="text-red-500" aria-hidden="true">♥</span> par <a href="mailto:mendydiop@gmail.com" class="font-bold text-cuivre transition-colors hover:underline">Save&amp;Dev</a>. Tous droits réservés.
        </footer>
    </flux:main>
</x-layouts::app.sidebar>
