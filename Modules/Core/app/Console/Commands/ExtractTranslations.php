<?php

declare(strict_types=1);

namespace Modules\Core\Console\Commands;

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
                            {--merge : Merge with existing translations instead of overwriting}
                            {--wrap : Wrap extracted strings in source files with __() function}
                            {--wrap-dry-run : Preview wrap changes without modifying files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract translatable strings from Blade files and save to JSON translation file';

    /**
     * Track files and their replacements for wrapping.
     *
     * @var array<string, array<array{original: string, replacement: string, line: int}>>
     */
    protected array $fileReplacements = [];

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
        '/^wire:/',                          // Livewire directives
        '/^x-/',                             // Alpine directives
        '/^svg/',                            // SVG-related
        '/^M\d/',                            // SVG paths
        '/^EGP$/',                           // Currency codes
        '/^\d+%$/',                          // Percentages
        '/^[a-z]+_[a-z_]+$/',                // snake_case identifiers
        '/^[a-z]+\.[a-z\.]+$/',              // Dot notation keys
        '/^[\(\)\[\]\{\}]+$/',               // Brackets only
        '/^\)[\s\w]*\(/',                    // Fragments like ") (OVERDUE)"
        '/^&[a-z]+;$/',                      // HTML entities like &copy;
        '/^[\.\,\;\:\!\?•]+$/',              // Punctuation only
        '/^[A-Z]{2,5}\s*\($/',               // Fragments like "VAT ("
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
        $this->fileReplacements = [];

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

        // Handle wrapping
        if ($this->option('wrap') || $this->option('wrap-dry-run')) {
            $this->newLine();
            $this->handleWrapping();
        }

        return Command::SUCCESS;
    }

    /**
     * Handle wrapping strings in source files with __() function.
     */
    protected function handleWrapping(): void
    {
        $totalReplacements = array_sum(array_map('count', $this->fileReplacements));

        if ($totalReplacements === 0) {
            $this->info('✨ No strings need wrapping - all strings are already translated!');

            return;
        }

        $this->info("🔄 Found {$totalReplacements} strings to wrap in ".count($this->fileReplacements).' files');
        $this->newLine();

        $isDryRun = $this->option('wrap-dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN - showing changes without modifying files:');
            $this->newLine();
        }

        foreach ($this->fileReplacements as $filePath => $replacements) {
            $relativePath = str_replace(base_path().'/', '', $filePath);
            $this->info("📄 {$relativePath}");

            if ($isDryRun) {
                foreach ($replacements as $replacement) {
                    $this->line("   Line {$replacement['line']}:");
                    $this->line("   <fg=red>- {$replacement['original']}</>");
                    $this->line("   <fg=green>+ {$replacement['replacement']}</>");
                }
            } else {
                $this->applyReplacements($filePath, $replacements);
                $this->line('   ✅ Applied '.count($replacements).' replacements');
            }
        }

        if (! $isDryRun) {
            $this->newLine();
            $this->info("✅ Wrapped {$totalReplacements} strings in ".count($this->fileReplacements).' files');
        }
    }

    /**
     * Apply replacements to a file.
     *
     * @param  array<array{original: string, replacement: string, line: int}>  $replacements
     */
    protected function applyReplacements(string $filePath, array $replacements): void
    {
        $content = File::get($filePath);

        // Sort replacements by length (longest first) to avoid partial replacements
        usort($replacements, fn ($a, $b) => strlen($b['original']) - strlen($a['original']));

        foreach ($replacements as $replacement) {
            // Use exact string replacement (only first occurrence per iteration)
            $pos = strpos($content, $replacement['original']);
            if ($pos !== false) {
                $content = substr_replace($content, $replacement['replacement'], $pos, strlen($replacement['original']));
            }
        }

        File::put($filePath, $content);
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

            $filePath = $file->getPathname();
            $content = File::get($filePath);
            $fileStrings = $this->extractFromContent($content, $filePath);

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
    protected function extractFromContent(string $content, string $filePath = ''): array
    {
        $strings = [];
        $originalContent = $content;

        // Remove PHP code blocks (but preserve for context checking)
        $contentWithoutPhp = preg_replace('/<\?php[\s\S]*?\?>/i', '', $content);

        // Remove Blade comments
        $contentWithoutPhp = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $contentWithoutPhp);

        // Remove script and style tags
        $contentWithoutPhp = preg_replace('/<script[\s\S]*?<\/script>/i', '', $contentWithoutPhp);
        $contentWithoutPhp = preg_replace('/<style[\s\S]*?<\/style>/i', '', $contentWithoutPhp);

        // Remove SVG content
        $contentWithoutPhp = preg_replace('/<svg[\s\S]*?<\/svg>/i', '', $contentWithoutPhp);

        // Extract text from various sources
        $this->extractTextBetweenTags($contentWithoutPhp, $strings, $originalContent, $filePath);
        $this->extractFromAttributes($contentWithoutPhp, $strings, $originalContent, $filePath);
        $this->extractFromFluxComponents($contentWithoutPhp, $strings, $originalContent, $filePath);
        $this->extractFromBladeSlots($contentWithoutPhp, $strings, $originalContent, $filePath);

        // Clean and filter strings
        $strings = array_map('trim', $strings);
        $strings = array_filter($strings, fn ($s) => $this->isTranslatable($s));
        $strings = array_map(fn ($s) => $this->cleanString($s), $strings);
        $strings = array_filter($strings, fn ($s) => strlen($s) > 2 && strlen($s) < 500);

        return array_values(array_unique($strings));
    }

    /**
     * Check if a string is already wrapped in translation function in the source.
     */
    protected function isAlreadyTranslated(string $string, string $originalContent): bool
    {
        $escapedString = preg_quote($string, '/');

        // Check various translation patterns
        $translationPatterns = [
            // {{ __('string') }} or {{ __("string") }}
            '/\{\{\s*__\s*\(\s*[\'"]'.preg_quote($string, '/').'/i',
            // @lang('string')
            '/@lang\s*\(\s*[\'"]'.preg_quote($string, '/').'/i',
            // trans('string')
            '/trans\s*\(\s*[\'"]'.preg_quote($string, '/').'/i',
            // __('string') in PHP
            '/__\s*\(\s*[\'"]'.preg_quote($string, '/').'/i',
            // :label="__('string')" in component attributes
            '/:\w+\s*=\s*["\']?\s*__\s*\(\s*[\'"]'.preg_quote($string, '/').'/i',
        ];

        foreach ($translationPatterns as $pattern) {
            if (preg_match($pattern, $originalContent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * HTML attributes that should use Blade syntax {{ __() }} instead of Alpine :attr binding.
     *
     * @var array<string>
     */
    protected array $htmlAttributes = ['placeholder', 'title', 'alt', 'aria-label', 'data-placeholder'];

    /**
     * Add a replacement for wrapping.
     */
    protected function addReplacement(string $filePath, string $original, string $text, string $originalContent, string $wrapType = 'blade'): void
    {
        if (empty($filePath) || $this->isAlreadyTranslated($text, $originalContent)) {
            return;
        }

        // Determine line number
        $pos = strpos($originalContent, $original);
        $line = $pos !== false ? substr_count(substr($originalContent, 0, $pos), "\n") + 1 : 0;

        // Create the wrapped version
        $escapedText = str_replace("'", "\\'", $text);

        switch ($wrapType) {
            case 'attribute':
                // For HTML attributes like placeholder="text" -> placeholder="{{ __('text') }}"
                // NOT :placeholder (that's Alpine.js binding which expects JavaScript, not PHP)
                if (preg_match('/^([\w\-]+)=["\'](.+)["\']$/', $original, $m)) {
                    $attrName = $m[1];
                    $attrValue = str_replace("'", "\\'", $m[2]);
                    $replacement = "{$attrName}=\"{{ __('{$attrValue}') }}\"";
                } else {
                    $replacement = "{{ __('{$escapedText}') }}";
                }
                break;

            case 'flux-attribute':
                // For Flux component attributes :label="text" -> :label="__('text')"
                // These ARE JavaScript bindings that accept PHP via Blade compilation
                if (preg_match('/^:([\w\-]+)=["\'](.+)["\']$/', $original, $m)) {
                    $replacement = ":{$m[1]}=\"__('".str_replace("'", "\\'", $m[2])."')\"";
                } else {
                    $replacement = "{{ __('{$escapedText}') }}";
                }
                break;

            case 'blade':
            default:
                $replacement = "{{ __('{$escapedText}') }}";
                break;
        }

        if (! isset($this->fileReplacements[$filePath])) {
            $this->fileReplacements[$filePath] = [];
        }

        // Avoid duplicate replacements
        foreach ($this->fileReplacements[$filePath] as $existing) {
            if ($existing['original'] === $original) {
                return;
            }
        }

        $this->fileReplacements[$filePath][] = [
            'original' => $original,
            'replacement' => $replacement,
            'line' => $line,
        ];
    }

    /**
     * Extract text between HTML tags.
     *
     * @param  array<string>  $strings
     */
    protected function extractTextBetweenTags(string $content, array &$strings, string $originalContent = '', string $filePath = ''): void
    {
        $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'a', 'button', 'label', 'li', 'td', 'th', 'strong', 'em', 'small', 'div', 'figcaption', 'legend'];

        foreach ($tags as $tag) {
            // Match opening tag, capture content, match closing tag
            preg_match_all("/<{$tag}[^>]*>(.*?)<\/{$tag}>/is", $content, $matches, PREG_OFFSET_CAPTURE);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $fullText = $match[0];

                    // Extract static text segments from mixed content (text + Blade syntax)
                    $staticSegments = $this->extractStaticTextSegments($fullText);

                    foreach ($staticSegments as $segment) {
                        $text = trim($segment['text']);
                        if ($this->isTranslatable($text) && ! $this->isAlreadyTranslated($text, $originalContent)) {
                            $strings[] = $text;

                            if ($filePath && ($this->option('wrap') || $this->option('wrap-dry-run'))) {
                                $this->addReplacement($filePath, $segment['original'], $text, $originalContent, 'blade');
                            }
                        }
                    }
                }
            }
        }

        // Also extract text from title elements in head
        preg_match_all('/<title[^>]*>([^<]+)<\/title>/i', $content, $matches);
        if (! empty($matches[1])) {
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if ($this->isTranslatable($text) && ! $this->isAlreadyTranslated($text, $originalContent)) {
                    $strings[] = $text;
                }
            }
        }
    }

    /**
     * Extract static text segments from content that may contain Blade syntax.
     *
     * @return array<array{text: string, original: string}>
     */
    protected function extractStaticTextSegments(string $content): array
    {
        $segments = [];

        // Skip if content contains HTML tags (nested elements)
        if (preg_match('/<[a-z]/i', $content)) {
            return $segments;
        }

        // Split by Blade syntax patterns: {{ }}, {!! !!}, @directive
        // This regex captures the delimiters so we can identify what's static
        $parts = preg_split(
            '/(\{\{.*?\}\}|\{!!.*?!!\}|@\w+(?:\s*\([^)]*\))?)/s',
            $content,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        foreach ($parts as $part) {
            // Skip if it's a Blade expression
            if (preg_match('/^\{\{|\{!!|^@\w+/', $part)) {
                continue;
            }

            // Skip if it looks like HTML
            if (preg_match('/<[a-z]/i', $part) || preg_match('/^["\']?>?\s*$/', $part)) {
                continue;
            }

            $text = trim($part);
            if (! empty($text) && strlen($text) > 1) {
                // Preserve trailing space/colon for proper formatting
                $originalText = $part;
                if (preg_match('/^(\s*)(.+?)(\s*)$/', $part, $m)) {
                    $originalText = $m[2]; // Just the text without leading/trailing whitespace
                    // But keep trailing space if followed by Blade syntax
                    if (! empty($m[3])) {
                        $originalText .= ' ';
                    }
                }

                $segments[] = [
                    'text' => $text,
                    'original' => trim($part),
                ];
            }
        }

        return $segments;
    }

    /**
     * Extract text from HTML attributes.
     *
     * @param  array<string>  $strings
     */
    protected function extractFromAttributes(string $content, array &$strings, string $originalContent = '', string $filePath = ''): void
    {
        $attributes = ['placeholder', 'title', 'alt', 'aria-label', 'data-placeholder'];

        foreach ($attributes as $attr) {
            preg_match_all("/{$attr}=[\"']([^\"']+)[\"']/i", $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $fullMatch = $match[0];
                $text = trim($match[1]);

                if ($this->isTranslatable($text) && ! $this->isAlreadyTranslated($text, $originalContent)) {
                    $strings[] = $text;

                    if ($filePath && ($this->option('wrap') || $this->option('wrap-dry-run'))) {
                        $this->addReplacement($filePath, $fullMatch, $text, $originalContent, 'attribute');
                    }
                }
            }
        }
    }

    /**
     * Extract text from Flux UI component attributes.
     *
     * @param  array<string>  $strings
     */
    protected function extractFromFluxComponents(string $content, array &$strings, string $originalContent = '', string $filePath = ''): void
    {
        // Flux component attributes that contain translatable text
        $fluxAttributes = [
            'label', 'heading', 'subheading', 'description', 'badge', 'text',
            'title', 'subtitle', 'placeholder', 'helper', 'hint', 'message',
        ];

        foreach ($fluxAttributes as $attr) {
            // Match :attribute="text" (already bound - check if needs translation)
            preg_match_all("/:{$attr}=[\"']([^\"']+)[\"']/i", $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $fullMatch = $match[0];
                $text = trim($match[1]);

                // Skip if it's already a function call like __('...')
                if (preg_match('/^__\s*\(/', $text) || preg_match('/^\$/', $text)) {
                    continue;
                }

                if ($this->isTranslatable($text) && ! $this->isAlreadyTranslated($text, $originalContent)) {
                    $strings[] = $text;

                    if ($filePath && ($this->option('wrap') || $this->option('wrap-dry-run'))) {
                        $this->addReplacement($filePath, $fullMatch, $text, $originalContent, 'flux-attribute');
                    }
                }
            }

            // Match attribute="text" (static attribute - needs to become :attribute="__('text')")
            preg_match_all("/(?<!:){$attr}=[\"']([^\"']+)[\"']/i", $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $fullMatch = $match[0];
                $text = trim($match[1]);

                if ($this->isTranslatable($text) && ! $this->isAlreadyTranslated($text, $originalContent)) {
                    $strings[] = $text;

                    if ($filePath && ($this->option('wrap') || $this->option('wrap-dry-run'))) {
                        $this->addReplacement($filePath, $fullMatch, $text, $originalContent, 'attribute');
                    }
                }
            }
        }
    }

    /**
     * Extract text from Blade component slots.
     *
     * @param  array<string>  $strings
     */
    protected function extractFromBladeSlots(string $content, array &$strings, string $originalContent = '', string $filePath = ''): void
    {
        // Match <x-slot:name>text</x-slot> or <x-slot name="...">text</x-slot>
        preg_match_all('/<x-slot[^>]*>([^<]+)<\/x-slot>/i', $content, $matches);

        if (! empty($matches[1])) {
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if ($this->isTranslatable($text) && ! $this->isAlreadyTranslated($text, $originalContent)) {
                    $strings[] = $text;

                    if ($filePath && ($this->option('wrap') || $this->option('wrap-dry-run'))) {
                        $this->addReplacement($filePath, $text, $text, $originalContent, 'blade');
                    }
                }
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
        if (str_contains($string, '{{') || str_contains($string, '{!!') || str_contains($string, '@') || str_contains($string, '$')) {
            return false;
        }

        // Skip if it contains Blade echo syntax (even partial)
        if (preg_match('/\{\{|\}\}|\{!!|!!\}/', $string)) {
            return false;
        }

        // Skip if it's already using translation function
        if (str_contains($string, '__(') || str_contains($string, 'trans(') || str_contains($string, '@lang(')) {
            return false;
        }

        // Skip single-character or very short strings (likely not actual text)
        if (strlen($string) <= 1) {
            return false;
        }

        // Skip if it looks like CSS class names, IDs, or code identifiers
        if (preg_match('/^[a-zA-Z0-9_\-\.]+$/', $string) && ! str_contains($string, ' ')) {
            // Single word without spaces - likely an identifier, not translatable text
            // Unless it's a common English word (basic heuristic)
            $commonWords = ['Home', 'About', 'Contact', 'Login', 'Register', 'Logout', 'Submit', 'Cancel', 'Save', 'Delete', 'Edit', 'View', 'Search', 'Settings', 'Profile', 'Dashboard', 'Welcome', 'Hello', 'Error', 'Success', 'Warning', 'Info', 'Yes', 'No', 'OK', 'Close', 'Open', 'Back', 'Next', 'Previous', 'Loading', 'Name', 'Email', 'Password', 'Phone', 'Address', 'Message', 'Send', 'Reset', 'Update', 'Create', 'Add', 'Remove', 'Select', 'Choose', 'Upload', 'Download', 'File', 'Image', 'Photo', 'Video', 'Audio', 'Document', 'Page', 'Post', 'Category', 'Tag', 'Date', 'Time', 'Price', 'Total', 'Amount', 'Quantity', 'Status', 'Active', 'Inactive', 'Pending', 'Approved', 'Rejected', 'Published', 'Draft', 'Archived', 'Details', 'Description', 'Title', 'Content', 'Summary', 'Actions', 'Options', 'More', 'Less', 'All', 'None', 'Filter', 'Sort', 'Order', 'List', 'Grid', 'Table', 'Form', 'Field', 'Input', 'Button', 'Link', 'Menu', 'Navigation', 'Header', 'Footer', 'Sidebar', 'Main', 'Section', 'Article', 'Comment', 'Reply', 'Share', 'Like', 'Follow', 'Subscribe', 'Unsubscribe', 'Notifications', 'Messages', 'Alerts', 'Help', 'Support', 'FAQ', 'Terms', 'Privacy', 'Policy', 'Copyright', 'Required', 'Optional', 'Membership', 'Plan', 'Plans', 'Subscribe', 'Features', 'Services', 'Products', 'Pricing', 'Team', 'Blog', 'News', 'Events', 'Gallery', 'Portfolio', 'Testimonials', 'Partners', 'Clients', 'Careers', 'Jobs'];

            if (! in_array($string, $commonWords)) {
                return false;
            }
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
