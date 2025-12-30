<?php

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteUnusedMediaCommand extends Command
{
    protected $signature = 'media:delete-unused';

    protected $description = 'Delete media files that are not referenced in the media table or other tables';

    public function handle(): void
    {
        // Get all files from storage/app/public directory
        $files = Storage::disk('public')->allFiles();

        // Get all media records from the database
        $mediaFiles = Media::all()->map(function ($media) {
            // Get all paths related to this media (original, conversions, responsive images)
            $paths = collect([$media->getPath()]);

            // Add conversion paths
            foreach ($media->getMediaConversionNames() as $conversion) {
                $paths->push($media->getPath($conversion));
            }

            // Add responsive image paths
            $paths->push($media->getPath('responsive-images'));

            return $paths;
        })->flatten()->filter()->toArray();

        // Find files that don't exist in the media table
        $unusedFiles = collect($files)->filter(function ($file) use ($mediaFiles) {
            // Skip .gitignore and other system files
            if (in_array($file, ['.gitignore', '.DS_Store'])) {
                return false;
            }

            // Check if file exists in media records
            return ! in_array($file, $mediaFiles);
        });

        if ($unusedFiles->isEmpty()) {
            $this->info('No unused media files found.');

            return;
        }

        $this->info('Found '.$unusedFiles->count().' unused files.');

        if ($this->confirm('Do you want to delete these files?')) {
            $unusedFiles->each(function ($file) {
                Storage::disk('public')->delete($file);
                $this->info("Deleted: {$file}");
            });

            $this->info('All unused media files have been deleted.');
        }
    }
}
