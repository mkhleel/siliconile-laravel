<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExtractTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:extract 
                            {--path=* : Specific paths to scan (default: resources/views, Modules/*/resources/views)}
                            {--output=lang/ar.json : Output file path}
                            {--dry-run : Show extracted strings without saving}
                            {--merge : Merge with existing translations instead of overwriting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract translatable strings from Blade files and save to JSON translation file';

    /**
     * Patterns to match translatable text in blade files.
     *
     * @var array<string>
     */
    protected array $patterns = [
        // Text between HTML tags (excluding script, style, etc.)
        '/<(?:h[1-6]|p|span|a|button|label|li|td|th|div|strong|em|small|title)[^>]*>([^<]+)<\/(?:h[1-6]|p|span|a|button|label|li|td|th|div|strong|em|small|title)>/i',
        // Placeholder attributes
        '/placeholder=["\']([^"\']+)["\']/i',
        // Title attributes
        '/title=["\']([^"\']+)["\']/i',
        // Alt attributes
        '/alt=["\']([^"\']+)["\']/i',
        // aria-label attributes
        '/aria-label=["\']([^"\']+)["\']/i',
    ];

    /**
     * Strings to exclude from extraction.
     *
     * @var array<string>
     */
    protected array $excludePatterns = [
        '/^\s*$/',                           // Empty or whitespace only
        '/^[\d\s\.\,\-\+\(\)]+$/',           // Numbers, spaces, punctuation only
        '/^\{\{.*\}\}$/',                    // Blade echo statements
        '/^@/',                              // Blade directives
        '/^</',                              // HTML tags
        '/^https?:\/\//',                    // URLs
        '/^[a-zA-Z0-9_\-\.]+@/',             // Email addresses
        '/^\+?\d[\d\s\-]+$/',                // Phone numbers
        '/^[#\.]?[a-zA-Z0-9_\-]+$/',         // CSS classes/IDs, single words that look like code
        '/^wire:/',                          // Livewire directives
        '/^x-/',                             // Alpine directives
        '/^svg/',                            // SVG-related
        '/^M\d/',                            // SVG paths
        '/^EGP$/',                           // Currency codes
        '/^\d+%$/',                          // Percentages
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Extracting translatable strings from Blade files...');
        $this->newLine();

        $paths = $this->option('path');
        if (empty($paths)) {
            $paths = [
                resource_path('views/pages'),
                resource_path('views/components'),
                resource_path('views/livewire'),
            ];

            // Add module paths
            $modulesPath = base_path('Modules');
            if (File::isDirectory($modulesPath)) {
                foreach (File::directories($modulesPath) as $module) {
                    $moduleViews = $module.'/resources/views';
                    if (File::isDirectory($moduleViews)) {
                        $paths[] = $moduleViews;
                    }
                }
            }
        }

        $extractedStrings = [];

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                $this->warn("⚠️  Path not found: {$path}");

                continue;
            }

            $this->info("📁 Scanning: {$path}");
            $strings = $this->extractFromPath($path);
            $extractedStrings = array_merge($extractedStrings, $strings);
        }

        // Remove duplicates and sort
        $extractedStrings = array_unique($extractedStrings);
        sort($extractedStrings);

        $this->newLine();
        $this->info('📊 Found '.count($extractedStrings).' unique translatable strings');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->table(['String'], array_map(fn ($s) => [$s], $extractedStrings));

            return Command::SUCCESS;
        }

        // Build translation array (key = English string, value = English string as placeholder)
        $translations = [];
        foreach ($extractedStrings as $string) {
            $translations[$string] = $string; // Placeholder - to be translated
        }

        $outputPath = base_path($this->option('output'));

        // Merge with existing if requested
        if ($this->option('merge') && File::exists($outputPath)) {
            $existing = json_decode(File::get($outputPath), true) ?? [];
            // Keep existing translations, add new ones
            foreach ($translations as $key => $value) {
                if (! isset($existing[$key])) {
                    $existing[$key] = $value;
                }
            }
            $translations = $existing;
        }

        // Sort by keys
        ksort($translations);

        // Save to file
        File::put(
            $outputPath,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->info("✅ Translations saved to: {$outputPath}");
        $this->newLine();

        // Show sample of what was extracted
        $sample = array_slice($extractedStrings, 0, 10);
        if (! empty($sample)) {
            $this->info('📝 Sample of extracted strings:');
            foreach ($sample as $string) {
                $this->line("   • {$string}");
            }
            if (count($extractedStrings) > 10) {
                $this->line('   ... and '.(count($extractedStrings) - 10).' more');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Extract translatable strings from a path.
     *
     * @return array<string>
     */
    protected function extractFromPath(string $path): array
    {
        $strings = [];
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());
            $fileStrings = $this->extractFromContent($content);

            if (! empty($fileStrings)) {
                $this->line("   📄 {$file->getFilename()}: ".count($fileStrings).' strings');
            }

            $strings = array_merge($strings, $fileStrings);
        }

        return $strings;
    }

    /**
     * Extract translatable strings from content.
     *
     * @return array<string>
     */
    protected function extractFromContent(string $content): array
    {
        $strings = [];

        // Remove PHP code blocks
        $content = preg_replace('/<\?php[\s\S]*?\?>/i', '', $content);

        // Remove Blade comments
        $content = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $content);

        // Remove script and style tags
        $content = preg_replace('/<script[\s\S]*?<\/script>/i', '', $content);
        $content = preg_replace('/<style[\s\S]*?<\/style>/i', '', $content);

        // Remove SVG content
        $content = preg_replace('/<svg[\s\S]*?<\/svg>/i', '', $content);

        // Extract text from various HTML elements
        $this->extractTextBetweenTags($content, $strings);

        // Extract from attributes
        $this->extractFromAttributes($content, $strings);

        // Clean and filter strings
        $strings = array_map('trim', $strings);
        $strings = array_filter($strings, fn ($s) => $this->isTranslatable($s));
        $strings = array_map(fn ($s) => $this->cleanString($s), $strings);
        $strings = array_filter($strings, fn ($s) => strlen($s) > 2 && strlen($s) < 500);

        return array_values(array_unique($strings));
    }

    /**
     * Extract text between HTML tags.
     *
     * @param  array<string>  $strings
     */
    protected function extractTextBetweenTags(string $content, array &$strings): void
    {
        // Match text content between tags (simplified approach)
        $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'a', 'button', 'label', 'li', 'td', 'th', 'strong', 'em', 'small', 'div'];

        foreach ($tags as $tag) {
            // Match opening tag, capture content, match closing tag
            preg_match_all("/<{$tag}[^>]*>([^<]*)<\/{$tag}>/i", $content, $matches);
            if (! empty($matches[1])) {
                $strings = array_merge($strings, $matches[1]);
            }
        }

        // Also extract text from title elements in head
        preg_match_all('/<title[^>]*>([^<]+)<\/title>/i', $content, $matches);
        if (! empty($matches[1])) {
            $strings = array_merge($strings, $matches[1]);
        }
    }

    /**
     * Extract text from HTML attributes.
     *
     * @param  array<string>  $strings
     */
    protected function extractFromAttributes(string $content, array &$strings): void
    {
        $attributes = ['placeholder', 'title', 'alt', 'aria-label', 'data-placeholder'];

        foreach ($attributes as $attr) {
            preg_match_all("/{$attr}=[\"']([^\"']+)[\"']/i", $content, $matches);
            if (! empty($matches[1])) {
                $strings = array_merge($strings, $matches[1]);
            }
        }
    }

    /**
     * Check if a string should be translated.
     */
    protected function isTranslatable(string $string): bool
    {
        $string = trim($string);

        if (empty($string)) {
            return false;
        }

        // Check exclusion patterns
        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $string)) {
                return false;
            }
        }

        // Must contain at least one letter
        if (! preg_match('/[a-zA-Z]/', $string)) {
            return false;
        }

        // Skip if it looks like a Blade variable or directive
        if (str_contains($string, '{{') || str_contains($string, '@') || str_contains($string, '$')) {
            return false;
        }

        // Skip if it's already using translation function
        if (str_contains($string, '__(') || str_contains($string, 'trans(')) {
            return false;
        }

        return true;
    }

    /**
     * Clean a string for use as a translation key.
     */
    protected function cleanString(string $string): string
    {
        // Normalize whitespace
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string);

        // Remove leading/trailing quotes if present
        $string = trim($string, '"\'');

        return $string;
    }
}
