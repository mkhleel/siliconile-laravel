# Language Translation Command

## Overview
Console command to translate language file values using Gemini AI. This command provides a simple interface to translate JSON language files from one locale to another.

## Command Signature
```bash
php artisan translate:gemini {source} {target} [options]
```

## Arguments
- `source` - Source language code (e.g., `en`)
- `target` - Target language code (e.g., `ar`)

## Options
- `--dry-run` - Preview translations without saving to file
- `--keys=KEY` - Translate specific keys only (can be used multiple times)
- `--chunk-size=50` - Number of keys per API request (default: 50)
- `--max-retries=3` - Maximum retry attempts for failed requests (default: 3)
- `--model=gemini-2.0-flash-exp` - Gemini model to use (default: gemini-2.0-flash-exp)

## Prerequisites
1. **Gemini API Key**: Set `GEMINI_API_KEY` in your `.env` file
   ```env
   GEMINI_API_KEY=your_api_key_here
   ```

2. **Source Language File**: The source language file must exist at `lang/{source}.json`

## Usage Examples

### 1. Translate All Missing Keys
Translate all keys from English to Arabic:
```bash
php artisan translate:gemini en ar
```

### 2. Dry Run (Preview Only)
Preview translations without saving:
```bash
php artisan translate:gemini en ar --dry-run
```

### 3. Translate Specific Keys
Translate only specific keys:
```bash
php artisan translate:gemini en ar --keys="Welcome" --keys="Dashboard"
```

### 4. Custom Chunk Size
Process more keys per API request:
```bash
php artisan translate:gemini en ar --chunk-size=100
```

### 5. Verbose Output
See detailed information:
```bash
php artisan translate:gemini en ar -v
```

## How It Works

1. **Load Source File**: Reads the source language file (`lang/{source}.json`)
2. **Load Target File**: Reads existing target file or prepares to create new one
3. **Identify Missing Keys**: Finds keys that are:
   - Missing from target file
   - Empty in target file
   - Same as source (not translated)
4. **Chunk Keys**: Splits keys into batches for API efficiency
5. **Translate**: Sends each chunk to Gemini AI for translation
6. **Merge Results**: Combines new translations with existing ones
7. **Save**: Updates target file with translations (unless --dry-run)

## Translation Rules

The command instructs Gemini AI to:
- ✅ Preserve HTML tags (e.g., `<strong>`, `<span>`, `<br>`)
- ✅ Keep placeholders intact (e.g., `:name`, `:count`, `{{variable}}`)
- ✅ Maintain natural tone for tech/business context
- ✅ Ensure cultural appropriateness for target language
- ✅ Return only valid JSON (no markdown or explanations)

## Output Format

The command provides detailed feedback:

```
📝 Keys to translate: 150
🌍 Source: en → Target: ar
🤖 Model: gemini-2.0-flash-exp
🔍 DRY RUN MODE - No changes will be saved

Processing chunk 1/3...
⠙ Translating chunk 1/3...

═══════════════════════════════════════════════════════
                  TRANSLATION RESULTS
═══════════════════════════════════════════════════════

┌────────────────┬──────────────┐
│ Metric         │ Value        │
├────────────────┼──────────────┤
│ Total Keys     │ 150          │
│ ✅ Successful  │ 148          │
│ ❌ Failed      │ 2            │
│ Mode           │ 🔍 DRY RUN   │
└────────────────┴──────────────┘

✅ Saved translations to: /path/to/lang/ar.json
```

## Error Handling

### Missing API Key
```bash
❌ Gemini API key is not configured.
⚠️  Set GEMINI_API_KEY in your .env file
```

### Source File Not Found
```bash
❌ Source language file not found: /path/to/lang/en.json
```

### Failed Translations
- Failed keys are tracked and reported
- Use `-v` flag to see detailed error messages
- Failed keys remain untranslated in target file

## File Structure

### Source File Example (`lang/en.json`)
```json
{
  "Welcome": "Welcome",
  "Hello :name": "Hello :name",
  "Dashboard": "Dashboard"
}
```

### Target File Example (`lang/ar.json`)
```json
{
  "Welcome": "مرحبا",
  "Hello :name": "مرحبا :name",
  "Dashboard": "لوحة التحكم"
}
```

## Best Practices

1. **Use Dry Run First**: Always test with `--dry-run` before actual translation
2. **Version Control**: Commit language files before running translations
3. **Chunk Size**: Adjust based on API rate limits and key complexity
   - Small keys (simple text): 75-100 per chunk
   - Complex keys (HTML, placeholders): 25-50 per chunk
4. **Review Translations**: Manually review critical translations
5. **Incremental Updates**: Use specific `--keys` for targeted updates

## Supported Languages

Common language codes:
- `en` - English
- `ar` - Arabic
- `fr` - French
- `es` - Spanish
- `de` - German
- `it` - Italian
- `pt` - Portuguese
- `ru` - Russian
- `zh` - Chinese
- `ja` - Japanese
- `ko` - Korean

## Integration with Existing System

This command complements the existing `translations:extract-and-generate` command:

1. **Extract & Generate**: Use for full workflow (scan → extract → translate)
   ```bash
   php artisan translations:extract-and-generate
   ```

2. **Translate Command**: Use for quick, targeted translations
   ```bash
   php artisan translate:gemini en ar
   ```

## Performance Considerations

- **API Costs**: Each chunk = 1 API call. Adjust `--chunk-size` accordingly
- **Rate Limits**: Respect Gemini API rate limits
- **Processing Time**: ~1-3 seconds per chunk (depends on key count)

## Troubleshooting

### Command Not Found
```bash
php artisan list | grep translate
```
If not listed, clear cache:
```bash
php artisan optimize:clear
```

### JSON Parse Errors
- Ensure source file has valid JSON syntax
- Use `--dry-run` to test before saving
- Check verbose output with `-v` flag

### Inconsistent Translations
- Reduce `--chunk-size` for better context
- Review and manually adjust critical keys
- Consider adding project context (future feature)

## Development

### Location
`app/Console/Commands/TranslateLanguageCommand.php`

### Testing
```bash
# Test with dry run
php artisan translate:gemini en ar --dry-run

# Test specific keys
php artisan translate:gemini en ar --keys="Test" --dry-run

# Test with verbose output
php artisan translate:gemini en ar --dry-run -v
```

## Future Enhancements

- [ ] Support for module-specific translations
- [ ] Translation memory/cache
- [ ] Batch processing of multiple target languages
- [ ] Project context injection for better translations
- [ ] Progress bars for large translation sets
- [ ] Rollback functionality
- [ ] Translation quality scoring

## License
Part of the Siliconile project - see main project license.
