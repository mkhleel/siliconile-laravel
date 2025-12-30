<?php

namespace Modules\Core\Providers\Filament;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Placeholder;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\BasePage;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Colors\Color;
use Filament\Support\Components\Component;
use Filament\Support\Concerns\Configurable;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\View\PanelsRenderHook;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Modules\Core\Filament\Auth\Login;
use Modules\Core\Filament\ModulesPluginsRegister;
use Modules\Core\Filament\Widgets\SystemHealthWidget;
use Modules\Core\Settings\GeneralSettings;
use Throwable;

class AdminPanelProvider extends PanelProvider
{
    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->darkMode(false)
            ->font(config('app.font', 'IBM Plex Sans Arabic'))
            ->favicon(url('/favicon.ico'))
            ->authGuard('admin')
            ->brandLogo(url('logo.svg'))
            ->emailVerification()
            ->brandLogoHeight('3.5rem')
            ->login(Login::class)
            ->profile()
            ->passwordReset()
            ->databaseNotifications()
            ->topNavigation(true)
            ->globalSearch(false)
            ->spa()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->plugin(new ModulesPluginsRegister)
            ->bootUsing(function (Panel $panel) {
                try {
                    $logotypeSetting = app(GeneralSettings::class)->get('site_logo');
                    $logotypeUrl = ! blank($logotypeSetting) ? Storage::url($logotypeSetting) : asset('logo.svg');
                } catch (Throwable $_) {
                    $logotypeUrl = asset('logo.svg');
                }
                $panel->brandLogo($logotypeUrl);
            })
            ->navigationGroups([
                NavigationGroup::make(__('Learning'))->collapsed(),
                NavigationGroup::make(__('Billing'))->collapsed(),
                NavigationGroup::make(__('Marketing'))->collapsed(),
                NavigationGroup::make(__('Settings'))->collapsed(),
                NavigationGroup::make(__('Access'))->collapsed(),
            ])
            ->databaseTransactions()
            ->widgets([
                //                Widgets\AccountWidget::class,
                SystemHealthWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->bootUsing(function (Panel $panel) {
                $panel->plugins([
                    SpatieTranslatablePlugin::make()->defaultLocales(config('core.localization.languages', ['en'])),
                ]);
                try {
                    $logotypeSetting = app(GeneralSettings::class)->site_logo;
                    $logotypeUrl = ! blank($logotypeSetting) ? Storage::url($logotypeSetting) : asset('images/logo.svg');
                    $panel->brandLogo($logotypeUrl);
                    $panel->brandLogoHeight('2.5rem');
                } catch (Throwable $_) {
                    info('Failed to load site logo: '.$_->getMessage());
                }

            })
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        $this->translatableComponents();

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            function (): string {
                return Blade::render('<a target="_blank" href="{{ url(\'/\') }}" role="menuitem"><x-heroicon-o-computer-desktop class="fi-icon fi-size-md" /></a>');
            }
        );

        BasePage::stickyFormActions();

        Table::configureUsing(function (Table $table): void {
            $table->extremePaginationLinks();
        });

        Notifications::verticalAlignment(VerticalAlignment::End);


    }

    protected function translatableComponents(): void
    {
        foreach ([Field::class, BaseFilter::class, TextEntry::class, Column::class, Entry::class, Section::class, Action::class, Tab::class] as $component) {
            /* @var Configurable $component */
            $component::configureUsing(function (Component $translatable): void {
                /** @phpstan-ignore method.notFound */
                $this->addKeyToTranslateJsonFile($translatable->getLabel());
                $translatable->translateLabel();
            });
        }
    }

    private function addKeyToTranslateJsonFile($getLabel): void
    {
        // loop through all the files in the resources/lang directory
        $files = glob(base_path('lang/*.json'));
        foreach ($files as $file) {
            if (File::exists($file)) {

                // Read the existing file and decode JSON to an array
                $words = json_decode(File::get($file), true);

                // Ensure $words is a valid array
                if (! is_array($words)) {
                    $words = [];
                }
            } else {
                // Initialize an empty array if the file doesn't exist
                $words = [];
            }

            if (array_key_exists($getLabel, $words)) {
                continue;
            }
            $words[$getLabel] = $getLabel;
            File::put($file, json_encode($words, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

    }
}
