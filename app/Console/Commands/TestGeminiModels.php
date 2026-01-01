<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Gemini\Laravel\Facades\Gemini;

class TestGeminiModels extends Command
{
    protected $signature = 'test:gemini';
    protected $description = 'Test different Gemini model names to find which works';

    public function handle()
    {
        $modelsToTest = [
            'gemini-1.5-pro',
            'gemini-1.5-flash',
            'gemini-pro',
            'models/gemini-1.5-pro',
            'models/gemini-1.5-flash',  
            'models/gemini-2.5-flash-lite',
            'models/gemini-pro',
            'gemini-exp-1206',
            'gemini-2.0-flash-exp',
        ];

        $this->info("Testing different Gemini model names...\n");

        foreach ($modelsToTest as $modelName) {
            $this->line("Testing: {$modelName}");
            
            try {
                $result = Gemini::generativeModel(model: $modelName)
                    ->generateContent("Translate 'Hello' to Arabic. Reply with only the Arabic word.");
                
                $translation = $result->text();
                
                $this->info("  ✓ SUCCESS! Model works. Translation: {$translation}");
                $this->comment("  Use this model name: {$modelName}\n");
                
                // Found a working model, stop testing
                return Command::SUCCESS;
                
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
                $this->newLine();
            }
        }

        $this->error("None of the models worked. Please check:");
        $this->line("  1. Your GEMINI_API_KEY in .env");
        $this->line("  2. Your API key has not exceeded quota");
        $this->line("  3. Your API key is valid and active");

        return Command::FAILURE;
    }
}