<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Gemini\Laravel\Facades\Gemini;

class TranslateJsonToArabic extends Command
{
    protected $signature = 'translate:json {file=ar.json} {--batch=5} {--delay=1} {--list-models : List available Gemini models}';
    protected $description = 'Translate Laravel JSON language file values to Arabic using Gemini AI';

    public function handle()
    {
        // List models if requested
        if ($this->option('list-models')) {
            return $this->listAvailableModels();
        }

        $fileName = $this->argument('file');
        $filePath = lang_path($fileName);
        $batchSize = (int) $this->option('batch');
        $delaySeconds = (int) $this->option('delay');

        // Check if file exists
        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        // Read JSON file
        $jsonContent = File::get($filePath);
        $translations = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file');
            return Command::FAILURE;
        }

        $this->info("Starting translation of {$fileName}...");
        $this->info("Total items to translate: " . count($translations));

        $progressBar = $this->output->createProgressBar(count($translations));
        $progressBar->start();

        $translatedData = [];
        $batch = [];
        $batchKeys = [];

        foreach ($translations as $key => $value) {
            $batch[] = $value;
            $batchKeys[] = $key;

            // Process batch when it reaches the batch size or it's the last item
            if (count($batch) >= $batchSize || $key === array_key_last($translations)) {
                try {
                    $translatedBatch = $this->translateBatch($batch);
                    
                    // Map translated values back to their keys
                    foreach ($batchKeys as $index => $batchKey) {
                        $translatedData[$batchKey] = $translatedBatch[$index] ?? $batch[$index];
                    }
                    
                    $progressBar->advance(count($batch));
                    
                    // Delay to avoid rate limiting
                    if ($delaySeconds > 0) {
                        sleep($delaySeconds);
                    }
                    
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("Failed to translate batch");
                    $this->warn("Error: " . $e->getMessage());
                    
                    // Keep original values on error
                    foreach ($batchKeys as $index => $batchKey) {
                        $translatedData[$batchKey] = $batch[$index];
                    }
                    
                    $progressBar->advance(count($batch));
                }

                // Reset batch
                $batch = [];
                $batchKeys = [];
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Save translated content
        $outputPath = lang_path('ar_translated.json');
        File::put($outputPath, json_encode($translatedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info("Translation completed!");
        $this->info("Translated file saved to: {$outputPath}");
        $this->comment("Review the file and then run: mv lang/ar_translated.json lang/{$fileName}");

        return Command::SUCCESS;
    }

    private function translateBatch(array $texts)
    {
        // Create a numbered list for batch translation
        $prompt = "Translate the following English texts to Arabic. Return ONLY the Arabic translations in the same order, one per line, with no numbers, explanations, or additional text:\n\n";
        
        foreach ($texts as $index => $text) {
            $prompt .= ($index + 1) . ". {$text}\n";
        }

        $result = Gemini::generativeModel(model: 'gemini-1.5-flash')->generateContent($prompt);

        $translatedText = $result->text();
        
        // Split by newlines and clean up
        $translations = array_filter(
            array_map('trim', explode("\n", $translatedText)),
            fn($line) => !empty($line)
        );

        // Remove any numbering that might be in the response
        $translations = array_map(function($line) {
            return preg_replace('/^\d+[\.\)]\s*/', '', $line);
        }, $translations);

        // If we don't get the expected number of translations, fall back to original
        if (count($translations) !== count($texts)) {
            $this->warn("Batch translation count mismatch. Trying individual translations...");
            return $this->translateIndividually($texts);
        }

        return array_values($translations);
    }

    private function translateIndividually(array $texts)
    {
        $results = [];
        
        foreach ($texts as $text) {
            try {
                $result = Gemini::generativeModel(model: 'gemini-1.5-flash')->generateContent(
                    "Translate to Arabic, return ONLY the translation: {$text}"
                );
                $results[] = trim($result->text());
                usleep(100000); // 0.1 second delay
            } catch (\Exception $e) {
                $results[] = $text; // Keep original on error
            }
        }
        
        return $results;
    }

    private function listAvailableModels()
    {
        $this->info("Fetching available Gemini models...\n");
        
        try {
            $models = Gemini::models()->list();
            
            $this->info("Available models that support generateContent:");
            $this->newLine();
            
            foreach ($models as $model) {
                if (in_array('generateContent', $model->supportedGenerationMethods ?? [])) {
                    $this->line("  • {$model->name}");
                }
            }
            
            $this->newLine();
            $this->comment("Use one of these models in the translateBatch() method.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to fetch models: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}