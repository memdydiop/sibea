<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

trait AddsMediaFromUploads
{
    /**
     * Mime types that will be stored as WebP instead of their original format.
     *
     * @var array<int, string>
     */
    protected static array $webpSourceMimeTypes = ['image/jpeg', 'image/png', 'image/bmp', 'image/avif'];

    /**
     * Add an uploaded file to a media collection. Raster images are stored as
     * WebP, everything else (SVG, PDF, …) keeps its original format, whether
     * Livewire stores temporary uploads on a local or a remote disk (S3, GCS).
     */
    protected static function addMediaFromUpload(Model $model, UploadedFile $file, string $collection): Media
    {
        $extension = preg_replace('/[^a-z0-9]/', '', strtolower((string) $file->getClientOriginalExtension())) ?? '';
        $baseName = static::uploadFileBaseName($file);

        $webpPath = static::convertUploadToWebp($file);
        $usesRemoteDisk = false;

        if ($webpPath !== null) {
            $fileAdder = FileAdderFactory::create($model, $webpPath);
        } else {
            $usesRemoteDisk = FileUploadConfiguration::isUsingS3() || FileUploadConfiguration::isUsingGCS();
            $path = (string) $file->getRealPath();

            $fileAdder = $usesRemoteDisk
                ? FileAdderFactory::createFromDisk($model, $path, FileUploadConfiguration::disk())
                : FileAdderFactory::create($model, $path);
        }

        $fileAdder->usingName($baseName);

        $fileName = $webpPath !== null
            ? "{$baseName}.webp"
            : ($extension !== '' ? "{$baseName}.{$extension}" : $baseName);

        $fileAdder->usingFileName($fileName);

        return $usesRemoteDisk
            ? $fileAdder->toMediaCollectionFromRemote($collection)
            : $fileAdder->toMediaCollection($collection);
    }

    /**
     * Build a safe, displayable file name from the uploaded file, guarding
     * against invalid UTF-8 and extremely long client file names.
     */
    protected static function uploadFileBaseName(UploadedFile $file): string
    {
        $originalName = mb_scrub((string) $file->getClientOriginalName(), 'UTF-8');

        $baseName = Str::limit(pathinfo(Str::ascii($originalName), PATHINFO_FILENAME), 200, '');

        return $baseName !== '' ? $baseName : 'fichier';
    }

    /**
     * Convert an uploaded raster image to a temporary WebP file.
     *
     * Returns null when the file is not a convertible image (or when the
     * conversion fails), so the caller falls back to the original file.
     */
    protected static function convertUploadToWebp(UploadedFile $file): ?string
    {
        if (! in_array($file->getMimeType(), static::$webpSourceMimeTypes, true)) {
            return null;
        }

        $sourcePath = tempnam(sys_get_temp_dir(), 'media-source');

        if ($sourcePath === false) {
            return null;
        }

        $contents = method_exists($file, 'get') ? $file->get() : file_get_contents((string) $file->getRealPath());

        file_put_contents($sourcePath, $contents);

        $webpPath = $sourcePath.'.webp';

        try {
            Image::load($sourcePath)->format('webp')->quality(85)->save($webpPath);
        } catch (Throwable) {
            @unlink($sourcePath);

            return null;
        }

        @unlink($sourcePath);

        return $webpPath;
    }
}
