<?php

namespace Database\Seeders;

use App\Models\Expertise;
use App\Models\Project;
use App\Models\Sector;
use GdImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Images de DÉMONSTRATION — à remplacer par de vrais visuels.
 *
 * Réutilise les photos de public/images/heroes et génère des visuels
 * abstraits aux couleurs de la marque pour les thèmes sans photo.
 * À lancer à la main en dev (php artisan db:seed --class=DemoMediaSeeder).
 */
class DemoMediaSeeder extends Seeder
{
    private string $directory;

    /** @var array<string, string> */
    private array $cache = [];

    public function seedSectors(): void
    {
        $images = [
            'btp' => $this->photo('secteurs.jpg'),
            'immobilier' => $this->photo('realisations.jpg'),
            'energie' => $this->generate('sector-energie', 'energie', 1600, 900),
            'agro-industrie' => $this->generate('sector-agro', 'agro', 1600, 900),
        ];

        foreach (Sector::all() as $sector) {
            if ($sector->hasMedia('hero')) {
                continue;
            }

            $sector->addMedia($images[$sector->slug] ?? $this->generate('sector-'.$sector->slug, 'btp', 1600, 900))
                ->preservingOriginal()
                ->usingFileName($sector->slug.'-hero.jpg')
                ->toMediaCollection('hero');
        }
    }

    public function seedExpertises(): void
    {
        $covers = [
            'etudes-ingenierie' => $this->photo('contact.jpg'),
            'construction-realisation' => $this->photo('secteurs.jpg'),
            'developpement-immobilier' => $this->photo('realisations.jpg'),
            'solutions-energetiques' => $this->generate('expertise-energie', 'energie', 1600, 1000),
            'solutions-agro-industrielles' => $this->generate('expertise-agro', 'agro', 1600, 1000),
        ];

        foreach (Expertise::all() as $expertise) {
            if (! $expertise->hasMedia('cover')) {
                $expertise->addMedia($covers[$expertise->slug] ?? $this->generate('expertise-'.$expertise->slug, 'immobilier', 1600, 1000))
                    ->preservingOriginal()
                    ->usingFileName($expertise->slug.'-cover.jpg')
                    ->toMediaCollection('cover');
            }

            if (! $expertise->hasMedia('icon')) {
                $expertise->addMedia($this->generateIcon('expertise-'.$expertise->slug, $this->themeForExpertise($expertise->slug)))
                    ->preservingOriginal()
                    ->usingFileName($expertise->slug.'-icon.jpg')
                    ->toMediaCollection('icon');
            }
        }
    }

    public function seedProjects(): void
    {
        $covers = [
            'residence-les-palmiers-cocody' => $this->photo('realisations.jpg'),
            'centrale-solaire-500-kwc-yamoussoukro' => $this->generate('project-centrale', 'energie', 1600, 1000),
            'rizerie-moderne-daloa' => $this->generate('project-rizerie', 'agro', 1600, 1000),
        ];

        $galleries = [
            'residence-les-palmiers-cocody' => ['secteurs.jpg', 'galerie-immobilier', 'galerie-btp'],
            'centrale-solaire-500-kwc-yamoussoukro' => ['galerie-energie-1', 'galerie-energie-2', 'galerie-energie-3'],
            'rizerie-moderne-daloa' => ['galerie-agro-1', 'galerie-agro-2', 'galerie-agro-3'],
        ];

        foreach (Project::all() as $index => $project) {
            if (! $project->hasMedia('cover')) {
                $project->addMedia($covers[$project->slug] ?? $this->generate('project-'.$project->slug, 'btp', 1600, 1000))
                    ->preservingOriginal()
                    ->usingFileName($project->slug.'-cover.jpg')
                    ->toMediaCollection('cover');
            }

            if ($project->hasMedia('gallery')) {
                continue;
            }

            foreach ($galleries[$project->slug] ?? [] as $position => $source) {
                $path = str_starts_with($source, 'galerie-')
                    ? $this->generate('gallery-'.$source, $this->themeForGallery($project->slug, $position), 1400, 1000)
                    : $this->photo($source);

                $project->addMedia($path)
                    ->preservingOriginal()
                    ->usingFileName($project->slug.'-'.($position + 1).'.jpg')
                    ->toMediaCollection('gallery');
            }
        }
    }

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->directory = sys_get_temp_dir().'/sibea-demo-'.uniqid();
        File::ensureDirectoryExists($this->directory);

