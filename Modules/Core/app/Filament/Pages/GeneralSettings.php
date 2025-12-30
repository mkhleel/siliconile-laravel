<?php

namespace Modules\Core\Filament\Pages;

use Artisan;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Modules\Core\Filament\Forms\Components\Picker;
use Modules\Core\Models\Localization\Currency;
use Modules\Core\Models\Localization\Language;
use Modules\Core\Settings\GeneralSettings as General;
use Nwidart\Modules\Facades\Module;
use Spatie\Sitemap\SitemapGenerator;
use Theme;

class GeneralSettings extends SettingsPage
{
    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    /**
     * @return string|null
     */
    public static function getNavigationLabel(): string
    {
        return __('General Settings');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('site_map')->
                label(__('Generate Site Map'))
                    ->icon('heroicon-o-document')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading(__('Site Map'))
                    ->modalDescription(__('This will generate a site map for your website.'))
                    ->action(function () {
                        // Generate the site map
                        SitemapGenerator::create(config('app.url'))
                            ->getSitemap()
                            ->add('/tours')

                            ->writeToFile(public_path('sitemap.xml'));

                        session()->flash('success', __('Site map generated successfully!'));
                    })
                    ->color('primary'),
            Action::make('clear_cache')

                ->icon('heroicon-o-trash')
                ->size('sm')
                ->requiresConfirmation()
                ->modalHeading(__('Clear Cache'))
                ->modalDescription(__('This will clear the application cache.'))
                ->action(function () {
                    // Clear the application cache
                    Artisan::call('optimize:clear');
                    if (app()->environment() == 'production') {
                        Artisan::call('optimize');
                    }

                    Notification::make()
                        ->title(__('Cache Cleared'))
                        ->success()
                        ->body(__('Application cache cleared successfully!'))
                        ->send();
                }),
        ];
    }

    protected static string $settings = General::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('Basics'))->schema([
                            Section::make(__('General Settings'))
                                ->aside()
                                ->columns(1)
                                ->schema([
                                    TextInput::make('site_name')
                                        ->required(),

                                    Picker::make('site_theme')
                                        ->inlineLabel()

                                        ->options(function () {
                                            if (class_exists(Theme::class)) {
                                                return Theme::getSelectThemesOptions('name');
                                            }

                                            return [];
                                        })
                                        ->imageSize(100)
                                        ->images(function () {
                                            if (class_exists(Theme::class)) {
                                                return Theme::getSelectThemesOptions();
                                            }

                                            return [];
                                        })
                                        ->default('ship'),

                                    FileUpload::make('site_logo')

                                        ->required(),

                                    FileUpload::make('site_favicon')

                                        ->required(),

                                    FileUpload::make('placeholder_image')

                                        ->label('Placeholder Image')
                                        ->helperText(__('Default image to show when product images are not available')),

                                    ColorPicker::make('site_color')

                                        ->required(),

                                    Select::make('currency')
                                        ->hint(__('Base currency for the site'))
                                        ->options(fn () => Currency::pluck('name', 'iso')->toArray())
                                        ->searchable()
                                        ->required(),

                                    Select::make('preferred_currency')
                                        ->hint(__('Default currency for users (can be changed by users)'))
                                        ->options(fn () => Currency::pluck('name', 'iso')->toArray())
                                        ->searchable()
                                        ->required(),

                                    Select::make('site_languages')

                                        ->helperText((new HtmlString('You can add more languages in the <a class="text-primary-600" href="'.route('filament.admin.resources.languages.index').'" class="text-blue-500 hover:underline">Languages</a> section.')))
                                        ->options(fn () => Language::activated()->pluck('name', 'iso')->toArray())
                                        ->multiple()
                                        ->required(),

                                    Select::make('site_locale')

                                        ->options(fn () => Language::activated()->pluck('name', 'iso')->toArray())
                                        ->required(),

                                    ToggleButtons::make('site_active')

                                        ->inlineLabel()
                                        ->helperText(__('Enable or disable the site'))
                                        ->boolean(),

                                    TextInput::make('admin_prefix')

                                        ->placeholder('admin')
                                        ->helperText(__('The URL prefix for the admin panel (default: admin)')),
                                ]),
                        ]),

                        Tab::make(__('Content'))->schema([
                            Section::make(__('Content Elements'))
                                ->aside()
                                ->columns(1)
                                ->schema([
                                    Repeater::make('slider')

                                        ->collapsed()
                                        ->labelBetweenItems(__('Slider'))
                                        ->schema([
                                            FileUpload::make('image'),
                                            TextInput::make('headline'),
                                            TextInput::make('sub_headline'),
                                        ]),

                                    Repeater::make('faqs')

                                        ->collapsed()
                                        ->labelBetweenItems(__('FAQ'))
                                        ->schema([
                                            TextInput::make('question'),
                                            TextInput::make('answer'),
                                        ]),

                                    Repeater::make('brands')

                                        ->collapsed()
                                        ->labelBetweenItems(__('Brand'))
                                        ->schema([
                                            TextInput::make('name'),
                                            TextInput::make('url'),
                                            FileUpload::make('logo'),
                                        ]),

                                    Repeater::make('testimonials')

                                        ->collapsed()
                                        ->labelBetweenItems(__('Testimonial'))
                                        ->schema([
                                            Textarea::make('body')

                                                ->required(),
                                            TextInput::make('who'),
                                            TextInput::make('company'),
                                        ]),
                                ]),
                        ]),

                        Tab::make(__('Email Settings'))->schema([
                            Section::make(__('SMTP Configuration'))
                                ->aside()
                                ->columns(1)
                                ->schema([
                                    TextInput::make('smtp_host'),

                                    TextInput::make('smtp_port'),

                                    TextInput::make('smtp_username'),

                                    TextInput::make('smtp_password')

                                        ->password(),

                                    Select::make('smtp_encryption')

                                        ->options([
                                            'tls' => 'TLS',
                                            'ssl' => 'SSL',
                                            '' => 'None',
                                        ]),

                                    TextInput::make('smtp_from_address'),

                                    TextInput::make('smtp_from_name'),
                                ]),

                            Section::make(__('Mailchimp Integration'))
                                ->aside()
                                ->collapsed()
                                ->schema([
                                    TextInput::make('mailchimp_api_key'),

                                    TextInput::make('mailchimp_list_id'),

                                    TextInput::make('mailchimp_email'),

                                    TextInput::make('mailchimp_name'),
                                ]),
                        ]),

                        Tab::make(__('3rd Party Services'))
                            ->schema([
                                Section::make(__('AI Settings'))
                                    ->aside()
                                    ->schema([
                                        Select::make('ai_provider')
                                            ->options([
                                                'groq' => __('Groq'),
                                                'openai' => __('OpenAI'),
                                                'anthropic' => __('Anthropic'),
                                                'gemini' => __('Gemini'),
                                            ])
                                            ->live()
                                            ->default('groq')
                                            ->required(),

                                        Select::make('ai_model')
                                            ->options(function (Get $get) {
                                                $provider = $get('ai_provider');
                                                if (! $provider) {
                                                    return [];
                                                }

                                                return match ($provider) {
                                                    'groq' => [
                                                        'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile',
                                                        'llama-3.3-70b-chat' => 'Llama 3 3 70B Chat',
                                                        'gemma2-9b-it' => 'Gemma 2 9B IT',
                                                        'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant',
                                                        'meta-llama/llama-guard-4-12b' => 'Llama Guard 4 12B',
                                                    ],
                                                    'openai' => [
                                                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                                                        'gpt-4' => 'GPT-4',
                                                        'gpt-4o' => 'GPT-4o',
                                                    ],
                                                    'anthropic' => [
                                                        'claude-2' => 'Claude 2',
                                                        'claude-instant-100k' => 'Claude Instant 100k',
                                                    ],
                                                    default => [],
                                                };
                                            }),

                                        TextInput::make('ai_api_key')
                                        //                                            ->formatStateUsing(function ($state) {
                                        //                                                if (!$state) return $state;
                                        //                                                return substr($state, 0, 4) . str_repeat('*', strlen($state) - 4);
                                        //                                            })
                                        ,
                                    ]),

                                Section::make(__('Zoom API Settings'))
                                    ->aside()
                                    ->schema([
                                        TextInput::make('zoom_client_id'),

                                        TextInput::make('zoom_client_secret'),

                                        TextInput::make('zoom_account_id'),
                                    ]),
                            ]),

                        Tab::make(__('Social Media'))->schema([
                            Section::make(__('Social Media Links'))
                                ->aside()
                                ->schema([
                                    TextInput::make('site_social.facebook'),

                                    TextInput::make('site_social.twitter'),

                                    TextInput::make('site_social.instagram'),

                                    TextInput::make('site_social.linkedin'),

                                    TextInput::make('site_social.whatsapp'),
                                ]),
                        ]),

                        Tab::make(__('Analytics & Tracking'))->schema([
                            Section::make(__('Tracking Codes'))
                                ->aside()
                                ->schema([
                                    TextInput::make('google_analytics_tracking_id'),

                                    TextInput::make('google_tag_manager_id'),

                                    TextInput::make('facebook_pixel_id'),
                                ]),
                        ]),

                        Tab::make(__('Watermark Settings'))->schema([
                            Section::make(__('Watermark Configuration'))
                                ->aside()
                                ->schema([
                                    Toggle::make('watermark_enabled')

                                        ->onIcon('heroicon-o-check')
                                        ->offIcon('heroicon-o-x-mark')
                                        ->default(false),

                                    TextInput::make('watermark_text'),

                                    Select::make('watermark_position')

                                        ->options([
                                            'top-left' => __('Top Left'),
                                            'top-center' => __('Top Center'),
                                            'top-right' => __('Top Right'),
                                            'middle-left' => __('Middle Left'),
                                            'middle-center' => __('Middle Center'),
                                            'middle-right' => __('Middle Right'),
                                            'bottom-left' => __('Bottom Left'),
                                            'bottom-center' => __('Bottom Center'),
                                            'bottom-right' => __('Bottom Right'),
                                        ])
                                        ->default('bottom-right'),

                                    TextInput::make('watermark_font')

                                        ->placeholder('Arial, sans-serif'),
                                ]),
                        ]),

                        Tab::make(__('Modules'))->schema([
                            Section::make(__('Module Management'))
                                ->aside()
                                ->schema([
                                    ToggleButtons::make('disabled_modules')

                                        ->helperText(__('Select the modules you want to disable'))
                                        ->options($this->getModulesOptions())
                                        ->colors(array_fill_keys($this->getModulesOptions(), 'danger'))
                                        ->multiple()
                                        ->columns(2)
                                        ->gridDirection('row'),
                                ]),
                        ]),
                    ]),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    private function getModulesOptions(): array
    {
        $modules = array_keys(Arr::except(Module::scan(), ['core', 'setting', 'navigation', 'theme', 'translate']));
        $titleCaseModules = array_map(function ($modules) {
            return Str::title($modules);
        }, $modules);

        return array_combine($titleCaseModules, $titleCaseModules);
    }
}
