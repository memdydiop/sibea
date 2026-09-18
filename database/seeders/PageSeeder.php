<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('site.pages', []) as $slug => $page) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'is_published' => true,
                ],
            );
        }
    }
}