        $this->seedSectors();
        $this->seedExpertises();
        $this->seedProjects();

        File::deleteDirectory($this->directory);
    }

    private function photo(string $name): string
    {
        return public_path('images/heroes/'.$name);
    }

    private function themeForExpertise(string $slug): string
    {
        return match ($slug) {
            'construction-realisation' => 'btp',
            'developpement-immobilier' => 'immobilier',
            'solutions-energetiques' => 'energie',
            'solutions-agro-industrielles' => 'agro',
            default => 'immobilier',
        };
    }

    private function themeForGallery(string $slug, int $position): string
    {
        if (str_contains($slug, 'solaire')) {
            return 'energie';
        }

        if (str_contains($slug, 'rizerie')) {
            return 'agro';
        }

        return $position % 2 === 0 ? 'immobilier' : 'btp';
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private function themeColors(string $theme): array
    {
        return match ($theme) {
            'btp' => [[0x0B, 0x1F, 0x33], [0xE5, 0x8A, 0x32]],
            'immobilier' => [[0x08, 0x14, 0x22], [0x1F, 0x4E, 0x79]],
            'energie' => [[0x0B, 0x1F, 0x33], [0xF2, 0xB3, 0x00]],
            'agro' => [[0x0B, 0x1F, 0x33], [0x3F, 0x7A, 0x3F]],
            default => [[0x0B, 0x1F, 0x33], [0x64, 0x74, 0x8B]],
        };
    }

    private function color(GdImage $image, int $red, int $green, int $blue, int $alpha = 0): int
    {
        $color = imagecolorallocatealpha(
            $image,
            max(0, min(255, $red)),
            max(0, min(255, $green)),
            max(0, min(255, $blue)),
            max(0, min(127, $alpha)),
        );

        if ($color === false) {
            throw new RuntimeException('Impossible d’allouer une couleur GD.');
        }

        return $color;
    }

    private function generate(string $key, string $theme, int $width, int $height): string
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        imagealphablending($image, true);

        [$from, $to] = $this->themeColors($theme);
        $this->paintGradient($image, $width, $height, $from, $to);
        $this->paintDots($image, $width, $height);
        $this->paintShapes($image, $theme, $width, $height);

        $path = $this->directory.'/'.$key.'.jpg';
        imagejpeg($image, $path, 88);
        imagedestroy($image);

        return $this->cache[$key] = $path;
    }

    private function generateIcon(string $key, string $theme): string
    {
        if (isset($this->cache[$key.'-icon'])) {
            return $this->cache[$key.'-icon'];
        }

        $size = 512;
        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, true);

        [$from, $to] = $this->themeColors($theme);
        $this->paintGradient($image, $size, $size, $from, $to);

        $soft = $this->color($image, 255, 255, 255, 112);
        imagefilledellipse($image, 160, 160, 150, 150, $soft);
        imagefilledrectangle($image, 300, 210, 400, 400, $soft);
        imagefilledrectangle($image, 130, 330, 280, 400, $soft);

        $path = $this->directory.'/'.$key.'-icon.jpg';
        imagejpeg($image, $path, 88);
        imagedestroy($image);

        return $this->cache[$key.'-icon'] = $path;
    }

    /**
     * @param  array<int, int>  $from
     * @param  array<int, int>  $to
     */
    private function paintGradient(GdImage $image, int $width, int $height, array $from, array $to): void
    {
        for ($y = 0; $y < $height; $y++) {
            $ratio = $height > 1 ? $y / ($height - 1) : 0;
            $color = $this->color(
                $image,
                (int) round($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) round($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) round($from[2] + ($to[2] - $from[2]) * $ratio),
            );

            imagefilledrectangle($image, 0, $y, $width, $y, $color);
        }
    }

    private function paintDots(GdImage $image, int $width, int $height): void
    {
        $dot = $this->color($image, 255, 255, 255, 120);

        for ($x = 40; $x < $width; $x += 56) {
            for ($y = 40; $y < $height; $y += 56) {
                imagefilledellipse($image, $x, $y, 4, 4, $dot);
            }
        }
    }

    private function paintShapes(GdImage $image, string $theme, int $width, int $height): void
    {
        $strong = $this->color($image, 255, 255, 255, 96);
        $soft = $this->color($image, 255, 255, 255, 112);

        match ($theme) {
            'btp' => $this->paintConstruction($image, $width, $height, $strong, $soft),
            'immobilier' => $this->paintSkyline($image, $width, $height, $strong, $soft),
            'energie' => $this->paintEnergy($image, $width, $height, $strong),
            'agro' => $this->paintFields($image, $width, $height, $strong),
            default => null,
        };
    }

    private function paintConstruction(GdImage $image, int $width, int $height, int $strong, int $soft): void
    {
        imagefilledrectangle($image, (int) ($width * 0.08), (int) ($height * 0.56), (int) ($width * 0.30), $height, $soft);
        imagefilledrectangle($image, (int) ($width * 0.34), (int) ($height * 0.44), (int) ($width * 0.52), $height, $strong);
        imagefilledrectangle($image, (int) ($width * 0.72), (int) ($height * 0.16), (int) ($width * 0.745), (int) ($height * 0.82), $strong);
        imagefilledrectangle($image, (int) ($width * 0.54), (int) ($height * 0.19), (int) ($width * 0.90), (int) ($height * 0.235), $strong);
        imagefilledrectangle($image, (int) ($width * 0.57), (int) ($height * 0.235), (int) ($width * 0.60), (int) ($height * 0.36), $soft);
    }

    private function paintSkyline(GdImage $image, int $width, int $height, int $strong, int $soft): void
    {
        imagefilledrectangle($image, (int) ($width * 0.10), (int) ($height * 0.50), (int) ($width * 0.24), $height, $soft);
        imagefilledrectangle($image, (int) ($width * 0.26), (int) ($height * 0.32), (int) ($width * 0.40), $height, $strong);
        imagefilledrectangle($image, (int) ($width * 0.42), (int) ($height * 0.58), (int) ($width * 0.54), $height, $soft);
        imagefilledrectangle($image, (int) ($width * 0.56), (int) ($height * 0.40), (int) ($width * 0.70), $height, $strong);
        imagefilledrectangle($image, (int) ($width * 0.72), (int) ($height * 0.62), (int) ($width * 0.86), $height, $soft);
    }

    private function paintEnergy(GdImage $image, int $width, int $height, int $strong): void
    {
        imagefilledellipse($image, (int) ($width * 0.74), (int) ($height * 0.26), (int) ($width * 0.15), (int) ($width * 0.15), $strong);

        for ($panel = 0; $panel < 3; $panel++) {
            $offset = (int) ($width * (0.16 + $panel * 0.22));

            imagefilledpolygon($image, [
                $offset, (int) ($height * 0.66),
                $offset + (int) ($width * 0.16), (int) ($height * 0.66),
                $offset + (int) ($width * 0.13), (int) ($height * 0.84),
                $offset + (int) ($width * 0.03), (int) ($height * 0.84),
            ], $strong);
        }
    }

    private function paintFields(GdImage $image, int $width, int $height, int $strong): void
    {
        imagefilledellipse($image, (int) ($width * 0.78), (int) ($height * 0.24), (int) ($width * 0.12), (int) ($width * 0.12), $strong);

        $fieldOne = $this->color($image, 0x4E, 0x8C, 0x4A, 70);
        $fieldTwo = $this->color($image, 0x3F, 0x7A, 0x3F, 88);

        imagefilledellipse($image, (int) ($width * 0.1), (int) ($height * 1.05), (int) ($width * 1.1), (int) ($height * 0.62), $fieldOne);
        imagefilledellipse($image, (int) ($width * 0.85), (int) ($height * 1.10), (int) ($width * 1.0), (int) ($height * 0.58), $fieldTwo);
    }
}
