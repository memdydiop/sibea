<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait AddsMediaFromUploads
{
    /**
     * Add an uploaded file to a media collection, whether Livewire stores its
     * temporary uploads on a local disk or on a remote disk (S3, GCS).
     */
    protected static function addMediaFromUpload(Model $model, UploadedFile $file, string $collection): Media
    {
        $usesRemoteDisk = FileUploadConfiguration::isUsingS3() || FileUploadConfiguration::isUsingGCS();

        $path = (string) $file->getRealPath();

        $fileAdder = $usesRemoteDisk
            ? FileAdderFactory::createFromDisk($model, $path, FileUploadConfiguration::disk())
            : FileAdderFactory::create($model, $path);

        $fileAdder->usingFileName($file->getClientOriginalName());

        return $usesRemoteDisk
            ? $fileAdder->toMediaCollectionFromRemote($collection)
            : $fileAdder->toMediaCollection($collection);
    }
}
