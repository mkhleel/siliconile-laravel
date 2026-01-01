<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('extracts translatable strings from blade files', function () {
    // Create a test Blade file
    $testDir = resource_path('views/test-extraction');
    File::makeDirectory($testDir, 0755, true);

    $testFile = $testDir.'/test.blade.php';
    File::put($testFile, <<<'BLADE'
        <h1>Welcome to Dashboard</h1>
        <p>This is a test paragraph</p>
        <button>Save Changes</button>
        <input placeholder="Enter your name" />
        BLADE
    );

    // Run the extraction command
    $this->artisan('translations:extract', [
        '--path' => [$testDir],
        '--output' => 'lang/test-extract.json',
    ])->assertSuccessful();

    // Verify the output file was created
    expect(File::exists(base_path('lang/test-extract.json')))->toBeTrue();

    // Verify the content
    $content = json_decode(File::get(base_path('lang/test-extract.json')), true);
    expect($content)->toBeArray();
    expect($content)->toHaveKey('Welcome to Dashboard');
    expect($content)->toHaveKey('This is a test paragraph');
    expect($content)->toHaveKey('Save Changes');
    expect($content)->toHaveKey('Enter your name');

    // Cleanup
    File::deleteDirectory($testDir);
    File::delete(base_path('lang/test-extract.json'));
});

it('merges with existing translations when merge option is used', function () {
    $testDir = resource_path('views/test-extraction-merge');
    File::makeDirectory($testDir, 0755, true);

    $testFile = $testDir.'/test.blade.php';
    File::put($testFile, '<h1>New String</h1>');

    $outputPath = 'lang/test-merge.json';
    $existingContent = json_encode([
        'Existing String' => 'Existing String',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    File::makeDirectory(dirname(base_path($outputPath)), 0755, true);
    File::put(base_path($outputPath), $existingContent);

    // Run with merge flag
    $this->artisan('translations:extract', [
        '--path' => [$testDir],
        '--output' => $outputPath,
        '--merge' => true,
    ])->assertSuccessful();

    // Verify both old and new strings exist
    $content = json_decode(File::get(base_path($outputPath)), true);
    expect($content)->toHaveKey('Existing String');
    expect($content)->toHaveKey('New String');

    // Cleanup
    File::deleteDirectory($testDir);
    File::delete(base_path($outputPath));
});

it('validates required gemini api key for translation', function () {
    $this->artisan('translations:extract', [
        '--dry-run' => true,
        '--translate' => true,
    ])->expectsOutput('Gemini API key is not configured');
});

it('shows dry run preview without saving files', function () {
    $testDir = resource_path('views/test-dry-run');
    File::makeDirectory($testDir, 0755, true);

    $testFile = $testDir.'/test.blade.php';
    File::put($testFile, '<h1>Test Heading</h1>');

    $outputPath = 'lang/test-dry-run.json';

    $this->artisan('translations:extract', [
        '--path' => [$testDir],
        '--output' => $outputPath,
        '--dry-run' => true,
    ])->assertSuccessful();

    // Verify file was not created
    expect(File::exists(base_path($outputPath)))->toBeFalse();

    // Cleanup
    File::deleteDirectory($testDir);
});
