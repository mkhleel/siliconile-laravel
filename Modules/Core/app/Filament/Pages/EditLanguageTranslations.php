<?php

namespace Modules\Core\Filament\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Stichoza\GoogleTranslate\GoogleTranslate;

class EditLanguageTranslations extends Page
{
    use InteractsWithForms;

    public ?string $language = 'ar';

    protected string $view = 'core::filament.pages.edit-language-translations';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public int $perPage = 100;

    public int $currentPage = 1;

    public int $totalTranslations = 0;

    #[Url]
    public string $search = '';

    private const CHUNK_SIZE = 5;

    private const DEFAULT_LANGUAGE = 'ar';

    public static function getNavigationLabel(): string
    {
        return __('Translates');
    }

    public function getHeading(): string|Htmlable
    {
        return __('Translates');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getClearSearchAction(),
            $this->getNextPageAction(),
            $this->getPageInfoAction(),
            $this->getPreviousPageAction(),
            $this->getRefreshAction(),
            $this->getAutoTranslateAction(),
            $this->getSubmitAction(),
        ];
    }

    protected function getClearSearchAction(): Action
    {
        return Action::make('clear_search')
            ->label('Clear Search')
            ->icon(Heroicon::XMark)
            ->color('gray')
            ->visible(fn () => filled($this->search))
            ->action(fn () => $this->clearSearch());
    }

    protected function getNextPageAction(): Action
    {
        return Action::make('next_page')
            ->label('Next')
            ->icon(Heroicon::ChevronRight)
            ->visible(fn () => $this->hasNextPage())
            ->action(fn () => $this->nextPage());
    }

    protected function getPageInfoAction(): Action
    {
        return Action::make('page_info')
            ->label(fn () => $this->getPageInfoLabel())
            ->disabled()
            ->color('gray');
    }

    protected function getPreviousPageAction(): Action
    {
        return Action::make('previous_page')
            ->label('Previous')
            ->icon(Heroicon::ChevronLeft)
            ->visible(fn () => $this->hasPreviousPage())
            ->action(fn () => $this->previousPage());
    }

    protected function getRefreshAction(): Action
    {
        return Action::make('refresh')
            ->label('Refresh')
            ->icon(Heroicon::ArrowPath)
            ->color('gray')
            ->action(fn () => $this->refreshTranslations());
    }

    protected function getAutoTranslateAction(): Action
    {
        return Action::make('auto_translate')
            ->label('Auto Translate')
            ->color('success')
            ->icon(Heroicon::Language)
            ->action(fn () => $this->auto_translate());
    }

    protected function getSubmitAction(): Action
    {
        return Action::make('submit')
            ->label('Save')
            ->color('success')
            ->icon(Heroicon::Check)
            ->action(fn () => $this->submit());
    }

    protected function getPageInfoLabel(): string
    {
        $totalPages = $this->getTotalPages();
        $baseInfo = "Page {$this->currentPage} of {$totalPages} ({$this->totalTranslations} total)";

        return filled($this->search)
            ? "Search: '{$this->search}' - {$baseInfo}"
            : $baseInfo;
    }

    public function mount(): void
    {
        $this->language = request()->get('language', self::DEFAULT_LANGUAGE);
        $this->loadTranslations();
    }

    protected function loadTranslations(): void
    {
        $this->ensureLanguageFileExists();

        try {
            $allTranslations = $this->getAllTranslations();
            $filteredTranslations = $this->applySearchFilter($allTranslations);

            $this->totalTranslations = count($filteredTranslations);
            $this->ensureValidPage();

            $paginatedTranslations = $this->paginateTranslations($filteredTranslations);
            $this->data = ['keys' => $this->formatTranslationsForRepeater($paginatedTranslations)];

            $this->form->fill($this->data);
        } catch (Exception $e) {
            $this->notify('error', 'Error loading translations: '.$e->getMessage());
        }
    }

    protected function ensureLanguageFileExists(): void
    {
        $path = $this->getLanguageFilePath();

        if (! File::exists($path)) {
            File::copy(lang_path('en.json'), $path);
        }
    }

    protected function getAllTranslations(): array
    {
        $path = $this->getLanguageFilePath();

        if (! File::exists($path)) {
            return [];
        }

        $content = File::get($path);

        return json_decode($content, true) ?? [];
    }

    protected function applySearchFilter(array $translations): array
    {
        if (blank($this->search)) {
            return $translations;
        }

        $searchTerm = strtolower($this->search);

        return array_filter($translations, function ($value, $key) use ($searchTerm) {
            return str_contains(strtolower($key), $searchTerm) ||
                str_contains(strtolower($value), $searchTerm);
        }, ARRAY_FILTER_USE_BOTH);
    }

    protected function ensureValidPage(): void
    {
        $maxPage = max(1, $this->getTotalPages());

        if ($this->currentPage > $maxPage) {
            $this->currentPage = 1;
        }
    }

    protected function paginateTranslations(array $translations): array
    {
        $offset = ($this->currentPage - 1) * $this->perPage;

        return array_slice($translations, $offset, $this->perPage, true);
    }

    protected function formatTranslationsForRepeater(array $translations): array
    {
        $formatted = [];

        foreach ($translations as $key => $value) {
            $formatted[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return $formatted;
    }

    protected function getLanguageFilePath(): string
    {
        return lang_path("{$this->language}.json");
    }

    protected function getTotalPages(): int
    {
        return (int) ceil($this->totalTranslations / $this->perPage);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Translations Keys (Language: :language)', ['language' => $this->language]))
                    ->description($this->getSearchDescription())
                    ->schema([
                        $this->getSearchInput(),
                        $this->getTranslationsRepeater(),
                    ])
                    ->columns(1),
            ])->statePath('data');
    }

    protected function getSearchInput(): TextInput
    {
        return TextInput::make('search')
            ->label('Search translations')
            ->placeholder('Search in keys or values...')
            ->default($this->search)
            ->live(debounce: 500)
            ->afterStateUpdated(function ($state) {
                $this->search = $state ?? '';
                $this->currentPage = 1;
                $this->loadTranslations();
            })
            ->suffixIcon(Heroicon::MagnifyingGlass)
            ->columnSpanFull();
    }

    protected function getTranslationsRepeater(): Repeater
    {
        return Repeater::make('keys')
            ->table([
                TableColumn::make('Key'),
                TableColumn::make('Translation Value'),
            ])
            ->schema([
                TextInput::make('key')
                    ->required()
                    ->readonly(),
                TextInput::make('value')
                    ->required(),
            ])
            ->addable(false)
            ->deletable(false)
            ->reorderable(false);
    }

    protected function getSearchDescription(): string
    {
        if (blank($this->search)) {
            return "Showing {$this->perPage} translations per page. Page {$this->currentPage} of {$this->getTotalPages()}";
        }

        return "Found {$this->totalTranslations} results for '{$this->search}'. Page {$this->currentPage} of {$this->getTotalPages()}";
    }

    public function submit(): void
    {
        try {
            $formData = $this->form->getState();

            if (blank($formData['keys'])) {
                $this->notify('warning', 'No translations to save');

                return;
            }

            $this->saveTranslations($formData['keys']);
            $this->notify('success', __('Translation keys saved'));
        } catch (Exception $e) {
            $this->notify('error', 'Error saving translations: '.$e->getMessage());
        }
    }

    protected function saveTranslations(array $keys): void
    {
        $path = $this->getLanguageFilePath();
        $allTranslations = $this->getAllTranslations();

        foreach ($keys as $item) {
            if (filled($item['key']) && isset($item['value'])) {
                $allTranslations[$item['key']] = $item['value'];
            }
        }

        File::put(
            $path,
            json_encode($allTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function auto_translate(): void
    {
        try {
            $keysToTranslate = $this->getKeysToTranslate();

            if (empty($keysToTranslate)) {
                $this->notify('warning', 'No valid keys to translate');

                return;
            }

            $translatedCount = $this->translateKeys($keysToTranslate);

            $this->loadTranslations();
            $this->notify('success', 'Translation completed', "{$translatedCount} keys have been translated successfully.");
        } catch (Exception $exception) {
            $this->notify('error', $exception->getMessage());
        }
    }

    protected function getKeysToTranslate(): array
    {
        $formData = $this->form->getState();

        if (blank($formData['keys'])) {
            return [];
        }

        return collect($formData['keys'])
            ->pluck('key')
            ->filter()
            ->toArray();
    }

    protected function translateKeys(array $keys): int
    {
        $path = $this->getLanguageFilePath();
        $allTranslations = $this->getAllTranslations();
        $translatedCount = 0;

        collect($keys)
            ->lazy()
            ->chunk(self::CHUNK_SIZE)
            ->each(function ($chunk) use (&$allTranslations, $path, &$translatedCount) {
                foreach ($chunk as $key) {
                    if ($this->translateSingleKey($key, $allTranslations)) {
                        $translatedCount++;
                    }
                }

                $this->saveTranslationsToFile($path, $allTranslations);
            });

        return $translatedCount;
    }

    protected function translateSingleKey(string $key, array &$translations): bool
    {
        try {
            $translated = (new GoogleTranslate)
                ->setTarget($this->language)
                ->translate($key);

            $translations[$key] = $translated;

            return true;
        } catch (Exception $e) {
            Log::error("Translation failed for key '{$key}': ".$e->getMessage());

            return false;
        }
    }

    protected function saveTranslationsToFile(string $path, array $translations): void
    {
        File::put($path, json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function goToPage(int $page): void
    {
        $maxPage = $this->getTotalPages();
        $this->currentPage = max(1, min($page, $maxPage));
        $this->loadTranslations();
    }

    public function refreshTranslations(): void
    {
        $this->loadTranslations();
        $this->notify('success', 'Translations refreshed');
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        $this->loadTranslations();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->currentPage = 1;
        $this->loadTranslations();
    }

    protected function nextPage(): void
    {
        $this->currentPage++;
        $this->loadTranslations();
    }

    protected function previousPage(): void
    {
        $this->currentPage--;
        $this->loadTranslations();
    }

    protected function hasNextPage(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }

    protected function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    protected function notify($type = 'success', string $title, ?string $body = null): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->status($type)
            ->send();
    }

}
